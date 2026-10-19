<?php

namespace App\Utils;

final class StringHelper
{
    /**
     * Tronque une chaîne UTF-8 à un nombre de caractères donné, en ajoutant un
     * suffixe si une troncature a eu lieu.
     *
     * Utilise mb_strlen()/mb_substr() (et non strlen()/substr()) afin de tronquer
     * par caractère plutôt que par octet : un caractère accentué UTF-8 multi-octets
     * (ex : "à" = 0xC3 0xA0) coupé en son milieu produit une séquence UTF-8 invalide,
     * ce qui fait échouer l'échappement Twig
     * (Twig\Error\RuntimeError: "The string to escape is not a valid UTF-8 string").
     */
    public static function truncate(string $string, int $length, string $suffix = '(...)'): string
    {
        if (mb_strlen($string, 'UTF-8') <= $length) {
            return $string;
        }

        return mb_substr($string, 0, $length, 'UTF-8').$suffix;
    }
}
