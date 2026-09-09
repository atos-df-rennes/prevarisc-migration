<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Entity\Trait\GetIdOrFailTrait;
use App\Repository\TypeTexteApplicableRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeTexteApplicableRepository::class)]
#[ORM\Table(name: 'typetextesappl')]
class TypeTexteApplicable
{
    use GetIdOrFailTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_TYPETEXTEAPPL', type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'LIBELLE_TYPETEXTEAPPL', type: Types::TEXT, nullable: false)]
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
