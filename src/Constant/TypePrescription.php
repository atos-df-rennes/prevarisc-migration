<?php

namespace App\Constant;

final class TypePrescription
{
    public const REGLEMENTAIRE = 0;

    public const EXPLOITATION = 1;

    public const AMELIORATION = 2;

    public const ALL = [
        self::REGLEMENTAIRE,
        self::EXPLOITATION,
        self::AMELIORATION,
    ];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
