<?php

namespace App\Constant;

/**
 * Constantes pour les avis.
 *
 * Correspond à la table `avis` en base de données.
 */
final class Avis
{
    public const FAVORABLE = 1;

    public const DEFAVORABLE = 2;

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
