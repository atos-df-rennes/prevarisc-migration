<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Service\DossierFields;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DossierFieldsTest extends TestCase
{
    private DossierFields $service;

    protected function setUp(): void
    {
        $this->service = new DossierFields();
    }

    #[DataProvider('etudePcProvider')]
    public function testShouldDisplayFieldForEtudePc(string $field, bool $expected): void
    {
        $dossier = $this->createDossierWithNature(1);
        $this->assertSame($expected, $this->service->shouldDisplayField($dossier, $field));
    }

    public static function etudePcProvider(): \Iterator
    {
        yield ['operationSdis', false];
        yield ['rex', false];
        yield ['chargeSecurite', false];
        yield ['dateSignature', false];
    }

    #[DataProvider('etudeDiaProvider')]
    public function testShouldDisplayFieldForEtudeDia(string $field, bool $expected): void
    {
        $dossier = $this->createDossierWithNature(6);
        $this->assertSame($expected, $this->service->shouldDisplayField($dossier, $field));
    }

    public static function etudeDiaProvider(): \Iterator
    {
        yield ['chargeSecurite', true];
    }

    #[DataProvider('interventionIncendieProvider')]
    public function testShouldDisplayFieldForInterventionIncendie(string $field, bool $expected): void
    {
        $dossier = $this->createDossierWithNature(37);
        $this->assertSame($expected, $this->service->shouldDisplayField($dossier, $field));
    }

    public static function interventionIncendieProvider(): \Iterator
    {
        yield ['operationSdis', true];
        yield ['rcci', true];
        yield ['rex', true];
    }

    #[DataProvider('interventionSapProvider')]
    public function testShouldDisplayFieldForInterventionSap(string $field, bool $expected): void
    {
        $dossier = $this->createDossierWithNature(38);
        $this->assertSame($expected, $this->service->shouldDisplayField($dossier, $field));
    }

    public static function interventionSapProvider(): \Iterator
    {
        yield ['operationSdis', true];
        yield ['rcci', false];
    }

    #[DataProvider('interventionDivProvider')]
    public function testShouldDisplayFieldForInterventionDiv(string $field, bool $expected): void
    {
        $dossier = $this->createDossierWithNature(39);
        $this->assertSame($expected, $this->service->shouldDisplayField($dossier, $field));
    }

    public static function interventionDivProvider(): \Iterator
    {
        yield ['rex', true];
    }

    #[DataProvider('arreteOuvertureProvider')]
    public function testShouldDisplayFieldForArreteOuverture(string $field, bool $expected): void
    {
        $dossier = $this->createDossierWithNature(40);
        $this->assertSame($expected, $this->service->shouldDisplayField($dossier, $field));
    }

    public static function arreteOuvertureProvider(): \Iterator
    {
        yield ['dateSignature', true];
    }

    public function testGetFieldsToDisplayReturnsEmptyArrayWhenNoNature(): void
    {
        $dossier = $this->createStub(Dossier::class);
        $dossier->method('getNature')->willReturn(null);

        $this->assertEmpty($this->service->getFieldsToDisplay($dossier));
    }

    public function testGetFieldsToDisplayReturnsCorrectFieldsForIncendie(): void
    {
        $dossier = $this->createDossierWithNature(37); // Incendie
        $fields = $this->service->getFieldsToDisplay($dossier);

        $this->assertContains('operationSdis', $fields);
        $this->assertContains('rcci', $fields);
        $this->assertContains('rex', $fields);
        $this->assertContains('numeroIntervention', $fields);
        $this->assertContains('dateIntervention', $fields);
        $this->assertContains('dureeIntervention', $fields);
    }

    private function createDossierWithNature(int $natureId): Dossier
    {
        $nature = $this->createStub(DossierNatureListe::class);
        $nature->method('getId')->willReturn($natureId);

        $dossier = $this->createStub(Dossier::class);
        $dossier->method('getNature')->willReturn($nature);

        return $dossier;
    }
}
