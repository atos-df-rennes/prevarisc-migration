<?php

namespace App\Entity;

use App\Repository\ListeDocumentAjouteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListeDocumentAjouteRepository::class)]
#[ORM\Table(name: 'listedocajout')]
class ListeDocumentAjoute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_DOCAJOUT', type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'LIBELLE_DOCAJOUT', type: Types::TEXT, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'REF_DOCAJOUT', type: Types::STRING, length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(name: 'DATE_DOCAJOUT', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(targetEntity: DossierNatureListe::class)]
    #[ORM\JoinColumn(name: 'ID_NATURE', referencedColumnName: 'ID_DOSSIERNATURE', nullable: false)]
    private ?DossierNatureListe $nature = null;

    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'documentsAjoutes')]
    #[ORM\JoinColumn(name: 'ID_DOSSIER', referencedColumnName: 'ID_DOSSIER', nullable: false)]
    private ?Dossier $dossier = null;

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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getNature(): ?DossierNatureListe
    {
        return $this->nature;
    }

    public function setNature(DossierNatureListe $nature): self
    {
        $this->nature = $nature;

        return $this;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }
}
