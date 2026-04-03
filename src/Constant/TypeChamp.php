<?php

namespace App\Constant;

/**
 * Constantes pour les types de champs de rubriques.
 *
 * Correspond à la colonne `ID_TYPECHAMP` de la table `listetypechamprubrique` en base de données.
 */
final class TypeChamp
{
    public const TEXT = 1;

    public const TEXTAREA = 2;

    public const SELECT = 3;

    public const NUMBER = 4;

    public const CHECKBOX = 5;

    public const PARENT = 6;

    public const DATE = 7;

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
