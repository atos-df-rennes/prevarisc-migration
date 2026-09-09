<?php

namespace App\Entity;

use App\Entity\Trait\GetIdOrFailTrait;
use App\Repository\CapsuleRubriqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CapsuleRubriqueRepository::class)]
#[ORM\Table(name: 'capsulerubrique')]
class CapsuleRubrique
{
    use GetIdOrFailTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_CAPSULERUBRIQUE', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'NOM_INTERNE', type: Types::STRING, length: 255, nullable: false)]
    private ?string $nomInterne = null;

    #[ORM\Column(name: 'NOM', type: Types::STRING, length: 255, nullable: false)]
    private ?string $nom = null;

    /**
     * @var Collection<int, Rubrique>
     */
    #[ORM\OneToMany(targetEntity: Rubrique::class, mappedBy: 'capsuleRubrique', orphanRemoval: true)]
    #[ORM\OrderBy(['idx' => 'ASC', 'id' => 'ASC'])]
    private Collection $rubriques;

    public function __construct()
    {
        $this->rubriques = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomInterne(): ?string
    {
        return $this->nomInterne;
    }

    public function setNomInterne(string $nomInterne): self
    {
        $this->nomInterne = $nomInterne;

        return $this;
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

    /**
     * @return Collection<int, Rubrique>
     */
    public function getRubriques(): Collection
    {
        return $this->rubriques;
    }

    public function addRubrique(Rubrique $rubrique): self
    {
        if (!$this->rubriques->contains($rubrique)) {
            $this->rubriques[] = $rubrique;
            $rubrique->setCapsuleRubrique($this);
        }

        return $this;
    }

    public function removeRubrique(Rubrique $rubrique): self
    {
        // set the owning side to null (unless already changed)
        if ($this->rubriques->removeElement($rubrique) && $rubrique->getCapsuleRubrique() === $this) {
            $rubrique->setCapsuleRubrique(null);
        }

        return $this;
    }
}
