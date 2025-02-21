<?php

namespace App\Entity;

use App\Repository\PrivilegesRepository;
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
}
