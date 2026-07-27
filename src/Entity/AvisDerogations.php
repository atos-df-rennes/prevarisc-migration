<?php

namespace App\Entity;

use App\Repository\AvisDerogationsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvisDerogationsRepository::class)]
#[ORM\Table(name: 'avisderogations')]
class AvisDerogations
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'ID_AVIS_DEROGATION', type: 'bigint')]
    private $id;

    #[ORM\Column(name: 'TYPE', type: 'string', length: 50, nullable: false)]
    private ?string $type = null;

    #[ORM\Column(name: 'TITRE', type: 'string', length: 100, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(name: 'OBJET', type: 'text', nullable: true)]
    private ?string $objet = null;

    #[ORM\Column(name: 'JUSTIFICATION', type: 'text', nullable: true)]
    private ?string $justification = null;

    #[ORM\Column(name: 'ARTICLES_CONCERNES', type: 'text', nullable: true)]
    private ?string $articlesConcernes = null;

    #[ORM\Column(name: 'MESURES_COMPENSATOIRES_PROPOSEES', type: 'text', nullable: true)]
    private ?string $mesuresCompensatoiresProposees = null;

    #[ORM\Column(name: 'ANALYSE_DE_RISQUE', type: 'text', nullable: true)]
    private ?string $analyseDeRisque = null;

    #[ORM\Column(name: 'DISPLAY_HISTORIQUE', type: 'boolean', options: ['default' => 0])]
    private bool $displayHistorique;

    #[ORM\ManyToOne(targetEntity: Avis::class)]
    #[ORM\JoinColumn(name: 'AVIS', referencedColumnName: 'ID_AVIS', nullable: true, options: ['unsigned' => true])]
    private ?Avis $avis = null;

    #[ORM\ManyToOne(targetEntity: Dossier::class)]
    #[ORM\JoinColumn(name: 'ID_DOSSIER_LIE', referencedColumnName: 'ID_DOSSIER', nullable: true)]
    private ?Dossier $dossierLie = null;

    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'avisDerogations')]
    #[ORM\JoinColumn(name: 'ID_DOSSIER', referencedColumnName: 'ID_DOSSIER', nullable: false)]
    private ?Dossier $dossier = null;

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
