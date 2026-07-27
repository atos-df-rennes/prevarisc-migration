<?php

namespace App\Entity;

use App\Repository\CommissionRegleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommissionRegleRepository::class)]
#[ORM\Table(name: 'commissionregle')]
class CommissionRegle
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_REGLE', type: 'bigint', options: ['unsigned' => true])]
    private $id;

    #[ORM\ManyToOne(targetEntity: Groupement::class)]
    #[ORM\JoinColumn(name: 'ID_GROUPEMENT', referencedColumnName: 'ID_GROUPEMENT', nullable: true)]
    private ?Groupement $groupement = null;

    #[ORM\ManyToOne(targetEntity: Commission::class, inversedBy: 'regles')]
    #[ORM\JoinColumn(name: 'ID_COMMISSION', referencedColumnName: 'ID_COMMISSION', nullable: false, options: ['unsigned' => true], onDelete: 'CASCADE')]
    private ?Commission $commission = null;

    #[ORM\ManyToOne(targetEntity: Commune::class)]
    #[ORM\JoinColumn(name: 'NUMINSEE_COMMUNE', referencedColumnName: 'NUMINSEE_COMMUNE', nullable: true, onDelete: 'CASCADE')]
    private ?Commune $commune = null;

    /**
     * @var Collection<int, EtablissementCategorie>
     */
    #[ORM\JoinTable(name: 'commissionreglecategorie')]
    #[ORM\JoinColumn(name: 'ID_REGLE', referencedColumnName: 'ID_REGLE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_CATEGORIE', referencedColumnName: 'ID_CATEGORIE', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: EtablissementCategorie::class)]
    private Collection $categories;

    /**
     * @var Collection<int, IghClasse>
     */
    #[ORM\JoinTable(name: 'commissionregleclasse')]
    #[ORM\JoinColumn(name: 'ID_REGLE', referencedColumnName: 'ID_REGLE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_CLASSE', referencedColumnName: 'ID_CLASSE', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: IghClasse::class)]
    private Collection $classes;

    /**
     * @var Collection<int, EtablissementType>
     */
    #[ORM\JoinTable(name: 'commissionregletype')]
    #[ORM\JoinColumn(name: 'ID_REGLE', referencedColumnName: 'ID_REGLE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_TYPE', referencedColumnName: 'ID_TYPE', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: EtablissementType::class)]
    private Collection $types;

    /**
     * @var Collection<int, CommissionRegleEtudeVisite>
     */
    #[ORM\OneToMany(targetEntity: CommissionRegleEtudeVisite::class, mappedBy: 'regle', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'ID_REGLE', referencedColumnName: 'ID_REGLE', options: ['unsigned' => true])]
    private Collection $etudeVisite;

    /**
     * @var Collection<int, CommissionRegleLocalSommeil>
     */
    #[ORM\OneToMany(targetEntity: CommissionRegleLocalSommeil::class, mappedBy: 'regle', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'ID_REGLE', referencedColumnName: 'ID_REGLE', options: ['unsigned' => true])]
    private Collection $localSommeil;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->classes = new ArrayCollection();
        $this->types = new ArrayCollection();
        $this->etudeVisite = new ArrayCollection();
        $this->localSommeil = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    public function getCommune(): ?Commune
    {
        return $this->commune;
    }

    public function setCommune(?Commune $commune): self
    {
        $this->commune = $commune;

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
     * @return Collection<int, EtablissementType>
     */
    public function getTypes(): Collection
    {
        return $this->types;
    }

    public function addType(EtablissementType $type): self
    {
        if (!$this->types->contains($type)) {
            $this->types[] = $type;
        }

        return $this;
    }

    public function removeType(EtablissementType $type): self
    {
        $this->types->removeElement($type);

        return $this;
    }

    /**
     * @return Collection<int, CommissionRegleEtudeVisite>
     */
    public function getEtudeVisite(): Collection
    {
        return $this->etudeVisite;
    }

    public function addEtudeVisite(CommissionRegleEtudeVisite $etudeVisite): self
    {
        if (!$this->etudeVisite->contains($etudeVisite)) {
            $this->etudeVisite[] = $etudeVisite;
            $etudeVisite->setRegle($this);
        }

        return $this;
    }

    public function removeEtudeVisite(CommissionRegleEtudeVisite $etudeVisite): self
    {
        $this->etudeVisite->removeElement($etudeVisite);

        return $this;
    }

    /**
     * @return Collection<int, CommissionRegleLocalSommeil>
     */
    public function getLocalSommeil(): Collection
    {
        return $this->localSommeil;
    }

    public function addLocalSommeil(CommissionRegleLocalSommeil $localSommeil): self
    {
        if (!$this->localSommeil->contains($localSommeil)) {
            $this->localSommeil[] = $localSommeil;
            $localSommeil->setRegle($this);
        }

        return $this;
    }

    public function removeLocalSommeil(CommissionRegleLocalSommeil $localSommeil): self
    {
        $this->localSommeil->removeElement($localSommeil);

        return $this;
    }
}
