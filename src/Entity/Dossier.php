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
     * @var ?UtilisateurInformations
     *
     * @ORM\ManyToOne(targetEntity=UtilisateurInformations::class)
     *
     * @ORM\JoinColumn(name="CREATEUR_DOSSIER", referencedColumnName="ID_UTILISATEURINFORMATIONS", nullable=true)
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

    public function getAvisCommission(): ?Avis
    {
        return $this->avisCommission;
    }

    public function setAvisCommission(?Avis $avisCommission): self
    {
        $this->avisCommission = $avisCommission;

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

    public function getCreateur(): ?UtilisateurInformations
    {
        return $this->createur;
    }

    public function setCreateur(?UtilisateurInformations $createur): self
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
}
