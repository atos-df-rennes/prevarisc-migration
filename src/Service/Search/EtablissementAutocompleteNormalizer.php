<?php

namespace App\Service\Search;

use App\Constant\GenreEtablissement;

/**
 * Normalise les lignes brutes retournées par EtablissementSearchService::etablissements()
 * en un payload compact destiné aux autocomplétions (TomSelect).
 *
 * Reproduit la résolution de commune effectuée côté JavaScript par le legacy
 * (nav-main.phtml, dossier/liees.phtml, etablissement/edit.phtml) : la commune affichée
 * dépend du genre de l'établissement.
 */
class EtablissementAutocompleteNormalizer
{
    /**
     * @param array<int|string, array<string, mixed>> $rows Lignes SQL brutes de la recherche
     *
     * @return array<int, array{id: int, numeroId: string|null, libelle: string, genre: string|null, genreId: int|null, commune: string|null}>
     */
    public function normalize(array $rows): array
    {
        $results = [];

        foreach ($rows as $row) {
            $genreId = isset($row['ID_GENRE']) && is_scalar($row['ID_GENRE']) ? (int) $row['ID_GENRE'] : null;

            $results[] = [
                'id' => isset($row['ID_ETABLISSEMENT']) && is_scalar($row['ID_ETABLISSEMENT']) ? (int) $row['ID_ETABLISSEMENT'] : 0,
                'numeroId' => $this->extractString($row, 'NUMEROID_ETABLISSEMENT'),
                'libelle' => (string) $this->extractString($row, 'LIBELLE_ETABLISSEMENTINFORMATIONS'),
                'genre' => $this->extractString($row, 'LIBELLE_GENRE'),
                'genreId' => $genreId,
                'commune' => $this->resolveCommune($row, $genreId),
            ];
        }

        return $results;
    }

    /**
     * Détermine la commune à afficher selon le genre de l'établissement.
     *
     * @param array<string, mixed> $row
     */
    private function resolveCommune(array $row, ?int $genreId): ?string
    {
        switch ($genreId) {
            case GenreEtablissement::SITE:
                $key = 'LIBELLE_COMMUNE_ADRESSE_SITE';

                break;

            case GenreEtablissement::CELLULE:
                $key = 'LIBELLE_COMMUNE_ADRESSE_CELLULE';

                break;

            default:
                $key = 'LIBELLE_COMMUNE_ADRESSE_DEFAULT';
        }

        return $this->extractString($row, $key);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractString(array $row, string $key): ?string
    {
        if (!isset($row[$key]) || !is_scalar($row[$key])) {
            return null;
        }

        $value = trim((string) $row[$key]);

        return '' === $value ? null : $value;
    }
}
