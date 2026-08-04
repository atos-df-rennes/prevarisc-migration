<?php

namespace App\Entity;

use App\Repository\CommissionMembreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommissionMembreRepository::class)]
#[ORM\Table(name: 'commissionmembre')]
class CommissionMembre
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_COMMISSIONMEMBRE', type: 'bigint', options: ['unsigned' => true])]
    private $id;

    #[ORM\Column(name: 'LIBELLE_COMMISSIONMEMBRE', type: 'string', length: 255, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'PRESENCE_COMMISSIONMEMBRE', type: 'integer', nullable: false)]
    private ?int $presence = null;

    #[ORM\Column(name: 'COURRIER_CONVOCATIONVISITE', type: 'string', length: 255, nullable: true)]
    private ?string $courrierConvocationVisite = null;

    #[ORM\Column(name: 'COURRIER_CONVOCATIONSALLE', type: 'string', length: 255, nullable: true)]
    private ?string $courrierConvocationSalle = null;

    #[ORM\Column(name: 'COURRIER_ODJ', type: 'string', length: 255, nullable: true)]
    private ?string $courrierOrdreDuJour = null;

    #[ORM\Column(name: 'COURRIER_PV', type: 'string', length: 255, nullable: true)]
    private ?string $courrierProcesVerbal = null;

    #[ORM\ManyToOne(targetEntity: Groupement::class)]
    #[ORM\JoinColumn(name: 'ID_GROUPEMENT', referencedColumnName: 'ID_GROUPEMENT', options: ['unsigned' => true])]
    private ?Groupement $groupement = null;

    #[ORM\ManyToOne(targetEntity: UtilisateurInformations::class)]
    #[ORM\JoinColumn(name: 'ID_UTILISATEURINFORMATIONS', referencedColumnName: 'ID_UTILISATEURINFORMATIONS', options: ['unsigned' => true])]
    private ?UtilisateurInformations $utilisateurInformations = null;

    #[ORM\ManyToOne(targetEntity: Commission::class, inversedBy: 'membres')]
    #[ORM\JoinColumn(name: 'ID_COMMISSION', referencedColumnName: 'ID_COMMISSION', nullable: false, options: ['unsigned' => true])]
    private ?Commission $commission = null;

    /**
     * @var Collection<int, EtablissementCategorie>
     */
    #[ORM\JoinTable(name: 'commissionmembrecategorie')]
    #[ORM\JoinColumn(name: 'ID_COMMISSIONMEMBRE', referencedColumnName: 'ID_COMMISSIONMEMBRE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_CATEGORIE', referencedColumnName: 'ID_CATEGORIE', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: EtablissementCategorie::class)]
    private Collection $categories;

    /**
     * @var Collection<int, EtablissementActivite>
     */
    #[ORM\JoinTable(name: 'commissionmembretypeactivite')]
    #[ORM\JoinColumn(name: 'ID_COMMISSIONMEMBRE', referencedColumnName: 'ID_COMMISSIONMEMBRE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_TYPEACTIVITE', referencedColumnName: 'ID_TYPEACTIVITE', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: EtablissementActivite::class)]
    private Collection $types;

    /**
     * @var Collection<int, IghClasse>
     */
    #[ORM\JoinTable(name: 'commissionmembreclasse')]
    #[ORM\JoinColumn(name: 'ID_COMMISSIONMEMBRE', referencedColumnName: 'ID_COMMISSIONMEMBRE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_CLASSE', referencedColumnName: 'ID_CLASSE', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: IghClasse::class)]
    private Collection $classes;

    /**
     * @var Collection<int, DossierNatureListe>
     */
    #[ORM\JoinTable(name: 'commissionmembredossiernature')]
    #[ORM\JoinColumn(name: 'ID_COMMISSIONMEMBRE', referencedColumnName: 'ID_COMMISSIONMEMBRE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_DOSSIERNATURE', referencedColumnName: 'ID_DOSSIERNATURE')]
    #[ORM\ManyToMany(targetEntity: DossierNatureListe::class)]
    private Collection $naturesDossier;

    /**
     * @var Collection<int, DossierType>
     */
    #[ORM\JoinTable(name: 'commissionmembredossiertype')]
    #[ORM\JoinColumn(name: 'ID_COMMISSIONMEMBRE', referencedColumnName: 'ID_COMMISSIONMEMBRE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_DOSSIERTYPE', referencedColumnName: 'ID_DOSSIERTYPE', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: DossierType::class)]
    private Collection $typesDossier;

    #[ORM\Column(name: 'MANIFESTATION_COMMISSIONMEMBRE', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $manifestation;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->types = new ArrayCollection();
        $this->classes = new ArrayCollection();
        $this->naturesDossier = new ArrayCollection();
        $this->typesDossier = new ArrayCollection();
        $this->manifestation = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdOrFail(): int
    {
        if (null === $this->id) {
            throw new \LogicException(sprintf('%s must be persisted before accessing ID', self::class));
        }

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

    public function getPresence(): ?int
    {
        return $this->presence;
    }

    public function setPresence(int $presence): self
    {
        $this->presence = $presence;

        return $this;
    }

    public function getCourrierConvocationVisite(): ?string
    {
        return $this->courrierConvocationVisite;
    }

    public function setCourrierConvocationVisite(?string $courrierConvocationVisite): self
    {
        $this->courrierConvocationVisite = $courrierConvocationVisite;

        return $this;
    }

    public function getCourrierConvocationSalle(): ?string
    {
        return $this->courrierConvocationSalle;
    }

    public function setCourrierConvocationSalle(?string $courrierConvocationSalle): self
    {
        $this->courrierConvocationSalle = $courrierConvocationSalle;

        return $this;
    }

    public function getCourrierOrdreDuJour(): ?string
    {
        return $this->courrierOrdreDuJour;
    }

    public function setCourrierOrdreDuJour(?string $courrierOrdreDuJour): self
    {
        $this->courrierOrdreDuJour = $courrierOrdreDuJour;

        return $this;
    }

    public function getCourrierProcesVerbal(): ?string
    {
        return $this->courrierProcesVerbal;
    }

    public function setCourrierProcesVerbal(?string $courrierProcesVerbal): self
    {
        $this->courrierProcesVerbal = $courrierProcesVerbal;

        return $this;
    }

    public function getGroupement(): ?Groupement
    {
        return $this->groupement;
    }

    public function setGroupement(?Groupement $groupement): self
    {
        $this->groupement = $groupement;

        return $this;
    }

    public function getUtilisateurInformations(): ?UtilisateurInformations
    {
        return $this->utilisateurInformations;
    }

    public function setUtilisateurInformations(?UtilisateurInformations $utilisateurInformations): self
    {
        $this->utilisateurInformations = $utilisateurInformations;

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

    /**
     * @return Collection<int, EtablissementCategorie>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(EtablissementCategorie $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    public function removeCategory(EtablissementCategorie $category): self
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, EtablissementActivite>
     */
    public function getTypes(): Collection
    {
        return $this->types;
    }

    public function addType(EtablissementActivite $type): self
    {
        if (!$this->types->contains($type)) {
            $this->types[] = $type;
        }

        return $this;
    }

    public function removeType(EtablissementActivite $type): self
    {
        $this->types->removeElement($type);

        return $this;
    }

    /**
     * @return Collection<int, IghClasse>
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    public function addClass(IghClasse $class): self
    {
        if (!$this->classes->contains($class)) {
            $this->classes[] = $class;
        }

        return $this;
    }

    public function removeClass(IghClasse $class): self
    {
        $this->classes->removeElement($class);

        return $this;
    }

    /**
     * @return Collection<int, DossierNatureListe>
     */
    public function getNaturesDossier(): Collection
    {
        return $this->naturesDossier;
    }

    public function addNaturesDossier(DossierNatureListe $naturesDossier): self
    {
        if (!$this->naturesDossier->contains($naturesDossier)) {
            $this->naturesDossier[] = $naturesDossier;
        }

        return $this;
    }

    public function removeNaturesDossier(DossierNatureListe $naturesDossier): self
    {
        $this->naturesDossier->removeElement($naturesDossier);

        return $this;
    }

    /**
     * @return Collection<int, DossierType>
     */
    public function getTypesDossier(): Collection
    {
        return $this->typesDossier;
    }

    public function addTypesDossier(DossierType $typesDossier): self
    {
        if (!$this->typesDossier->contains($typesDossier)) {
            $this->typesDossier[] = $typesDossier;
        }

        return $this;
    }

    public function removeTypesDossier(DossierType $typesDossier): self
    {
        $this->typesDossier->removeElement($typesDossier);

        return $this;
    }

    public function isManifestation(): bool
    {
        return $this->manifestation;
    }

    public function setManifestation(bool $manifestation): self
    {
        $this->manifestation = $manifestation;

        return $this;
    }
}
