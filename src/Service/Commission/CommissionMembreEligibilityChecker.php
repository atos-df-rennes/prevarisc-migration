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
        // Vérification que les données établissement existent
        if (!isset($dossier['infosEtab']['informations'])) {
            return false;
        }

        $informations = $dossier['infosEtab']['informations'];

        // Condition sur le type de dossier (obligatoire dans tous les cas)
        if (empty($membre['dossiertypes'][$dossier['TYPE_DOSSIER']]['ID_COMMISSIONMEMBRE'])) {
            return false;
        }

        // Condition genre manifestation
        if (8 == $informations['ID_GENRE'] && !empty($membre['manifestation'])) {
            return true;
        }

        // Condition catégorie ET type d'activité
        if (
            !empty($membre['categories'][$informations['ID_CATEGORIE']]['ID_COMMISSIONMEMBRE'])
            && !empty($membre['types'][$informations['ID_TYPEACTIVITE']]['ID_COMMISSIONMEMBRE'])
        ) {
            return true;
        }

        // Condition classe
        return !empty($membre['classes'][$informations['ID_CLASSE']]['ID_COMMISSIONMEMBRE']);
    }
}
