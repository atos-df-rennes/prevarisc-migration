<?php

namespace App\Constant;

/**
 * Constantes pour les types de dossiers.
 *
 * Correspond à la table `dossiertype` en base de données.
 */
final class DossierType
{
    public const int ETUDE = 1;

    public const int GROUPE_DE_VISITE = 3;

    public const int COURRIER = 5;

    public const array TYPES_PRINCIPAUX = [1, 2, 3];

    public const array TYPES_VISITE = [2, 3];

    public const array TYPES_SANS_PRESCRIPTIONS = [5];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
