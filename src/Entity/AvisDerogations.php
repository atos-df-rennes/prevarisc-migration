<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AvisDerogationsRepository")
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
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @ORM\Column(type="bigint", name="ID_AVIS_DEROGATION")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, name="TYPE")
     */
    private $type;

    /**
     * @var ?string
     *
     * @ORM\Column(type="string", length=100, name="TITRE", nullable=true)
     */
    private $titre;

    /**
     * @var ?string
     *
     * @ORM\Column(type="text", name="OBJET", nullable=true)
     */
    private $objet;

    /**
     * @var ?string
     *
     * @ORM\Column(type="text", name="JUSTIFICATION", nullable=true)
     */
    private $justification;

    /**
     * @var ?string
     *
     * @ORM\Column(type="text", name="ARTICLES_CONCERNES", nullable=true)
     */
    private $articlesConcernes;

    /**
     * @var ?string
     *
     * @ORM\Column(type="text", name="MESURES_COMPENSATOIRES_PROPOSEES", nullable=true)
     */
    private $mesuresCompensatoiresProposees;

    /**
     * @var ?string
     *
     * @ORM\Column(type="text", name="ANALYSE_DE_RISQUE", nullable=true)
     */
    private $analyseDeRisque;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", name="DISPLAY_HISTORIQUE", options={"default": 0})
     */
    private $displayHistorique;

    /**
     * @var ?Avis
     *
     * @ORM\ManyToOne(targetEntity=Avis::class, cascade={"persist", "remove"})
     *
     * @ORM\JoinColumn(name="AVIS", referencedColumnName="ID_AVIS", nullable=true)
     */
    private $avis;

    /**
     * @var ?Dossier
     *
     * @ORM\ManyToOne(targetEntity=Dossier::class, cascade={"persist", "remove"})
     *
     * @ORM\JoinColumn(name="ID_DOSSIER_LIE", referencedColumnName="ID_DOSSIER", nullable=true)
     */
    private $dossierLie;

    /**
     * @var ?Dossier
     *
     * @ORM\ManyToOne(targetEntity=Dossier::class, inversedBy="avisDerogations")
     *
     * @ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER", nullable=false)
     */
    private $dossier;

    public function __construct()
    {
        $this->displayHistorique = false;
    }

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

    public function setMesuresCompensatoiresProposees(?string $mesuresCompensatoiresProposees): self
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

    public function getDisplayHistorique(): ?bool
    {
        return $this->displayHistorique;
    }

    public function setDisplayHistorique(bool $displayHistorique): self
    {
        $this->displayHistorique = $displayHistorique;

        return $this;
    }

    public function getAvis(): ?Avis
    {
        return $this->avis;
    }

    public function setAvis(?Avis $avis): self
    {
        $this->avis = $avis;

        return $this;
    }

    public function getDossierLie(): ?Dossier
    {
        return $this->dossierLie;
    }

    public function setDossierLie(?Dossier $dossierLie): self
    {
        $this->dossierLie = $dossierLie;

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
