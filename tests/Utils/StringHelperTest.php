<?php

namespace App\Tests\Utils;

use App\Utils\StringHelper;
use PHPUnit\Framework\TestCase;

final class StringHelperTest extends TestCase
{
    /**
     * @dataProvider truncateProvider
     */
    public function testTruncate(string $input, int $length, string $expected): void
    {
        self::assertSame($expected, StringHelper::truncate($input, $length));
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: string}>
     */
    public static function truncateProvider(): array
    {
        return [
            'chaîne plus courte que la limite' => [
                'Fête de village',
                70,
                'Fête de village',
            ],
            'chaîne de longueur égale à la limite' => [
                str_repeat('a', 70),
                70,
                str_repeat('a', 70),
            ],
            // Cas réel ayant provoqué un Twig\Error\RuntimeError ("not a valid UTF-8 string")
            // sur templates/etablissement/dossiers/_item.html.twig : le "à" (caractère UTF-8
            // sur 2 octets, 0xC3 0xA0) tombe exactement à la coupe de l'ancien substr(0, 70),
            // ce qui produisait une séquence UTF-8 invalide.
            'chaîne réelle coupant un caractère accentué multi-octets' => [
                "Fête de l'agriculture le 22/08/26 et spectacle pyrotechnique soumis à autorisation",
                70,
                "Fête de l'agriculture le 22/08/26 et spectacle pyrotechnique soumis à (...)",
            ],
            'chaîne avec accents tronquée proprement' => [
                'Réunion préparatoire à la commission de sécurité incendie et accessibilité',
                20,
                'Réunion préparatoire(...)',
            ],
        ];
    }

    /**
     * @dataProvider truncateProducesValidUtf8Provider
     */
    public function testTruncateAlwaysProducesValidUtf8(string $input, int $length): void
    {
        $result = StringHelper::truncate($input, $length);

        self::assertTrue(mb_check_encoding($result, 'UTF-8'), 'Le résultat de truncate() doit toujours être une chaîne UTF-8 valide.');
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function truncateProducesValidUtf8Provider(): array
    {
        $objet = "Fête de l'agriculture le 22/08/26 et spectacle pyrotechnique soumis à autorisation";

        // On teste toutes les longueurs de coupe possibles autour des caractères accentués
        // pour s'assurer qu'aucune ne produit de séquence UTF-8 invalide.
        $cases = [];
        for ($length = 1; $length <= mb_strlen($objet, 'UTF-8'); ++$length) {
            $cases['longueur '.$length] = [$objet, $length];
        }

        return $cases;
    }

    public function testTruncateAvecSuffixePersonnalise(): void
    {
        self::assertSame('Bonjour…', StringHelper::truncate('Bonjour tout le monde', 7, '…'));
    }
}
