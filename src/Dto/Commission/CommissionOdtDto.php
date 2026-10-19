<?php

namespace App\Dto\Commission;

/**
 * DTO représentant une commission avec son type et ses fichiers ODT disponibles.
 * Utilisé par DossierRapportController pour passer des données typées au template Twig
 * en lieu et place d'un tableau associatif brut (REF-R-07).
 */
final class CommissionOdtDto
{
    /**
     * @param string[] $listeFichier
     */
    public function __construct(public int $id, public ?string $libelle, public int $typeId, public ?string $typeLibelle, public array $listeFichier)
    {
    }
}
