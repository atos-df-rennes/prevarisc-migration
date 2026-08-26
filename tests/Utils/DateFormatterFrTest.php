<?php

namespace App\Tests\Utils;

use App\Utils\DateFormatterFr;
use PHPUnit\Framework\TestCase;

final class DateFormatterFrTest extends TestCase
{
    /**
     * @dataProvider provideFormatLong
     */
    public function testFormatLong(\DateTimeInterface $date, string $attendu): void
    {
        self::assertSame($attendu, DateFormatterFr::formatLong($date));
    }

    public function provideFormatLong(): iterable
    {
        yield 'jour sur un chiffre ajoute un zero' => [new \DateTime('2024-01-05'), '05 janvier 2024'];
        yield 'jour sur deux chiffres' => [new \DateTime('2024-06-15'), '15 juin 2024'];
    }

    /**
     * @dataProvider provideFormatShort
     */
    public function testFormatShort(?string $dateStr, string $attendu): void
    {
        self::assertSame($attendu, DateFormatterFr::formatShort($dateStr));
    }

    public function provideFormatShort(): iterable
    {
        yield "jour sur un chiffre n'ajoute pas de zero" => ['2024-01-05', '5 janv. 2024'];
        yield 'jour sur deux chiffres' => ['2024-06-15', '15 juin 2024'];
        yield 'date avec heure' => ['2024-01-05 10:30:00', '5 janv. 2024'];
        yield 'retourne vide si null' => [null, ''];
        yield 'retourne vide si vide' => ['', ''];
    }

    /**
     * @dataProvider provideFormatLongFromStr
     */
    public function testFormatLongFromStr(?string $dateStr, string $attendu): void
    {
        self::assertSame($attendu, DateFormatterFr::formatLongFromStr($dateStr));
    }

    public function provideFormatLongFromStr(): iterable
    {
        yield 'jour sur un chiffre ajoute un zero' => ['2024-01-05', '05 janvier 2024'];
        yield 'retourne vide si null' => [null, ''];
    }
}
