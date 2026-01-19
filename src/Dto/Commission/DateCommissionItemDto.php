<?php

namespace App\Dto\Commission;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO représentant un item de date (date + heures) dans la collection.
 */
final class DateCommissionItemDto
{
    /**
     * @var ?\DateTime
     *
     * @Assert\NotNull(message="La date est obligatoire")
     */
    private $date;

    /**
     * @var ?\DateTime
     *
     * @Assert\NotNull(message="L'heure de début est obligatoire")
     */
    private $heureDebut;

    /**
     * @var ?\DateTime
     *
     * @Assert\NotNull(message="L'heure de fin est obligatoire")
     */
    private $heureFin;

    public function __construct(?\DateTime $date = null) {
        $this->date = $date;

        if (null !== $date) {
            $heureDebut = clone $date;
            $this->heureDebut = $heureDebut->setTime(9, 0);

            $heureFin = clone $date;
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
