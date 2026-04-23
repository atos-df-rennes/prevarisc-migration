<?php

namespace App\Constant;

/**
 * Constantes pour les placements d'une pièce jointe d'établissement.
 * 0 = liste principale, 1 = diaporama, 2 = plans.
 */
final class PlacementEtablissementPj
{
    public const LISTE = 0;

    public const DIAPORAMA = 1;

    public const PLANS = 2;

    /** @var int[] */
    public const ALL = [self::LISTE, self::DIAPORAMA, self::PLANS];

    private function __construct()
    {
    }
}
