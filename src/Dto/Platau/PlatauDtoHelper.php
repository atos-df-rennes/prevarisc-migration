<?php

namespace App\Dto\Platau;

/**
 * Utilitaires pour les DTOs Plat'AU.
 */
final class PlatauDtoHelper
{
    /**
     * Normalise une valeur en chaîne de caractères.
     * Les chaînes vides ou ne contenant que des espaces sont converties en null.
     *
     * @param mixed $value
     */
    public static function normalizeString($value): ?string
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return $value;
    }

    private function __construct()
    {
    }
}
