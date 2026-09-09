<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Resource;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Strategy\ValidationStrategyInterface;
use App\Strategy\CommonResourceStrategy;
use App\Strategy\HighSecurityStrategy;
use App\Strategy\MeetingRoomStrategy;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Manager central para la lógica de negocio de Reservas
 * Implementa Inyección de Dependencias y Patrón Strategy
 */
class ReservationManager
{
    private ReservationRepository $reservationRepository;
    private AuditLogManager $auditLogManager;
    private EmailNotificationService $emailService;
    private RealtimeNotificationService $realtimeService;
    private EntityManagerInterface $entityManager;

    /** @var array<string, ValidationStrategyInterface> */
    private array $strategies = [];

    public function __construct(
        ReservationRepository $reservationRepository,
        AuditLogManager $auditLogManager,
        EmailNotificationService $emailService,
        RealtimeNotificationService $realtimeService,
        EntityManagerInterface $entityManager
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->auditLogManager = $auditLogManager;
        $this->emailService = $emailService;
        $this->realtimeService = $realtimeService;
        $this->entityManager = $entityManager;

        $this->registerStrategy(new CommonResourceStrategy());
        $this->registerStrategy(new HighSecurityStrategy());
        $this->registerStrategy(new MeetingRoomStrategy());
    }

    /**
     * Registra una nueva estrategia de validación
     */
    public function registerStrategy(ValidationStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getName()] = $strategy;
    }

    /**
     * Obtiene la estrategia correcta según el tipo de recurso
     */
    private function getStrategyForResource(Resource $resource): ValidationStrategyInterface
    {
        $strategyName = $resource->getValidationStrategy()
            ?? $resource->getResourceType()?->getValidationStrategy()
            ?? 'CommonResourceStrategy';

        if (!isset($this->strategies[$strategyName])) {
            throw new \RuntimeException(
                sprintf('Estrategia de validación "%s" no encontrada', $strategyName)
            );
        }

        return $this->strategies[$strategyName];
    }

    /**
     * Crea una nueva reserva aplicando todas las validaciones y auditoría
     */
    public function createReservation(
        User $user,
        Resource $resource,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        ?string $notes = null,
        ?array $metadata = null
    ): Reservation {
        $strategy = $this->getStrategyForResource($resource);
        $strategy->validate($user, $resource, $startTime, $endTime);

        $reservation = $this->reservationRepository->createReservationAtomically(
            $user,
            $resource,
            $startTime,
            $endTime,
            Reservation::STATUS_PENDING,
            $notes,
            $metadata
        );

        $this->auditLogManager->log(
            user: $user,
            action: 'RESERVATION_CREATED',
            entityType: 'Reservation',
            entityId: $reservation->getId(),
            newValues: [
                'resource_id' => $resource->getId(),
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
                'status' => $reservation->getStatus(),
                'strategy_used' => $strategy->getName()
            ]
        );

        try {
            $this->emailService->notifyReservationCreated($reservation);
            $this->realtimeService->notifyReservationCreated($reservation);
        } catch (\Exception $e) {
            error_log("Error enviando email de creación: " . $e->getMessage());
        }

        return $reservation;
    }

    /**
     * Cancela una reserva existente
     */
    public function cancelReservation(Reservation $reservation, User $user): void
    {
        $oldStatus = $reservation->getStatus();
        $this->reservationRepository->updateStatusAtomically(
            $reservation,
            Reservation::STATUS_CANCELLED
        );

        $this->auditLogManager->log(
            user: $user,
            action: 'RESERVATION_CANCELLED',
            entityType: 'Reservation',
            entityId: $reservation->getId(),
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => Reservation::STATUS_CANCELLED]
        );

        try {
            $this->emailService->notifyReservationCancelled($reservation);
            $this->realtimeService->notifyReservationCancelled($reservation);
        } catch (\Exception $e) {
            error_log("Error enviando email de cancelación: " . $e->getMessage());
        }
    }

    /**
     * Confirma una reserva pendiente
     */
    public function confirmReservation(Reservation $reservation, User $approver): void
    {
        if ($reservation->getStatus() !== Reservation::STATUS_PENDING) {
            throw new \RuntimeException(
                'Solo se pueden confirmar reservas en estado PENDING'
            );
        }

        $this->reservationRepository->updateStatusAtomically(
            $reservation,
            Reservation::STATUS_CONFIRMED
        );

        $this->auditLogManager->log(
            user: $approver,
            action: 'RESERVATION_CONFIRMED',
            entityType: 'Reservation',
            entityId: $reservation->getId(),
            oldValues: ['status' => Reservation::STATUS_PENDING],
            newValues: ['status' => Reservation::STATUS_CONFIRMED]
        );

        try {
            $this->emailService->notifyReservationConfirmed($reservation);
            $this->realtimeService->notifyReservationConfirmed($reservation);
        } catch (\Exception $e) {
            error_log("Error enviando email de confirmación: " . $e->getMessage());
        }
    }

    /**
     * Obtiene las reservas activas de un usuario
     */
    public function getUserActiveReservations(User $user): array
    {
        return $this->reservationRepository->findActiveReservationsByUser($user);
    }

    /**
     * Verifica disponibilidad de un recurso en un rango de tiempo
     */
    public function checkAvailability(
        Resource $resource,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ): bool {
        $conflicts = $this->reservationRepository->findConflictingReservations(
            $resource,
            $startTime,
            $endTime
        );

        return count($conflicts) === 0;
    }
}