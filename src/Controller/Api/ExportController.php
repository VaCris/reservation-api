<?php

namespace App\Controller\Api;

use App\Service\ReportExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/export', name: 'api_v1_export_')]
class ExportController extends AbstractController
{
    public function __construct(
        private ReportExportService $exportService
    ) {
    }

    /**
     * GET /api/v1/export/pdf
     */
    #[Route('/pdf', name: 'pdf', methods: ['GET'])]
    public function exportPdf(Request $request): Response
    {
        $filters = $this->getFiltersFromRequest($request);

        $pdfContent = $this->exportService->exportToPdf($filters);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="reservas.pdf"');

        return $response;
    }

    /**
     * GET /api/v1/export/excel
     */
    #[Route('/excel', name: 'excel', methods: ['GET'])]
    public function exportExcel(Request $request): Response
    {
        $filters = $this->getFiltersFromRequest($request);

        $excelContent = $this->exportService->exportToExcel($filters);

        $response = new Response($excelContent);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="reservas.xlsx"');

        return $response;
    }

    /**
     * GET /api/v1/export/ical
     */
    #[Route('/ical', name: 'ical', methods: ['GET'])]
    public function exportIcal(Request $request): Response
    {
        $filters = $this->getFiltersFromRequest($request);

        $icalContent = $this->exportService->exportToIcal($filters);

        $response = new Response($icalContent);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="reservas.ics"');

        return $response;
    }

    /**
     * Extraer filtros del request
     */
    private function getFiltersFromRequest(Request $request): array
    {
        return [
            'start_date' => $request->query->get('start_date'),
            'end_date' => $request->query->get('end_date'),
            'status' => $request->query->get('status'),
            'user_id' => $request->query->get('user_id'),
            'resource_id' => $request->query->get('resource_id'),
        ];
    }
}