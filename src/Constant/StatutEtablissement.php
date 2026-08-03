<?php

namespace App\Constant;

final class StatutEtablissement
{
    public const int PROJET = 1;

    public const int OUVERT = 2;

    public const int FERME = 3;

    public const int ITINERANT_PERIODIQUE = 4;

    public const array ALL = [
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
