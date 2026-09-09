<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateRecurringReservationDTO
{
    #[Assert\NotNull]
    #[Assert\Positive]
    public int $resourceId;

    #[Assert\NotNull]
    #[Assert\DateTime]
    public string $startTime;

    #[Assert\NotNull]
    #[Assert\DateTime]
    public string $endTime;

    public ?string $notes = null;

    public ?array $metadata = null;

    #[Assert\NotNull]
    #[Assert\Choice(['daily', 'weekly', 'monthly', 'yearly'])]
    public string $frequency = 'weekly';

    #[Assert\Positive]
    public int $interval = 1;

    #[Assert\NotNull]
    #[Assert\DateTime]
    public string $recurringStartDate;

    #[Assert\DateTime]
    public ?string $recurringEndDate = null;

    public array $daysOfWeek = [1, 2, 3, 4, 5, 6];

    #[Assert\Positive]
    public int $maxInstances = 52;
}