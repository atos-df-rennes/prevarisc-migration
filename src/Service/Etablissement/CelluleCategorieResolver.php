<?php

namespace App\Service\Etablissement;

use App\Constant\GenreEtablissement;

class CelluleCategorieResolver
{
    /**
     * @param array<string, mixed>      $informations
     * @param array<string, mixed>|null $parentInformations Null si aucun parent non supprimé
     *
     * @return array<string, mixed>
     */
    public static function resolve(array $informations, ?array $parentInformations): array
    {
        /** @var int|string|null $genre */
        $genre = $informations['ID_GENRE'] ?? null;
        if (GenreEtablissement::CELLULE === (int) $genre && null !== $parentInformations) {
            $informations['ID_CATEGORIE'] = $parentInformations['ID_CATEGORIE'] ?? null;
            $informations['LIBELLE_CATEGORIE'] = $parentInformations['LIBELLE_CATEGORIE'] ?? null;
        }

        return $informations;
    }
}
