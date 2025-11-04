<?php

namespace App\Controller\Api;

use App\DTO\CreateReservationDTO;
use App\DTO\ReservationResponseDTO;
use App\DTO\CreateRecurringReservationDTO;
use App\Entity\User;
use App\Repository\ResourceRepository;
use App\Repository\ReservationRepository;
use App\Repository\RecurringPatternRepository;
use App\Service\ReservationManager;
use App\Service\RecurringReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/reservations', name: 'api_v1_reservations_')]
class ReservationController extends AbstractController
{
    public function __construct(
        private ReservationManager $reservationManager,
        private ReservationRepository $reservationRepository,
        private ResourceRepository $resourceRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Crear una nueva reserva
     * POST /api/v1/reservations
     */
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        #[MapRequestPayload] CreateReservationDTO $dto
    ): JsonResponse {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $resource = $this->resourceRepository->find($dto->resourceId);
            if (!$resource) {
                return $this->json([
                    'error' => 'Recurso no encontrado',
                    'code' => 'RESOURCE_NOT_FOUND'
                ], Response::HTTP_NOT_FOUND);
            }

            $startTime = new \DateTimeImmutable($dto->startTime);
            $endTime = new \DateTimeImmutable($dto->endTime);

            $reservation = $this->reservationManager->createReservation(
                user: $user,
                resource: $resource,
                startTime: $startTime,
                endTime: $endTime,
                notes: $dto->notes,
                metadata: $dto->metadata
            );


            $responseDto = ReservationResponseDTO::fromEntity($reservation);

            return $this->json([
                'message' => 'Reserva creada exitosamente',
                'data' => $responseDto->toArray()
            ], Response::HTTP_CREATED);

        } catch (\RuntimeException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'VALIDATION_ERROR'
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error interno del servidor',
                'code' => 'INTERNAL_ERROR'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Listar las reservas del usuario autenticado
     * GET /api/v1/reservations
     */
    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $reservations = $this->reservationManager->getUserActiveReservations($user);

        $data = array_map(
            fn($reservation) => ReservationResponseDTO::fromEntity($reservation)->toArray(),
            $reservations
        );

        return $this->json([
            'data' => $data,
            'count' => count($data)
        ]);
    }

    /**
     * Obtener una reserva específica
     * GET/api/v1/reservations/{id}
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(int $id): JsonResponse
    {
        $reservation = $this->reservationRepository->find($id);

        if (!$reservation) {
            return $this->json([
                'error' => 'Reserva no encontrada',
                'code' => 'RESERVATION_NOT_FOUND'
            ], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($reservation->getUser()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'No tiene permisos para ver esta reserva',
                'code' => 'ACCESS_DENIED'
            ], Response::HTTP_FORBIDDEN);
        }

        $responseDto = ReservationResponseDTO::fromEntity($reservation);

        return $this->json([
            'data' => $responseDto->toArray()
        ]);
    }

    /**
     * Cancelar una reserva
     * PUT /api/v1/reservations/{id}/cancel
     */
    #[Route('/{id}/cancel', name: 'cancel', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(int $id): JsonResponse
    {
        $reservation = $this->reservationRepository->find($id);

        if (!$reservation) {
            return $this->json([
                'error' => 'Reserva no encontrada',
                'code' => 'RESERVATION_NOT_FOUND'
            ], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($reservation->getUser()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'No tiene permisos para cancelar esta reserva',
                'code' => 'ACCESS_DENIED'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->reservationManager->cancelReservation($reservation, $user);

            return $this->json([
                'message' => 'Reserva cancelada exitosamente',
                'data' => ReservationResponseDTO::fromEntity($reservation)->toArray()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'CANCELLATION_ERROR'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Confirmar una reserva (solo administradores)
     * PUT /api/v1/reservations/{id}/confirm
     */
    #[Route('/{id}/confirm', name: 'confirm', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function confirm(int $id): JsonResponse
    {
        $reservation = $this->reservationRepository->find($id);

        if (!$reservation) {
            return $this->json([
                'error' => 'Reserva no encontrada',
                'code' => 'RESERVATION_NOT_FOUND'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            /** @var User $admin */
            $admin = $this->getUser();
            $this->reservationManager->confirmReservation($reservation, $admin);

            return $this->json([
                'message' => 'Reserva confirmada exitosamente',
                'data' => ReservationResponseDTO::fromEntity($reservation)->toArray()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'CONFIRMATION_ERROR'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Verificar disponibilidad de un recurso
     * GET /api/v1/reservations/availability/{resourceId}
     */
    #[Route('/availability/{resourceId}', name: 'check_availability', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function checkAvailability(
        int $resourceId,
        Request $request
    ): JsonResponse {
        $resource = $this->resourceRepository->find($resourceId);

        if (!$resource) {
            return $this->json([
                'error' => 'Recurso no encontrado',
                'code' => 'RESOURCE_NOT_FOUND'
            ], Response::HTTP_NOT_FOUND);
        }

        $startTime = $request->query->get('start_time');
        $endTime = $request->query->get('end_time');

        if (!$startTime || !$endTime) {
            return $this->json([
                'error' => 'Se requieren los parámetros start_time y end_time',
                'code' => 'MISSING_PARAMETERS'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startTimeString = $request->query->get('start_time');
            $endTimeString = $request->query->get('end_time');

            if (!$startTimeString || !$endTimeString) {
                return $this->json([
                    'error' => 'Se requieren start_time y end_time',
                    'code' => 'MISSING_PARAMETERS'
                ], Response::HTTP_BAD_REQUEST);
            }

            $startTime = $this->parseDateTime($startTimeString);
            $endTime = $this->parseDateTime($endTimeString);

            if (!$startTime || !$endTime) {
                return $this->json([
                    'error' => 'Formato de fecha inválido',
                    'code' => 'INVALID_DATE_FORMAT',
                    'expected_formats' => [
                        '2025-11-10T14:00:00+00:00',
                        '2025-11-10T14:00:00',
                        '2025-11-10 14:00:00'
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }

            $isAvailable = $this->reservationManager->checkAvailability(
                $resource,
                $startTime,
                $endTime
            );

            return $this->json([
                'available' => $isAvailable,
                'resource_id' => $resourceId,
                'start_time' => $startTime->format(\DateTimeInterface::ATOM),
                'end_time' => $endTime->format(\DateTimeInterface::ATOM)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Formato de fecha inválido',
                'code' => 'INVALID_DATE_FORMAT',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Crear reserva recurrente
     * POST /api/v1/reservations/recurring
     */
    #[Route('/recurring', name: 'create_recurring', methods: ['POST'])]
    public function createRecurring(
        Request $request,
        RecurringReservationService $recurringService,
        ResourceRepository $resourceRepository
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $required = ['resourceId', 'startTime', 'endTime', 'frequency', 'recurringStartDate'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    return $this->json([
                        'error' => "Campo requerido: {$field}",
                        'code' => 'MISSING_FIELD'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $resource = $resourceRepository->find($data['resourceId']);
            if (!$resource) {
                return $this->json([
                    'error' => 'Recurso no encontrado',
                    'code' => 'RESOURCE_NOT_FOUND'
                ], Response::HTTP_NOT_FOUND);
            }

            $dto = new CreateRecurringReservationDTO();
            $dto->resourceId = $data['resourceId'];
            $dto->startTime = $data['startTime'];
            $dto->endTime = $data['endTime'];
            $dto->notes = $data['notes'] ?? null;
            $dto->metadata = $data['metadata'] ?? null;
            $dto->frequency = $data['frequency'];
            $dto->interval = $data['interval'] ?? 1;
            $dto->recurringStartDate = $data['recurringStartDate'];
            $dto->recurringEndDate = $data['recurringEndDate'] ?? null;
            $dto->daysOfWeek = $data['daysOfWeek'] ?? [1, 2, 3, 4, 5];
            $dto->maxInstances = $data['maxInstances'] ?? 52;

            $pattern = $recurringService->createRecurringReservation(
                $dto,
                $resource,
                $this->getUser()
            );

            $reservationCount = count($pattern->getReservations());

            return $this->json([
                'message' => 'Reservas recurrentes creadas exitosamente',
                'data' => [
                    'pattern_id' => $pattern->getId(),
                    'frequency' => $pattern->getFrequency(),
                    'recurrence_interval' => $pattern->getInterval(),
                    'total_reservations' => $reservationCount,
                    'start_date' => $pattern->getStartDate()->format('Y-m-d'),
                    'end_date' => $pattern->getEndDate()?->format('Y-m-d'),
                    'days_of_week' => $pattern->getDaysOfWeek(),
                    'first_reservation' => $reservationCount > 0 ? $pattern->getReservations()->first()->getStartTime()->format('Y-m-d H:i:s') : null,
                    'last_reservation' => $reservationCount > 0 ? $pattern->getReservations()->last()->getStartTime()->format('Y-m-d H:i:s') : null,
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error al crear reservas recurrentes',
                'code' => 'RECURRING_ERROR',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cancelar todas las instancias de una reserva recurrente
     * DELETE /api/v1/reservations/recurring/{patternId}
     */
    #[Route('/recurring/{patternId}', name: 'cancel_recurring', methods: ['DELETE'])]
    public function cancelRecurring(
        int $patternId,
        RecurringPatternRepository $patternRepository,
        RecurringReservationService $recurringService
    ): JsonResponse {
        try {
            $pattern = $patternRepository->find($patternId);

            if (!$pattern) {
                return $this->json([
                    'error' => 'Patrón de recurrencia no encontrado',
                    'code' => 'PATTERN_NOT_FOUND'
                ], Response::HTTP_NOT_FOUND);
            }

            $count = $recurringService->cancelRecurringReservations($pattern);

            return $this->json([
                'message' => 'Reservas recurrentes canceladas',
                'data' => [
                    'pattern_id' => $patternId,
                    'cancelled_count' => $count
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error al cancelar',
                'code' => 'CANCEL_ERROR',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function parseDateTime(string $dateString): ?\DateTimeImmutable
    {
        $dateString = str_replace(' ', '+', $dateString);
        $formats = [
            \DateTimeInterface::ATOM,
            'Y-m-d\TH:i:sP',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s.uP',
        ];

        try {
            return new \DateTimeImmutable($dateString);
        } catch (\Exception $e) {
            // Si falla,se intenta con formatos específicos
        }

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date;
            }
        }

        return null;
    }
}