<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\PlatauStatutPecEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PlatauStatutPecEnumTest extends TestCase
{
    #[DataProvider('labelProvider')]
    public function testGetLabelReturnsCorrectValueForStatus(?string $status, string $expected): void
    {
        $this->assertSame($expected, PlatauStatutPecEnum::getLabel($status));
    }

    public static function labelProvider(): \Generator
    {
        yield 'inconnu' => [PlatauStatutPecEnum::INCONNU, 'Statut inconnu'];
        yield 'en attente' => [PlatauStatutPecEnum::EN_ATTENTE, 'En attente de complétude sur Prevarisc'];
        yield 'prise en compte' => [PlatauStatutPecEnum::PRISE_EN_COMPTE, "Prise en compte sur Plat'AU"];
        yield 'renvoi' => [PlatauStatutPecEnum::A_RENVOYER, "Prise en compte sur Plat'AU (en attente de renvoi)"];
        yield 'en erreur' => [PlatauStatutPecEnum::EN_ERREUR, "En erreur sur Plat'AU"];
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
        yield 'inconnu' => [PlatauStatutPecEnum::INCONNU, 'unknown'];
        yield 'en attente' => [PlatauStatutPecEnum::EN_ATTENTE, 'awaiting'];
        yield 'prise en compte' => [PlatauStatutPecEnum::PRISE_EN_COMPTE, 'taken_into_account'];
        yield 'à renvoyer' => [PlatauStatutPecEnum::A_RENVOYER, 'to_export'];
        yield 'en erreur' => [PlatauStatutPecEnum::EN_ERREUR, 'in_error'];
    }
}
