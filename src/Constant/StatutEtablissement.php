<?php

namespace App\Constant;

final class StatutEtablissement
{
    public const PROJET = 1;

    public const OUVERT = 2;

    public const ALL = [
        self::PROJET,
        self::OUVERT,
    ];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
