<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\DossierDocumentManquantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierDocumentManquantRepository::class)]
#[ORM\Table(name: 'dossierdocmanquant')]
class DossierDocumentManquant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_DOCMANQUANT', type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'NUM_DOCSMANQUANT', type: Types::STRING, length: 45, nullable: false)]
    private ?string $numero = null;

    #[ORM\Column(name: 'DOCMANQUANT', type: Types::TEXT, nullable: true)]
    private ?string $documentManquant = null;

    #[ORM\Column(name: 'DATE_DOCSMANQUANT', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'DATE_RECEPTION_DOC', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateReception = null;

    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'documentsManquants')]
    #[ORM\JoinColumn(name: 'ID_DOSSIER', referencedColumnName: 'ID_DOSSIER', nullable: false)]
    private ?Dossier $dossier = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getDocumentManquant(): ?string
    {
        return $this->documentManquant;
    }

    public function setDocumentManquant(?string $documentManquant): self
    {
        $this->documentManquant = $documentManquant;

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

    public function getDateReception(): ?\DateTimeInterface
    {
        return $this->dateReception;
    }

    public function setDateReception(?\DateTimeInterface $dateReception): self
    {
        $this->dateReception = $dateReception;

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
