<?php

namespace App\Constant;

final class StatutEtablissement
{
    public const PROJET = 1;

    public const OUVERT = 2;

    public const FERME = 3;

    public const ITINERANT_PERIODIQUE = 4;

    public const ALL = [
        self::PROJET,
        self::OUVERT,
        self::FERME,
        self::ITINERANT_PERIODIQUE,
    ];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
