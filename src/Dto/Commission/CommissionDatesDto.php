<?php

namespace App\Dto\Commission;

use App\Entity\Commission;
use App\Entity\CommissionTypeEvenement;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * DTO pour la création/édition d'une commission avec ses dates.
 */
final class CommissionDatesDto
{
    #[Assert\Length(max: 255, maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères')]
    private ?string $libelle = null;

    #[Assert\NotNull(message: 'La commission est obligatoire')]
    private ?Commission $commission = null;

    #[Assert\NotNull(message: "Le type d'événement est obligatoire")]
    private ?CommissionTypeEvenement $typeEvenement = null;

    /**
     * @var ArrayCollection<int, DateCommissionItemDto>
     */
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'Au moins une date est requise')]
    private ArrayCollection $dates;

    private bool $withPeriodicite = false;

    private ?\DateTimeInterface $dateFin = null;

    private int $periodicite = 1;

    /**
     * Valide que dateFin est renseignée quand withPeriodicite est activée.
     */
    #[Assert\Callback]
    public function validatePeriodicite(ExecutionContextInterface $context): void
    {
        if ($this->withPeriodicite && !$this->dateFin instanceof \DateTimeInterface) {
            $context->buildViolation('La date de fin est obligatoire pour la répétition périodique')
                ->atPath('dateFin')
                ->addViolation();
        }
    }

    public function __construct()
    {
        $this->dates = new ArrayCollection();
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(Commission $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    public function getTypeEvenement(): ?CommissionTypeEvenement
    {
        return $this->typeEvenement;
    }

    public function setTypeEvenement(CommissionTypeEvenement $typeEvenement): self
    {
        $this->typeEvenement = $typeEvenement;

        return $this;
    }

    /**
     * @return ArrayCollection<int, DateCommissionItemDto>
     */
    public function getDates(): ArrayCollection
    {
        return $this->dates;
    }

    public function addDate(DateCommissionItemDto $date): self
    {
        if (!$this->dates->contains($date)) {
            $this->dates[] = $date;
        }

        return $this;
    }

    public function removeDate(DateCommissionItemDto $date): self
    {
        $this->dates->removeElement($date);

        return $this;
    }

    public function isWithPeriodicite(): bool
    {
        return $this->withPeriodicite;
    }

    public function setWithPeriodicite(bool $withPeriodicite): self
    {
        $this->withPeriodicite = $withPeriodicite;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getPeriodicite(): int
    {
        return $this->periodicite;
    }

    public function setPeriodicite(int $periodicite): self
    {
        $this->periodicite = $periodicite;

        return $this;
    }
}
