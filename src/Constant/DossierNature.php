<?php

namespace App\Constant;

/**
 * Constantes pour les natures de dossiers.
 *
 * Correspond à la table `dossiernatureliste` en base de données.
 */
final class DossierNature
{
    /**
     * Nature : Écheancier de travaux.
     */
    public const ECHEANCIER_TRAVAUX = 46;

    /**
     * Natures de dossiers donnant avis.
     *
     * @var int[]
     */
    public const NATURES_DONNANT_AVIS = [7, 16, 17, 19, 21, 23, 24, 47, 26, 28, 29, 48];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
