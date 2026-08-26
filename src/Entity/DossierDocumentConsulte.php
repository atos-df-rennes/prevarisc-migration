<?php

namespace App\Entity;

use App\Repository\DossierDocumentConsulteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierDocumentConsulteRepository::class)]
#[ORM\Table(name: 'dossierdocconsulte')]
class DossierDocumentConsulte
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_DOSSIERDOCCONSULTE', type: 'bigint')]
    private $id;

    #[ORM\Column(name: 'REF_CONSULTE', type: 'string', length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(name: 'DATE_CONSULTE', type: 'date', nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'DOC_CONSULTE', type: 'boolean', nullable: false)]
    private ?bool $consulte = null;

    #[ORM\ManyToOne(targetEntity: DossierNatureListe::class)]
    #[ORM\JoinColumn(name: 'ID_NATURE', referencedColumnName: 'ID_DOSSIERNATURE', nullable: false)]
    private ?DossierNatureListe $nature = null;

    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'documentsConsultes')]
    #[ORM\JoinColumn(name: 'ID_DOSSIER', referencedColumnName: 'ID_DOSSIER', nullable: false)]
    private ?Dossier $dossier = null;

    #[ORM\Column(name: 'ID_DOC', type: 'bigint', nullable: false)]
    private string $document;

    public function getId(): ?int
    {
        return $this->id;
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

    public function isConsulte(): ?bool
    {
        return $this->consulte;
    }

    public function setConsulte(bool $consulte): self
    {
        $this->consulte = $consulte;

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

    public function getDocument(): string
    {
        return $this->document;
    }

    public function setDocument(string $document): self
    {
        $this->document = $document;

        return $this;
    }
}
