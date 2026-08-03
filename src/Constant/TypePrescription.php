<?php

namespace App\Constant;

final class TypePrescription
{
    public const int REGLEMENTAIRE = 0;

    public const int EXPLOITATION = 1;

    public const int AMELIORATION = 2;

    public const array ALL = [
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
