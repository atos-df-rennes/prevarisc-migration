<?php

namespace App\Constant;

/**
 * Constantes pour les catégories d'établissements.
 *
 * Correspond à la table `categorie` en base de données.
 */
final class CategorieEtablissement
{
    public const int CINQUIEME = 5;

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
