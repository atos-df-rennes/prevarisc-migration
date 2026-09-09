<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\TypePlanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypePlanRepository::class)]
#[ORM\Table(name: 'typeplan')]
class TypePlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_TYPEPLAN', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'LIBELLE_TYPEPLAN', type: Types::STRING, length: 100, nullable: false)]
    private ?string $libelle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }
}
