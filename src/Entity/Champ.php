<?php

namespace App\Entity;

use App\Repository\ChampRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: ChampRepository::class)]
class Champ
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_CHAMP', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'NOM', type: 'string', length: 255, nullable: false)]
    private ?string $nom = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $tableau = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $idx = null;

    #[ORM\ManyToOne(targetEntity: TypeChamp::class)]
    #[ORM\JoinColumn(name: 'ID_TYPECHAMP', referencedColumnName: 'ID_TYPECHAMP', nullable: false)]
    private ?TypeChamp $type = null;

    #[ORM\ManyToOne(targetEntity: Rubrique::class, inversedBy: 'champs')]
    #[ORM\JoinColumn(name: 'ID_RUBRIQUE', referencedColumnName: 'ID_RUBRIQUE', nullable: false)]
    private ?Rubrique $rubrique = null;

    #[MaxDepth(1)]
    #[ORM\ManyToOne(targetEntity: Champ::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(name: 'ID_PARENT', referencedColumnName: 'ID_CHAMP', nullable: true)]
    private ?Champ $parent = null;

    /**
     * @var Collection<int, Champ>
     */
    #[MaxDepth(1)]
    #[ORM\OneToMany(targetEntity: Champ::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['idx' => 'ASC', 'id' => 'ASC'])]
    private Collection $enfants;

    /**
     * @var Collection<int, ChampValeurListe>
     */
    #[ORM\OneToMany(targetEntity: ChampValeurListe::class, mappedBy: 'champ', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['idx' => 'ASC', 'id' => 'ASC'])]
    private Collection $valeursListe;

    /**
     * @var Collection<int, Valeur>
     */
    #[ORM\OneToMany(targetEntity: Valeur::class, mappedBy: 'champ', orphanRemoval: true)]
    private Collection $valeurs;

    public function __construct()
    {
        $this->enfants = new ArrayCollection();
        $this->valeursListe = new ArrayCollection();
        $this->valeurs = new ArrayCollection();
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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getTableau(): ?bool
    {
        return $this->tableau;
    }

    public function setTableau(?bool $tableau): self
    {
        $this->tableau = $tableau;

        return $this;
    }

    public function getIdx(): ?int
    {
        return $this->idx;
    }

    public function setIdx(int $idx): self
    {
        $this->idx = $idx;

        return $this;
    }

    public function getType(): ?TypeChamp
    {
        return $this->type;
    }

    public function setType(?TypeChamp $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRubrique(): ?Rubrique
    {
        return $this->rubrique;
    }

    public function setRubrique(?Rubrique $rubrique): self
    {
        $this->rubrique = $rubrique;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(self $enfant): self
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants[] = $enfant;
            $enfant->setParent($this);
            $enfant->setRubrique($this->rubrique);
            $enfant->setIdx($this->enfants->count() - 1);
        }

        return $this;
    }

    public function removeEnfant(self $enfant): self
    {
        // set the owning side to null (unless already changed)
        if ($this->enfants->removeElement($enfant) && $enfant->getParent() === $this) {
            $enfant->setParent(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ChampValeurListe>
     */
    public function getValeursListe(): Collection
    {
        return $this->valeursListe;
    }

    public function addValeursListe(ChampValeurListe $valeursListe): self
    {
        if (!$this->valeursListe->contains($valeursListe)) {
            $this->valeursListe[] = $valeursListe;
            $valeursListe->setChamp($this);
            $valeursListe->setIdx($this->valeursListe->count() - 1);
        }

        return $this;
    }

    public function removeValeursListe(ChampValeurListe $valeursListe): self
    {
        // set the owning side to null (unless already changed)
        if ($this->valeursListe->removeElement($valeursListe) && $valeursListe->getChamp() === $this) {
            $valeursListe->setChamp(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Valeur>
     */
    public function getValeurs(): Collection
    {
        return $this->valeurs;
    }

    public function addValeur(Valeur $valeur): self
    {
        if (!$this->valeurs->contains($valeur)) {
            $this->valeurs[] = $valeur;
            $valeur->setChamp($this);
        }

        return $this;
    }

    public function removeValeur(Valeur $valeur): self
    {
        // set the owning side to null (unless already changed)
        if ($this->valeurs->removeElement($valeur) && $valeur->getChamp() === $this) {
            $valeur->setChamp(null);
        }

        return $this;
    }
}
