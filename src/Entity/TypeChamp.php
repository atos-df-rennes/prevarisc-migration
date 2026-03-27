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
    public const TEXT = 1;

    public const TEXTAREA = 2;

    public const SELECT = 3;

    public const NUMBER = 4;

    public const CHECKBOX = 5;

    public const PARENT = 6;

    public const DATE = 7;

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
     * @ORM\Column(name="TYPE", type="string", length=50, nullable=false)
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
