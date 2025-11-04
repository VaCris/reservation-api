<?php

namespace App\Strategy;

use App\Entity\Resource;
use App\Entity\User;

/**
 * Estrategia de validación para salas de reuniones
 */
class MeetingRoomStrategy implements ValidationStrategyInterface
{
    public function validate(
        User $user,
        Resource $resource,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ): bool {
        // Validación 1: Aplicar validaciones comunes
        $commonStrategy = new CommonResourceStrategy();
        $commonStrategy->validate($user, $resource, $startTime, $endTime);

        // Validación 2: Las reservas deben empezar en intervalos de 30 minutos
        $startMinute = (int) $startTime->format('i');
        if ($startMinute !== 0 && $startMinute !== 30) {
            throw new \RuntimeException(
                'Las reservas de salas deben comenzar en punto o y media (00 o 30 minutos)'
            );
        }

        // Validación 3: Duración máxima de 4 horas
        $duration = $endTime->getTimestamp() - $startTime->getTimestamp();
        if ($duration > 14400) { // 4 horas
            throw new \RuntimeException(
                'La duración máxima de una sala de reuniones es de 4 horas'
            );
        }

        // Validación 4: Verificar capacidad del recurso en metadata
        if ($resource->getMetadata() && isset($resource->getMetadata()['min_attendees'])) {
            $minAttendees = $resource->getMetadata()['min_attendees'];
            // Esta validación se puede extender cuando se agregue campo de asistentes
        }

        return true;
    }

    public function getName(): string
    {
        return 'MeetingRoomStrategy';
    }

    public function getDescription(): string
    {
        return 'Salas de reuniones: inicio en :00 o :30, máx 4h';
    }
}