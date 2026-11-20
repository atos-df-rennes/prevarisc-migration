<?php

declare(strict_types=1);

namespace App\Tests\Platau;

use App\Platau\Dto\Consultation;
use App\Platau\Dto\Nomenclature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConsultationTest extends TestCase
{
    /** @return iterable<string, array{?string, ?int, ?string, ?string}> */
    public static function echeances(): iterable
    {
        yield 'jours calendaires, année bissextile' => ['2024-02-28', 2, 'Jours calendaires', '2024-03-01'];
        yield 'mois sans plafonnement au dernier jour' => ['2025-01-31', 1, 'Mois', '2025-03-03'];
        yield 'délai nul en jours' => ['2026-03-10', 0, 'Jours calendaires', '2026-03-10'];
        yield 'pas de repli sur la date de décision' => [null, 1, 'Mois', null];
        yield 'format inattendu' => ['10/03/2026', 1, 'Mois', null];
        yield 'jour débordant accepté par le parseur historique' => ['2025-02-29', 1, 'Jours calendaires', '2025-03-02'];
        yield 'délai absent' => ['2026-03-10', null, 'Mois', null];
        yield 'unité non reconnue' => ['2026-03-10', 1, 'Jours ouvrés', null];
        yield 'unité absente' => ['2026-03-10', 1, null, null];
    }

    #[DataProvider('echeances')]
    public function testDateReponseAttendue(?string $emission, ?int $delai, ?string $unite, ?string $attendue): void
    {
        $consultation = new Consultation(
            'ABC',
            dtEmission: $emission,
            dtConsultation: '2026-03-09',
            delaiDeReponse: $delai,
            nomTypeDelai: null === $unite ? null : new Nomenclature(libNom: $unite),
        );

        $this->assertSame($attendue, $consultation->dateReponseAttendue());
    }
}
