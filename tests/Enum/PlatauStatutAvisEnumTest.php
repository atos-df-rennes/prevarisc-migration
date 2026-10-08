<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\PlatauStatutAvisEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PlatauStatutAvisEnumTest extends TestCase
{
    #[DataProvider('labelProvider')]
    public function testGetLabelReturnsCorrectValueForStatus(?string $status, string $expected): void
    {
        $this->assertSame($expected, PlatauStatutAvisEnum::getLabel($status));
    }

    public static function labelProvider(): \Generator
    {
        yield 'inconnu' => [PlatauStatutAvisEnum::INCONNU, 'Statut inconnu'];
        yield 'en cours' => [PlatauStatutAvisEnum::EN_COURS, "En attente d'avis sur Prevarisc"];
        yield 'avis renseigné' => [PlatauStatutAvisEnum::SAISI_A_TRANSMETTRE, "Avis renseigné sur Prevarisc (en attente de transmission à Plat'AU)"];
        yield 'traité' => [PlatauStatutAvisEnum::TRAITE, 'Traité sur Plat\'AU'];
        yield 'renvoi' => [PlatauStatutAvisEnum::A_RENVOYER, 'Traité sur Plat\'AU (en attente de renvoi)'];
        yield 'en erreur' => [PlatauStatutAvisEnum::EN_ERREUR, "En erreur sur Plat'AU"];
        yield 'null' => [null, 'Statut inconnu'];
        yield 'invalide' => ['invalid_status', 'Statut inconnu'];
    }

    #[DataProvider('constantProvider')]
    public function testConstantValues(string $constant, string $expected): void
    {
        $this->assertSame($expected, $constant);
    }

    public static function constantProvider(): \Generator
    {
        yield 'inconnu' => [PlatauStatutAvisEnum::INCONNU, 'unknown'];
        yield 'en cours' => [PlatauStatutAvisEnum::EN_COURS, 'in_progress'];
        yield 'avis renseigné' => [PlatauStatutAvisEnum::SAISI_A_TRANSMETTRE, 'filled_awaiting_export'];
        yield 'traité' => [PlatauStatutAvisEnum::TRAITE, 'treated'];
        yield 'à renvoyer' => [PlatauStatutAvisEnum::A_RENVOYER, 'to_export'];
        yield 'en erreur' => [PlatauStatutAvisEnum::EN_ERREUR, 'in_error'];
    }
}
