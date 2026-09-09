<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\AdresseRueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdresseRueRepository::class)]
#[ORM\Table(name: 'adresserue')]
class AdresseRue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_RUE', type: Types::BIGINT, options: ['unsigned' => true])]
    private ?string $id = null;

    #[ORM\Column(name: 'LIBELLE_RUE', type: Types::STRING, length: 255, nullable: false)]
    private ?string $libelle = null;

    #[ORM\ManyToOne(targetEntity: AdresseRueType::class)]
    #[ORM\JoinColumn(name: 'ID_RUETYPE', referencedColumnName: 'ID_RUETYPE', nullable: false, options: ['unsigned' => true])]
    private ?AdresseRueType $type = null;

    #[ORM\ManyToOne(targetEntity: Commune::class)]
    #[ORM\JoinColumn(name: 'NUMINSEE_COMMUNE', referencedColumnName: 'NUMINSEE_COMMUNE', nullable: false)]
    private ?Commune $commune = null;

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

    public function getType(): ?AdresseRueType
    {
        return $this->type;
    }

    public function setType(AdresseRueType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCommune(): ?Commune
    {
        return $this->commune;
    }

    public function setCommune(Commune $commune): self
    {
        $this->commune = $commune;

        return $this;
    }
}
