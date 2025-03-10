<?php

namespace App\Entity;

use App\Repository\PrivilegesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PrivilegesRepository::class)
 */
class Privileges
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="id_privilege", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var ?string
     *
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $name;

    /**
     * @var ?string
     *
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $text;

    /**
     * @var ?Resources
     *
     * @ORM\ManyToOne(targetEntity=Resources::class, inversedBy="privileges")
     *
     * @ORM\JoinColumn(name="id_resource", referencedColumnName="id_resource", options={"unsigned":true})
     */
    private $resource;

    /**
     * @var Collection<int, Groupe>
     *
     * @ORM\ManyToMany(targetEntity=Groupe::class, mappedBy="privileges")
     */
    private $groupes;

    public function __construct()
    {
        $this->groupes = new ArrayCollection();
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

    public function getResource(): ?Resources
    {
        return $this->resource;
    }

    public function setResource(?Resources $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return Collection<int, Groupe>
     */
    public function getGroupes(): Collection
    {
        return $this->groupes;
    }

    public function addGroupe(Groupe $groupe): self
    {
        if (!$this->groupes->contains($groupe)) {
            $this->groupes[] = $groupe;
            $groupe->addPrivilege($this);
        }

        return $this;
    }

    public function removeGroupe(Groupe $groupe): self
    {
        if ($this->groupes->removeElement($groupe)) {
            $groupe->removePrivilege($this);
        }

        return $this;
    }
}
