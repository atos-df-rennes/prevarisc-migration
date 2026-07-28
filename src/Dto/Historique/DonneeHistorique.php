<?php

namespace App\Dto\Historique;

class DonneeHistorique
{
    private ?int $fin = null;

    public function __construct(private readonly ?int $date, private readonly ?string $valeur, private readonly ?Auteur $auteur)
    {
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
