<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO - crear una reserva
 */
class CreateReservationDTO
{
    #[Assert\NotBlank(message: 'El ID del recurso es obligatorio')]
    #[Assert\Type(type: 'integer')]
    public ?int $resourceId = null;

    #[Assert\NotBlank(message: 'La fecha de inicio es obligatoria')]
    #[Assert\DateTime(format: \DateTimeInterface::ATOM)]
    public ?string $startTime = null;

    #[Assert\NotBlank(message: 'La fecha de fin es obligatoria')]
    #[Assert\DateTime(format: \DateTimeInterface::ATOM)]
    public ?string $endTime = null;

    #[Assert\Length(max: 1000, maxMessage: 'Las notas no pueden exceder {{ limit }} caracteres')]
    public ?string $notes = null;

    #[Assert\Type(type: 'array')]
    public ?array $metadata = null;

    public ?bool $isRecurring = false;
    public ?string $recurrenceFrequency = null;
    public ?int $recurrenceInterval = null;
    public ?\DateTimeImmutable $recurrenceEndDate = null;
    public ?array $recurrenceDaysOfWeek = null;

    /**
     * Valida que la fecha de fin sea posterior a la fecha de inicio
     */
    #[Assert\IsTrue(message: 'La fecha de fin debe ser posterior a la fecha de inicio')]
    public function isEndTimeValid(): bool
    {
        if ($this->startTime === null || $this->endTime === null) {
            return true;
        }

        try {
            $start = new \DateTimeImmutable($this->startTime);
            $end = new \DateTimeImmutable($this->endTime);
            return $end > $start;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Convierte el DTO a un array para logging
     */
    public function toArray(): array
    {
        return [
            'resource_id' => $this->resourceId,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}