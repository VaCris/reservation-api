<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

#[Route('/api/v1/mercure', name: 'api_v1_mercure_')]
class MercureController extends AbstractController
{
    /**
     * Obtener token de suscripción a Mercure
     * GET /api/v1/mercure/token
     */
    #[Route('/token', name: 'token', methods: ['GET'])]
    public function getToken(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'Usuario no autenticado'], 401);
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($_ENV['MERCURE_JWT_SECRET'])
        );

        $token = $config->builder()
            ->issuedAt(new \DateTimeImmutable())
            ->expiresAt(new \DateTimeImmutable('+1 hour'))
            ->withClaim('mercure', [
                'subscribe' => [
                    'https://reservationapi.com/reservations',
                    'https://reservationapi.com/user/' . $user->getId(),
                    'https://reservationapi.com/resources',
                ]
            ])
            ->getToken($config->signer(), $config->signingKey());

        return $this->json([
            'token' => $token->toString(),
            'mercure_url' => $_ENV['MERCURE_PUBLIC_URL'],
        ]);
    }
}