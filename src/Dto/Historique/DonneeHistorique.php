<?php

namespace App\Dto\Historique;

class DonneeHistorique
{
    /** @var int|null */
    private $date;

    private ?string $valeur;

    private ?Auteur $auteur;

    private ?int $fin = null;

    /**
     * @param int|null $date
     */
    public function __construct(
        $date,
        ?string $valeur,
        ?Auteur $auteur
    ) {
        $this->date = $date;
        $this->valeur = $valeur;
        $this->auteur = $auteur;
        $this->fin = null; // Set to int via setFin() when the value period ends
    }

    /**
     * @return int|null
     */
    public function getDate()
    {
        return $this->date;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function getAuteur(): ?Auteur
    {
        return $this->auteur;
    }

    public function setFin(?int $fin): self
    {
        $this->fin = $fin;

        return $this;
    }

    public function getFin(): ?int
    {
        return $this->fin;
    }
}
