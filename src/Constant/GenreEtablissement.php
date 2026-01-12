<?php

namespace App\Constant;

/**
 * Constantes pour les genres d'établissements.
 *
 * Correspond à la table `genre` en base de données.
 */
final class GenreEtablissement
{
    /**
     * ERP - Établissement Recevant du Public.
     */
    public const ERP = 2;

    /**
     * IGH - Immeuble de Grande Hauteur.
     */
    public const IGH = 5;

    /**
     * Habitation.
     */
    public const HABITATION = 4;

    /**
     * BUP - Bâtiment à Usage Principal.
     */
    public const BUP = 6;

    /**
     * Liste de tous les genres disponibles.
     *
     * @var int[]
     */
    public const ALL = [
        self::ERP,
        self::IGH,
        self::HABITATION,
        self::BUP,
    ];

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
