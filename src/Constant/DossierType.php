<?php

namespace App\Constant;

/**
 * Constantes pour les types de dossiers.
 *
 * Correspond à la table `dossiertype` en base de données.
 */
final class DossierType
{
    public const ETUDE = 1;

    public const GROUPE_DE_VISITE = 3;

    public const COURRIER = 5;

    public const TYPES_PRINCIPAUX = [1, 2, 3];

    public const TYPES_VISITE = [2, 3];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
