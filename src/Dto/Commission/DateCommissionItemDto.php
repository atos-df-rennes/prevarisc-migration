<?php

namespace App\Dto\Commission;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO représentant un item de date (date + heures) dans la collection.
 */
final class DateCommissionItemDto
{
    /**
     * @var \DateTimeInterface|null
     *
     * @Assert\NotNull(message="La date est obligatoire")
     */
    private $date;

    /**
     * @var \DateTimeInterface|null
     *
     * @Assert\NotNull(message="L'heure de début est obligatoire")
     */
    private $heureDebut;

    /**
     * @var \DateTimeInterface|null
     *
     * @Assert\NotNull(message="L'heure de fin est obligatoire")
     */
    private $heureFin;

    private function __construct(
        ?\DateTimeInterface $date = null,
        ?\DateTimeInterface $heureDebut = null,
        ?\DateTimeInterface $heureFin = null
    ) {
        $this->date = $date;
        $this->heureDebut = $heureDebut;
        $this->heureFin = $heureFin;
    }

    public static function fromDateTime(\DateTimeInterface $date): self
    {
        $dateDebut = clone $date;
        $dateDebut->setTime(9, 0);

        $dateFin = clone $date;
        $dateFin->setTime(18, 0);

        return new self($date, $dateDebut, $dateFin);
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?\DateTimeInterface $heureDebut): self
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTimeInterface $heureFin): self
    {
        $this->heureFin = $heureFin;

        return $this;
    }
}
