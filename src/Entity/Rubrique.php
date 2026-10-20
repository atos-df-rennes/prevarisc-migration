<?php

namespace App\Entity;

use App\Entity\Trait\GetIdOrFailTrait;
use App\Repository\RubriqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RubriqueRepository::class)]
class Rubrique
{
    use GetIdOrFailTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_RUBRIQUE', type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'NOM', type: Types::STRING, length: 255, nullable: false)]
    private ?string $nom = null;

    #[ORM\Column(name: 'DEFAULT_DISPLAY', type: Types::BOOLEAN, nullable: true)]
    private ?bool $defaultDisplay = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $idx = null;

    #[ORM\ManyToOne(targetEntity: CapsuleRubrique::class, inversedBy: 'rubriques')]
    #[ORM\JoinColumn(name: 'ID_CAPSULERUBRIQUE', referencedColumnName: 'ID_CAPSULERUBRIQUE', nullable: false)]
    private ?CapsuleRubrique $capsuleRubrique = null;

    /**
     * @var Collection<int, Champ>
     */
    #[ORM\OneToMany(targetEntity: Champ::class, mappedBy: 'rubrique', orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['idx' => 'ASC', 'id' => 'ASC'])]
    private Collection $champs;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $ouverte = false;

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

    public function setIdx(int $idx): self
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

    /**
     * Retourne uniquement les champs racine (sans parent) de la rubrique, dans l'ordre idx.
     *
     * En base de données, les champs enfants partagent la même FK rubrique que leur parent.
     * Cette méthode filtre la collection en mémoire pour n'exposer que les champs de premier niveau.
     *
     * @return Collection<int, Champ>
     */
    public function getRootChamps(): Collection
    {
        return $this->champs->filter(fn (Champ $champ): bool => null === $champ->getParent());
    }

    public function addChamp(Champ $champ): self
    {
        if (!$this->champs->contains($champ)) {
            $this->champs[] = $champ;
            $champ->setRubrique($this);
            $champ->setIdx($this->champs->filter(fn (Champ $champ): bool => null === $champ->getParent())->count() - 1);
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

    public function getOuverte(): ?bool
    {
        return $this->ouverte;
    }

    public function setOuverte(bool $ouverte): self
    {
        $this->ouverte = $ouverte;

        return $this;
    }
}
