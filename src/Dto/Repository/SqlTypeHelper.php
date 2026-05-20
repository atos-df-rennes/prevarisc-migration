<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * Utilitaire statique de conversion de types pour les DTOs de résultats SQL.
 *
 * Fournit des méthodes de cast sûres pour les valeurs retournées par fetchAllAssociative()
 * et NativeQuery, compatibles PHPStan niveau 10.
 */
final class SqlTypeHelper
{
    private function __construct()
    {
    }

    /**
     * Convertit une valeur mixte en int ou null de façon sûre pour PHPStan niveau 10.
     *
     * @param mixed $value
     */
    public static function toInt($value): ?int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value)) {
            return (int) $value;
        }

        if (\is_float($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Convertit une valeur mixte en string ou null de façon sûre pour PHPStan niveau 10.
     *
     * @param mixed $value
     */
    public static function toStr($value): ?string
    {
        if (\is_string($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return (string) $value;
        }

        if (\is_float($value)) {
            return (string) $value;
        }

        return null;
    }
}
