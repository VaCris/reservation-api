<?php

namespace App\Strategy;

use App\Entity\Reservation;
use App\Entity\Resource;
use App\Entity\User;

/**
 * Interfaz para el Patrón Strategy de Validación
 * Cumple con el Principio Abierto/Cerrado (Open/Closed Principle)
 */
interface ValidationStrategyInterface
{
    /**
     * Valida si una reserva cumple con las reglas de negocio
     *
     * @throws \RuntimeException si la validación falla
     */
    public function validate(
        User $user,
        Resource $resource,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ): bool;

    /**
     * Retorna el nombre de la estrategia
     */
    public function getName(): string;

    /**
     * Retorna una descripción de las reglas que implementa
     */
    public function getDescription(): string;
}