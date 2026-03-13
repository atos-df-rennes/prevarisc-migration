<?php

namespace App\Constant;

final class StatutEtablissement
{
    public const OUVERT = 2;

    public const ALL = [
        self::OUVERT,
    ];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
