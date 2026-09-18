<?php

namespace App\Constant;

final class TypeChangement
{
    public const int STATUT = 1;

    public const int AVIS = 2;

    public const int CLASSEMENT = 3;

    public const array ALL = [
        self::STATUT,
        self::AVIS,
        self::CLASSEMENT,
    ];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
