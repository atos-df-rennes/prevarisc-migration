<?php

namespace App\Entity;

use App\Repository\CommissionRegleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommissionRegleRepository::class)
 *
 * @ORM\Table(name="commissionregle")
 */
class CommissionRegle
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_REGLE", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var ?Groupement
     *
     * @ORM\ManyToOne(targetEntity=Groupement::class)
     *
     * @ORM\JoinColumn(name="ID_GROUPEMENT", referencedColumnName="ID_GROUPEMENT", nullable=true)
     */
    private $groupement;

    /**
     * @var ?Commission
     *
     * @ORM\ManyToOne(targetEntity=Commission::class, inversedBy="regles")
     *
     * @ORM\JoinColumn(name="ID_COMMISSION", referencedColumnName="ID_COMMISSION", nullable=false, options={"unsigned":true}, onDelete="CASCADE")
     */
    private $commission;

    /**
     * @var ?Commune
     *
     * @ORM\ManyToOne(targetEntity=Commune::class)
     *
     * @ORM\JoinColumn(name="NUMINSEE_COMMUNE", referencedColumnName="NUMINSEE_COMMUNE", nullable=true, onDelete="CASCADE")
     */
    private $commune;

    /**
     * @var Collection<int, EtablissementCategorie>
     *
     * @ORM\JoinTable(
     *     name="commissionreglecategorie",
     *     joinColumns={@ORM\JoinColumn(name="ID_REGLE", referencedColumnName="ID_REGLE", options={"unsigned":true})},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_CATEGORIE", referencedColumnName="ID_CATEGORIE", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=EtablissementCategorie::class)
     */
    private $categories;

    /**
     * @var Collection<int, IghClasse>
     *
     * @ORM\JoinTable(
     *     name="commissionregleclasse",
     *     joinColumns={@ORM\JoinColumn(name="ID_REGLE", referencedColumnName="ID_REGLE", options={"unsigned":true})},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_CLASSE", referencedColumnName="ID_CLASSE", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=IghClasse::class)
     */
    private $classes;

    /**
     * @var Collection<int, EtablissementType>
     *
     * @ORM\JoinTable(
     *     name="commissionregletype",
     *     joinColumns={@ORM\JoinColumn(name="ID_REGLE", referencedColumnName="ID_REGLE", options={"unsigned":true})},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ID_TYPE", referencedColumnName="ID_TYPE", options={"unsigned":true})}
     * )
     *
     * @ORM\ManyToMany(targetEntity=EtablissementType::class)
     */
    private $types;

    /**
     * @var Collection<int, CommissionRegleEtudeVisite>
     *
     * @ORM\OneToMany(targetEntity=CommissionRegleEtudeVisite::class, mappedBy="regle", cascade={"persist"}, orphanRemoval=true)
     *
     * @ORM\JoinColumn(name="ID_REGLE", referencedColumnName="ID_REGLE", options={"unsigned":true})
     */
    private $etudeVisite;

    /**
     * @var Collection<int, CommissionRegleLocalSommeil>
     *
     * @ORM\OneToMany(targetEntity=CommissionRegleLocalSommeil::class, mappedBy="regle", cascade={"persist"}, orphanRemoval=true)
     *
     * @ORM\JoinColumn(name="ID_REGLE", referencedColumnName="ID_REGLE", options={"unsigned":true})
     */
    private $localSommeil;

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

    /**
     * Setter utilisé par le formulaire (DataTransformer) pour remplacer la collection.
     * La comparaison par valeur entière évite les doublons sur la clé composite.
     *
     * @param Collection<int, CommissionRegleEtudeVisite> $newValues
     */
    public function setEtudeVisite(Collection $newValues): self
    {
        $newInts = [];
        foreach ($newValues as $ev) {
            $newInts[] = (int) $ev->getEtudeVisite();
        }

        // Retirer les entrées qui ne sont plus sélectionnées
        foreach ($this->etudeVisite->toArray() as $existing) {
            if (!in_array((int) $existing->getEtudeVisite(), $newInts, true)) {
                $this->removeEtudeVisite($existing);
            }
        }

        // Ajouter les nouvelles valeurs absentes de la collection courante
        $currentInts = [];
        foreach ($this->etudeVisite as $ev) {
            $currentInts[] = (int) $ev->getEtudeVisite();
        }

        foreach ($newValues as $ev) {
            if (!in_array((int) $ev->getEtudeVisite(), $currentInts, true)) {
                $this->addEtudeVisite($ev);
            }
        }

        return $this;
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
        // set the owning side to null (unless already changed)
        if ($this->etudeVisite->removeElement($etudeVisite) && $etudeVisite->getRegle() === $this) {
            $etudeVisite->setRegle(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, CommissionRegleLocalSommeil>
     */
    public function getLocalSommeil(): Collection
    {
        return $this->localSommeil;
    }

    /**
     * Setter utilisé par le formulaire (DataTransformer) pour remplacer la collection.
     * La comparaison par valeur entière évite les doublons sur la clé composite.
     *
     * @param Collection<int, CommissionRegleLocalSommeil> $newValues
     */
    public function setLocalSommeil(Collection $newValues): self
    {
        $newInts = [];
        foreach ($newValues as $ls) {
            $newInts[] = (int) $ls->getLocalSommeil();
        }

        // Retirer les entrées qui ne sont plus sélectionnées
        foreach ($this->localSommeil->toArray() as $existing) {
            if (!in_array((int) $existing->getLocalSommeil(), $newInts, true)) {
                $this->removeLocalSommeil($existing);
            }
        }

        // Ajouter les nouvelles valeurs absentes de la collection courante
        $currentInts = [];
        foreach ($this->localSommeil as $ls) {
            $currentInts[] = (int) $ls->getLocalSommeil();
        }

        foreach ($newValues as $ls) {
            if (!in_array((int) $ls->getLocalSommeil(), $currentInts, true)) {
                $this->addLocalSommeil($ls);
            }
        }

        return $this;
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
        // set the owning side to null (unless already changed)
        if ($this->localSommeil->removeElement($localSommeil) && $localSommeil->getRegle() === $this) {
            $localSommeil->setRegle(null);
        }

        return $this;
    }
}
