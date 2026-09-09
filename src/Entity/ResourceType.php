<?php

namespace App\Entity;

use App\Repository\ResourceTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResourceTypeRepository::class)]
#[ORM\Table(name: 'resource_types')]
class ResourceType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $defaultDuration = 60;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $validationStrategy = null;

    #[ORM\Column(type: 'boolean')]
    private bool $requiresApproval = false;

    #[ORM\OneToMany(mappedBy: 'resourceType', targetEntity: Resource::class)]
    private Collection $resources;

    public function __construct()
    {
        $this->resources = new ArrayCollection();
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

    public function getValidationStrategy(): ?string
    {
        return $this->validationStrategy;
    }

    public function setValidationStrategy(?string $validationStrategy): self
    {
        $this->validationStrategy = $validationStrategy;
        return $this;
    }

    public function getDefaultDuration(): int
    {
        return $this->defaultDuration;
    }

    public function setDefaultDuration(int $defaultDuration): self
    {
        $this->defaultDuration = $defaultDuration;
        return $this;
    }

    public function requiresApproval(): bool
    {
        return $this->requiresApproval;
    }

    public function setRequiresApproval(bool $requiresApproval): self
    {
        $this->requiresApproval = $requiresApproval;
        return $this;
    }

    public function getResources(): Collection
    {
        return $this->resources;
    }

    public function isRequiresApproval(): ?bool
    {
        return $this->requiresApproval;
    }

    public function addResource(Resource $resource): static
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->setResourceType($this);
        }

        return $this;
    }

    public function removeResource(Resource $resource): static
    {
        if ($this->resources->removeElement($resource)) {
            // set the owning side to null (unless already changed)
            if ($resource->getResourceType() === $this) {
                $resource->setResourceType(null);
            }
        }

        return $this;
    }
}