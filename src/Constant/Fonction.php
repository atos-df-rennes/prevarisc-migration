<?php

namespace App\Constant;

/**
 * Constantes pour les fonctions.
 *
 * Correspond à la table `fonction` en base de données.
 */
final class Fonction
{
    public const MAIRE = 2;

    public const EXPLOITANT = 7;

    public const DIRECTEUR_UNIQUE_DE_SECURITE = 8;

    public const RESPONSABLE_DE_SECURITE = 9;

    public const PROPRIETAIRE = 17;

    public const FONCTIONS_COPIE_CONTACTS = [
        self::EXPLOITANT,
        self::DIRECTEUR_UNIQUE_DE_SECURITE,
        self::RESPONSABLE_DE_SECURITE,
        self::PROPRIETAIRE,
    ];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
