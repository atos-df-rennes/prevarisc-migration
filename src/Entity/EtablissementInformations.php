<?php

namespace App\Entity;

use App\Repository\EtablissementInformationsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtablissementInformationsRepository::class)]
#[ORM\Table(name: 'etablissementinformations')]
class EtablissementInformations
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_ETABLISSEMENTINFORMATIONS', type: 'bigint', options: ['unsigned' => true])]
    private $id;

    #[ORM\Column(name: 'LIBELLE_ETABLISSEMENTINFORMATIONS', type: 'string', length: 255, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'DATE_ETABLISSEMENTINFORMATIONS', type: 'date', nullable: false)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS', type: 'boolean', nullable: true)]
    private ?bool $localSommeil = null;

    #[ORM\Column(name: 'PERIODICITE_ETABLISSEMENTINFORMATIONS', type: 'smallint', nullable: true)]
    private ?int $periodicite = null;

    #[ORM\Column(name: 'ICPE_ETABLISSEMENTINFORMATIONS', type: 'boolean', nullable: true)]
    private ?bool $icpe = null;

    #[ORM\Column(name: 'R14320_ETABLISSEMENTINFORMATIONS', type: 'boolean', nullable: true)]
    private ?bool $r14320 = null;

    #[ORM\Column(name: 'DROITPUBLIC_ETABLISSEMENTINFORMATIONS', type: 'boolean', nullable: true)]
    private ?bool $droitPublic = null;

    #[ORM\Column(name: 'EFFECTIFHABITATION_ETABLISSEMENTINFORMATIONS', type: 'integer', nullable: true)]
    private ?int $effectifHabitation = null;

    #[ORM\Column(name: 'EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS', type: 'integer', nullable: true)]
    private ?int $effectifCaravane = null;

    #[ORM\Column(name: 'EFFECTIFEMPLACEMENTNU_ETABLISSEMENTINFORMATIONS', type: 'integer', nullable: true)]
    private ?int $effectifEmplacementNu = null;

    #[ORM\Column(name: 'EFFECTIFDIVERS_ETABLISSEMENTINFORMATIONS', type: 'integer', nullable: true)]
    private ?int $effectifDivers = null;

    #[ORM\Column(name: 'EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS', type: 'integer', nullable: true)]
    private ?int $effectifPublic = null;

    #[ORM\Column(name: 'EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS', type: 'integer', nullable: true)]
    private ?int $effectifPersonnel = null;

    #[ORM\Column(name: 'EFFECTIFHEBERGE_ETABLISSEMENTINFORMATIONS', type: 'integer', nullable: true)]
    private ?int $effectifHeberge = null;

    #[ORM\Column(name: 'EFFECTIFJUSTIFIANTCLASSEMENT_ETABLISSEMENTINFORMATIONS', type: 'integer', nullable: true)]
    private ?int $effectifJustifiantClassement = null;

    #[ORM\ManyToOne(targetEntity: Etablissement::class, inversedBy: 'informations')]
    #[ORM\JoinColumn(name: 'ID_ETABLISSEMENT', referencedColumnName: 'ID_ETABLISSEMENT', nullable: false, options: ['unsigned' => true])]
    private ?Etablissement $etablissement = null;

    #[ORM\ManyToOne(targetEntity: EtablissementStatut::class)]
    #[ORM\JoinColumn(name: 'ID_STATUT', referencedColumnName: 'ID_STATUT', nullable: false, options: ['unsigned' => true])]
    private ?EtablissementStatut $statut = null;

    #[ORM\ManyToOne(targetEntity: EtablissementType::class)]
    #[ORM\JoinColumn(name: 'ID_TYPE', referencedColumnName: 'ID_TYPE', nullable: true, options: ['unsigned' => true])]
    private ?EtablissementType $type = null;

    #[ORM\ManyToOne(targetEntity: EtablissementCategorie::class)]
    #[ORM\JoinColumn(name: 'ID_CATEGORIE', referencedColumnName: 'ID_CATEGORIE', nullable: true, options: ['unsigned' => true])]
    private ?EtablissementCategorie $categorie = null;

    #[ORM\ManyToOne(targetEntity: IghClasse::class)]
    #[ORM\JoinColumn(name: 'ID_CLASSE', referencedColumnName: 'ID_CLASSE', nullable: true, options: ['unsigned' => true])]
    private ?IghClasse $classe = null;

    #[ORM\ManyToOne(targetEntity: Famille::class)]
    #[ORM\JoinColumn(name: 'ID_FAMILLE', referencedColumnName: 'ID_FAMILLE', nullable: true)]
    private ?Famille $famille = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'UTILISATEUR_ETABLISSEMENTINFORMATIONS', referencedColumnName: 'ID_UTILISATEUR', nullable: true, options: ['unsigned' => true])]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: Genre::class)]
    #[ORM\JoinColumn(name: 'ID_GENRE', referencedColumnName: 'ID_GENRE', nullable: false, options: ['unsigned' => true])]
    private ?Genre $genre = null;

    /**
     * @var Collection<int, Utilisateur>
     */
    #[ORM\JoinTable(name: 'etablissementinformationspreventionniste')]
    #[ORM\JoinColumn(name: 'ID_ETABLISSEMENTINFORMATIONS', referencedColumnName: 'ID_ETABLISSEMENTINFORMATIONS', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_UTILISATEUR', referencedColumnName: 'ID_UTILISATEUR', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: Utilisateur::class)]
    private Collection $preventionnistes;

    /**
     * @var Collection<int, EtablissementInformationsPlan>
     */
    #[ORM\OneToMany(targetEntity: EtablissementInformationsPlan::class, mappedBy: 'etablissementInformations', orphanRemoval: true)]
    private Collection $plans;

    /**
     * @var Collection<int, EtablissementInformationsTypesActivitesSecondaires>
     */
    #[ORM\OneToMany(targetEntity: EtablissementInformationsTypesActivitesSecondaires::class, mappedBy: 'etablissementInformations', orphanRemoval: true)]
    private Collection $typesActivitesSecondaires;

    /**
     * @var Collection<int, EtablissementInformationsRubriques>
     */
    #[ORM\OneToMany(targetEntity: EtablissementInformationsRubriques::class, mappedBy: 'etablissementInformations', orphanRemoval: true)]
    private Collection $rubriquesIcpe;

    #[ORM\ManyToOne(targetEntity: EtablissementActivite::class)]
    #[ORM\JoinColumn(name: 'ID_TYPEACTIVITE', referencedColumnName: 'ID_TYPEACTIVITE', nullable: true, options: ['unsigned' => true])]
    private ?EtablissementActivite $typeActivite = null;

    #[ORM\ManyToOne(targetEntity: Classement::class)]
    #[ORM\JoinColumn(name: 'ID_CLASSEMENT', referencedColumnName: 'ID_CLASSEMENT', nullable: true, options: ['unsigned' => true])]
    private ?Classement $classement = null;

    #[ORM\ManyToOne(targetEntity: Commission::class)]
    #[ORM\JoinColumn(name: 'ID_COMMISSION', referencedColumnName: 'ID_COMMISSION', nullable: true, options: ['unsigned' => true])]
    private ?Commission $commission = null;

    public function __construct()
    {
        $this->preventionnistes = new ArrayCollection();
        $this->plans = new ArrayCollection();
        $this->typesActivitesSecondaires = new ArrayCollection();
        $this->rubriquesIcpe = new ArrayCollection();
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

    public function getLocalSommeil(): bool
    {
        return $this->localSommeil ?? false;
    }

    public function setLocalSommeil(?bool $localSommeil): self
    {
        $this->localSommeil = $localSommeil;

        return $this;
    }

    public function getPeriodicite(): ?int
    {
        return $this->periodicite;
    }

    public function setPeriodicite(?int $periodicite): self
    {
        $this->periodicite = $periodicite;

        return $this;
    }

    public function getIcpe(): bool
    {
        return $this->icpe ?? false;
    }

    public function setIcpe(?bool $icpe): self
    {
        $this->icpe = $icpe;

        return $this;
    }

    public function getR14320(): bool
    {
        return $this->r14320 ?? false;
    }

    public function setR14320(?bool $r14320): self
    {
        $this->r14320 = $r14320;

        return $this;
    }

    public function getDroitPublic(): bool
    {
        return $this->droitPublic ?? false;
    }

    public function setDroitPublic(?bool $droitPublic): self
    {
        $this->droitPublic = $droitPublic;

        return $this;
    }

    public function getEffectifHabitation(): ?int
    {
        return $this->effectifHabitation;
    }

    public function setEffectifHabitation(?int $effectifHabitation): self
    {
        $this->effectifHabitation = $effectifHabitation;

        return $this;
    }

    public function getEffectifCaravane(): ?int
    {
        return $this->effectifCaravane;
    }

    public function setEffectifCaravane(?int $effectifCaravane): self
    {
        $this->effectifCaravane = $effectifCaravane;

        return $this;
    }

    public function getEffectifEmplacementNu(): ?int
    {
        return $this->effectifEmplacementNu;
    }

    public function setEffectifEmplacementNu(?int $effectifEmplacementNu): self
    {
        $this->effectifEmplacementNu = $effectifEmplacementNu;

        return $this;
    }

    public function getEffectifDivers(): ?int
    {
        return $this->effectifDivers;
    }

    public function setEffectifDivers(?int $effectifDivers): self
    {
        $this->effectifDivers = $effectifDivers;

        return $this;
    }

    public function getEffectifPublic(): ?int
    {
        return $this->effectifPublic;
    }

    public function setEffectifPublic(?int $effectifPublic): self
    {
        $this->effectifPublic = $effectifPublic;

        return $this;
    }

    public function getEffectifPersonnel(): ?int
    {
        return $this->effectifPersonnel;
    }

    public function setEffectifPersonnel(?int $effectifPersonnel): self
    {
        $this->effectifPersonnel = $effectifPersonnel;

        return $this;
    }

    public function getEffectifHeberge(): ?int
    {
        return $this->effectifHeberge;
    }

    public function setEffectifHeberge(?int $effectifHeberge): self
    {
        $this->effectifHeberge = $effectifHeberge;

        return $this;
    }

    public function getEffectifJustifiantClassement(): ?int
    {
        return $this->effectifJustifiantClassement;
    }

    public function setEffectifJustifiantClassement(?int $effectifJustifiantClassement): self
    {
        $this->effectifJustifiantClassement = $effectifJustifiantClassement;

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
        $criteria = Criteria::create(true)->andWhere(Criteria::expr()->eq('actif', true));

        // PHPStan n'a pas l'extension Doctrine et n'est donc pas au courant que ça fonctionne bien.
        /* @phpstan-ignore-next-line */
        return $this->preventionnistes->matching($criteria);
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
     * Supprime tous les préventionnistes de la fiche (actifs et inactifs).
     */
    public function clearPreventionnistes(): self
    {
        $this->preventionnistes->clear();

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

    /**
     * @return Collection<int, EtablissementInformationsRubriques>
     */
    public function getRubriquesIcpe(): Collection
    {
        return $this->rubriquesIcpe;
    }

    public function addRubriqueIcpe(EtablissementInformationsRubriques $rubriqueIcpe): self
    {
        if (!$this->rubriquesIcpe->contains($rubriqueIcpe)) {
            $this->rubriquesIcpe[] = $rubriqueIcpe;
            $rubriqueIcpe->setEtablissementInformations($this);
        }

        return $this;
    }

    public function removeRubriqueIcpe(EtablissementInformationsRubriques $rubriqueIcpe): self
    {
        if ($this->rubriquesIcpe->removeElement($rubriqueIcpe) && $rubriqueIcpe->getEtablissementInformations() === $this) {
            $rubriqueIcpe->setEtablissementInformations(null);
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
