<?php

namespace App\Strategy;

use App\Entity\Resource;
use App\Entity\User;

class CommonResourceStrategy implements ValidationStrategyInterface
{
    public function validate(
        User $user,
        Resource $resource,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ): bool {
        if (!$resource->isActive()) {
            throw new \RuntimeException(
                sprintf('El recurso "%s" no está activo', $resource->getName())
            );
        }

        if ($startTime >= $endTime) {
            throw new \RuntimeException(
                'La fecha de inicio debe ser anterior a la fecha de fin'
            );
        }

        $now = new \DateTimeImmutable();
        if ($startTime < $now) {
            throw new \RuntimeException(
                'No se pueden crear reservas con fecha de inicio en el pasado'
            );
        }

        $duration = $endTime->getTimestamp() - $startTime->getTimestamp();
        if ($duration < 900) { // 15 min
            throw new \RuntimeException(
                'La duración mínima de una reserva es de 15 minutos'
            );
        }

        if ($duration > 28800) { // max 8 hrs
            throw new \RuntimeException(
                'La duración máxima de una reserva es de 8 horas'
            );
        }

        return true;
    }

    public function getName(): string
    {
        return 'CommonResourceStrategy';
    }

    public function getDescription(): string
    {
        return 'Validación estándar: recurso activo, duración 15min-8h, no pasado';
    }
}