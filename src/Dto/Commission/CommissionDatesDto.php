<?php

namespace App\Dto\Commission;

use App\Entity\Commission;
use App\Entity\CommissionTypeEvenement;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la création/édition d'une commission avec ses dates.
 */
final class CommissionDatesDto
{
    /**
     * @var ?string
     *
     * @Assert\Length(max=255, maxMessage="Le libellé ne peut pas dépasser {{ limit }} caractères")
     */
    private $libelle;

    /**
     * @var ?Commission
     *
     * @Assert\NotNull(message="La commission est obligatoire")
     */
    private $commission;

    /**
     * @var ?CommissionTypeEvenement
     *
     * @Assert\NotNull(message="Le type d'événement est obligatoire")
     */
    private $typeEvenement;

    /**
     * @var Collection<int, DateCommissionItemDto>
     *
     * @Assert\Valid
     *
     * @Assert\Count(
     *     min=1,
     *     minMessage="Au moins une date est requise"
     * )
     */
    private $dates;

    /**
     * @var bool
     */
    private $withPeriodicite = false;

    /**
     * @var ?\DateTimeInterface
     */
    private $dateFin;

    /**
     * @var int
     */
    private $periodicite = 1;

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
     * @return Collection<int, DateCommissionItemDto>
     */
    public function getDates(): Collection
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
