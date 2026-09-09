<?php

namespace App\Strategy;

use App\Entity\Resource;
use App\Entity\User;

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

        $startMinute = (int) $startTime->format('i');
        if ($startMinute !== 0 && $startMinute !== 30) {
            throw new \RuntimeException(
                'Las reservas de salas deben comenzar en punto o y media (00 o 30 minutos)'
            );
        }

        $duration = $endTime->getTimestamp() - $startTime->getTimestamp();
        if ($duration > 14400) { // 4 horas
            throw new \RuntimeException(
                'La duración máxima de una sala de reuniones es de 4 horas'
            );
        }

        if ($resource->getMetadata() && isset($resource->getMetadata()['min_attendees'])) {
            $minAttendees = $resource->getMetadata()['min_attendees'];
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