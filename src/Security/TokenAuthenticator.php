<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class TokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/api/v1')
            && $request->getPathInfo() !== '/api/v1/login';
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get('Authorization');

        if (!$token) {
            throw new AuthenticationException('Token no proporcionado');
        }

        $token = str_replace('Bearer ', '', $token);

        $email = base64_decode($token);

        return new SelfValidatingPassport(
            new UserBadge($email, function($userIdentifier) {
                return $this->userRepository->findOneBy(['email' => $userIdentifier]);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Autenticación fallida',
            'message' => $exception->getMessage(),
            'code' => 'AUTHENTICATION_FAILED'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'error' => 'Autenticación requerida',
            'message' => 'Por favor, inicia sesión para acceder a este recurso',
            'code' => 'AUTHENTICATION_REQUIRED'
        ], Response::HTTP_UNAUTHORIZED);
    }
}