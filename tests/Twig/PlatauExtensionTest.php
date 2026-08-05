<?php

namespace App\Tests\Twig;

use App\Enum\PlatauStatutAvisEnum;
use App\Enum\PlatauStatutPecEnum;
use App\Twig\PlatauExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlatauExtensionTest extends TestCase
{
    private PlatauExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new PlatauExtension();
    }

    #[DataProvider('getPecLabelProvider')]
    public function testGetPecLabel(?string $statut, string $expectedLabel): void
    {
        $result = $this->extension->getPecLabel($statut);

        $this->assertSame($expectedLabel, $result);
    }

    public static function getPecLabelProvider(): array
    {
        return [
            'null returns inconnu' => [null, 'Statut inconnu'],
            'statut non défini returns inconnu' => ['toto', 'Statut inconnu'],
            'EN_ATTENTE' => [PlatauStatutPecEnum::EN_ATTENTE, 'En attente de complétude sur Prevarisc'],
            'PRISE_EN_COMPTE' => [PlatauStatutPecEnum::PRISE_EN_COMPTE, "Prise en compte sur Plat'AU"],
            'A_RENVOYER' => [PlatauStatutPecEnum::A_RENVOYER, "Prise en compte sur Plat'AU (en attente de renvoi)"],
            'EN_ERREUR' => [PlatauStatutPecEnum::EN_ERREUR, "En erreur sur Plat'AU"],
        ];
    }

    #[DataProvider('getPecClassProvider')]
    public function testGetPecClass(?string $statut, string $expectedClass): void
    {
        $result = $this->extension->getPecClass($statut);

        $this->assertSame($expectedClass, $result);
    }

    public static function getPecClassProvider(): array
    {
        return [
            'null returns text-muted' => [null, 'text-muted'],
            'statut non défini returns text-muted' => ['toto', 'text-muted'],
            'EN_ATTENTE returns text-info' => [PlatauStatutPecEnum::EN_ATTENTE, 'text-info'],
            'PRISE_EN_COMPTE returns text-success' => [PlatauStatutPecEnum::PRISE_EN_COMPTE, 'text-success'],
            'A_RENVOYER returns text-warning' => [PlatauStatutPecEnum::A_RENVOYER, 'text-warning'],
            'EN_ERREUR returns text-danger' => [PlatauStatutPecEnum::EN_ERREUR, 'text-danger'],
        ];
    }

    #[DataProvider('getAvisLabelProvider')]
    public function testGetAvisLabel(?string $statut, string $expectedLabel): void
    {
        $result = $this->extension->getAvisLabel($statut);

        $this->assertSame($expectedLabel, $result);
    }

    public static function getAvisLabelProvider(): array
    {
        return [
            'null returns inconnu' => [null, 'Statut inconnu'],
            'statut non défini returns inconnu' => ['toto', 'Statut inconnu'],
            'EN_COURS' => [PlatauStatutAvisEnum::EN_COURS, 'En attente de complétude sur Prevarisc'],
            'TRAITE' => [PlatauStatutAvisEnum::TRAITE, "Traité sur Plat'AU"],
            'A_RENVOYER' => [PlatauStatutAvisEnum::A_RENVOYER, "Traité sur Plat'AU (en attente de renvoi)"],
            'EN_ERREUR' => [PlatauStatutAvisEnum::EN_ERREUR, "En erreur sur Plat'AU"],
        ];
    }

    #[DataProvider('getAvisClassProvider')]
    public function testGetAvisClass(?string $statut, string $expectedClass): void
    {
        $result = $this->extension->getAvisClass($statut);

        $this->assertSame($expectedClass, $result);
    }

    public static function getAvisClassProvider(): array
    {
        return [
            'null returns text-muted' => [null, 'text-muted'],
            'statut non défini returns text-muted' => ['toto', 'text-muted'],
            'EN_COURS returns text-info' => [PlatauStatutAvisEnum::EN_COURS, 'text-info'],
            'TRAITE returns text-success' => [PlatauStatutAvisEnum::TRAITE, 'text-success'],
            'A_RENVOYER returns text-warning' => [PlatauStatutAvisEnum::A_RENVOYER, 'text-warning'],
            'EN_ERREUR returns text-danger' => [PlatauStatutAvisEnum::EN_ERREUR, 'text-danger'],
        ];
    }
}
