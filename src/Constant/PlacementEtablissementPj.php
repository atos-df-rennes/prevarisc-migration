<?php

namespace App\Constant;

/**
 * Constantes pour les placements d'une pièce jointe d'établissement.
 */
final class PlacementEtablissementPj
{
    public const LISTE = null;

    public const DIAPORAMA = 1;

    public const PLANS = 0;

    public const MISES_EN_AVANT = [self::DIAPORAMA, self::PLANS];

    private function __construct()
    {
    }
}
