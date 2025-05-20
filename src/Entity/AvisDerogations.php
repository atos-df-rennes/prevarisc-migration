<?php

namespace App\Entity;

use App\Repository\AvisDerogationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AvisDerogationsRepository::class)
 * 
 * @ORM\Table(name="avisderogations")
 */
class AvisDerogations
{
    /**
     * @var int
     *
     * @ORM\Id
     * 
     * @ORM\GeneratedValue
     * 
     * @ORM\Column(name="ID_AVIS_DEROGATION", type="integer")
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="TYPE", type="string", length=50)
     */
    private $type;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="TITRE", type="string", length=100, nullable=true)
     */
    private $titre;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="OBJET", type="text", nullable=true)
     */
    private $objet;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="JUSTIFICATION", type="text", nullable=true)
     */
    private $justification;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="ARTICLES_CONCERNES", type="text", nullable=true)
     */
    private $articlesConcernes;

    /**
     * @var string
     * 
     * @ORM\Column(name="MESURES_COMPENSATOIRES_PROPOSEES", type="text")
     */
    private $mesuresCompensatoiresProposees;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="ANALYSE_DE_RISQUE", type="text", nullable=true)
     */
    private $analyseDeRisque;

    /**
     * @var ?int
     * 
     * @ORM\Column(name="AVIS", type="integer", nullable=true)
     */
    private $avis;

    /**
     * @var ?int
     * 
     * @ORM\Column(name="ID_DOSSIER_LIE", type="bigint", nullable=true)
     */
    private $idDossierLie;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="DISPLAY_HISTORIQUE", type="boolean", nullable=true)
     */
    private $displayHistorique;

    /**
     * @var Dossier
     * 
     * @ORM\ManyToOne(targetEntity=Dossier::class, inversedBy="avisDerogations")
     * 
     * @ORM\JoinColumn(name="ID_DOSSIER", nullable=false)
     */
    private $idDossier;

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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(?string $objet): self
    {
        $this->objet = $objet;

        return $this;
    }

    public function getJustification(): ?string
    {
        return $this->justification;
    }

    public function setJustification(?string $justification): self
    {
        $this->justification = $justification;

        return $this;
    }

    public function getArticlesConcernes(): ?string
    {
        return $this->articlesConcernes;
    }

    public function setArticlesConcernes(?string $articlesConcernes): self
    {
        $this->articlesConcernes = $articlesConcernes;

        return $this;
    }

    public function getMesuresCompensatoiresProposees(): ?string
    {
        return $this->mesuresCompensatoiresProposees;
    }

    public function setMesuresCompensatoiresProposees(string $mesuresCompensatoiresProposees): self
    {
        $this->mesuresCompensatoiresProposees = $mesuresCompensatoiresProposees;

        return $this;
    }

    public function getAnalyseDeRisque(): ?string
    {
        return $this->analyseDeRisque;
    }

    public function setAnalyseDeRisque(?string $analyseDeRisque): self
    {
        $this->analyseDeRisque = $analyseDeRisque;

        return $this;
    }

    public function getAvis(): ?int
    {
        return $this->avis;
    }

    public function setAvis(?int $avis): self
    {
        $this->avis = $avis;

        return $this;
    }

    public function getIdDossierLie(): ?string
    {
        return $this->idDossierLie;
    }

    public function setIdDossierLie(?string $idDossierLie): self
    {
        $this->idDossierLie = $idDossierLie;

        return $this;
    }

    public function getDisplayHistorique(): ?bool
    {
        return $this->displayHistorique;
    }

    public function setDisplayHistorique(?bool $displayHistorique): self
    {
        $this->displayHistorique = $displayHistorique;

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
