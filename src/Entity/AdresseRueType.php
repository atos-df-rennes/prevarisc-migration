<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\AdresseRueTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdresseRueTypeRepository::class)]
#[ORM\Table(name: 'adresseruetype')]
class AdresseRueType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_RUETYPE', type: Types::BIGINT, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'LIBELLE_RUETYPE', type: Types::STRING, length: 32, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'ABREVIATION_RUETYPE', type: Types::STRING, length: 16, nullable: false)]
    private ?string $abbreviation = null;

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

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation): self
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }
}
