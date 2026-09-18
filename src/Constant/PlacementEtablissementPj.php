<?php

namespace App\Constant;

/**
 * Constantes pour les placements d'une pièce jointe d'établissement.
 */
final class PlacementEtablissementPj
{
    public const null LISTE = null;

    public const int DIAPORAMA = 1;

    public const int PLANS = 0;

    public const array MISES_EN_AVANT = [self::DIAPORAMA, self::PLANS];

    private function __construct()
    {
    }
}
