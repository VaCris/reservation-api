<?php

namespace App\Entity;

use App\Repository\AvailabilitySlotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilitySlotRepository::class)]
#[ORM\Table(name: 'availability_slots')]
class AvailabilitySlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Resource::class, inversedBy: 'availabilitySlots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Resource $resource = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isAvailable = true;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $recurrencePattern = null;

    #[ORM\Column(type: 'integer')]
    private int $maxCapacity = 1;

    #[ORM\Column(type: 'integer')]
    private int $currentReservations = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    public function setResource(?Resource $resource): self
    {
        $this->resource = $resource;
        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable && $this->currentReservations < $this->maxCapacity;
    }

    public function setIsAvailable(bool $isAvailable): self
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }

    public function getRecurrencePattern(): ?string
    {
        return $this->recurrencePattern;
    }

    public function setRecurrencePattern(?string $recurrencePattern): self
    {
        $this->recurrencePattern = $recurrencePattern;
        return $this;
    }

    public function getMaxCapacity(): int
    {
        return $this->maxCapacity;
    }

    public function setMaxCapacity(int $maxCapacity): self
    {
        $this->maxCapacity = $maxCapacity;
        return $this;
    }

    public function getCurrentReservations(): int
    {
        return $this->currentReservations;
    }

    public function setCurrentReservations(int $currentReservations): self
    {
        $this->currentReservations = $currentReservations;
        return $this;
    }

    public function incrementReservations(): self
    {
        $this->currentReservations++;
        return $this;
    }

    public function decrementReservations(): self
    {
        if ($this->currentReservations > 0) {
            $this->currentReservations--;
        }
        return $this;
    }
}