<?php

declare(strict_types=1);

namespace App\Tests\Dto\Platau;

use App\Dto\Platau\MaintenanceInfo;
use PHPUnit\Framework\TestCase;

final class MaintenanceInfoTest extends TestCase
{
    public function testFromApiResponseActiveMaintenance(): void
    {
        $response = new \stdClass();
        $response->maintenance = true;
        $response->typeMaintenance = 'CURATIVE';
        $response->descMaintenance = 'Maintenance urgente';
        $response->prochaineMaintenance = null;
        $response->descProchaineMaintenance = null;

        $info = MaintenanceInfo::fromApiResponse($response);

        $this->assertTrue($info->isActive());
        $this->assertSame('CURATIVE', $info->getTypeMaintenance());
        $this->assertSame('Maintenance urgente', $info->getDescMaintenance());
        $this->assertNull($info->getProchaineMaintenance());
        $this->assertTrue($info->hasAnyMaintenanceInfo());
    }

    public function testFromApiResponseNullMaintenanceFieldTreatedAsFalse(): void
    {
        $response = new \stdClass();
        $response->maintenance = null;
        $response->prochaineMaintenance = null;

        $info = MaintenanceInfo::fromApiResponse($response);

        $this->assertFalse($info->isActive());
    }

    public function testFromApiResponseMissingMaintenanceFieldTreatedAsFalse(): void
    {
        $response = new \stdClass();

        $info = MaintenanceInfo::fromApiResponse($response);

        $this->assertFalse($info->isActive());
    }

    public function testFromApiResponseUpcomingMaintenance(): void
    {
        $response = new \stdClass();
        $response->maintenance = false;
        $response->prochaineMaintenance = '2026-07-01T22:00:00Z';
        $response->descProchaineMaintenance = 'Mise à jour planifiée';

        $info = MaintenanceInfo::fromApiResponse($response);

        $this->assertFalse($info->isActive());
        $this->assertNotNull($info->getProchaineMaintenance());
        $this->assertStringContainsString('01/07/2026', $info->getProchaineMaintenance());
        $this->assertSame('Mise à jour planifiée', $info->getDescProchaineMaintenance());
        $this->assertTrue($info->hasAnyMaintenanceInfo());
    }

    public function testFromApiResponseNormalizesEmptyStringsToNull(): void
    {
        $response = new \stdClass();
        $response->maintenance = false;
        $response->typeMaintenance = '';
        $response->descMaintenance = '   ';
        $response->prochaineMaintenance = null;
        $response->descProchaineMaintenance = '';

        $info = MaintenanceInfo::fromApiResponse($response);

        $this->assertNull($info->getTypeMaintenance());
        $this->assertNull($info->getDescMaintenance());
        $this->assertNull($info->getDescProchaineMaintenance());
        $this->assertFalse($info->hasAnyMaintenanceInfo());
    }

    public function testHasAnyMaintenanceInfoFalseWhenInactive(): void
    {
        $info = new MaintenanceInfo(false, null, null, null, null);

        $this->assertFalse($info->hasAnyMaintenanceInfo());
    }

    public function testToDisplayMessageForActiveMaintenance(): void
    {
        $info = new MaintenanceInfo(true, 'PREVENTIVE', 'Intervention planifiée', null, null);

        $message = $info->toDisplayMessage();

        $this->assertStringContainsString('Maintenance en cours', $message);
        $this->assertStringContainsString('PREVENTIVE', $message);
        $this->assertStringContainsString('Intervention planifiée', $message);
    }

    public function testToDisplayMessageEscapesHtml(): void
    {
        $info = new MaintenanceInfo(true, '<script>alert(1)</script>', null, null, null);

        $message = $info->toDisplayMessage();

        $this->assertStringNotContainsString('<script>', $message);
        $this->assertStringContainsString('&lt;script&gt;', $message);
    }

    public function testToDisplayMessageForUpcomingMaintenance(): void
    {
        $info = new MaintenanceInfo(false, null, null, '01/07/2026 à 22h00', 'Mise à jour majeure');

        $message = $info->toDisplayMessage();

        $this->assertStringContainsString('Prochaine maintenance', $message);
        $this->assertStringContainsString('01/07/2026 à 22h00', $message);
        $this->assertStringContainsString('Mise à jour majeure', $message);
    }

    public function testJsonSerializeIncludesMessage(): void
    {
        $info = new MaintenanceInfo(true, 'CURATIVE', 'En cours', null, null);

        $json = json_encode($info);
        $data = json_decode((string) $json, true);

        $this->assertArrayHasKey('message', $data);
        $this->assertNotEmpty($data['message']);
        $this->assertTrue($data['active']);
        $this->assertSame('CURATIVE', $data['typeMaintenance']);
    }
}
