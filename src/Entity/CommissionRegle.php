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

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->classes = new ArrayCollection();
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
}
