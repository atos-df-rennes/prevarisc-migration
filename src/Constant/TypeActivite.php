<?php

namespace App\Constant;

/**
 * Constantes pour les types d'activités d'établissements.
 *
 * Correspond à la table `typeactivite` en base de données.
 */
final class TypeActivite
{
    /**
     * Centre Commercial (CC) — active le bloc cellulesPresc dans les rapports ODT.
     *
     * Legacy : DossierController::ID_ACTIVITE_CENTRE_COMMERCIAL = 29.
     */
    public const int CENTRE_COMMERCIAL = 29;

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
