<?php

namespace App\Entity;

use App\Repository\DossierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
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
     * @ORM\JoinColumn(name="TYPE_DOSSIER", referencedColumnName="ID_DOSSIERTYPE", nullable=false, options={"unsigned":true})
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
     * @ORM\Column(type="datetime", nullable=true, name="DATEINSERT_DOSSIER")
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
     * @ORM\OneToMany(targetEntity=DossierDocumentManquant::class, mappedBy="dossier", orphanRemoval=true)
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
     * @var ?\App\Entity\Commission
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
     * Par défaut, ne récupère pas les établissements qui ont été supprimés.
     *
     * @return Collection<int, Etablissement>
     */
    public function getEtablissements(): Collection
    {
        $criteria = Criteria::create()->andWhere(Criteria::expr()->isNull('dateSuppression'));

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

    public function getAvisDiffere(): ?bool
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

    public function addDossiersLy(self $dossiersLy): self
    {
        if (!$this->dossiersLies->contains($dossiersLy)) {
            $this->dossiersLies[] = $dossiersLy;
        }

        return $this;
    }

    public function removeDossiersLy(self $dossiersLy): self
    {
        $this->dossiersLies->removeElement($dossiersLy);

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
            $dossiersAyantLie->addDossiersLy($this);
        }

        return $this;
    }

    public function removeDossiersAyantLie(self $dossiersAyantLie): self
    {
        if ($this->dossiersAyantLie->removeElement($dossiersAyantLie)) {
            $dossiersAyantLie->removeDossiersLy($this);
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

    public function getVerrou(): ?bool
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

    public function getServiceInstructeur(): ?string
    {
        return $this->serviceInstructeur;
    }

    public function setServiceInstructeur(?string $serviceInstructeur): self
    {
        $this->serviceInstructeur = $serviceInstructeur;

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
}
