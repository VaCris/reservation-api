<?php

namespace App\Repository;

use App\Entity\RecurringPattern;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecurringPattern>
 */
class RecurringPatternRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringPattern::class);
    }

    /**
     * Buscar patrones activos (que aún no han expirado)
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.endDate IS NULL OR rp.endDate >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('rp.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtener próximas instancias a generar
     */
    public function findExpiringPatterns(): array
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.endDate IS NOT NULL')
            ->andWhere('rp.endDate <= :futureDate')
            ->setParameter('futureDate', new \DateTimeImmutable('+7 days'))
            ->getQuery()
            ->getResult();
    }
}
