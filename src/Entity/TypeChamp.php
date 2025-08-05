<?php

namespace App\Entity;

use App\Repository\TypeChampRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="listetypechamprubrique")
 *
 * @ORM\Entity(repositoryClass=TypeChampRepository::class)
 */
class TypeChamp
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_TYPECHAMP", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     */
    private $type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
