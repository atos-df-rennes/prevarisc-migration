<?php

declare(strict_types=1);

namespace App\Tests\Platau;

use App\Platau\Dto\Piece;
use App\Platau\ReceptionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PieceTest extends TestCase
{
    /** @return iterable<string, array{?string, ?string}> */
    public static function datesDepot(): iterable
    {
        yield 'absente' => [null, null];
        yield 'date' => ['2026-11-01', '2026-11-01'];
        yield 'date-heure avec décalage' => ['2026-11-01T23:15:00-03:00', '2026-11-01'];
        yield 'date-heure UTC et fractions' => ['2026-11-01T10:15:00.123Z', '2026-11-01'];
        yield 'date-heure sans fuseau' => ['2026-11-01T10:15:00', '2026-11-01'];
    }

    #[DataProvider('datesDepot')]
    public function testNormaliseDateDepot(?string $date, ?string $attendue): void
    {
        $piece = new Piece('ID', 'PC1', 1, 'plan.pdf', 'url', 'token', dtDepot: $date);

        $this->assertSame($attendue, $piece->dtDepot);
    }

    /** @return iterable<string, array{string}> */
    public static function datesDepotInvalides(): iterable
    {
        yield 'date inexistante' => ['2026-02-29'];
        yield 'date-heure inexistante' => ['2026-02-29T10:15:00+01:00'];
        yield 'heure incorrecte' => ['2026-11-01T25:15:00+01:00'];
        yield 'format incorrect' => ['01/11/2026'];
        yield 'texte supplémentaire' => ['2026-11-01T10:15:00+01:00 extra'];
    }

    #[DataProvider('datesDepotInvalides')]
    public function testRefuseDateDepotInvalide(string $date): void
    {
        $this->expectException(ReceptionException::class);
        new Piece('ID', 'PC1', 1, 'plan.pdf', 'url', 'token', dtDepot: $date);
    }
}
