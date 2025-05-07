<?php

namespace App\Entity;

use App\Repository\DossierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DossierRepository::class)
 *
 * @ORM\Table(indexes={@ORM\Index(columns={"DATESUPPRESSION_DOSSIER"})})
 */
class Dossier
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_DOSSIER", type="bigint")
     */
    private $id;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATESUPPRESSION_DOSSIER", type="date", nullable=true)
     */
    private $dateSuppression;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATEINSERT_DOSSIER", type="datetime")
     */
    private $dateCreation;

    /**
     * @var ?Utilisateur
     *
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     *
     * @ORM\JoinColumn(name="DELETED_BY", referencedColumnName="ID_UTILISATEUR", options={"unsigned":true})
     */
    private $deletedBy;

    /**
     * @var ?string
     *
     * @ORM\Column(name="OBJET_DOSSIER", type="text", nullable=true)
     */
    private $objet;

    /**
     * @var DossierType
     *
     * @ORM\ManyToOne(targetEntity=DossierType::class)
     *
     * @ORM\JoinColumn(name="TYPE_DOSSIER", referencedColumnName="ID_DOSSIERTYPE", nullable=false)
     */
    private $type;

    /**
     * @var Collection<int, DossierNatureListe>
     *
     * @ORM\JoinTable(name="dossiernature",
     *    joinColumns={@ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER")},
     *    inverseJoinColumns={@ORM\JoinColumn(name="ID_NATURE", referencedColumnName="ID_DOSSIERNATURE")}
     * )
     *
     * @ORM\ManyToMany(targetEntity=DossierNatureListe::class)
     */
    private $nature;

    /**
     * @var Collection<int, Etablissement>
     *
     * @ORM\JoinTable(name="etablissementdossier",
     *     joinColumns={@ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=Etablissement::class, inversedBy="dossiers")
     */
    private $etablissements;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="DEMANDEUR_DOSSIER", type="text", nullable=true)
     */
    private $demandeur;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="LIEUREUNION_DOSSIER", type="text", nullable=true)
     */
    private $lieuReunion;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="SERVICEINSTRUC_DOSSIER", type="string", length=255, nullable=true)
     */
    private $serviceInstructeur;
    
    /**
     * @var ?string
     * 
     * @ORM\Column(name="DESCANAL_DOSSIER", type="text", nullable=true)
     */
    private $descriptifAnalyseRisque;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="REGLEDEROG_DOSSIER", type="text", nullable=true)
     */
    private $reglesADeroger;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="JUSTIFDEROG_DOSSIER", type="text", nullable=true)
     */
    private $justificatifDerogation;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="MESURESCOMPENS_DOSSIER", type="text", nullable=true)
     */
    private $mesuresCompensatoires;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="MESURESCOMPLE_DOSSIER", type="text", nullable=true)
     */
    private $mesuresComplementaires;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="INCOMPLET_DOSSIER", type="boolean", nullable=true, options={"default":0})
     */
        private $etatDossier;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="HORSDELAI_DOSSIER", type="boolean", nullable=true, options={"default":0})
     */
        private $horsDelai;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="DIFFEREAVIS_DOSSIER", type="boolean", nullable=true)
     */
        private $differeAvis;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="NPSP_DOSSIER", type="boolean", nullable=true)
     */
        private $nonPrononce;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATEREUN_DOSSIER", type="datetime", nullable=true)
     */
    private $dateReunion;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATEMAIRIE_DOSSIER", type="datetime", nullable=true)
     */
    private $dateMairie;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATESECRETARIAT_DOSSIER", type="datetime", nullable=true)
     */
    private $dateSecretariat;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATEENVTRANSIT_DOSSIER", type="date", nullable=true)
     */
    private $dateEnvTransit;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATESDIS_DOSSIER", type="datetime", nullable=true)
     */
    private $dateSdis;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATEVISITE_DOSSIER", type="date", nullable=true)
     */
    private $dateVisite;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATERVRAT_DOSSIER", type="date", nullable=true)
     */
    private $dateRVRAT;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="DATECOMM_DOSSIER", type="text", nullable=true)
     */
    private $dateCommission;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATEPREF_DOSSIER", type="datetime", nullable=true)
     */
    private $datePrefecture;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATEREP_DOSSIER", type="datetime", nullable=true)
     */
    private $dateReponse;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATETRANSFERTCOMM_DOSSIER", type="datetime", nullable=true)
     */
    private $dateTransfertCommission;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATERECEPTIONCOMM_DOSSIER", type="datetime", nullable=true)
     */
    private $dateReceptionCommission;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DELAIPRESC_DOSSIER", type="date", nullable=true)
     */
    private $delaiPrescription;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATESIGN_DOSSIER", type="date", nullable=true)
     */
    private $dateSignature;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DATEINTERV_DOSSIER", type="datetime", nullable=true)
     */
    private $dateIntervention;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="DUREEINTERV_DOSSIER", type="time", nullable=true)
     */
    private $DureeIntervention;

    /**
     * @var int
     * 
     * @ORM\Column(name="DUREEDEPL_DOSSIER", type="integer", nullable=true)
     */
    private $dureeDeplacement;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="ABSQUORUM_DOSSIER", type="boolean", nullable=true)
     */
    private $absenceQuorum;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="CNE_DOSSIER", type="boolean", nullable=true)
     */
    private $celluleNonExploitée;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="FACTDANGE_DOSSIER", type="boolean", nullable=true)
     */
    private $facteurDanger;

    /**
     * @var \DateTimeInterface
     * 
     * @ORM\Column(name="ECHEANCIERTRAV_DOSSIER", type="date", nullable=true)
     */
    private $echeancierTravaux;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="ANOMALIE_DOSSIER", type="text", nullable=true)
     */
    private $anomalie;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="OPERSDIS_DOSSIER", type="boolean", nullable=true)
     */
    private $operationSDIS;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="RCCI_DOSSIER", type="boolean", nullable=true)
     */
    private $RCCI;

    /**
     * @var ?string 
     * 
     * @ORM\Column(name="REX_DOSSIER", type="text", nullable=true)
     */
    private $REX;

    /** @var ?string
     * 
     * @ORM\Column(name="CHARGESEC_DOSSIER", type="text", nullable=true)
     */
    private $chargeSecurite;

    /** @var ?string
     * 
     * @ORM\Column(name="GRAVPRESC_DOSSIER", type="text", nullable=true)
     */
    private $gravitePrescription;

    /**
     * @var int
     * 
     * @ORM\Column(name="NUMINTERV_DOSSIER", type="integer", nullable=true)
     */
    private $numIntervention;

    /**
     * @var Collection<int, DossierDocurba>
     * 
     * @ORM\OneToMany(targetEntity=DossierDocurba::class, mappedBy="dossier", orphanRemoval=true)
     */
    private $numDossierDocurba;

    /**
     * @var Commission
     * 
     * @ORM\ManyToOne(targetEntity=Commission::class)
     * @ORM\JoinColumn(name="COMMISSION_DOSSIER", referencedColumnName="ID_COMMISSION", nullable=true, onDelete="SET NULL")
     * 
     */
    private $commissionDossier;

    /**
     * @var Avis
     * 
     * @ORM\ManyToOne(targetEntity=Avis::class)
     * @ORM\JoinColumn(name="AVIS_DOSSIER", referencedColumnName="ID_AVIS", nullable=true)
     */
    private $avisDossier;

    /**
     * @var Avis
     * 
     * @ORM\ManyToOne(targetEntity=Avis::class)
     * @ORM\JoinColumn(name="AVIS_DOSSIER_COMMISSION", referencedColumnName="ID_AVIS", nullable=true, onDelete="SET NULL")
     */
    private $avisDossierCommission;

    /**
     * @var Collection<int, Utilisateur>
     * 
     * @ORM\ManyToMany(targetEntity=Utilisateur::class, inversedBy="dossiersPrevention")
     * @ORM\JoinTable(
     *  name="dossierpreventionniste",
     *  joinColumns={@ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="ID_PREVENTIONNISTE", referencedColumnName="ID_UTILISATEUR")}
     * )
     */
    private $preventionnisteDossier;

    /**
     * @var Collection<int, DossierDocManquant>
     * 
     * @ORM\OneToMany(targetEntity=DossierDocManquant::class, mappedBy="idDossier")
     */
    private $dossierDocManquants;

    /**
     * @var ?string 
     * 
     * @ORM\Column(name="OBSERVATION_DOSSIER", type="text", nullable=true)
     */
    private $observations;

    /**
     * @var ?PlatauConsultation
     * @ORM\OneToOne(targetEntity=PlatauConsultation::class, inversedBy="dossier", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="ID_PLATAU", referencedColumnName="ID_PLATAU", nullable=true)
     */
    private $platauConsultation;

    /**
     * @var ?Utilisateur
     * 
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     * @ORM\JoinColumn(name="CREATEUR_DOSSIER", referencedColumnName="ID_UTILISATEUR", nullable=true)

     */
    private $createurDossier;

    /**
     * @var ?bool
     * 
     * @ORM\Column(name="VERROU_DOSSIER", type="boolean", nullable=true)
     */
    private $verrouDossier;

    /**
     * @var ?Utilisateur
     * 
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     * @ORM\JoinColumn(name="VERROU_USER_DOSSIER", referencedColumnName="ID_UTILISATEUR", nullable=true)
     */
    private $verrouUserDossier;

    
    
    public function __construct()
    {
        $this->nature = new ArrayCollection();
        $this->etablissements = new ArrayCollection();
        $this->numDossierDocurba = new ArrayCollection();
        $this->preventionnisteDossier = new ArrayCollection();
        $this->dossierDocManquants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSuppression(): ?\DateTimeInterface
    {
        return $this->dateSuppression;
    }

    public function setDateSuppression(?\DateTimeInterface $dateSuppression): self
    {
        $this->dateSuppression = $dateSuppression;

        return $this;
    }

    public function getDeletedBy(): ?Utilisateur
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?Utilisateur $deletedBy): self
    {
        $this->deletedBy = $deletedBy;

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

    public function getType(): ?DossierType
    {
        return $this->type;
    }

    public function setType(DossierType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /*
     * On a une liste de natures associées car il y a une table de jointure `dossiernature`.
     * Cependant un dossier ne peut être associé qu'à une seule nature.
     * On récupère donc toujours le premier élément de la collection.
     */
    public function getNature(): ?DossierNatureListe
    {
        return $this->nature->first() ?: null;
    }

    /*
     * Même raisonnement que pour le getter.
     * Comme on a qu'une seule nature, on reset la collection à chaque fois.
     */
    public function setNature(DossierNatureListe $nature): self
    {
        $this->nature = new ArrayCollection();
        $this->nature->add($nature);

        return $this;
    }

    /**
     * @return Collection<int, Etablissement>
     */
    public function getEtablissements(): Collection
    {
        return $this->etablissements;
    }

    public function addEtablissement(Etablissement $etablissement): self
    {
        if (!$this->etablissements->contains($etablissement)) {
            $this->etablissements[] = $etablissement;
        }

        return $this;
    }

    public function removeEtablissement(Etablissement $etablissement): self
    {
        $this->etablissements->removeElement($etablissement);

        return $this;
    }
    
    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

   public function getDemandeur(): ?string
   {
       return $this->demandeur;
   }

   public function setDemandeur(?string $demandeur): self
   {
       $this->demandeur = $demandeur;

       return $this;
   }

   public function getLieuReunion(): ?string
   {
       return $this->lieuReunion;
   }

   public function setLieuReunion(?string $lieuReunion): self
   {
       $this->lieuReunion = $lieuReunion;

       return $this;
   }

   public function getServiceInstructeur(): ?string
   {
       return $this->serviceInstructeur;
   }

   public function setServiceInstructeur(?string $serviceInstructeur): self
   {
       $this->serviceInstructeur = $serviceInstructeur;

       return $this;
   }

   public function getDescriptifAnalyseRisque(): ?string
   {
       return $this->descriptifAnalyseRisque;
   }

   public function setDescriptifAnalyseRisque(?string $descriptifAnalyseRisque): self
   {
       $this->descriptifAnalyseRisque = $descriptifAnalyseRisque;

       return $this;
   }

   public function getReglesADeroger(): ?string
   {
       return $this->reglesADeroger;
   }

   public function setReglesADeroger(?string $reglesADeroger): self
   {
       $this->reglesADeroger = $reglesADeroger;

       return $this;
   }

   public function getJustificatifDerogation(): ?string
   {
       return $this->justificatifDerogation;
   }

   public function setJustificatifDerogation(?string $justificatifDerogation): self
   {
       $this->justificatifDerogation = $justificatifDerogation;

       return $this;
   }

   public function getMesuresCompensatoires(): ?string
   {
       return $this->mesuresCompensatoires;
   }

   public function setMesuresCompensatoires(?string $mesuresCompensatoires): self
   {
       $this->mesuresCompensatoires = $mesuresCompensatoires;

       return $this;
   }

   public function getMesuresComplementaires(): ?string
   {
       return $this->mesuresComplementaires;
   }

   public function setMesuresComplementaires(?string $mesuresComplementaires): self
   {
       $this->mesuresComplementaires = $mesuresComplementaires;

       return $this;
   }

   public function getEtatDossier(): ?string
   {
       return $this->etatDossier;
   }

   public function setEtatDossier(?string $etatDossier): self
   {
       $this->etatDossier = $etatDossier;

       return $this;
   }

   public function getHorsDelai(): ?string
   {
       return $this->horsDelai;
   }

   public function setHorsDelai(string $horsDelai): self
   {
       $this->horsDelai = $horsDelai;

       return $this;
   }

   public function getDiffereAvis(): ?string
   {
       return $this->differeAvis;
   }

   public function setDiffereAvis(string $differeAvis): self
   {
       $this->differeAvis = $differeAvis;

       return $this;
   }

   public function getNonPrononce(): ?string
   {
       return $this->nonPrononce;
   }

   public function setNonPrononce(string $nonPrononce): self
   {
       $this->nonPrononce = $nonPrononce;

       return $this;
   }

   public function getDateReunion(): ?\DateTimeInterface
   {
       return $this->dateReunion;
   }

   public function setDateReunion(?\DateTimeInterface $dateReunion): self
   {
       $this->dateReunion = $dateReunion;

       return $this;
   }

   public function getDateMairie(): ?\DateTimeInterface
   {
       return $this->dateMairie;
   }

   public function setDateMairie(?\DateTimeInterface $dateMairie): self
   {
       $this->dateMairie = $dateMairie;

       return $this;
   }

   public function getDateSecretariat(): ?\DateTimeInterface
   {
       return $this->dateSecretariat;
   }

   public function setDateSecretariat(?\DateTimeInterface $dateSecretariat): self
   {
       $this->dateSecretariat = $dateSecretariat;

       return $this;
   }

   public function getDateEnvTransit(): ?\DateTimeInterface
   {
       return $this->dateEnvTransit;
   }

   public function setDateEnvTransit(?\DateTimeInterface $dateEnvTransit): self
   {
       $this->dateEnvTransit = $dateEnvTransit;

       return $this;
   }

   public function getDateSdis(): ?\DateTimeInterface
   {
       return $this->dateSdis;
   }

   public function setDateSdis(?\DateTimeInterface $dateSdis): self
   {
       $this->dateSdis = $dateSdis;

       return $this;
   }

   public function getDateVisite(): ?\DateTimeInterface
   {
       return $this->dateVisite;
   }

   public function setDateVisite(?\DateTimeInterface $dateVisite): self
   {
       $this->dateVisite = $dateVisite;

       return $this;
   }

   public function getDateRVRAT(): ?\DateTimeInterface
   {
       return $this->dateRVRAT;
   }

   public function setDateRVRAT(?\DateTimeInterface $dateRVRAT): self
   {
       $this->dateRVRAT = $dateRVRAT;

       return $this;
   }

   public function getDateCommission(): ?string
   {
       return $this->dateCommission;
   }

   public function setDateCommission(?string $dateCommission): self
   {
       $this->dateCommission = $dateCommission;

       return $this;
   }

   public function getDatePrefecture(): ?\DateTimeInterface
   {
       return $this->datePrefecture;
   }

   public function setDatePrefecture(?\DateTimeInterface $datePrefecture): self
   {
       $this->datePrefecture = $datePrefecture;

       return $this;
   }

   public function getDateReponse(): ?\DateTimeInterface
   {
       return $this->dateReponse;
   }

   public function setDateReponse(?\DateTimeInterface $dateReponse): self
   {
       $this->dateReponse = $dateReponse;

       return $this;
   }

   public function getDateTransfertCommission(): ?\DateTimeInterface
   {
       return $this->dateTransfertCommission;
   }

   public function setDateTransfertCommission(?\DateTimeInterface $dateTransfertCommission): self
   {
       $this->dateTransfertCommission = $dateTransfertCommission;

       return $this;
   }

   public function getDateReceptionCommission(): ?\DateTimeInterface
   {
       return $this->dateReceptionCommission;
   }

   public function setDateReceptionCommission(?\DateTimeInterface $dateReceptionCommission): self
   {
       $this->dateReceptionCommission = $dateReceptionCommission;

       return $this;
   }

   public function getDelaiPrescription(): ?\DateTimeInterface
   {
       return $this->delaiPrescription;
   }

   public function setDelaiPrescription(?\DateTimeInterface $delaiPrescription): self
   {
       $this->delaiPrescription = $delaiPrescription;

       return $this;
   }

   public function getDateSignature(): ?\DateTimeInterface
   {
       return $this->dateSignature;
   }

   public function setDateSignature(?\DateTimeInterface $dateSignature): self
   {
       $this->dateSignature = $dateSignature;

       return $this;
   }

   public function getDateIntervention(): ?\DateTimeInterface
   {
       return $this->dateIntervention;
   }

   public function setDateIntervention(?\DateTimeInterface $dateIntervention): self
   {
       $this->dateIntervention = $dateIntervention;

       return $this;
   }

   public function getDureeIntervention(): ?\DateTimeInterface
   {
       return $this->DureeIntervention;
   }

   public function setDureeIntervention(?\DateTimeInterface $DureeIntervention): self
   {
       $this->DureeIntervention = $DureeIntervention;

       return $this;
   }

   public function getDureeDeplacement(): ?int
   {
       return $this->dureeDeplacement;
   }

   public function setDureeDeplacement(?int $dureeDeplacement): self
   {
       $this->dureeDeplacement = $dureeDeplacement;

       return $this;
   }

   public function getAbsenceQuorum(): ?bool
   {
       return $this->absenceQuorum;
   }

   public function setAbsenceQuorum(?bool $absenceQuorum): self
   {
       $this->absenceQuorum = $absenceQuorum;

       return $this;
   }

   public function getCelluleNonExploitée(): ?bool
   {
       return $this->celluleNonExploitée;
   }

   public function setCelluleNonExploitée(?bool $celluleNonExploitée): self
   {
       $this->celluleNonExploitée = $celluleNonExploitée;

       return $this;
   }

   public function getFacteurDanger(): ?bool
   {
       return $this->facteurDanger;
   }

   public function setFacteurDanger(?bool $facteurDanger): self
   {
       $this->facteurDanger = $facteurDanger;

       return $this;
   }

   public function getEcheancierTravaux(): ?\DateTimeInterface
   {
       return $this->echeancierTravaux;
   }

   public function setEcheancierTravaux(?\DateTimeInterface $echeancierTravaux): self
   {
       $this->echeancierTravaux = $echeancierTravaux;

       return $this;
   }

   public function getAnomalie(): ?string
   {
       return $this->anomalie;
   }

   public function setAnomalie(?string $anomalie): self
   {
       $this->anomalie = $anomalie;

       return $this;
   }

   public function getOperationSDIS(): ?bool
   {
       return $this->operationSDIS;
   }

   public function setOperationSDIS(?bool $operationSDIS): self
   {
       $this->operationSDIS = $operationSDIS;

       return $this;
   }

   public function getRCCI(): ?bool
   {
       return $this->RCCI;
   }

   public function setRCCI(?bool $RCCI): self
   {
       $this->RCCI = $RCCI;

       return $this;
   }

   public function getREX(): ?string
   {
       return $this->REX;
   }

   public function setREX(?string $REX): self
   {
       $this->REX = $REX;

       return $this;
   }

   public function getChargeSecurite(): ?string
   {
       return $this->chargeSecurite;
   }

   public function setChargeSecurite(?string $chargeSecurite): self
   {
       $this->chargeSecurite = $chargeSecurite;

       return $this;
   }

   public function getGravitePrescription(): ?string
   {
       return $this->gravitePrescription;
   }

   public function setGravitePrescription(?string $gravitePrescription): self
   {
       $this->gravitePrescription = $gravitePrescription;

       return $this;
   }

   public function getNumIntervention(): ?int
   {
       return $this->numIntervention;
   }

   public function setNumIntervention(?int $numIntervention): self
   {
       $this->numIntervention = $numIntervention;

       return $this;
   }

   /**
    * @return Collection<int, DossierDocurba>
    */
   public function getNumDossierDocurba(): Collection
   {
       return $this->numDossierDocurba;
   }

   public function addNumDossierDocurba(DossierDocurba $numDossierDocurba): self
   {
       if (!$this->numDossierDocurba->contains($numDossierDocurba)) {
           $this->numDossierDocurba[] = $numDossierDocurba;
           $numDossierDocurba->setDossier($this);
       }

       return $this;
   }

   public function removeNumDossierDocurba(DossierDocurba $numDossierDocurba): self
   {
       if ($this->numDossierDocurba->removeElement($numDossierDocurba)) {
           // set the owning side to null (unless already changed)
           if ($numDossierDocurba->getDossier() === $this) {
               $numDossierDocurba->setDossier(null);
           }
       }

       return $this;
   }

   public function getCommissionDossier(): ?Commission
   {
       return $this->commissionDossier;
   }

   public function setCommissionDossier(?Commission $commissionDossier): self
   {
       $this->commissionDossier = $commissionDossier;

       return $this;
   }

   public function getAvisDossier(): ?Avis
   {
       return $this->avisDossier;
   }

   public function setAvisDossier(?Avis $avisDossier): self
   {
       $this->avisDossier = $avisDossier;

       return $this;
   }

   public function getAvisDossierCommission(): ?Avis
   {
       return $this->avisDossierCommission;
   }

   public function setAvisDossierCommission(?Avis $avisDossierCommission): self
   {
       $this->avisDossierCommission = $avisDossierCommission;

       return $this;
   }

   /**
    * @return Collection<int, Utilisateur>
    */
   public function getPreventionnisteDossier(): Collection
   {
       return $this->preventionnisteDossier;
   }

   public function addPreventionnisteDossier(Utilisateur $preventionnisteDossier): self
   {
       if (!$this->preventionnisteDossier->contains($preventionnisteDossier)) {
           $this->preventionnisteDossier[] = $preventionnisteDossier;
       }

       return $this;
   }

   public function removePreventionnisteDossier(Utilisateur $preventionnisteDossier): self
   {
       $this->preventionnisteDossier->removeElement($preventionnisteDossier);

       return $this;
   }

   /**
    * @return Collection<int, DossierDocManquant>
    */
   public function getDossierDocManquants(): Collection
   {
       return $this->dossierDocManquants;
   }

   public function addDossierDocManquant(DossierDocManquant $dossierDocManquant): self
   {
       if (!$this->dossierDocManquants->contains($dossierDocManquant)) {
           $this->dossierDocManquants[] = $dossierDocManquant;
           $dossierDocManquant->setIdDossier($this);
       }

       return $this;
   }

   public function removeDossierDocManquant(DossierDocManquant $dossierDocManquant): self
   {
       if ($this->dossierDocManquants->removeElement($dossierDocManquant)) {
           // set the owning side to null (unless already changed)
           if ($dossierDocManquant->getIdDossier() === $this) {
               $dossierDocManquant->setIdDossier(null);
           }
       }

       return $this;
   }

   public function getObservations(): ?string
   {
       return $this->observations;
   }

   public function setObservations(?string $observations): self
   {
       $this->observations = $observations;

       return $this;
   }

   public function getPlatauConsultation(): ?PlatauConsultation
   {
       return $this->platauConsultation;
   }

   public function setPlatauConsultation(?PlatauConsultation $platauConsultation): self
   {
       $this->platauConsultation = $platauConsultation;

       return $this;
   }

   public function getCreateurDossier(): ?Utilisateur
   {
       return $this->createurDossier;
   }

   public function setCreateurDossier(?Utilisateur $createurDossier): self
   {
       $this->createurDossier = $createurDossier;

       return $this;
   }

   public function getVerrouDossier(): ?bool
   {
       return $this->verrouDossier;
   }

   public function setVerrouDossier(?bool $verrouDossier): self
   {
       $this->verrouDossier = $verrouDossier;

       return $this;
   }

   public function getVerrouUserDossier(): ?Utilisateur
   {
       return $this->verrouUserDossier;
   }

   public function setVerrouUserDossier(?Utilisateur $verrouUserDossier): self
   {
       $this->verrouUserDossier = $verrouUserDossier;

       return $this;
   }



}
