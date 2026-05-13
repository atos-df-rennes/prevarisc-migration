<?php

namespace App\Dto\Commission;

/**
 * DTO représentant une commission avec son type et ses fichiers ODT disponibles.
 * Utilisé par DossierRapportController pour passer des données typées au template Twig
 * en lieu et place d'un tableau associatif brut (REF-R-07).
 */
class CommissionOdtDto
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string|null
     */
    public $libelle;

    /**
     * @var int
     */
    public $typeId;

    /**
     * @var string|null
     */
    public $typeLibelle;

    /**
     * @var string[]
     */
    public $listeFichier;

    /**
     * @param string[] $listeFichier
     */
    public function __construct(
        int $id,
        ?string $libelle,
        int $typeId,
        ?string $typeLibelle,
        array $listeFichier
    ) {
        $this->id = $id;
        $this->libelle = $libelle;
        $this->typeId = $typeId;
        $this->typeLibelle = $typeLibelle;
        $this->listeFichier = $listeFichier;
    }
}
