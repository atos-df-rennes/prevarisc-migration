<?php

namespace App\Entity;

use App\Entity\Trait\GetIdOrFailTrait;
use App\Repository\TypeChampRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeChampRepository::class)]
#[ORM\Table(name: 'listetypechamprubrique')]
class TypeChamp
{
    use GetIdOrFailTrait;

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_TYPECHAMP', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'TYPE', type: 'string', length: 50, nullable: false)]
    private string $type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
