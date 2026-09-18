<?php

namespace App\Constant;

/**
 * Constantes pour les avis.
 *
 * Correspond à la table `avis` en base de données.
 */
final class Avis
{
    public const int FAVORABLE = 1;

    public const int DEFAVORABLE = 2;

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
