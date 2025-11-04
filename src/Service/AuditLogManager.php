<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio para registrar eventos de auditoría
 */
class AuditLogManager
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * Registra una acción en el log de auditoría
     */
    public function log(
        ?User $user,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        $auditLog = new AuditLog();
        $auditLog->setCreatedBy($user)
            ->setAction($action)
            ->setEntityType($entityType)
            ->setEntityId($entityId)
            ->setOldValues($oldValues)
            ->setNewValues($newValues);

        // Capturar información del request si está disponible
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $auditLog->setIpAddress($request->getClientIp())
                ->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();

        return $auditLog;
    }
}