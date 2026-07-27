<?php

namespace App\Dto\Commission;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO représentant un item de date (date + heures) dans la collection.
 */
final class DateCommissionItemDto
{
    /**
     * @Assert\NotNull(message="L'heure de début est obligatoire")
     */
    private ?\DateTime $heureDebut = null;

    /**
     * @Assert\NotNull(message="L'heure de fin est obligatoire")
     */
    private ?\DateTime $heureFin = null;

    /**
     * @param \DateTime|null $date La date de la commission
     *
     * @Assert\NotNull(message="La date est obligatoire")
     */
    public function __construct(
        /**
         * @Assert\NotNull(message="La date est obligatoire")
         */
        private ?\DateTime $date = null)
    {
        if (null !== $this->date) {
            $heureDebut = clone $this->date;
            $this->heureDebut = $heureDebut->setTime(9, 0);

            $heureFin = clone $this->date;
            $this->heureFin = $heureFin->setTime(18, 0);
        }
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getHeureDebut(): ?\DateTime
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTime $heureDebut): self
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTime
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTime $heureFin): self
    {
        $this->heureFin = $heureFin;

        return $this;
    }
}
