<?php

namespace App\Service\Commission;

/**
 * Détermine si un dossier concerne un membre de commission.
 *
 * Port direct de la logique de filtrage répétée dans les templates legacy :
 * generationconvoc.phtml, generationodj.phtml.
 *
 * Un dossier concerne un membre si :
 * - L'établissement est de genre "manifestation" (ID_GENRE == 8) ET le membre a le flag manifestation
 * OU
 * - La catégorie de l'établissement ET le type d'activité sont dans les compétences du membre
 * OU
 * - La classe de l'établissement est dans les compétences du membre
 * ET DANS TOUS LES CAS :
 * - Le type de dossier est dans les compétences du membre
 */
class CommissionMembreEligibilityChecker
{
    /**
     * Vérifie si un dossier concerne un membre de commission.
     *
     * @param array<string, mixed> $membre  Données du membre (avec clés 'manifestation', 'categories', 'types', 'classes', 'dossiertypes')
     * @param array<string, mixed> $dossier Données enrichies du dossier (avec clé 'infosEtab' contenant 'informations')
     */
    public function isDossierConcerneMembre(array $membre, array $dossier): bool
    {
        /** @var array<string, mixed> $infosEtab */
        $infosEtab = $dossier['infosEtab'] ?? [];
        if (!isset($infosEtab['informations'])) {
            return false;
        }

        /** @var array<string, mixed> $informations */
        $informations = $infosEtab['informations'];
        /** @var array<int|string, array<string, mixed>> $dossierTypes */
        $dossierTypes = $membre['dossiertypes'] ?? [];
        /** @var int|string $typeDossier */
        $typeDossier = $dossier['TYPE_DOSSIER'] ?? 0;

        if (empty($dossierTypes[$typeDossier]['ID_COMMISSIONMEMBRE'])) {
            return false;
        }

        /** @var int|string $genreId */
        $genreId = $informations['ID_GENRE'] ?? 0;
        if (8 === (int) $genreId && !empty($membre['manifestation'])) {
            return true;
        }

        /** @var array<int|string, array<string, mixed>> $categories */
        $categories = $membre['categories'] ?? [];
        /** @var array<int|string, array<string, mixed>> $types */
        $types = $membre['types'] ?? [];
        /** @var int|string $categorieId */
        $categorieId = $informations['ID_CATEGORIE'] ?? 0;
        /** @var int|string $typeActiviteId */
        $typeActiviteId = $informations['ID_TYPEACTIVITE'] ?? 0;

        if (
            !empty($categories[$categorieId]['ID_COMMISSIONMEMBRE'])
            && !empty($types[$typeActiviteId]['ID_COMMISSIONMEMBRE'])
        ) {
            return true;
        }

        /** @var array<int|string, array<string, mixed>> $classes */
        $classes = $membre['classes'] ?? [];
        /** @var int|string $classeId */
        $classeId = $informations['ID_CLASSE'] ?? 0;

        return !empty($classes[$classeId]['ID_COMMISSIONMEMBRE']);
    }
}
