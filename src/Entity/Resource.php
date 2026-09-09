<?php

namespace App\Entity;

use App\Repository\ResourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\Table(name: 'resources')]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: ResourceType::class, inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ResourceType $resourceType = null;

    #[ORM\Column(type: 'integer')]
    private int $capacity = 1;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Location $location = null;

    #[ORM\OneToMany(mappedBy: 'resource', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: 'resource', targetEntity: AvailabilitySlot::class)]
    private Collection $availabilitySlots;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $validationStrategy = null;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->availabilitySlots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getResourceType(): ?ResourceType
    {
        return $this->resourceType;
    }

    public function setResourceType(?ResourceType $resourceType): self
    {
        $this->resourceType = $resourceType;
        return $this;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function getAvailabilitySlots(): Collection
    {
        return $this->availabilitySlots;
    }

    public function getValidationStrategy(): ?string
    {
        return $this->validationStrategy;
    }

    public function setValidationStrategy(?string $validationStrategy): self
    {
        $this->validationStrategy = $validationStrategy;
        return $this;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setResource($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getResource() === $this) {
                $reservation->setResource(null);
            }
        }

        return $this;
    }

    public function addAvailabilitySlot(AvailabilitySlot $availabilitySlot): static
    {
        if (!$this->availabilitySlots->contains($availabilitySlot)) {
            $this->availabilitySlots->add($availabilitySlot);
            $availabilitySlot->setResource($this);
        }

        return $this;
    }

    public function removeAvailabilitySlot(AvailabilitySlot $availabilitySlot): static
    {
        if ($this->availabilitySlots->removeElement($availabilitySlot)) {
            // set the owning side to null (unless already changed)
            if ($availabilitySlot->getResource() === $this) {
                $availabilitySlot->setResource(null);
            }
        }

        return $this;
    }
}