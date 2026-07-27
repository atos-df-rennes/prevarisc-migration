<?php

namespace App\Entity;

use App\Repository\ResourcesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResourcesRepository::class)]
class Resources
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_resource', type: 'bigint', options: ['unsigned' => true])]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $text = null;

    /**
     * @var Collection<int, Privileges>
     */
    #[ORM\OneToMany(targetEntity: Privileges::class, mappedBy: 'resource', orphanRemoval: true)]
    private ArrayCollection|array $privileges;

    public function __construct()
    {
        $this->privileges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return Collection<int, Privileges>
     */
    public function getPrivileges(): Collection
    {
        return $this->privileges;
    }

    public function addPrivilege(Privileges $privilege): self
    {
        if (!$this->privileges->contains($privilege)) {
            $this->privileges[] = $privilege;
            $privilege->setResource($this);
        }

        return $this;
    }

    public function removePrivilege(Privileges $privilege): self
    {
        // set the owning side to null (unless already changed)
        if ($this->privileges->removeElement($privilege) && $privilege->getResource() === $this) {
            $privilege->setResource(null);
        }

        return $this;
    }
}
