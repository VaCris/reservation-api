<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Resource;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Reservation::class);
        $this->entityManager = $entityManager;
    }

    /**
     * Crea una reserva de forma atómica (transaccional)
     */
    public function createReservationAtomically(
        User $user,
        Resource $resource,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        string $status = Reservation::STATUS_PENDING,
        ?string $notes = null,
        ?array $metadata = null
    ): Reservation {
        return $this->entityManager->wrapInTransaction(function (EntityManagerInterface $em) use (
            $user,
            $resource,
            $startTime,
            $endTime,
            $status,
            $notes,
            $metadata
        ) {
            $conflicts = $this->findConflictingReservations($resource, $startTime, $endTime);

            if (count($conflicts) > 0) {
                throw new \RuntimeException(
                    'El recurso no está disponible en el horario seleccionado'
                );
            }

            $reservation = new Reservation();
            $reservation->setUser($user)
                ->setResource($resource)
                ->setStartTime($startTime)
                ->setEndTime($endTime)
                ->setStatus($status)
                ->setNotes($notes)
                ->setMetadata($metadata);

            $em->persist($reservation);
            return $reservation;
        });
    }

    /**
     * Encuentra reservas que se solapan con el período dado
     */
    public function findConflictingReservations(
        Resource $resource,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        ?Reservation $excludeReservation = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->where('r.resource = :resource')
            ->andWhere('r.status != :cancelled')
            ->andWhere(
                '(r.startTime < :endTime AND r.endTime > :startTime)'
            )
            ->setParameter('resource', $resource)
            ->setParameter('cancelled', Reservation::STATUS_CANCELLED)
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        if ($excludeReservation !== null) {
            $qb->andWhere('r.id != :excludeId')
               ->setParameter('excludeId', $excludeReservation->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Obtiene reservas activas de un usuario
     */
    public function findActiveReservationsByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.status IN (:activeStatuses)')
            ->andWhere('r.endTime >= :now')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', [
                Reservation::STATUS_PENDING,
                Reservation::STATUS_CONFIRMED
            ])
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('r.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene reservas por rango de fechas
     */
    public function findByResourceAndDateRange(
        Resource $resource,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        return $this->createQueryBuilder('r')
            ->where('r.resource = :resource')
            ->andWhere('r.startTime >= :startDate')
            ->andWhere('r.endTime <= :endDate')
            ->andWhere('r.status != :cancelled')
            ->setParameter('resource', $resource)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('cancelled', Reservation::STATUS_CANCELLED)
            ->orderBy('r.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Actualiza el estado de una reserva de forma atómica
     */
    public function updateStatusAtomically(Reservation $reservation, string $newStatus): void
    {
        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $em) use ($reservation, $newStatus) {
            $reservation->setStatus($newStatus);
        });
    }
}