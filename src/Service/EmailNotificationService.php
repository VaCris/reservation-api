<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private string $appName = 'Reservation API'
    ) {
    }

    /**
     * Enviar notificación de reserva creada
     */
    public function notifyReservationCreated(Reservation $reservation): void
    {
        $user = $reservation->getUser();
        $resource = $reservation->getResource();

        $subject = "Reserva creada - {$resource->getName()}";

        $body = $this->renderTemplate('reservation_created', [
            'user_name' => $user->getFirstName(),
            'resource_name' => $resource->getName(),
            'start_time' => $reservation->getStartTime()->format('d/m/Y H:i'),
            'end_time' => $reservation->getEndTime()->format('d/m/Y H:i'),
            'confirmation_code' => $reservation->getConfirmationCode(),
        ]);

        $this->sendEmail(
            $user,
            $subject,
            $body,
            'reservation_created',
            [
                'reservation_id' => $reservation->getId(),
                'confirmation_code' => $reservation->getConfirmationCode(),
            ]
        );
    }

    /**
     * Enviar notificación de reserva confirmada
     */
    public function notifyReservationConfirmed(Reservation $reservation): void
    {
        $user = $reservation->getUser();
        $resource = $reservation->getResource();

        $subject = "Reserva confirmada - {$resource->getName()}";

        $body = $this->renderTemplate('reservation_confirmed', [
            'user_name' => $user->getFirstName(),
            'resource_name' => $resource->getName(),
            'start_time' => $reservation->getStartTime()->format('d/m/Y H:i'),
            'end_time' => $reservation->getEndTime()->format('d/m/Y H:i'),
            'confirmation_code' => $reservation->getConfirmationCode(),
        ]);

        $this->sendEmail(
            $user,
            $subject,
            $body,
            'reservation_confirmed',
            ['reservation_id' => $reservation->getId()]
        );
    }

    /**
     * Enviar notificación de reserva cancelada
     */
    public function notifyReservationCancelled(Reservation $reservation): void
    {
        $user = $reservation->getUser();
        $resource = $reservation->getResource();

        $subject = "Reserva cancelada - {$resource->getName()}";

        $body = $this->renderTemplate('reservation_cancelled', [
            'user_name' => $user->getFirstName(),
            'resource_name' => $resource->getName(),
            'start_time' => $reservation->getStartTime()->format('d/m/Y H:i'),
            'confirmation_code' => $reservation->getConfirmationCode(),
        ]);

        $this->sendEmail(
            $user,
            $subject,
            $body,
            'reservation_cancelled',
            ['reservation_id' => $reservation->getId()]
        );
    }

    /**
     * Enviar email genérico
     */
    private function sendEmail(
        User $user,
        string $subject,
        string $body,
        string $type,
        array $metadata = []
    ): void {
        try {
            $notification = new Notification();
            $notification->setUser($user);
            $notification->setType($type);
            $notification->setSubject($subject);
            $notification->setBody($body);
            $notification->setRecipientEmail($user->getEmail());
            $notification->setMetadata($metadata);
            $notification->setStatus('pending');

            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            $email = (new Email())
                ->from('hello@demomailtrap.co')
                ->to($user->getEmail())
                ->subject($subject)
                ->html($this->wrapTemplate($body, $subject));

            $this->mailer->send($email);

            // Marcar como enviado
            $notification->setStatus('sent');
            $notification->setSentAt(new \DateTimeImmutable());
            $this->entityManager->flush();

        } catch (\Exception $e) {
            error_log("Error enviando email: " . $e->getMessage());

            if (isset($notification)) {
                $notification->setStatus('failed');
                $notification->setErrorMessage($e->getMessage());
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Renderizar plantilla simple HTML
     */
    private function renderTemplate(string $template, array $data = []): string
    {
        return match ($template) {
            'reservation_created' => $this->templateReservationCreated($data),
            'reservation_confirmed' => $this->templateReservationConfirmed($data),
            'reservation_cancelled' => $this->templateReservationCancelled($data),
            default => 'Notificación',
        };
    }

    /**
     * Plantilla: Reserva Creada
     */
    private function templateReservationCreated(array $data): string
    {
        return <<<HTML
        <h2>¡Reserva creada exitosamente!</h2>
        <p>Hola {$data['user_name']},</p>
        <p>Tu reserva ha sido registrada correctamente:</p>
        <ul>
            <li><strong>Recurso:</strong> {$data['resource_name']}</li>
            <li><strong>Desde:</strong> {$data['start_time']}</li>
            <li><strong>Hasta:</strong> {$data['end_time']}</li>
            <li><strong>Código:</strong> {$data['confirmation_code']}</li>
        </ul>
        <p>Tu reserva está en estado <strong>pendiente</strong> de confirmación por un administrador.</p>
        <p>Te notificaremos cuando sea confirmada.</p>
        HTML;
    }

    /**
     * Plantilla: Reserva Confirmada
     */
    private function templateReservationConfirmed(array $data): string
    {
        return <<<HTML
        <h2>¡Tu reserva fue confirmada!</h2>
        <p>Hola {$data['user_name']},</p>
        <p>Tu reserva ha sido confirmada por un administrador:</p>
        <ul>
            <li><strong>Recurso:</strong> {$data['resource_name']}</li>
            <li><strong>Desde:</strong> {$data['start_time']}</li>
            <li><strong>Hasta:</strong> {$data['end_time']}</li>
            <li><strong>Código:</strong> {$data['confirmation_code']}</li>
        </ul>
        <p>¡Tu reserva está confirmada! Te recordaremos 24 horas antes.</p>
        HTML;
    }

    /**
     * Plantilla: Reserva Cancelada
     */
    private function templateReservationCancelled(array $data): string
    {
        return <<<HTML
        <h2>Reserva cancelada</h2>
        <p>Hola {$data['user_name']},</p>
        <p>Tu reserva ha sido cancelada:</p>
        <ul>
            <li><strong>Recurso:</strong> {$data['resource_name']}</li>
            <li><strong>Desde:</strong> {$data['start_time']}</li>
            <li><strong>Hasta:</strong> {$data['end_time']}</li>
            <li><strong>Código:</strong> {$data['confirmation_code']}</li>
        </ul>
        <p>Si tienes preguntas, contacta al equipo de soporte.</p>
        HTML;
    }

    /**
     * Envolver contenido con header/footer
     */
    private function wrapTemplate(string $content, string $subject): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { background: #333; color: white; padding: 10px; text-align: center; border-radius: 0 0 5px 5px; }
                h2 { color: #007bff; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>{$this->appName}</h1>
                </div>
                <div class="content">
                    {$content}
                </div>
                <div class="footer">
                    <p>&copy; 2025 {$this->appName}. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}