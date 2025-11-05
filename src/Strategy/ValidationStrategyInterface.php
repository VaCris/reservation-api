<?php

namespace App\Strategy;

use App\Entity\Reservation;
use App\Entity\Resource;
use App\Entity\User;

interface ValidationStrategyInterface
{
    public function validate(
        User $user,
        Resource $resource,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ): bool;

    public function getName(): string;

    public function getDescription(): string;
}