<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Service\DossierFields;
use PHPUnit\Framework\TestCase;

final class DossierFieldsTest extends TestCase
{
    private DossierFields $service;

    protected function setUp(): void
    {
        $this->service = new DossierFields();
    }

    public function testShouldDisplayFieldReturnsTrueForOperationSdisInIntervention(): void
    {
        $dossier = $this->createDossierWithNature(37); // Nature Incendie
        $this->assertTrue($this->service->shouldDisplayField($dossier, 'operationSdis'));
    }

    public function testShouldDisplayFieldReturnsFalseForOperationSdisInEtude(): void
    {
        $dossier = $this->createDossierWithNature(1); // Nature PC
        $this->assertFalse($this->service->shouldDisplayField($dossier, 'operationSdis'));
    }

    public function testShouldDisplayFieldReturnsTrueForRcciInIncendie(): void
    {
        $dossier = $this->createDossierWithNature(37); // Nature Incendie
        $this->assertTrue($this->service->shouldDisplayField($dossier, 'rcci'));
    }

    public function testShouldDisplayFieldReturnsFalseForRcciInSap(): void
    {
        $dossier = $this->createDossierWithNature(38); // Nature SAP
        $this->assertFalse($this->service->shouldDisplayField($dossier, 'rcci'));
    }

    public function testShouldDisplayFieldReturnsTrueForRexInAllInterventions(): void
    {
        $dossier37 = $this->createDossierWithNature(37); // Incendie
        $this->assertTrue($this->service->shouldDisplayField($dossier37, 'rex'));

        $dossier38 = $this->createDossierWithNature(38); // SAP
        $this->assertTrue($this->service->shouldDisplayField($dossier38, 'rex'));

        $dossier39 = $this->createDossierWithNature(39); // Intervention div
        $this->assertTrue($this->service->shouldDisplayField($dossier39, 'rex'));
    }

    public function testShouldDisplayFieldReturnsFalseForRexInEtude(): void
    {
        $dossier = $this->createDossierWithNature(1); // Nature PC
        $this->assertFalse($this->service->shouldDisplayField($dossier, 'rex'));
    }

    public function testShouldDisplayFieldReturnsTrueForChargeSecuriteInDia(): void
    {
        $dossier = $this->createDossierWithNature(6); // DIA
        $this->assertTrue($this->service->shouldDisplayField($dossier, 'chargeSecurite'));
    }

    public function testShouldDisplayFieldReturnsFalseForChargeSecuriteInPc(): void
    {
        $dossier = $this->createDossierWithNature(1); // PC
        $this->assertFalse($this->service->shouldDisplayField($dossier, 'chargeSecurite'));
    }

    public function testShouldDisplayFieldReturnsTrueForDateSignInArrete(): void
    {
        $dossier = $this->createDossierWithNature(40); // Arrêté d'ouverture
        $this->assertTrue($this->service->shouldDisplayField($dossier, 'dateSignature'));
    }

    public function testShouldDisplayFieldReturnsFalseForDateSignInEtude(): void
    {
        $dossier = $this->createDossierWithNature(1); // PC
        $this->assertFalse($this->service->shouldDisplayField($dossier, 'dateSignature'));
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

    public function testReferenceCourrierIsAvailableForEveryNature(): void
    {
        foreach ($this->service->getAllFieldsByNature() as $natureId => $fields) {
            $this->assertContains('referenceCourrier', $fields, sprintf('Nature %s must display the reference courrier field.', $natureId));
        }
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
