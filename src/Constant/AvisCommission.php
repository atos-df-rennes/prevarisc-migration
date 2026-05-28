<?php

namespace App\Constant;

/**
 * Convertit les codes numériques d'avis en libellés textuels.
 *
 * Port direct de la fonction getAvis() du legacy (generationconvoc.phtml).
 */
final class AvisCommission
{
    public const FAVORABLE = 1;

    public const DEFAVORABLE = 2;

    public const FAVORABLE_AVEC_PRESCRIPTIONS = 3;

    /**
     * Retourne le libellé d'un code d'avis.
     *
     * @param int|string|null $avis Code numérique de l'avis
     *
     * @return string Libellé de l'avis ou chaîne vide si non reconnu
     */
    public static function getLibelle($avis): string
    {
        $avisInt = (int) $avis;

        switch ($avisInt) {
            case self::FAVORABLE:
                return 'Favorable';
            case self::DEFAVORABLE:
                return 'Défavorable';
            case self::FAVORABLE_AVEC_PRESCRIPTIONS:
                return 'Favorable avec prescriptions';
            default:
                return '';
        }
    }

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
