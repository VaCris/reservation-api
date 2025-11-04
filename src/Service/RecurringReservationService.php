<?php

namespace App\Service;

use App\DTO\CreateRecurringReservationDTO;
use App\Entity\Reservation;
use App\Entity\RecurringPattern;
use App\Entity\Resource;
use App\Entity\User;
use App\Repository\RecurringPatternRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecurringReservationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecurringPatternRepository $recurringPatternRepository,
        private ReservationManager $reservationManager
    ) {
    }

    /**
     * Crear una reserva recurrente
     */
    public function createRecurringReservation(
        CreateRecurringReservationDTO $dto,
        Resource $resource,
        User $user
    ): RecurringPattern {
        $pattern = new RecurringPattern();
        $pattern->setFrequency($dto->frequency);
        $pattern->setInterval($dto->interval);
        $pattern->setStartDate(new \DateTimeImmutable($dto->recurringStartDate));

        if ($dto->recurringEndDate) {
            $pattern->setEndDate(new \DateTimeImmutable($dto->recurringEndDate));
        }

        if ($dto->frequency === 'weekly') {
            $pattern->setDaysOfWeek($dto->daysOfWeek);
        }

        $pattern->setMetadata(['max_instances' => $dto->maxInstances]);

        $this->entityManager->persist($pattern);
        $this->entityManager->flush();

        $reservations = $this->generateReservations(
            $dto,
            $resource,
            $user,
            $pattern
        );

        foreach ($reservations as $reservation) {
            $this->entityManager->persist($reservation);
        }
        $this->entityManager->flush();
        $this->entityManager->refresh($pattern);
        return $pattern;
    }

    /**
     * Generar fechas según el patrón de recurrencia
     */
    private function generateReservations(
        CreateRecurringReservationDTO $dto,
        Resource $resource,
        User $user,
        RecurringPattern $pattern
    ): array {
        $reservations = [];
        $startDate = new \DateTimeImmutable($dto->recurringStartDate);
        $endDate = $pattern->getEndDate();
        $startTime = new \DateTimeImmutable($dto->startTime);
        $endTime = new \DateTimeImmutable($dto->endTime);

        $currentDate = $startDate;
        $count = 0;
        $maxInstances = $dto->maxInstances;

        // error_log("START DATE: " . $startDate->format('Y-m-d N')); // N = day of week
        // error_log("END DATE: " . ($endDate ? $endDate->format('Y-m-d') : 'null'));
        // error_log("FREQUENCY: " . $pattern->getFrequency());
        // error_log("DAYS OF WEEK: " . json_encode($pattern->getDaysOfWeek()));
        // error_log("MAX INSTANCES: " . $maxInstances);

        while (($endDate === null || $currentDate <= $endDate) && $count < $maxInstances) {
            $dayOfWeek = (int) $currentDate->format('N');
            $shouldCreate = $this->shouldCreateReservation($currentDate, $pattern);

            // error_log("Checking: " . $currentDate->format('Y-m-d') . " (day $dayOfWeek) -> $shouldCreate");

            if ($shouldCreate) {
                $hour = (int) $startTime->format('H');
                $minute = (int) $startTime->format('i');
                $second = (int) $startTime->format('s');

                $endHour = (int) $endTime->format('H');
                $endMinute = (int) $endTime->format('i');
                $endSecond = (int) $endTime->format('s');

                $reservationStartTime = $currentDate->setTime($hour, $minute, $second);
                $reservationEndTime = $currentDate->setTime($endHour, $endMinute, $endSecond);

                $reservation = new Reservation();
                $reservation->setResource($resource);
                $reservation->setUser($user);
                $reservation->setStartTime($reservationStartTime);
                $reservation->setEndTime($reservationEndTime);
                $reservation->setNotes($dto->notes);
                $reservation->setMetadata($dto->metadata ?? []);
                $reservation->setRecurringPattern($pattern);
                $reservation->setStatus('pending');
                $reservation->setConfirmationCode($this->generateConfirmationCode());

                $reservations[] = $reservation;
                $count++;

                error_log("Created reservation #$count for " . $reservationStartTime->format('Y-m-d H:i'));
            }

            $currentDate = $this->getNextDate($currentDate, $pattern);
        }

        error_log("TOTAL RESERVATIONS CREATED: " . $count);

        return $reservations;
    }

    /**
     * Validar si se debe crear una reserva para esta fecha
     */
    private function shouldCreateReservation(
        \DateTimeImmutable $date,
        RecurringPattern $pattern
    ): bool {
        $frequency = $pattern->getFrequency();

        return match ($frequency) {
            'daily' => true,
            'weekly' => in_array((int) $date->format('N'), $pattern->getDaysOfWeek()),
            'monthly' => (int) $date->format('d') === (int) $pattern->getStartDate()->format('d'),
            'yearly' => $date->format('m-d') === $pattern->getStartDate()->format('m-d'),
            default => false,
        };
    }

    /**
     * Calcular la siguiente fecha según el patrón
     */
    private function getNextDate(
        \DateTimeImmutable $current,
        RecurringPattern $pattern
    ): \DateTimeImmutable {
        $frequency = $pattern->getFrequency();
        $interval = $pattern->getInterval();

        return match ($frequency) {
            'daily' => $current->modify("+{$interval} day"),
            'weekly' => $current->modify("+1 day"),
            'monthly' => $current->modify("+1 day"),
            'yearly' => $current->modify("+1 day"),
            default => $current->modify('+1 day'),
        };
    }

    /**
     * Generar código único de confirmación
     */
    private function generateConfirmationCode(): string
    {
        return strtoupper(bin2hex(random_bytes(8)));
    }

    /**
     * Cancelar todas las instancias de una reserva recurrente
     */
    public function cancelRecurringReservations(RecurringPattern $pattern, ?User $user = null): int
    {
        $count = 0;
        foreach ($pattern->getReservations() as $reservation) {
            if ($reservation->getStatus() !== 'cancelled') {
                $reservation->setStatus('cancelled');
                $count++;
            }
        }
        $this->entityManager->flush();
        return $count;
    }

    /**
     * Cancelar solo instancias futuras
     */
    public function cancelFutureReservations(RecurringPattern $pattern): int
    {
        $now = new \DateTimeImmutable();
        $count = 0;

        foreach ($pattern->getReservations() as $reservation) {
            if ($reservation->getStartTime() > $now && $reservation->getStatus() !== 'cancelled') {
                $reservation->setStatus('cancelled');
                $count++;
            }
        }
        $this->entityManager->flush();
        return $count;
    }
}
