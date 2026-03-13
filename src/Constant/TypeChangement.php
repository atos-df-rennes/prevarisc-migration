<?php

namespace App\Constant;

final class TypeChangement
{
    public const STATUT = 1;

    public const AVIS = 2;

    public const CLASSEMENT = 3;

    public const ALL = [
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
