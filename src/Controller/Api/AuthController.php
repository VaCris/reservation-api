<?php

namespace App\Controller\Api;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1', name: 'api_v1_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    private function getRoleFriendlyName(string $roleName): string
    {
        return match ($roleName) {
            'ROLE_ADMIN' => 'Administrator',
            'ROLE_MANAGER' => 'Manager',
            'ROLE_USER' => 'User',
            default => ucfirst(strtolower(str_replace('ROLE_', '', $roleName)))
        };
    }

    /**
     * POST /api/v1/login
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json([
                'error' => 'Credenciales inválidas',
                'code' => 'INVALID_CREDENTIALS'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'message' => 'Login exitoso',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getFirstName() . ' ' . $user->getLastName(),
                'roles' => array_map(fn($r) => $r->getName(), $user->getRoleEntities()->toArray())
            ]
        ]);
    }

    /*
     * GET /api/v1/me
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'No autenticado',
                'code' => 'NOT_AUTHENTICATED'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getFirstName() . ' ' . $user->getLastName(),
            'roles' => array_map(fn($r) => $r->getName(), $user->getRoleEntities()->toArray())
        ]);
    }

    /**
     * POST /api/v1/logout
     */
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->json([
            'message' => 'Logout exitoso'
        ]);
    }
}
