<?php

namespace App\Dto\Historique;

class DonneeHistorique {
    /** @var false|int */
    private $date;

    /** @var ?string */
    private $valeur;

    /** @var ?Auteur */
    private $auteur;

    /** @var false|int */
    private $fin;

    /**
     * @param false|int $date
     */
    public function __construct(
        $date,
        ?string $valeur,
        ?Auteur $auteur
    ) {
        $this->date = $date;
        $this->valeur = $valeur;
        $this->auteur = $auteur;
        $this->fin = false;
    }

    /**
     * @return false|int
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

    /**
     * @param false|int $fin
     */
    public function setFin($fin): self
    {
        $this->fin = $fin;

        return $this;
    }

    /**
     * @return false|int
     */
    public function getFin() {
        return $this->fin;
    }
}
