<?php

namespace App\Constant;

/**
 * Constantes pour les natures de dossiers.
 *
 * Correspond à la table `dossiernatureliste` en base de données.
 */
final class DossierNature
{
    public const ECHEANCIER_TRAVAUX = 46;

    /**
     * Natures Visite Avant Ouverture (VAO).
     *
     * @var int[]
     */
    public const VISITE_AVANT_OUVERTURE = [47, 48]; // VAO, VAO groupe

    /**
     * @var int[]
     */
    public const NATURES_DONNANT_AVIS = [7, 16, 17, 19, 21, 23, 24, 26, 28, 29, 47, 48];

    /**
     * @var int[]
     */
    public const NATURES_EXPLOITATION = [7, 16, 17, 19];

    /**
     * @var int[]
     */
    public const RECEPTIONS_TRAVAUX = [20, 25];

    /**
     * Natures Réception Travaux et Visite Avant Ouverture (interchangeables).
     *
     * @var int[]
     */
    public const NATURES_RT_VAO = [20, 47, 25, 48]; // RT, VAO, RT groupe, VAO groupe

    public const NATURES_SANS_DESCRIPTIF = [3, 9];

    public const NATURES_DESCRIPTIF_TRAVAUX = [1, 2];

    public const NATURES_SANS_PRESCRIPTIONS = [3, 5, 31, 32, 40, 41, 42, 44];

    public const NATURES_SANS_CONTACTS = [40, 41, 42, 44];

    public const NATURES_ALERTE_RECEPTION_TRAVAUX = [1, 2, 12, 13];

    /**
     * Natures déclenchant la copie des textes applicables de l'établissement vers le dossier.
     *
     * @var int[]
     */
    public const NATURES_COPIE_TEXTES_APPLICABLES = [21, 22, 24, 26, 27, 29];

    /**
     * Natures "visite périodique" — déclenchent la copie des documents de la dernière VP.
     *
     * @var int[]
     */
    public const VISITE_PERIODIQUE = [21, 26];

    /**
     * Natures "levée" (prescriptions ou avis définitif) — déclenchent la création de 5 docs "Attestation de".
     *
     * @var int[]
     */
    public const LEVEE = [7, 19];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
