<?php

namespace App\Dto\Historique;

class Informations {
    /** @var DonneeHistorique[] */
    private $statut;

    /** @var DonneeHistorique[] */
    private $type;

    /** @var DonneeHistorique[] */
    private $classe;

    /** @var DonneeHistorique[] */
    private $famille;

    /** @var DonneeHistorique[] */
    private $libelle;

    /** @var DonneeHistorique[] */
    private $categorie;

    /** @var DonneeHistorique[] */
    private $avis;

    public function __construct() {
        $this->statut = [];
        $this->type = [];
        $this->classe = [];
        $this->famille = [];
        $this->libelle = [];
        $this->categorie = [];
        $this->avis = [];
    }

    public function isEmpty(): bool
    {
        return $this->statut === []
            && $this->type === []
            && $this->classe === []
            && $this->famille === []
            && $this->libelle === []
            && $this->categorie === []
        ;
    }

    /**
     * @return DonneeHistorique[]
     */
    public function getStatut(): array {
        return $this->statut;
    }

    public function addStatut(DonneeHistorique $statut): self {
        $this->statut[] = $statut;

        return $this;
    }

    /**
     * @return DonneeHistorique[]
     */
    public function getType(): array {
        return $this->type;
    }

    public function addType(DonneeHistorique $type): self {
        $this->type[] = $type;

        return $this;
    }

    /**
     * @return DonneeHistorique[]
     */
    public function getClasse(): array {
        return $this->classe;
    }

    public function addClasse(DonneeHistorique $classe): self {
        $this->classe[] = $classe;

        return $this;
    }

    /**
     * @return DonneeHistorique[]
     */
    public function getFamille(): array {
        return $this->famille;
    }

    public function addFamille(DonneeHistorique $famille): self {
        $this->famille[] = $famille;

        return $this;
    }

    /**
     * @return DonneeHistorique[]
     */
    public function getLibelle(): array {
        return $this->libelle;
    }

    public function addLibelle(DonneeHistorique $libelle): self {
        $this->libelle[] = $libelle;

        return $this;
    }

    /**
     * @return DonneeHistorique[]
     */
    public function getCategorie(): array {
        return $this->categorie;
    }

    public function addCategorie(DonneeHistorique $categorie): self {
        $this->categorie[] = $categorie;

        return $this;
    }

    /**
     * @return DonneeHistorique[]
     */
    public function getAvis(): array
    {
        return $this->avis;
    }

    /**
     * @param DonneeHistorique[] $avis
     */
    public function setAvis(array $avis): self {
        $this->avis = $avis;

        return $this;
    }
}
