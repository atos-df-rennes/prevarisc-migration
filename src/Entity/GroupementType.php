<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\GroupementTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GroupementTypeRepository::class)]
#[ORM\Table(name: 'groupementtype')]
class GroupementType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_GROUPEMENTTYPE', type: Types::BIGINT, options: ['unsigned' => true])]
    private ?string $id = null;

    #[ORM\Column(name: 'LIBELLE_GROUPEMENTTYPE', type: Types::STRING, length: 255, nullable: false)]
    #[Groups(['api_response'])]
    private ?string $libelle = null;

    /**
     * @var Collection<int, Groupement>
     */
    #[ORM\OneToMany(targetEntity: Groupement::class, mappedBy: 'type')]
    #[ORM\OrderBy(['libelle' => 'ASC'])]
    private Collection $groupements;

    public function __construct()
    {
        $this->groupements = new ArrayCollection();
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

    /**
     * @return Collection<int, Groupement>
     */
    public function getGroupements(): Collection
    {
        return $this->groupements;
    }

    public function addGroupement(Groupement $groupement): self
    {
        if (!$this->groupements->contains($groupement)) {
            $this->groupements[] = $groupement;
            $groupement->setType($this);
        }

        return $this;
    }

    public function removeGroupement(Groupement $groupement): self
    {
        // set the owning side to null (unless already changed)
        if ($this->groupements->removeElement($groupement) && $groupement->getType() === $this) {
            $groupement->setType(null);
        }

        return $this;
    }
}
