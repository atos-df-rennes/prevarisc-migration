<?php

namespace App\Entity;

use App\Repository\RubriqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RubriqueRepository::class)
 */
class Rubrique
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_RUBRIQUE", type="bigint")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="NOM", type="string", length=255)
     */
    private $nom;

    /**
     * @var ?bool
     *
     * @ORM\Column(name="DEFAULT_DISPLAY", type="boolean", nullable=true)
     */
    private $defaultDisplay;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $idx;

    /**
     * @var ?CapsuleRubrique
     *
     * @ORM\ManyToOne(targetEntity=CapsuleRubrique::class, inversedBy="rubriques")
     *
     * @ORM\JoinColumn(name="ID_CAPSULERUBRIQUE", referencedColumnName="ID_CAPSULERUBRIQUE", nullable=false)
     */
    private $capsuleRubrique;

    /**
     * @var Collection<int, Champ>
     *
     * @ORM\OneToMany(targetEntity=Champ::class, mappedBy="rubrique", orphanRemoval=true)
     */
    private $champs;

    public function __construct()
    {
        $this->champs = new ArrayCollection();
    }

    public function getId(): ?int
    {
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

    public function getDefaultDisplay(): ?bool
    {
        return $this->defaultDisplay;
    }

    public function setDefaultDisplay(?bool $defaultDisplay): self
    {
        $this->defaultDisplay = $defaultDisplay;

        return $this;
    }

    public function getIdx(): ?int
    {
        return $this->idx;
    }

    public function setIdx(?int $idx): self
    {
        $this->idx = $idx;

        return $this;
    }

    public function getCapsuleRubrique(): ?CapsuleRubrique
    {
        return $this->capsuleRubrique;
    }

    public function setCapsuleRubrique(?CapsuleRubrique $capsuleRubrique): self
    {
        $this->capsuleRubrique = $capsuleRubrique;

        return $this;
    }

    /**
     * @return Collection<int, Champ>
     */
    public function getChamps(): Collection
    {
        return $this->champs;
    }

    public function addChamp(Champ $champ): self
    {
        if (!$this->champs->contains($champ)) {
            $this->champs[] = $champ;
            $champ->setRubrique($this);
        }

        return $this;
    }

    public function removeChamp(Champ $champ): self
    {
        // set the owning side to null (unless already changed)
        if ($this->champs->removeElement($champ) && $champ->getRubrique() === $this) {
            $champ->setRubrique(null);
        }

        return $this;
    }
}
