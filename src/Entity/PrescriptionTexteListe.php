<?php

namespace App\Entity;

use App\Repository\PrescriptionTexteListeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionTexteListeRepository::class)]
#[ORM\Table(name: 'prescriptiontexteliste')]
class PrescriptionTexteListe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_TEXTE', type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'LIBELLE_TEXTE', type: Types::STRING, length: 255, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'VISIBLE_TEXTE', type: Types::BOOLEAN, nullable: true)]
    private ?bool $visible = null;

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

    public function getVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(?bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }
}
