<?php

namespace App\Constant;

/**
 * Constantes pour les types de champs de rubriques.
 *
 * Correspond à la colonne `ID_TYPECHAMP` de la table `listetypechamprubrique` en base de données.
 */
final class TypeChamp
{
    public const int TEXT = 1;

    public const int TEXTAREA = 2;

    public const int SELECT = 3;

    public const int NUMBER = 4;

    public const int CHECKBOX = 5;

    public const int PARENT = 6;

    public const int DATE = 7;

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
