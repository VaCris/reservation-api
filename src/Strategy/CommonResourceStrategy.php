<?php

namespace App\Strategy;

use App\Entity\Resource;
use App\Entity\User;

/**
 * Estrategia de validación común para recursos estándar
 */
class CommonResourceStrategy implements ValidationStrategyInterface
{
    public function validate(
        User $user,
        Resource $resource,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ): bool {
        // Validación 1: El recurso debe estar activo
        if (!$resource->isActive()) {
            throw new \RuntimeException(
                sprintf('El recurso "%s" no está activo', $resource->getName())
            );
        }

        // Validación 2: La fecha de inicio debe ser anterior a la fecha de fin
        if ($startTime >= $endTime) {
            throw new \RuntimeException(
                'La fecha de inicio debe ser anterior a la fecha de fin'
            );
        }

        // Validación 3: No se pueden hacer reservas en el pasado
        $now = new \DateTimeImmutable();
        if ($startTime < $now) {
            throw new \RuntimeException(
                'No se pueden crear reservas con fecha de inicio en el pasado'
            );
        }

        // Validación 4: La duración mínima es de 15 minutos
        $duration = $endTime->getTimestamp() - $startTime->getTimestamp();
        if ($duration < 900) { // 15 minutos = 900 segundos
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