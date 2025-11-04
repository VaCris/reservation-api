<?php

namespace App\Service;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Sabre\VObject\Component\VCalendar;

/**
 * Servicio para exportar reportes de reservas
 */
class ReportExportService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Exportar reservas a PDF
     */
    public function exportToPdf(array $filters = []): string
    {
        $reservations = $this->getReservationsWithFilters($filters);

        $html = $this->generatePdfHtml($reservations, $filters);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Exportar reservas a Excel
     */
    public function exportToExcel(array $filters = []): string
    {
        $reservations = $this->getReservationsWithFilters($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Título
        $sheet->setCellValue('A1', 'Reporte de Reservas');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Información de filtros
        $row = 2;
        if (!empty($filters['start_date'])) {
            $sheet->setCellValue('A' . $row, 'Desde: ' . $filters['start_date']);
            $row++;
        }
        if (!empty($filters['end_date'])) {
            $sheet->setCellValue('A' . $row, 'Hasta: ' . $filters['end_date']);
            $row++;
        }
        $row++;

        // Encabezados
        $headers = ['ID', 'Usuario', 'Recurso', 'Inicio', 'Fin', 'Estado', 'Código'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4472C4');
            $sheet->getStyle($col . $row)->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Datos
        $row++;
        foreach ($reservations as $reservation) {
            $sheet->setCellValue('A' . $row, $reservation->getId());
            $sheet->setCellValue('B' . $row, $reservation->getUser()->getFirstName() . ' ' . $reservation->getUser()->getLastName());
            $sheet->setCellValue('C' . $row, $reservation->getResource()->getName());
            $sheet->setCellValue('D' . $row, $reservation->getStartTime()->format('Y-m-d H:i'));
            $sheet->setCellValue('E' . $row, $reservation->getEndTime()->format('Y-m-d H:i'));
            $sheet->setCellValue('F' . $row, ucfirst($reservation->getStatus()));
            $sheet->setCellValue('G' . $row, $reservation->getConfirmationCode());
            $row++;
        }

        // Ajustar ancho de columnas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Generar archivo
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'reservations_');
        $writer->save($tempFile);

        return file_get_contents($tempFile);
    }

    /**
     * Exportar reservas a iCalendar (.ics)
     */
    public function exportToIcal(array $filters = []): string
    {
        $reservations = $this->getReservationsWithFilters($filters);

        $calendar = new VCalendar([
            'PRODID' => '-//Reservation API//Calendar//EN',
            'VERSION' => '2.0',
            'CALSCALE' => 'GREGORIAN',
            'METHOD' => 'PUBLISH',
            'X-WR-CALNAME' => 'Reservas',
            'X-WR-TIMEZONE' => 'America/Lima',
        ]);

        foreach ($reservations as $reservation) {
            $event = $calendar->add('VEVENT', [
                'SUMMARY' => $reservation->getResource()->getName(),
                'DESCRIPTION' => sprintf(
                    'Usuario: %s %s\nCódigo: %s\nNotas: %s',
                    $reservation->getUser()->getFirstName(),
                    $reservation->getUser()->getLastName(),
                    $reservation->getConfirmationCode(),
                    $reservation->getNotes() ?? 'N/A'
                ),
                'DTSTART' => $reservation->getStartTime(),
                'DTEND' => $reservation->getEndTime(),
                'STATUS' => $this->mapStatusToIcal($reservation->getStatus()),
                'UID' => 'reservation-' . $reservation->getId() . '@reservationapi.com',
                'LOCATION' => $reservation->getResource()->getName(),
            ]);
        }

        return $calendar->serialize();
    }

    /**
     * Obtener reservas con filtros
     */
    private function getReservationsWithFilters(array $filters): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('r')
            ->from(Reservation::class, 'r')
            ->join('r.user', 'u')
            ->join('r.resource', 'res')
            ->orderBy('r.startTime', 'DESC');

        if (!empty($filters['start_date'])) {
            $qb->andWhere('r.startTime >= :start_date')
                ->setParameter('start_date', new \DateTimeImmutable($filters['start_date']));
        }

        if (!empty($filters['end_date'])) {
            $qb->andWhere('r.startTime <= :end_date')
                ->setParameter('end_date', new \DateTimeImmutable($filters['end_date']));
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $qb->andWhere('r.user = :user_id')
                ->setParameter('user_id', $filters['user_id']);
        }

        if (!empty($filters['resource_id'])) {
            $qb->andWhere('r.resource = :resource_id')
                ->setParameter('resource_id', $filters['resource_id']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Generar HTML para PDF
     */
    private function generatePdfHtml(array $reservations, array $filters): string
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                h1 { text-align: center; color: #333; }
                .filters { background: #f0f0f0; padding: 10px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background: #4472C4; color: white; padding: 10px; text-align: left; }
                td { padding: 8px; border-bottom: 1px solid #ddd; }
                tr:nth-child(even) { background: #f9f9f9; }
                .status-confirmed { color: green; font-weight: bold; }
                .status-pending { color: orange; font-weight: bold; }
                .status-cancelled { color: red; font-weight: bold; }
            </style>
        </head>
        <body>
            <h1>Reporte de Reservas</h1>
            <div class="filters">
                <strong>Filtros aplicados:</strong><br>';

        if (!empty($filters['start_date'])) {
            $html .= 'Desde: ' . $filters['start_date'] . '<br>';
        }
        if (!empty($filters['end_date'])) {
            $html .= 'Hasta: ' . $filters['end_date'] . '<br>';
        }
        if (empty($filters)) {
            $html .= 'Sin filtros (todas las reservas)';
        }

        $html .= '
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Recurso</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Estado</th>
                        <th>Código</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($reservations as $reservation) {
            $statusClass = 'status-' . $reservation->getStatus();
            $html .= sprintf(
                '<tr>
                    <td>%d</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td class="%s">%s</td>
                    <td>%s</td>
                </tr>',
                $reservation->getId(),
                $reservation->getUser()->getFirstName() . ' ' . $reservation->getUser()->getLastName(),
                $reservation->getResource()->getName(),
                $reservation->getStartTime()->format('Y-m-d H:i'),
                $reservation->getEndTime()->format('Y-m-d H:i'),
                $statusClass,
                ucfirst($reservation->getStatus()),
                $reservation->getConfirmationCode()
            );
        }

        $html .= '
                </tbody>
            </table>
            <p style="text-align: center; margin-top: 30px; color: #666;">
                Generado el ' . date('Y-m-d H:i:s') . '
            </p>
        </body>
        </html>';

        return $html;
    }

    /**
     * Mapear estados a formato iCalendar
     */
    private function mapStatusToIcal(string $status): string
    {
        return match ($status) {
            'confirmed' => 'CONFIRMED',
            'cancelled' => 'CANCELLED',
            'pending' => 'TENTATIVE',
            default => 'TENTATIVE',
        };
    }
}