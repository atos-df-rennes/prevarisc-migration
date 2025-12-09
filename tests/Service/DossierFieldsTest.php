<?php

namespace App\Tests\Service;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Service\DossierFields;
use PHPUnit\Framework\TestCase;

class DossierFieldsTest extends TestCase
{
    /** @var DossierFields */
    private $service;

    protected function setUp(): void
    {
        $this->service = new DossierFields();
    }

    public function testShouldDisplayFieldReturnsTrueForOperationSdisInIntervention(): void
    {
        $dossier = $this->createDossierWithNature(37); // Nature Incendie
        $this->assertTrue($this->service->shouldDisplayField($dossier, 'OPERSDIS'));
    }

    public function testShouldDisplayFieldReturnsFalseForOperationSdisInEtude(): void
    {
        $dossier = $this->createDossierWithNature(1); // Nature PC
        $this->assertFalse($this->service->shouldDisplayField($dossier, 'OPERSDIS'));
    }

    public function testShouldDisplayFieldReturnsTrueForRcciInIncendie(): void
    {
        $dossier = $this->createDossierWithNature(37); // Nature Incendie
        $this->assertTrue($this->service->shouldDisplayField($dossier, 'RCCI'));
    }

    public function testShouldDisplayFieldReturnsFalseForRcciInSap(): void
    {
        $dossier = $this->createDossierWithNature(38); // Nature SAP
        $this->assertFalse($this->service->shouldDisplayField($dossier, 'RCCI'));
    }

    public function testShouldDisplayFieldReturnsTrueForRexInAllInterventions(): void
    {
        $dossier37 = $this->createDossierWithNature(37); // Incendie
        $this->assertTrue($this->service->shouldDisplayField($dossier37, 'REX'));

        $dossier38 = $this->createDossierWithNature(38); // SAP
        $this->assertTrue($this->service->shouldDisplayField($dossier38, 'REX'));

        $dossier39 = $this->createDossierWithNature(39); // Intervention div
        $this->assertTrue($this->service->shouldDisplayField($dossier39, 'REX'));
    }

    public function testShouldDisplayFieldReturnsFalseForRexInEtude(): void
    {
        $dossier = $this->createDossierWithNature(1); // Nature PC
        $this->assertFalse($this->service->shouldDisplayField($dossier, 'REX'));
    }

    public function testShouldDisplayFieldReturnsTrueForChargeSecuriteInDia(): void
    {
        $dossier = $this->createDossierWithNature(6); // DIA
        $this->assertTrue($this->service->shouldDisplayField($dossier, 'CHARGESEC'));
    }

    public function testShouldDisplayFieldReturnsFalseForChargeSecuriteInPc(): void
    {
        $dossier = $this->createDossierWithNature(1); // PC
        $this->assertFalse($this->service->shouldDisplayField($dossier, 'CHARGESEC'));
    }

    public function testShouldDisplayFieldReturnsTrueForDateSignInArrete(): void
    {
        $dossier = $this->createDossierWithNature(40); // Arrêté d'ouverture
        $this->assertTrue($this->service->shouldDisplayField($dossier, 'DATESIGN'));
    }

    public function testShouldDisplayFieldReturnsFalseForDateSignInEtude(): void
    {
        $dossier = $this->createDossierWithNature(1); // PC
        $this->assertFalse($this->service->shouldDisplayField($dossier, 'DATESIGN'));
    }

    public function testGetFieldsToDisplayReturnsEmptyArrayWhenNoNature(): void
    {
        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getNature')->willReturn(null);

        $this->assertEmpty($this->service->getFieldsToDisplay($dossier));
    }

    public function testGetFieldsToDisplayReturnsCorrectFieldsForIncendie(): void
    {
        $dossier = $this->createDossierWithNature(37); // Incendie
        $fields = $this->service->getFieldsToDisplay($dossier);

        $this->assertContains('OPERSDIS', $fields);
        $this->assertContains('RCCI', $fields);
        $this->assertContains('REX', $fields);
        $this->assertContains('NUMINTERV', $fields);
        $this->assertContains('DATEINTERV', $fields);
        $this->assertContains('DUREEINTERV', $fields);
    }

    private function createDossierWithNature(int $natureId): Dossier
    {
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn($natureId);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getNature')->willReturn($nature);

        return $dossier;
    }
}
