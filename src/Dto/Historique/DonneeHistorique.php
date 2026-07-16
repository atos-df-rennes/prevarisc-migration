<?php

namespace App\Dto\Historique;

class DonneeHistorique
{
    private ?int $date;

    private ?string $valeur;

    private ?Auteur $auteur;

    private ?int $fin = null;

    public function __construct(
        ?int $date,
        ?string $valeur,
        ?Auteur $auteur
    ) {
        $this->date = $date;
        $this->valeur = $valeur;
        $this->auteur = $auteur;
    }

    public function getDate(): ?int
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
