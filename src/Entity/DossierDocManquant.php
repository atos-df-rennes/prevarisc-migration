<?php

namespace App\Entity;

use App\Repository\DossierDocManquantRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DossierDocManquantRepository::class)
 * 
 * @ORM\Table(name="dossierdocmanquant")
 */
class DossierDocManquant
{
    /**
     * @var int
     * 
     * @ORM\Id
     * 
     * @ORM\GeneratedValue
     * 
     * @ORM\Column(name="ID_DOCMANQUANT", type="integer")
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="NUM_DOCSMANQUANT", type="string", length=45)
     */
    private $numDocsManquant;

    /**
     * @var ?string
     *
     * @ORM\Column(name="DOCMANQUANT", type="text", nullable=true)
     */
    private $docManquant;

    /**
     * @var ?\DateTimeInterface
     * 
     * @ORM\Column(name="DATE_DOCSMANQUANT", type="date", nullable=true)
     */
    private $dateDocsManquant;

    /**
     * @var ?\DateTimeInterface
     * 
     * @ORM\Column(name="DATE_RECEPTION_DOC", type="date", nullable=true)
     */
    private $dateReceptionDoc;

    /**
     * @var Dossier
     * 
     * @ORM\ManyToOne(targetEntity=Dossier::class, inversedBy="dossierDocManquants")
     * 
     * @ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER", nullable=false)
     */
    private $idDossier;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumDocsManquant(): ?string
    {
        return $this->numDocsManquant;
    }

    public function setNumDocsManquant(string $numDocsManquant): self
    {
        $this->numDocsManquant = $numDocsManquant;

        return $this;
    }

    public function getDocManquant(): ?string
    {
        return $this->docManquant;
    }

    public function setDocManquant(?string $docManquant): self
    {
        $this->docManquant = $docManquant;

        return $this;
    }

    public function getDateDocsManquant(): ?\DateTimeInterface
    {
        return $this->dateDocsManquant;
    }

    public function setDateDocsManquant(?\DateTimeInterface $dateDocsManquant): self
    {
        $this->dateDocsManquant = $dateDocsManquant;

        return $this;
    }

    public function getDateReceptionDoc(): ?\DateTimeInterface
    {
        return $this->dateReceptionDoc;
    }

    public function setDateReceptionDoc(?\DateTimeInterface $dateReceptionDoc): self
    {
        $this->dateReceptionDoc = $dateReceptionDoc;

        return $this;
    }

    public function getIdDossier(): ?Dossier
    {
        return $this->idDossier;
    }

    public function setIdDossier(?Dossier $idDossier): self
    {
        $this->idDossier = $idDossier;

        return $this;
    }
}
