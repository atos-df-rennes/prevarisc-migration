<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Enum\PlatauStatutAvisEnum;
use App\Enum\PlatauStatutPecEnum;
use App\Twig\PlatauExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PlatauExtensionTest extends TestCase
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

    public static function getPecLabelProvider(): \Iterator
    {
        yield 'null returns inconnu' => [null, 'Statut inconnu'];
        yield 'statut non défini returns inconnu' => ['toto', 'Statut inconnu'];
        yield 'EN_ATTENTE' => [PlatauStatutPecEnum::EN_ATTENTE, 'En attente de complétude sur Prevarisc'];
        yield 'SAISIE_A_TRANSMETTRE' => [PlatauStatutPecEnum::SAISIE_A_TRANSMETTRE, "Complétude renseignée sur Prevarisc (en attente de transmission à Plat'AU)"];
        yield 'PRISE_EN_COMPTE' => [PlatauStatutPecEnum::PRISE_EN_COMPTE, "Prise en compte sur Plat'AU"];
        yield 'A_RENVOYER' => [PlatauStatutPecEnum::A_RENVOYER, "Prise en compte sur Plat'AU (en attente de renvoi)"];
        yield 'EN_ERREUR' => [PlatauStatutPecEnum::EN_ERREUR, "En erreur sur Plat'AU"];
    }

    #[DataProvider('getPecClassProvider')]
    public function testGetPecClass(?string $statut, string $expectedClass): void
    {
        $result = $this->extension->getPecClass($statut);

        $this->assertSame($expectedClass, $result);
    }

    public static function getPecClassProvider(): \Iterator
    {
        yield 'null returns text-muted' => [null, 'text-muted'];
        yield 'statut non défini returns text-muted' => ['toto', 'text-muted'];
        yield 'EN_ATTENTE returns text-info' => [PlatauStatutPecEnum::EN_ATTENTE, 'text-info'];
        yield 'SAISIE_A_TRANSMETTRE returns text-primary' => [PlatauStatutPecEnum::SAISIE_A_TRANSMETTRE, 'text-primary'];
        yield 'PRISE_EN_COMPTE returns text-success' => [PlatauStatutPecEnum::PRISE_EN_COMPTE, 'text-success'];
        yield 'A_RENVOYER returns text-warning' => [PlatauStatutPecEnum::A_RENVOYER, 'text-warning'];
        yield 'EN_ERREUR returns text-danger' => [PlatauStatutPecEnum::EN_ERREUR, 'text-danger'];
    }

    #[DataProvider('getAvisLabelProvider')]
    public function testGetAvisLabel(?string $statut, string $expectedLabel): void
    {
        $result = $this->extension->getAvisLabel($statut);

        $this->assertSame($expectedLabel, $result);
    }

    public static function getAvisLabelProvider(): \Iterator
    {
        yield 'null returns inconnu' => [null, 'Statut inconnu'];
        yield 'statut non défini returns inconnu' => ['toto', 'Statut inconnu'];
        yield 'EN_COURS' => [PlatauStatutAvisEnum::EN_COURS, "En attente d'avis sur Prevarisc"];
        yield 'SAISI_A_TRANSMETTRE' => [PlatauStatutAvisEnum::SAISI_A_TRANSMETTRE, "Avis renseigné sur Prevarisc (en attente de transmission à Plat'AU)"];
        yield 'TRAITE' => [PlatauStatutAvisEnum::TRAITE, "Traité sur Plat'AU"];
        yield 'A_RENVOYER' => [PlatauStatutAvisEnum::A_RENVOYER, "Traité sur Plat'AU (en attente de renvoi)"];
        yield 'EN_ERREUR' => [PlatauStatutAvisEnum::EN_ERREUR, "En erreur sur Plat'AU"];
    }

    #[DataProvider('getAvisClassProvider')]
    public function testGetAvisClass(?string $statut, string $expectedClass): void
    {
        $result = $this->extension->getAvisClass($statut);

        $this->assertSame($expectedClass, $result);
    }

    public static function getAvisClassProvider(): \Iterator
    {
        yield 'null returns text-muted' => [null, 'text-muted'];
        yield 'statut non défini returns text-muted' => ['toto', 'text-muted'];
        yield 'EN_COURS returns text-info' => [PlatauStatutAvisEnum::EN_COURS, 'text-info'];
        yield 'SAISI_A_TRANSMETTRE returns text-primary' => [PlatauStatutAvisEnum::SAISI_A_TRANSMETTRE, 'text-primary'];
        yield 'TRAITE returns text-success' => [PlatauStatutAvisEnum::TRAITE, 'text-success'];
        yield 'A_RENVOYER returns text-warning' => [PlatauStatutAvisEnum::A_RENVOYER, 'text-warning'];
        yield 'EN_ERREUR returns text-danger' => [PlatauStatutAvisEnum::EN_ERREUR, 'text-danger'];
    }
}
