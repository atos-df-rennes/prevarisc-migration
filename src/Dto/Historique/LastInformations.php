<?php

namespace App\Dto\Historique;

use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementStatut;
use App\Entity\EtablissementType;
use App\Entity\Famille;
use App\Entity\IghClasse;

class LastInformations {
    /** @var ?\App\Entity\EtablissementStatut */
    private $statut;

    /** @var ?\App\Entity\EtablissementType */
    private $type;

    /** @var ?\App\Entity\EtablissementCategorie */
    private $categorie;

    /** @var ?string */
    private $libelle;

    /** @var ?\App\Entity\IghClasse */
    private $classe;

    /** @var ?\App\Entity\Famille */
    private $famille;

    public function __construct() {
        $this->statut = null;
        $this->type = null;
        $this->categorie = null;
        $this->libelle = null;
        $this->classe = null;
        $this->famille = null;
    }

    public function getStatut(): ?EtablissementStatut {
        return $this->statut;
    }

    public function setStatut(?EtablissementStatut $statut): self {
        $this->statut = $statut;

        return $this;
    }

    public function getType(): ?EtablissementType {
        return $this->type;
    }

    public function setType(?EtablissementType $type): self {
        $this->type = $type;

        return $this;
    }

    public function getCategorie(): ?EtablissementCategorie {
        return $this->categorie;
    }

    public function setCategorie(?EtablissementCategorie $categorie): self {
        $this->categorie = $categorie;

        return $this;
    }

    public function getLibelle(): ?string {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self {
        $this->libelle = $libelle;

        return $this;
    }

    public function getClasse(): ?IghClasse {
        return $this->classe;
    }

    public function setClasse(?IghClasse $classe): self {
        $this->classe = $classe;

        return $this;
    }

    public function getFamille(): ?Famille {
        return $this->famille;
    }

    public function setFamille(?Famille $famille): self {
        $this->famille = $famille;

        return $this;
    }
}
