<?php

namespace App\Service;

final class Changement {
    public const BALISES = [
        '{activitePrincipaleEtablissement}' => [
            'description' => "L'activité principale de l'établissement",
            'model' => 'informations',
            'champ' => 'LIBELLE_TYPEACTIVITE_PRINCIPAL',
        ],
        '{categorieEtablissement}' => [
            'description' => "La catégorie de l'etablissement",
            'model' => 'informations',
            'champ' => 'LIBELLE_CATEGORIE',
        ],
        '{etablissementAvis}' => [
            'description' => "L'avis de l'établissement",
            'model' => 'avis',
            'champ' => '',
        ],
        '{etablissementLibelle}' => [
            'description' => "Le libelle de l'établissement",
            'model' => 'informations',
            'champ' => 'LIBELLE_ETABLISSEMENTINFORMATIONS',
        ],
        '{etablissementNumeroId}' => [
            'description' => "Le numéro Id de l'établissement",
            'model' => 'general',
            'champ' => 'NUMEROID_ETABLISSEMENT',
        ],
        '{etablissementStatut}' => [
            'description' => "Le statut (Ouvert ou Fermé) de l'établissement",
            'model' => 'informations',
            'champ' => 'LIBELLE_STATUT',
        ],
        '{typePrincipalEtablissement}' => [
            'description' => "Le type principal de l'établissement",
            'model' => 'informations',
            'champ' => 'LIBELLE_TYPE_PRINCIPAL',
        ],
    ];

    public function getBalises(): array
    {
        return self::BALISES;
    }
}
