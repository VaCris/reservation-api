<?php

namespace App\Service;

use App\Entity\Reservation;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Servicio para enviar notificaciones en tiempo real vía Mercure
 */
class RealtimeNotificationService
{
    public function __construct(
        private HubInterface $hub
    ) {
    }

    /**
     * Notificar que se creó una reserva
     */
    public function notifyReservationCreated(Reservation $reservation): void
    {
        $data = [
            'type' => 'reservation.created',
            'reservation_id' => $reservation->getId(),
            'resource' => [
                'id' => $reservation->getResource()->getId(),
                'name' => $reservation->getResource()->getName(),
            ],
            'user' => [
                'id' => $reservation->getUser()->getId(),
                'name' => $reservation->getUser()->getFirstName() . ' ' . $reservation->getUser()->getLastName(),
            ],
            'start_time' => $reservation->getStartTime()->format('Y-m-d H:i:s'),
            'end_time' => $reservation->getEndTime()->format('Y-m-d H:i:s'),
            'status' => $reservation->getStatus(),
            'confirmation_code' => $reservation->getConfirmationCode(),
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $this->publish('reservations', $data);
        $this->publish('user/' . $reservation->getUser()->getId(), $data);
    }

    /**
     * Notificar que se confirmó una reserva
     */
    public function notifyReservationConfirmed(Reservation $reservation): void
    {
        $data = [
            'type' => 'reservation.confirmed',
            'reservation_id' => $reservation->getId(),
            'resource' => [
                'id' => $reservation->getResource()->getId(),
                'name' => $reservation->getResource()->getName(),
            ],
            'user' => [
                'id' => $reservation->getUser()->getId(),
                'name' => $reservation->getUser()->getFirstName() . ' ' . $reservation->getUser()->getLastName(),
            ],
            'start_time' => $reservation->getStartTime()->format('Y-m-d H:i:s'),
            'end_time' => $reservation->getEndTime()->format('Y-m-d H:i:s'),
            'status' => $reservation->getStatus(),
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $this->publish('reservations', $data);
        $this->publish('user/' . $reservation->getUser()->getId(), $data);
    }

    /**
     * Notificar que se canceló una reserva
     */
    public function notifyReservationCancelled(Reservation $reservation): void
    {
        $data = [
            'type' => 'reservation.cancelled',
            'reservation_id' => $reservation->getId(),
            'resource' => [
                'id' => $reservation->getResource()->getId(),
                'name' => $reservation->getResource()->getName(),
            ],
            'user' => [
                'id' => $reservation->getUser()->getId(),
                'name' => $reservation->getUser()->getFirstName() . ' ' . $reservation->getUser()->getLastName(),
            ],
            'start_time' => $reservation->getStartTime()->format('Y-m-d H:i:s'),
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $this->publish('reservations', $data);
        $this->publish('user/' . $reservation->getUser()->getId(), $data);

        // Notificar que el recurso está disponible
        $this->notifyResourceAvailable($reservation->getResource()->getId());
    }

    /**
     * Notificar que un recurso está disponible
     */
    public function notifyResourceAvailable(int $resourceId): void
    {
        $data = [
            'type' => 'resource.available',
            'resource_id' => $resourceId,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $this->publish('resources', $data);
        $this->publish('resource/' . $resourceId, $data);
    }

    /**
     * Publicar un mensaje a un topic
     */
    private function publish(string $topic, array $data): void
    {
        try {
            $update = new Update(
                'https://reservationapi.com/' . $topic,
                json_encode($data)
            );

            $this->hub->publish($update);
        } catch (\Throwable $e) {
            error_log('Error publicando notificación: ' . $e->getMessage());
        }
    }

}