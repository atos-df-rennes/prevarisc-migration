<?php

namespace App\Entity;

use App\Repository\CommissionTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommissionTypeRepository::class)]
#[ORM\Table(name: 'commissiontype')]
class CommissionType
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_COMMISSIONTYPE', type: 'integer', length: 2, options: ['unsigned' => true])]
    private $id;

    #[Assert\NotBlank(message: 'Le type de commission est obligatoire.')]
    #[Assert\Length(max: 50, maxMessage: 'Le libellé du type de commission ne peut pas dépasser {{ limit }} caractères.')]
    #[ORM\Column(name: 'LIBELLE_COMMISSIONTYPE', type: 'string', length: 50, nullable: false)]
    private ?string $libelle = null;

    /**
     * @var Collection<int, Commission>
     */
    #[ORM\OneToMany(targetEntity: Commission::class, mappedBy: 'type')]
    private Collection $commissions;

    public function __construct()
    {
        $this->commissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdOrFail(): int
    {
        if ($this->id === null) {
            throw new \LogicException(sprintf(
                '%s must be persisted before accessing ID',
                self::class
            ));
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

    /**
     * @return Collection<int, Commission>
     */
    public function getCommissions(): Collection
    {
        return $this->commissions;
    }

    public function addCommission(Commission $commission): self
    {
        if (!$this->commissions->contains($commission)) {
            $this->commissions[] = $commission;
            $commission->setType($this);
        }

        return $this;
    }

    public function removeCommission(Commission $commission): self
    {
        // set the owning side to null (unless already changed)
        if ($this->commissions->removeElement($commission) && $commission->getType() === $this) {
            $commission->setType(null);
        }

        return $this;
    }
}
