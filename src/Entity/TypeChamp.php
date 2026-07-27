<?php

namespace App\Entity;

use App\Repository\TypeChampRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeChampRepository::class)]
#[ORM\Table(name: 'listetypechamprubrique')]
class TypeChamp
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_TYPECHAMP', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'TYPE', type: 'string', length: 50, nullable: false)]
    private ?string $type = null;

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
