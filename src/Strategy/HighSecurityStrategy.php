<?php

namespace App\Strategy;

use App\Entity\Resource;
use App\Entity\User;

class HighSecurityStrategy implements ValidationStrategyInterface
{
    public function validate(
        User $user,
        Resource $resource,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ): bool {
        $commonStrategy = new CommonResourceStrategy();
        $commonStrategy->validate($user, $resource, $startTime, $endTime);

        $startHour = (int) $startTime->format('H');
        $endHour = (int) $endTime->format('H');

        if ($startHour < 9 || $endHour > 18) {
            throw new \RuntimeException(
                'Los recursos de alta seguridad solo pueden reservarse entre 9:00 y 18:00'
            );
        }

        $dayOfWeek = (int) $startTime->format('N');
        if ($dayOfWeek >= 6) {
            throw new \RuntimeException(
                'Los recursos de alta seguridad no pueden reservarse los fines de semana'
            );
        }

        $duration = $endTime->getTimestamp() - $startTime->getTimestamp();
        if ($duration > 14400) { // 4 hrs
            throw new \RuntimeException(
                'La duración máxima para recursos de alta seguridad es de 4 horas'
            );
        }

        $now = new \DateTimeImmutable();
        $hoursUntilReservation = ($startTime->getTimestamp() - $now->getTimestamp()) / 3600;

        if ($hoursUntilReservation < 24) {
            throw new \RuntimeException(
                'Los recursos de alta seguridad requieren reserva con 24 horas de anticipación'
            );
        }

        return true;
    }

    public function getName(): string
    {
        return 'HighSecurityStrategy';
    }

    public function getDescription(): string
    {
        return 'Recursos críticos: horario 9-18h, lunes-viernes, máx 4h, anticipación 24h';
    }
}