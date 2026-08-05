<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

/**
 * Filtre Twig urlize : convertit les URLs en liens cliquables.
 * Port direct de la regex utilisée dans la vue feeds du legacy.
 */
final class UrlizeExtension
{
    /**
     * Convertit les URLs et adresses www dans le texte en liens HTML cliquables.
     * Port direct de la logique preg_replace du legacy (index/partials/feeds.phtml).
     */
    #[AsTwigFilter(name: 'urlize', isSafe: ['html'])]
    public function urlize(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $in = [
            '`((?:https?|ftp)://\S+[[:alnum:]]/?)`si',
            '`((?<!//)(www\.\S+[[:alnum:]]/?))`si',
        ];
        $out = [
            '<a href="$1">$1</a>',
            '<a href="http://$1">$1</a>',
        ];

        return (string) preg_replace($in, $out, $text);
    }
}
