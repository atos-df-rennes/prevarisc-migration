<?php

namespace App\Entity;

use App\Constant\DossierNature;
use App\Repository\DossierRepository;
use App\Validator\DossierObjet;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=DossierRepository::class)
 *
 * @ORM\Table(indexes={@ORM\Index(columns={"DATESUPPRESSION_DOSSIER"})})
 *
 * @DossierObjet
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
     * @var ?string
     *
     * @ORM\Column(name="DESCGEN_DOSSIER", type="text", nullable=true)
     */
    private $descriptifGeneral;

    /**
     * @var ?string
     *
     * @ORM\Column(name="DESCEFF_DOSSIER", type="text", nullable=true)
     */
    private $descriptifEffectifs;

    /**
     * @var ?string
     *
     * @ORM\Column(name="COORDSSI_DOSSIER", type="text", nullable=true)
     */
    private $coordonneesSsi;

    /**
     * @var DossierType
     *
     * @ORM\ManyToOne(targetEntity=DossierType::class)
     *
     * @ORM\JoinColumn(name="TYPE_DOSSIER", referencedColumnName="ID_DOSSIERTYPE", nullable=false, options={"unsigned":true})
     *
     * @Assert\NotNull(message="Le type de dossier est obligatoire.")
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
     *
     * @Assert\Count(
     *     min=1,
     *     minMessage="La nature du dossier est obligatoire."
     * )
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
     * @var ?Avis
     *
     * @ORM\ManyToOne(targetEntity=Avis::class)
     *
     * @ORM\JoinColumn(name="AVIS_DOSSIER_COMMISSION", referencedColumnName="ID_AVIS", nullable=true, options={"unsigned":true})
     */
    private $avisCommission;

    /**
     * @var ?int
     *
     * @ORM\Column(name="FACTDANGE_DOSSIER", type="integer", nullable=true, options={"unsigned":true})
     */
    private $facteurDangerosite;

    /**
     * @var ?string
     *
     * @ORM\Column(type="string", length=10, nullable=true, name="DATECOMM_DOSSIER")
     */
    private $dateCommission;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(type="date", nullable=true, name="DATEVISITE_DOSSIER")
     */
    private $dateVisite;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(type="datetime", nullable=false, name="DATEINSERT_DOSSIER")
     *
     * @Assert\NotNull(message="La date de création du dossier est obligatoire.")
     *
     * @Assert\DateTime(message="La date de création doit être une date valide.")
     */
    private $dateInsert;

    /**
     * @var ?Utilisateur
     *
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     *
     * @ORM\JoinColumn(name="CREATEUR_DOSSIER", referencedColumnName="ID_UTILISATEUR", nullable=true)
     */
    private $createur;

    /**
     * @var Collection<int, AvisDerogations>
     *
     * @ORM\OneToMany(targetEntity=AvisDerogations::class, mappedBy="dossier", orphanRemoval=true)
     */
    private $avisDerogations;

    /**
     * @var ?bool
     *
     * @ORM\Column(name="DIFFEREAVIS_DOSSIER", type="boolean", nullable=true)
     */
    private $avisDiffere;

    /**
     * @var Collection<int, DossierDisplayRubrique>
     *
     * @ORM\OneToMany(targetEntity=DossierDisplayRubrique::class, mappedBy="dossier", orphanRemoval=true)
     */
    private $displayRubriques;

    /**
     * @var Collection<int, UtilisateurInformations>
     *
     * @ORM\JoinTable(
     *     name="dossiercontact",
     *     joinColumns={@ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_UTILISATEURINFORMATIONS", referencedColumnName="ID_UTILISATEURINFORMATIONS", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=UtilisateurInformations::class)
     */
    private $contacts;

    /**
     * @var Collection<int, Dossier>
     *
     * @ORM\JoinTable(
     *     name="dossierlie",
     *     joinColumns={@ORM\JoinColumn(name="ID_DOSSIER1", referencedColumnName="ID_DOSSIER")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_DOSSIER2", referencedColumnName="ID_DOSSIER")}
     * )
     *
     * @ORM\ManyToMany(targetEntity=Dossier::class, inversedBy="dossiersAyantLie")
     */
    private $dossiersLies;

    /**
     * @var Collection<int, Dossier>
     *
     * @ORM\JoinTable(
     *     name="dossierlie",
     *     joinColumns={@ORM\JoinColumn(name="ID_DOSSIER2", referencedColumnName="ID_DOSSIER")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_DOSSIER1", referencedColumnName="ID_DOSSIER")}
     * )
     *
     * @ORM\ManyToMany(targetEntity=Dossier::class, mappedBy="dossiersLies")
     */
    private $dossiersAyantLie;

    /**
     * @var Collection<int, DossierDocumentManquant>
     *
     * @ORM\OneToMany(targetEntity=DossierDocumentManquant::class, mappedBy="dossier", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $documentsManquants;

    /**
     * @var Collection<int, DossierDocumentUrbanisme>
     *
     * @ORM\OneToMany(targetEntity=DossierDocumentUrbanisme::class, mappedBy="dossier", orphanRemoval=true)
     */
    private $documentsUrbanisme;

    /**
     * @var Collection<int, Utilisateur>
     *
     * @ORM\JoinTable(
     *     name="dossierpreventionniste",
     *     joinColumns={@ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_PREVENTIONNISTE", referencedColumnName="ID_UTILISATEUR", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=Utilisateur::class)
     */
    private $preventionnistes;

    /**
     * @var Collection<int, TexteApplicable>
     *
     * @ORM\JoinTable(
     *     name="dossiertextesappl",
     *     joinColumns={@ORM\JoinColumn(name="ID_DOSSIER", referencedColumnName="ID_DOSSIER")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_TEXTESAPPL", referencedColumnName="ID_TEXTESAPPL")}
     * )
     *
     * @ORM\ManyToMany(targetEntity=TexteApplicable::class)
     */
    private $textesApplicables;

    /**
     * @var Collection<int, ListeDocumentAjoute>
     *
     * @ORM\OneToMany(targetEntity=ListeDocumentAjoute::class, mappedBy="dossier", orphanRemoval=true)
     */
    private $documentsAjoutes;

    /**
     * @var Collection<int, DossierDocumentConsulte>
     *
     * @ORM\OneToMany(targetEntity=DossierDocumentConsulte::class, mappedBy="dossier", orphanRemoval=true)
     */
    private $documentsConsultes;

    /**
     * @var Collection<int, DossierPieceJointe>
     *
     * @ORM\OneToMany(targetEntity=DossierPieceJointe::class, mappedBy="dossier", orphanRemoval=true)
     */
    private $piecesJointes;

    /**
     * @var Collection<int, PrescriptionDossier>
     *
     * @ORM\OneToMany(targetEntity=PrescriptionDossier::class, mappedBy="dossier", orphanRemoval=true)
     */
    private $prescriptions;

    /**
     * @var ?bool
     *
     * @ORM\Column(name="VERROU_DOSSIER", type="boolean", nullable=true)
     */
    private $verrou;

    /**
     * @var ?int
     *
     * @ORM\Column(name="VERROU_USER_DOSSIER", type="bigint", nullable=true)
     */
    private $utilisateurVerrou;

    /**
     * @var ?PlatauConsultation
     *
     * @ORM\OneToOne(targetEntity=PlatauConsultation::class, cascade={"persist", "remove"})
     *
     * @ORM\JoinColumn(name="ID_PLATAU", referencedColumnName="ID_PLATAU", options={"fixed":true})
     */
    private $consultationPlatau;

    /**
     * @var ?string
     *
     * @ORM\Column(name="DEMANDEUR_DOSSIER", type="text", nullable=true)
     */
    private $demandeur;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATESDIS_DOSSIER", type="datetime", nullable=true)
     */
    private $dateReception;

    /**
     * @var ?Commission
     *
     * @ORM\ManyToOne(targetEntity=Commission::class)
     *
     * @ORM\JoinColumn(name="COMMISSION_DOSSIER", referencedColumnName="ID_COMMISSION", nullable=true, options={"unsigned":true})
     */
    private $commission;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATEMAIRIE_DOSSIER", type="datetime", nullable=true)
     */
    private $dateMairie;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATESECRETARIAT_DOSSIER", type="datetime", nullable=true)
     */
    private $dateSecretariat;

    /**
     * @var ?string
     *
     * @ORM\Column(name="SERVICEINSTRUC_DOSSIER", type="string", length=255, nullable=true)
     */
    private $serviceInstructeur;

    /**
     * @var ?Avis
     *
     * @ORM\ManyToOne(targetEntity=Avis::class)
     *
     * @ORM\JoinColumn(name="AVIS_DOSSIER", referencedColumnName="ID_AVIS", nullable=true, options={"unsigned":true})
     */
    private $avis;

    /**
     * @var ?string
     *
     * @ORM\Column(name="ANOMALIE_DOSSIER", type="text", nullable=true)
     */
    private $analyseDeRisque;

    /**
     * @var ?string
     *
     * @ORM\Column(name="OBSERVATION_DOSSIER", type="text", nullable=true)
     */
    private $observations;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATEREP_DOSSIER", type="datetime", nullable=true)
     */
    private $dateReponse;

    /**
     * @var ?bool
     *
     * @ORM\Column(name="HORSDELAI_DOSSIER", type="boolean", nullable=true, options={"default": 0})
     */
    private $horsDelai;

    /**
     * @var ?bool
     *
     * @ORM\Column(name="ABSQUORUM_DOSSIER", type="boolean", nullable=true)
     */
    private $absenceDeQuorum;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DELAIPRESC_DOSSIER", type="date", nullable=true)
     */
    private $dateLimitePrescription;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="ECHEANCIERTRAV_DOSSIER", type="date", nullable=true)
     */
    private $echeancierTravaux;

    /**
     * @var ?bool
     *
     * @ORM\Column(name="INCOMPLET_DOSSIER", type="boolean", nullable=true, options={"default": 0})
     */
    private $incomplet;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATEINCOMPLET_DOSSIER", type="date", nullable=true)
     */
    private $dateIncomplet;

    /**
     * @var ?string
     *
     * @ORM\Column(name="LIEUREUNION_DOSSIER", type="text", nullable=true)
     */
    private $lieuReunion;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATEENVTRANSIT_DOSSIER", type="date", nullable=true)
     */
    private $dateEnvoiTransit;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATERVRAT_DOSSIER", type="date", nullable=true)
     */
    private $dateReceptionRvrat;

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
    private $reglesDerogation;

    /**
     * @var ?string
     *
     * @ORM\Column(name="JUSTIFDEROG_DOSSIER", type="text", nullable=true)
     */
    private $justificationDerogation;

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
     * @ORM\Column(name="NPSP_DOSSIER", type="boolean", nullable=true)
     */
    private $nePeutSePrononcer;

    /**
     * @var ?bool
     *
     * @ORM\Column(name="CNE_DOSSIER", type="boolean", nullable=true)
     */
    private $celluleNonExploitee;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATEPREF_DOSSIER", type="datetime", nullable=true)
     */
    private $datePrefecture;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATETRANSFERTCOMM_DOSSIER", type="datetime", nullable=true)
     */
    private $dateTransfertCommission;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATERECEPTIONCOMM_DOSSIER", type="datetime", nullable=true)
     */
    private $dateReceptionCommission;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATEREUN_DOSSIER", type="datetime", nullable=true)
     */
    private $dateReunion;

    /**
     * @var ?bool
     *
     * @ORM\Column(name="OPERSDIS_DOSSIER", type="boolean", nullable=true)
     */
    private $operationSdis;

    /**
     * @var ?bool
     *
     * @ORM\Column(name="RCCI_DOSSIER", type="boolean", nullable=true)
     */
    private $rcci;

    /**
     * @var ?string
     *
     * @ORM\Column(name="REX_DOSSIER", type="text", nullable=true)
     */
    private $rex;

    /**
     * @var ?string
     *
     * @ORM\Column(name="CHARGESEC_DOSSIER", type="text", nullable=true)
     */
    private $chargeSecurite;

    /**
     * @var ?int
     *
     * @ORM\Column(name="DUREEDEPL_DOSSIER", type="integer", nullable=true)
     */
    private $dureeDeplacement;

    /**
     * @var ?string
     *
     * @ORM\Column(name="GRAVPRESC_DOSSIER", type="text", nullable=true)
     */
    private $gravitePrescription;

    /**
     * @var ?int
     *
     * @ORM\Column(name="NUMINTERV_DOSSIER", type="integer", nullable=true)
     */
    private $numeroIntervention;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATEINTERV_DOSSIER", type="datetime", nullable=true)
     */
    private $dateIntervention;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DUREEINTERV_DOSSIER", type="time", nullable=true)
     */
    private $dureeIntervention;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATESIGN_DOSSIER", type="date", nullable=true)
     */
    private $dateSignature;

    // @todo: Voir s'il y a des valeurs par défaut à mettre (notamment les booléens)
    public function __construct()
    {
        $this->nature = new ArrayCollection();
        $this->etablissements = new ArrayCollection();
        $this->avisDerogations = new ArrayCollection();
        $this->displayRubriques = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->dossiersLies = new ArrayCollection();
        $this->dossiersAyantLie = new ArrayCollection();
        $this->documentsManquants = new ArrayCollection();
        $this->documentsUrbanisme = new ArrayCollection();
        $this->preventionnistes = new ArrayCollection();
        $this->textesApplicables = new ArrayCollection();
        $this->documentsAjoutes = new ArrayCollection();
        $this->documentsConsultes = new ArrayCollection();
        $this->piecesJointes = new ArrayCollection();
        $this->prescriptions = new ArrayCollection();
        $this->horsDelai = false;
        $this->incomplet = false;
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

    public function getDescriptifGeneral(): ?string
    {
        return $this->descriptifGeneral;
    }

    public function setDescriptifGeneral(?string $descriptifGeneral): self
    {
        $this->descriptifGeneral = $descriptifGeneral;

        return $this;
    }

    public function getDescriptifEffectifs(): ?string
    {
        return $this->descriptifEffectifs;
    }

    public function setDescriptifEffectifs(?string $descriptifEffectifs): self
    {
        $this->descriptifEffectifs = $descriptifEffectifs;

        return $this;
    }

    public function getCoordonneesSsi(): ?string
    {
        return $this->coordonneesSsi;
    }

    public function setCoordonneesSsi(?string $coordonneesSsi): self
    {
        $this->coordonneesSsi = $coordonneesSsi;

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
     * Par défaut, ne récupère pas les établissements qui ont été supprimés.
     *
     * @return Collection<int, Etablissement>
     */
    public function getEtablissements(): Collection
    {
        $criteria = Criteria::create()->andWhere(Criteria::expr()->isNull('dateSuppression'));

        // PHPStan n'a pas l'extension Doctrine et n'est donc pas au courant que ça fonctionne bien.
        /* @phpstan-ignore-next-line */
        return $this->etablissements->matching($criteria);
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

    public function getAvisCommission(): ?Avis
    {
        return $this->avisCommission;
    }

    public function setAvisCommission(?Avis $avisCommission): self
    {
        $this->avisCommission = $avisCommission;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFacteurDangerosite()
    {
        return $this->facteurDangerosite;
    }

    /**
     * @param int|null $facteurDangerosite
     */
    public function setFacteurDangerosite($facteurDangerosite): self
    {
        $this->facteurDangerosite = $facteurDangerosite;

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

    public function getDateVisite(): ?\DateTimeInterface
    {
        return $this->dateVisite;
    }

    public function setDateVisite(?\DateTimeInterface $dateVisite): self
    {
        $this->dateVisite = $dateVisite;

        return $this;
    }

    public function getDateInsert(): ?\DateTimeInterface
    {
        return $this->dateInsert;
    }

    public function setDateInsert(?\DateTimeInterface $dateInsert): self
    {
        $this->dateInsert = $dateInsert;

        return $this;
    }

    public function getCreateur(): ?Utilisateur
    {
        return $this->createur;
    }

    public function setCreateur(?Utilisateur $createur): self
    {
        $this->createur = $createur;

        return $this;
    }

    /**
     * @return Collection<int, AvisDerogations>
     */
    public function getAvisDerogations(): Collection
    {
        return $this->avisDerogations;
    }

    public function addAvisDerogation(AvisDerogations $avisDerogation): self
    {
        if (!$this->avisDerogations->contains($avisDerogation)) {
            $this->avisDerogations[] = $avisDerogation;
            $avisDerogation->setDossier($this);
        }

        return $this;
    }

    public function removeAvisDerogation(AvisDerogations $avisDerogation): self
    {
        // set the owning side to null (unless already changed)
        if ($this->avisDerogations->removeElement($avisDerogation) && $avisDerogation->getDossier() === $this) {
            $avisDerogation->setDossier(null);
        }

        return $this;
    }

    public function hasAvisDiffere(): ?bool
    {
        return $this->avisDiffere;
    }

    public function setAvisDiffere(?bool $avisDiffere): self
    {
        $this->avisDiffere = $avisDiffere;

        return $this;
    }

    /**
     * @return Collection<int, DossierDisplayRubrique>
     */
    public function getDisplayRubriques(): Collection
    {
        return $this->displayRubriques;
    }

    public function addDisplayRubrique(DossierDisplayRubrique $displayRubrique): self
    {
        if (!$this->displayRubriques->contains($displayRubrique)) {
            $this->displayRubriques[] = $displayRubrique;
            $displayRubrique->setDossier($this);
        }

        return $this;
    }

    public function removeDisplayRubrique(DossierDisplayRubrique $displayRubrique): self
    {
        // set the owning side to null (unless already changed)
        if ($this->displayRubriques->removeElement($displayRubrique) && $displayRubrique->getDossier() === $this) {
            $displayRubrique->setDossier(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, UtilisateurInformations>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(UtilisateurInformations $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
        }

        return $this;
    }

    public function removeContact(UtilisateurInformations $contact): self
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getDossiersLies(): Collection
    {
        return $this->dossiersLies;
    }

    public function addDossiersLies(self $dossiersLies): self
    {
        if (!$this->dossiersLies->contains($dossiersLies)) {
            $this->dossiersLies[] = $dossiersLies;
        }

        return $this;
    }

    public function removeDossiersLies(self $dossiersLies): self
    {
        $this->dossiersLies->removeElement($dossiersLies);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getDossiersAyantLie(): Collection
    {
        return $this->dossiersAyantLie;
    }

    public function addDossiersAyantLie(self $dossiersAyantLie): self
    {
        if (!$this->dossiersAyantLie->contains($dossiersAyantLie)) {
            $this->dossiersAyantLie[] = $dossiersAyantLie;
            $dossiersAyantLie->addDossiersLies($this);
        }

        return $this;
    }

    public function removeDossiersAyantLie(self $dossiersAyantLie): self
    {
        if ($this->dossiersAyantLie->removeElement($dossiersAyantLie)) {
            $dossiersAyantLie->removeDossiersLies($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, DossierDocumentManquant>
     */
    public function getDocumentsManquants(): Collection
    {
        return $this->documentsManquants;
    }

    public function addDocumentsManquant(DossierDocumentManquant $documentsManquant): self
    {
        if (!$this->documentsManquants->contains($documentsManquant)) {
            $this->documentsManquants[] = $documentsManquant;
            $documentsManquant->setDossier($this);
        }

        return $this;
    }

    public function removeDocumentsManquant(DossierDocumentManquant $documentsManquant): self
    {
        // set the owning side to null (unless already changed)
        if ($this->documentsManquants->removeElement($documentsManquant) && $documentsManquant->getDossier() === $this) {
            $documentsManquant->setDossier(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, DossierDocumentUrbanisme>
     */
    public function getDocumentsUrbanisme(): Collection
    {
        return $this->documentsUrbanisme;
    }

    public function addDocumentsUrbanisme(DossierDocumentUrbanisme $documentsUrbanisme): self
    {
        if (!$this->documentsUrbanisme->contains($documentsUrbanisme)) {
            $this->documentsUrbanisme[] = $documentsUrbanisme;
            $documentsUrbanisme->setDossier($this);
        }

        return $this;
    }

    public function removeDocumentsUrbanisme(DossierDocumentUrbanisme $documentsUrbanisme): self
    {
        // set the owning side to null (unless already changed)
        if ($this->documentsUrbanisme->removeElement($documentsUrbanisme) && $documentsUrbanisme->getDossier() === $this) {
            $documentsUrbanisme->setDossier(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getPreventionnistes(): Collection
    {
        return $this->preventionnistes;
    }

    public function addPreventionniste(Utilisateur $preventionniste): self
    {
        if (!$this->preventionnistes->contains($preventionniste)) {
            $this->preventionnistes[] = $preventionniste;
        }

        return $this;
    }

    public function removePreventionniste(Utilisateur $preventionniste): self
    {
        $this->preventionnistes->removeElement($preventionniste);

        return $this;
    }

    /**
     * @return Collection<int, TexteApplicable>
     */
    public function getTextesApplicables(): Collection
    {
        return $this->textesApplicables;
    }

    public function addTextesApplicable(TexteApplicable $textesApplicable): self
    {
        if (!$this->textesApplicables->contains($textesApplicable)) {
            $this->textesApplicables[] = $textesApplicable;
        }

        return $this;
    }

    public function removeTextesApplicable(TexteApplicable $textesApplicable): self
    {
        $this->textesApplicables->removeElement($textesApplicable);

        return $this;
    }

    /**
     * @return Collection<int, ListeDocumentAjoute>
     */
    public function getDocumentsAjoutes(): Collection
    {
        return $this->documentsAjoutes;
    }

    public function addDocumentsAjoute(ListeDocumentAjoute $documentsAjoute): self
    {
        if (!$this->documentsAjoutes->contains($documentsAjoute)) {
            $this->documentsAjoutes[] = $documentsAjoute;
            $documentsAjoute->setDossier($this);
        }

        return $this;
    }

    public function removeDocumentsAjoute(ListeDocumentAjoute $documentsAjoute): self
    {
        // set the owning side to null (unless already changed)
        if ($this->documentsAjoutes->removeElement($documentsAjoute) && $documentsAjoute->getDossier() === $this) {
            $documentsAjoute->setDossier(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, DossierDocumentConsulte>
     */
    public function getDocumentsConsultes(): Collection
    {
        return $this->documentsConsultes;
    }

    public function addDocumentsConsulte(DossierDocumentConsulte $documentsConsulte): self
    {
        if (!$this->documentsConsultes->contains($documentsConsulte)) {
            $this->documentsConsultes[] = $documentsConsulte;
            $documentsConsulte->setDossier($this);
        }

        return $this;
    }

    public function removeDocumentsConsulte(DossierDocumentConsulte $documentsConsulte): self
    {
        // set the owning side to null (unless already changed)
        if ($this->documentsConsultes->removeElement($documentsConsulte) && $documentsConsulte->getDossier() === $this) {
            $documentsConsulte->setDossier(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, DossierPieceJointe>
     */
    public function getPiecesJointes(): Collection
    {
        return $this->piecesJointes;
    }

    public function addPiecesJointe(DossierPieceJointe $piecesJointe): self
    {
        if (!$this->piecesJointes->contains($piecesJointe)) {
            $this->piecesJointes[] = $piecesJointe;
            $piecesJointe->setDossier($this);
        }

        return $this;
    }

    public function removePiecesJointe(DossierPieceJointe $piecesJointe): self
    {
        // set the owning side to null (unless already changed)
        if ($this->piecesJointes->removeElement($piecesJointe) && $piecesJointe->getDossier() === $this) {
            $piecesJointe->setDossier(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, PrescriptionDossier>
     */
    public function getPrescriptions(): Collection
    {
        return $this->prescriptions;
    }

    public function addPrescription(PrescriptionDossier $prescription): self
    {
        if (!$this->prescriptions->contains($prescription)) {
            $this->prescriptions[] = $prescription;
            $prescription->setDossier($this);
        }

        return $this;
    }

    public function removePrescription(PrescriptionDossier $prescription): self
    {
        // set the owning side to null (unless already changed)
        if ($this->prescriptions->removeElement($prescription) && $prescription->getDossier() === $this) {
            $prescription->setDossier(null);
        }

        return $this;
    }

    public function hasVerrou(): ?bool
    {
        return $this->verrou;
    }

    public function setVerrou(?bool $verrou): self
    {
        $this->verrou = $verrou;

        return $this;
    }

    public function getUtilisateurVerrou(): ?int
    {
        return $this->utilisateurVerrou;
    }

    public function setUtilisateurVerrou(?int $utilisateurVerrou): self
    {
        $this->utilisateurVerrou = $utilisateurVerrou;

        return $this;
    }

    public function getConsultationPlatau(): ?PlatauConsultation
    {
        return $this->consultationPlatau;
    }

    public function setConsultationPlatau(?PlatauConsultation $consultationPlatau): self
    {
        $this->consultationPlatau = $consultationPlatau;

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

    public function getDateReception(): ?\DateTimeInterface
    {
        return $this->dateReception;
    }

    public function setDateReception(?\DateTimeInterface $dateReception): self
    {
        $this->dateReception = $dateReception;

        return $this;
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission): self
    {
        $this->commission = $commission;

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

    /**
     * Retourne le service instructeur.
     * Normalise les valeurs legacy "0" et chaînes vides en null.
     */
    public function getServiceInstructeur(): ?string
    {
        // Normaliser les valeurs legacy héritées de l'ancien système
        // "0" et chaînes vides sont considérés comme absence de service instructeur
        // TODO: Nettoyer la BDD avec migration SQL quand création dossier migrée dans Symfony
        if (in_array($this->serviceInstructeur, ['0', ''], true)) {
            return null;
        }

        return $this->serviceInstructeur;
    }

    /**
     * Définit le service instructeur.
     * Normalise les valeurs "0" et chaînes vides en null avant persistence.
     */
    public function setServiceInstructeur(?string $serviceInstructeur): self
    {
        // Normaliser les valeurs "0" et vides en null
        // Évite de persister des données legacy depuis le code Symfony
        $this->serviceInstructeur = in_array($serviceInstructeur, ['0', ''], true) ? null : $serviceInstructeur;

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

    public function getAnalyseDeRisque(): ?string
    {
        return $this->analyseDeRisque;
    }

    public function setAnalyseDeRisque(?string $analyseDeRisque): self
    {
        $this->analyseDeRisque = $analyseDeRisque;

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

    public function getDateReponse(): ?\DateTimeInterface
    {
        return $this->dateReponse;
    }

    public function setDateReponse(?\DateTimeInterface $dateReponse): self
    {
        $this->dateReponse = $dateReponse;

        return $this;
    }

    public function isHorsDelai(): ?bool
    {
        return $this->horsDelai;
    }

    public function setHorsDelai(?bool $horsDelai): self
    {
        $this->horsDelai = $horsDelai;

        return $this;
    }

    public function hasAbsenceDeQuorum(): ?bool
    {
        return $this->absenceDeQuorum;
    }

    public function setAbsenceDeQuorum(?bool $absenceDeQuorum): self
    {
        $this->absenceDeQuorum = $absenceDeQuorum;

        return $this;
    }

    public function getDateLimitePrescription(): ?\DateTimeInterface
    {
        return $this->dateLimitePrescription;
    }

    public function setDateLimitePrescription(?\DateTimeInterface $dateLimitePrescription): self
    {
        $this->dateLimitePrescription = $dateLimitePrescription;

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

    public function isIncomplet(): ?bool
    {
        return $this->incomplet;
    }

    public function setIncomplet(?bool $incomplet): self
    {
        $this->incomplet = $incomplet;

        return $this;
    }

    public function getDateIncomplet(): ?\DateTimeInterface
    {
        return $this->dateIncomplet;
    }

    public function setDateIncomplet(?\DateTimeInterface $dateIncomplet): self
    {
        $this->dateIncomplet = $dateIncomplet;

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

    public function getDateEnvoiTransit(): ?\DateTimeInterface
    {
        return $this->dateEnvoiTransit;
    }

    public function setDateEnvoiTransit(?\DateTimeInterface $dateEnvoiTransit): self
    {
        $this->dateEnvoiTransit = $dateEnvoiTransit;

        return $this;
    }

    public function getDateReceptionRvrat(): ?\DateTimeInterface
    {
        return $this->dateReceptionRvrat;
    }

    public function setDateReceptionRvrat(?\DateTimeInterface $dateReceptionRvrat): self
    {
        $this->dateReceptionRvrat = $dateReceptionRvrat;

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

    public function getReglesDerogation(): ?string
    {
        return $this->reglesDerogation;
    }

    public function setReglesDerogation(?string $reglesDerogation): self
    {
        $this->reglesDerogation = $reglesDerogation;

        return $this;
    }

    public function getJustificationDerogation(): ?string
    {
        return $this->justificationDerogation;
    }

    public function setJustificationDerogation(?string $justificationDerogation): self
    {
        $this->justificationDerogation = $justificationDerogation;

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

    public function hasNePeutSePrononcer(): ?bool
    {
        return $this->nePeutSePrononcer;
    }

    public function setNePeutSePrononcer(?bool $nePeutSePrononcer): self
    {
        $this->nePeutSePrononcer = $nePeutSePrononcer;

        return $this;
    }

    public function getCelluleNonExploitee(): ?bool
    {
        return $this->celluleNonExploitee;
    }

    public function setCelluleNonExploitee(?bool $celluleNonExploitee): self
    {
        $this->celluleNonExploitee = $celluleNonExploitee;

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

    public function getDateReunion(): ?\DateTimeInterface
    {
        return $this->dateReunion;
    }

    public function setDateReunion(?\DateTimeInterface $dateReunion): self
    {
        $this->dateReunion = $dateReunion;

        return $this;
    }

    public function getOperationSdis(): ?bool
    {
        return $this->operationSdis;
    }

    public function setOperationSdis(?bool $operationSdis): self
    {
        $this->operationSdis = $operationSdis;

        return $this;
    }

    public function getRcci(): ?bool
    {
        return $this->rcci;
    }

    public function setRcci(?bool $rcci): self
    {
        $this->rcci = $rcci;

        return $this;
    }

    public function getRex(): ?string
    {
        return $this->rex;
    }

    public function setRex(?string $rex): self
    {
        $this->rex = $rex;

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

    public function getDureeDeplacement(): ?int
    {
        return $this->dureeDeplacement;
    }

    public function setDureeDeplacement(?int $dureeDeplacement): self
    {
        $this->dureeDeplacement = $dureeDeplacement;

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

    public function getNumeroIntervention(): ?int
    {
        return $this->numeroIntervention;
    }

    public function setNumeroIntervention(?int $numeroIntervention): self
    {
        $this->numeroIntervention = $numeroIntervention;

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
        return $this->dureeIntervention;
    }

    public function setDureeIntervention(?\DateTimeInterface $dureeIntervention): self
    {
        $this->dureeIntervention = $dureeIntervention;

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

    /**
     * Vérifie si ce dossier donne un avis pour l'établissement.
     *
     * Un dossier donne avis si sa nature fait partie de la liste définie
     * dans DossierNature::NATURES_DONNANT_AVIS.
     */
    public function donneAvis(): bool
    {
        $nature = $this->getNature();

        if (null === $nature) {
            return false;
        }

        return in_array($nature->getId(), DossierNature::NATURES_DONNANT_AVIS, true);
    }
}
