<?php

namespace App\Constant;

/**
 * Constantes pour les natures de dossiers.
 *
 * Correspond à la table `dossiernatureliste` en base de données.
 *
 * @todo: Lister les natures utilisées (à ajouter en constantes) pour les référencer dans les tableaux.
 */
final class DossierNature
{
    public const ECHEANCIER_TRAVAUX = 46;

    public const LEVEE_PRESCRIPTIONS = 7;

    public const LEVEE_AVIS_DEFAVORABLE = 19;

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
     * Natures de type "visite" pour lesquelles la date pertinente est DATEVISITE_DOSSIER.
     *
     * Legacy : getNaturesVisites() dans Service_Dossier.
     * Ces natures nécessitent que DATEVISITE_DOSSIER soit renseignée pour donner avis.
     * Les autres natures donnant avis (études + groupes) nécessitent DATECOMM_DOSSIER.
     *
     * @var int[]
     */
    public const NATURES_VISITES_COMMISSION = [21, 23, 24, 47];

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
     * Natures "visite inopinée" (individuelle et groupe).
     *
     * @var int[]
     */
    public const VISITES_INOPINEES = [24, 29];

    /**
     * Natures "visite de contrôle" (individuelle et groupe).
     *
     * @var int[]
     */
    public const VISITES_CONTROLE = [23, 28];

    /**
     * Natures déclenchant la logique CC (cellulesPresc) dans les rapports ODT :
     * VISITE_PERIODIQUE (21,26) + VISITES_INOPINEES (24,29) + VISITES_CONTROLE (23,28).
     *
     * Legacy : DossierController::$natureCC = [21, 26, 24, 29, 23, 28].
     *
     * @var int[]
     */
    public const NATURES_CC = [21, 26, 24, 29, 23, 28];

    /**
     * Natures Études avec documents de visite (levées de prescriptions, etc.).
     *
     * @var int[]
     */
    public const LEVEES = [7, 19];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
