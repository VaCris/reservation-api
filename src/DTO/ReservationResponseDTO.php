<?php

namespace App\DTO;

use App\Entity\Reservation;

/**
 * DTO para respuestas de reservas
 * Evita exponer toda la entidad en las respuestas de la API
 */
class ReservationResponseDTO
{
    public int $id;
    public int $resourceId;
    public string $resourceName;
    public int $userId;
    public string $userName;
    public string $startTime;
    public string $endTime;
    public string $status;
    public ?string $notes;
    public string $confirmationCode;
    public string $createdAt;

    public static function fromEntity(Reservation $reservation): self
    {
        $dto = new self();
        $dto->id = $reservation->getId();
        $dto->resourceId = $reservation->getResource()->getId();
        $dto->resourceName = $reservation->getResource()->getName();
        $dto->userId = $reservation->getUser()->getId();
        $dto->userName = $reservation->getUser()->getFirstName() . ' ' . $reservation->getUser()->getLastName();
        $dto->startTime = $reservation->getStartTime()->format(\DateTimeInterface::ATOM);
        $dto->endTime = $reservation->getEndTime()->format(\DateTimeInterface::ATOM);
        $dto->status = $reservation->getStatus();
        $dto->notes = $reservation->getNotes();
        $dto->confirmationCode = $reservation->getConfirmationCode();
        $dto->createdAt = $reservation->getCreatedAt()->format(\DateTimeInterface::ATOM);

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'resource' => [
                'id' => $this->resourceId,
                'name' => $this->resourceName,
            ],
            'user' => [
                'id' => $this->userId,
                'name' => $this->userName,
            ],
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'status' => $this->status,
            'notes' => $this->notes,
            'confirmation_code' => $this->confirmationCode,
            'created_at' => $this->createdAt,
        ];
    }
}