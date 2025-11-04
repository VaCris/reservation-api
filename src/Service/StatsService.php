<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Servicio para generar estadísticas de reservas
 */
class StatsService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Obtener estadísticas completas para el dashboard
     */
    public function getDashboardStats(\DateTime $startDate): array
    {
        return [
            'overview' => [
                'total_reservations' => $this->getTotalReservations($startDate),
                'confirmed_reservations' => $this->getReservationsByStatus('confirmed', $startDate),
                'pending_reservations' => $this->getReservationsByStatus('pending', $startDate),
                'cancelled_reservations' => $this->getReservationsByStatus('cancelled', $startDate),
                'cancellation_rate' => $this->getCancellationRate($startDate),
            ],
            'top_resources' => $this->getTopResources($startDate, 5),
            'top_users' => $this->getTopUsers($startDate, 5),
            'reservations_by_day' => $this->getReservationsByDay($startDate),
            'peak_hours' => $this->getPeakHours($startDate),
        ];
    }

    /**
     * Total de reservas desde una fecha
     */
    public function getTotalReservations(\DateTime $startDate): int
    {
        return (int) $this->entityManager->createQuery(
            'SELECT COUNT(r.id) FROM App\Entity\Reservation r
            WHERE r.createdAt >= :startDate'
        )
            ->setParameter('startDate', $startDate)
            ->getSingleScalarResult();
    }

    /**
     * Reservas por estado
     */
    public function getReservationsByStatus(string $status, \DateTime $startDate): int
    {
        return (int) $this->entityManager->createQuery(
            'SELECT COUNT(r.id) FROM App\Entity\Reservation r
            WHERE r.status = :status AND r.createdAt >= :startDate'
        )
            ->setParameter('status', $status)
            ->setParameter('startDate', $startDate)
            ->getSingleScalarResult();
    }

    /**
     * Tasa de cancelación en porcentaje
     */
    public function getCancellationRate(\DateTime $startDate): float
    {
        $total = $this->getTotalReservations($startDate);
        if ($total === 0) {
            return 0.0;
        }

        $cancelled = $this->getReservationsByStatus('cancelled', $startDate);
        return round(($cancelled / $total) * 100, 2);
    }

    /**
     * Recursos más reservados
     */
    public function getTopResources(\DateTime $startDate, int $limit): array
    {
        $results = $this->entityManager->createQuery(
            'SELECT res.id, res.name, COUNT(r.id) as total_reservations
            FROM App\Entity\Reservation r
            JOIN r.resource res
            WHERE r.createdAt >= :startDate
            GROUP BY res.id, res.name
            ORDER BY total_reservations DESC'
        )
            ->setParameter('startDate', $startDate)
            ->setMaxResults($limit)
            ->getResult();

        return array_map(fn($row) => [
            'resource_id' => $row['id'],
            'resource_name' => $row['name'],
            'total_reservations' => (int) $row['total_reservations'],
        ], $results);
    }

    /**
     * Usuarios más activos
     */
    public function getTopUsers(\DateTime $startDate, int $limit): array
    {
        $results = $this->entityManager->createQuery(
            'SELECT u.id, u.firstName, u.lastName, u.email, COUNT(r.id) as total_reservations
            FROM App\Entity\Reservation r
            JOIN r.user u
            WHERE r.createdAt >= :startDate
            GROUP BY u.id, u.firstName, u.lastName, u.email
            ORDER BY total_reservations DESC'
        )
            ->setParameter('startDate', $startDate)
            ->setMaxResults($limit)
            ->getResult();

        return array_map(fn($row) => [
            'user_id' => $row['id'],
            'user_name' => $row['firstName'] . ' ' . $row['lastName'],
            'email' => $row['email'],
            'total_reservations' => (int) $row['total_reservations'],
        ], $results);
    }

    /**
     * Reservas por día
     */
    public function getReservationsByDay(\DateTime $startDate): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = '
            SELECT DATE(created_at) as date, COUNT(id) as total
            FROM reservations
            WHERE created_at >= :startDate
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ';

        $stmt = $conn->prepare($sql);
        $results = $stmt->executeQuery(['startDate' => $startDate->format('Y-m-d H:i:s')])->fetchAllAssociative();

        return array_map(fn($row) => [
            'date' => $row['date'],
            'total' => (int) $row['total'],
        ], $results);
    }

    /**
     * Horas pico de reservas
     */
    public function getPeakHours(\DateTime $startDate): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = '
            SELECT HOUR(start_time) as hour, COUNT(id) as total
            FROM reservations
            WHERE created_at >= :startDate
            GROUP BY HOUR(start_time)
            ORDER BY total DESC
            LIMIT 24
        ';

        $stmt = $conn->prepare($sql);
        $results = $stmt->executeQuery(['startDate' => $startDate->format('Y-m-d H:i:s')])->fetchAllAssociative();

        return array_map(fn($row) => [
            'hour' => (int) $row['hour'],
            'hour_formatted' => sprintf('%02d:00', $row['hour']),
            'total_reservations' => (int) $row['total'],
        ], $results);
    }
}