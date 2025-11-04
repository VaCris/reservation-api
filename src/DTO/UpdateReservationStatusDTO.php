<?php

namespace App\DTO;

use App\Entity\Reservation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO para actualizar el estado de una reserva
 */
class UpdateReservationStatusDTO
{
    #[Assert\NotBlank(message: 'El estado es obligatorio')]
    #[Assert\Choice(
        choices: [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_CANCELLED,
            Reservation::STATUS_COMPLETED
        ],
        message: 'El estado debe ser uno de: {{ choices }}'
    )]
    public ?string $status = null;

    #[Assert\Length(max: 500, maxMessage: 'El motivo no puede exceder {{ limit }} caracteres')]
    public ?string $reason = null;
}