<?php

namespace App\Controller\Api;

use App\Service\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/stats', name: 'api_v1_stats_')]
class StatsController extends AbstractController
{
    public function __construct(
        private StatsService $statsService
    ) {
    }

    /**
     * Dashboard general con todas las estadísticas
     * GET /api/v1/stats/dashboard
     */
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'month');
        $startDate = $this->getStartDateFromPeriod($period);

        $stats = $this->statsService->getDashboardStats($startDate);

        return $this->json([
            'period' => $period,
            ...$stats
        ]);
    }

    /**
     * Total de reservas en un período
     * GET /api/v1/stats/total
     */
    #[Route('/total', name: 'total', methods: ['GET'])]
    public function total(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 30);
        $startDate = new \DateTime("-{$days} days");

        return $this->json([
            'total' => $this->statsService->getTotalReservations($startDate),
            'period_days' => $days,
            'start_date' => $startDate->format('Y-m-d'),
        ]);
    }

    /**
     * Recursos más utilizados
     * GET /api/v1/stats/top-resources
     */
    #[Route('/top-resources', name: 'top_resources', methods: ['GET'])]
    public function topResources(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 10);
        $days = (int) $request->query->get('days', 30);
        $startDate = new \DateTime("-{$days} days");

        return $this->json([
            'data' => $this->statsService->getTopResources($startDate, $limit),
        ]);
    }

    /**
     * Usuarios más activos
     * GET /api/v1/stats/top-users
     */
    #[Route('/top-users', name: 'top_users', methods: ['GET'])]
    public function topUsers(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 10);
        $days = (int) $request->query->get('days', 30);
        $startDate = new \DateTime("-{$days} days");

        return $this->json([
            'data' => $this->statsService->getTopUsers($startDate, $limit),
        ]);
    }

    /**
     * Reservas por día
     * GET /api/v1/stats/by-day
     */
    #[Route('/by-day', name: 'by_day', methods: ['GET'])]
    public function byDay(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 30);
        $startDate = new \DateTime("-{$days} days");

        return $this->json([
            'data' => $this->statsService->getReservationsByDay($startDate),
        ]);
    }

    /**
     * Horas pico de reservas
     * GET /api/v1/stats/peak-hours
     */
    #[Route('/peak-hours', name: 'peak_hours', methods: ['GET'])]
    public function peakHours(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 30);
        $startDate = new \DateTime("-{$days} days");

        return $this->json([
            'data' => $this->statsService->getPeakHours($startDate),
        ]);
    }

    /**
     * Helper: Convertir período a fecha de inicio
     */
    private function getStartDateFromPeriod(string $period): \DateTime
    {
        return match ($period) {
            'week' => new \DateTime('-7 days'),
            'month' => new \DateTime('-30 days'),
            'year' => new \DateTime('-365 days'),
            default => new \DateTime('-30 days'),
        };
    }
}