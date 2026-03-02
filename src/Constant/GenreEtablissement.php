<?php

namespace App\Constant;

/**
 * Constantes pour les genres d'établissements.
 *
 * Correspond à la table `genre` en base de données.
 */
final class GenreEtablissement
{
    public const SITE = 1;

    /**
     * ERP - Établissement Recevant du Public.
     */
    public const ERP = 2;

    public const CELLULE = 3;

    public const HABITATION = 4;

    /**
     * IGH - Immeuble de Grande Hauteur.
     */
    public const IGH = 5;

    /**
     * BUP - Bâtiment d'Utilité Publique.
     */
    public const BUP = 6;

    public const CAMPING = 7;

    public const MANIFESTATION_TEMPORAIRE = 8;

    public const IOP = 9;

    public const ZONE = 10;

    public const GENRES_AVEC_PERIODICITE = [self::SITE, self::ERP, self::CELLULE, self::IGH];

    public const GENRES_AVEC_EFFECTIF = [self::ERP, self::CELLULE, self::IGH, self::BUP];

    public const GENRES_AVEC_EFFECTIF_PUBLIC = [self::ERP, self::CELLULE, self::IGH];

    /**
     * Liste de tous les genres disponibles.
     *
     * @var int[]
     */
    public const ALL = [
        self::SITE,
        self::ERP,
        self::CELLULE,
        self::HABITATION,
        self::IGH,
        self::BUP,
        self::CAMPING,
        self::MANIFESTATION_TEMPORAIRE,
        self::IOP,
        self::ZONE,
    ];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
