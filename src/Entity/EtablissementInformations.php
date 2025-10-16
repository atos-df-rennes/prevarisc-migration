<?php

namespace App\Entity;

use App\Repository\EtablissementInformationsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EtablissementInformationsRepository::class)
 *
 * @ORM\Table(name="etablissementinformations")
 */
class EtablissementInformations
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_ETABLISSEMENTINFORMATIONS", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_ETABLISSEMENTINFORMATIONS", type="string", length=255, nullable=false)
     */
    private $libelle;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="DATE_ETABLISSEMENTINFORMATIONS", type="date", nullable=false)
     */
    private $date;

    /**
     * @var int
     *
     * @ORM\Column(name="LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS", type="boolean" )
     */
    private $localSommeil;

    /**
     * @var ?Etablissement
     *
     * @ORM\ManyToOne(targetEntity=Etablissement::class, inversedBy="informations")
     *
     * @ORM\JoinColumn(name="ID_ETABLISSEMENT", referencedColumnName="ID_ETABLISSEMENT", nullable=false, options={"unsigned":true})
     */
    private $etablissement;

    /**
     * @var EtablissementStatut
     *
     * @ORM\ManyToOne(targetEntity=EtablissementStatut::class)
     *
     * @ORM\JoinColumn(name="ID_STATUT", referencedColumnName="ID_STATUT", nullable=false, options={"unsigned":true})
     */
    private $statut;

    /**
     * @var ?EtablissementType
     *
     * @ORM\ManyToOne(targetEntity=EtablissementType::class)
     *
     * @ORM\JoinColumn(name="ID_TYPE", referencedColumnName="ID_TYPE", nullable=true, options={"unsigned":true})
     */
    private $type;

    /**
     * @var ?EtablissementCategorie
     *
     * @ORM\ManyToOne(targetEntity=EtablissementCategorie::class)
     *
     * @ORM\JoinColumn(name="ID_CATEGORIE", referencedColumnName="ID_CATEGORIE", nullable=true, options={"unsigned":true})
     */
    private $categorie;

    /**
     * @var ?IghClasse
     *
     * @ORM\ManyToOne(targetEntity=IghClasse::class)
     *
     * @ORM\JoinColumn(name="ID_CLASSE", referencedColumnName="ID_CLASSE", nullable=true, options={"unsigned":true})
     */
    private $classe;

    /**
     * @var ?Famille
     *
     * @ORM\ManyToOne(targetEntity=Famille::class)
     *
     * @ORM\JoinColumn(name="ID_FAMILLE", referencedColumnName="ID_FAMILLE", nullable=true)
     */
    private $famille;

    /**
     * @var ?Utilisateur
     *
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     *
     * @ORM\JoinColumn(name="UTILISATEUR_ETABLISSEMENTINFORMATIONS", referencedColumnName="ID_UTILISATEUR", nullable=true, options={"unsigned":true})
     */
    private $utilisateur;

    /**
     * @var Genre
     *
     * @ORM\ManyToOne(targetEntity=Genre::class)
     *
     * @ORM\JoinColumn(name="ID_GENRE", referencedColumnName="ID_GENRE", nullable=false, options={"unsigned":true})
     */
    private $genre;

    /**
     * @var Collection<int, Utilisateur>
     *
     * @ORM\JoinTable(
     *     name="etablissementinformationspreventionniste",
     *     joinColumns={@ORM\JoinColumn(name="ID_ETABLISSEMENTINFORMATIONS", referencedColumnName="ID_ETABLISSEMENTINFORMATIONS", options={"unsigned":true})},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_UTILISATEUR", referencedColumnName="ID_UTILISATEUR", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=Utilisateur::class)
     */
    private $preventionnistes;

    /**
     * @var Collection<int, EtablissementInformationsPlan>
     *
     * @ORM\OneToMany(targetEntity=EtablissementInformationsPlan::class, mappedBy="etablissementInformations", orphanRemoval=true)
     */
    private $plans;

    /**
     * @var Collection<int, EtablissementInformationsTypesActivitesSecondaires>
     *
     * @ORM\OneToMany(targetEntity=EtablissementInformationsTypesActivitesSecondaires::class, mappedBy="etablissementInformations", orphanRemoval=true)
     */
    private $typesActivitesSecondaires;

    /**
     * @var ?EtablissementActivite
     *
     * @ORM\ManyToOne(targetEntity=EtablissementActivite::class)
     *
     * @ORM\JoinColumn(name="ID_TYPEACTIVITE", referencedColumnName="ID_TYPEACTIVITE", nullable=true, options={"unsigned":true})
     */
    private $typeActivite;

    /**
     * @var ?Classement
     *
     * @ORM\ManyToOne(targetEntity=Classement::class)
     *
     * @ORM\JoinColumn(name="ID_CLASSEMENT", referencedColumnName="ID_CLASSEMENT", nullable=true, options={"unsigned":true})
     */
    private $classement;

    /**
     * @var ?Commission
     *
     * @ORM\ManyToOne(targetEntity=Commission::class)
     *
     * @ORM\JoinColumn(name="ID_COMMISSION", referencedColumnName="ID_COMMISSION", nullable=true, options={"unsigned":true})
     */
    private $commission;

    public function __construct()
    {
        $this->preventionnistes = new ArrayCollection();
        $this->plans = new ArrayCollection();
        $this->typesActivitesSecondaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getLocalSommeil(): ?int
    {
        return $this->localSommeil;
    }

    public function setLocalSommeil(?int $localSommeil): self
    {
        $this->localSommeil = $localSommeil;

        return $this;
    }

    public function getEtablissement(): ?Etablissement
    {
        return $this->etablissement;
    }

    public function setEtablissement(?Etablissement $etablissement): self
    {
        $this->etablissement = $etablissement;

        return $this;
    }

    public function getStatut(): ?EtablissementStatut
    {
        return $this->statut;
    }

    public function setStatut(EtablissementStatut $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getType(): ?EtablissementType
    {
        return $this->type;
    }

    public function setType(?EtablissementType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCategorie(): ?EtablissementCategorie
    {
        return $this->categorie;
    }

    public function setCategorie(?EtablissementCategorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getClasse(): ?IghClasse
    {
        return $this->classe;
    }

    public function setClasse(?IghClasse $classe): self
    {
        $this->classe = $classe;

        return $this;
    }

    public function getFamille(): ?Famille
    {
        return $this->famille;
    }

    public function setFamille(?Famille $famille): self
    {
        $this->famille = $famille;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getGenre(): ?Genre
    {
        return $this->genre;
    }

    public function setGenre(Genre $genre): self
    {
        $this->genre = $genre;

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
     * @return Collection<int, EtablissementInformationsPlan>
     */
    public function getPlans(): Collection
    {
        return $this->plans;
    }

    public function addPlan(EtablissementInformationsPlan $plan): self
    {
        if (!$this->plans->contains($plan)) {
            $this->plans[] = $plan;
            $plan->setEtablissementInformations($this);
        }

        return $this;
    }

    public function removePlan(EtablissementInformationsPlan $plan): self
    {
        // set the owning side to null (unless already changed)
        if ($this->plans->removeElement($plan) && $plan->getEtablissementInformations() === $this) {
            $plan->setEtablissementInformations(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, EtablissementInformationsTypesActivitesSecondaires>
     */
    public function getTypesActivitesSecondaires(): Collection
    {
        return $this->typesActivitesSecondaires;
    }

    public function addTypesActivitesSecondaire(EtablissementInformationsTypesActivitesSecondaires $typesActivitesSecondaire): self
    {
        if (!$this->typesActivitesSecondaires->contains($typesActivitesSecondaire)) {
            $this->typesActivitesSecondaires[] = $typesActivitesSecondaire;
            $typesActivitesSecondaire->setEtablissementInformations($this);
        }

        return $this;
    }

    public function removeTypesActivitesSecondaire(EtablissementInformationsTypesActivitesSecondaires $typesActivitesSecondaire): self
    {
        // set the owning side to null (unless already changed)
        if ($this->typesActivitesSecondaires->removeElement($typesActivitesSecondaire) && $typesActivitesSecondaire->getEtablissementInformations() === $this) {
            $typesActivitesSecondaire->setEtablissementInformations(null);
        }

        return $this;
    }

    public function getTypeActivite(): ?EtablissementActivite
    {
        return $this->typeActivite;
    }

    public function setTypeActivite(?EtablissementActivite $typeActivite): self
    {
        $this->typeActivite = $typeActivite;

        return $this;
    }

    public function getClassement(): ?Classement
    {
        return $this->classement;
    }

    public function setClassement(?Classement $classement): self
    {
        $this->classement = $classement;

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
}
