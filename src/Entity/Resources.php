<?php

namespace App\Entity;

use App\Repository\ResourcesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ResourcesRepository::class)
 */
class Resources
{
    /**
     * @var int
     * 
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id_resource", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     * 
     * @ORM\Column(type="text", nullable=true)
     */
    private $text;

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
}
