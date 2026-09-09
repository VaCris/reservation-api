<?php

namespace App\Controller\Api;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1', name: 'api_v1_')]
class NotificationController extends AbstractController
{
    /**
     * GET /api/v1/notifications
     */
    #[Route('/notifications', name: 'get_notifications', methods: ['GET'])]
    public function getNotifications(NotificationRepository $notificationRepository): JsonResponse
    {
        $user = $this->getUser();
        $notifications = $notificationRepository->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return $this->json([
            'total' => count($notifications),
            'data' => array_map(fn($n) => [
                'id' => $n->getId(),
                'type' => $n->getType(),
                'subject' => $n->getSubject(),
                'status' => $n->getStatus(),
                'email' => $n->getRecipientEmail(),
                'created_at' => $n->getCreatedAt()->format('Y-m-d H:i:s'),
                'sent_at' => $n->getSentAt()?->format('Y-m-d H:i:s'),
            ], $notifications)
        ]);
    }
}