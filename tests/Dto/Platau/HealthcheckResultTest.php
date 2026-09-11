<?php

declare(strict_types=1);

namespace App\Tests\Dto\Platau;

use App\Constant\Platau\HealthcheckErrorSource;
use App\Dto\Platau\HealthcheckResult;
use App\Dto\Platau\MaintenanceInfo;
use App\Dto\Platau\VersionInfo;
use PHPUnit\Framework\TestCase;

final class HealthcheckResultTest extends TestCase
{
    public function testSuccessFactorySerializesCorrectly(): void
    {
        $version = new VersionInfo('1.0.0', 'model-1', 'nomen-1');
        $result = HealthcheckResult::success($version, null);

        $json = json_encode($result);
        $data = json_decode((string) $json, true);

        $this->assertTrue($data['success']);
        $this->assertNull($data['errorSource']);
        $this->assertNull($data['maintenance']);
        $this->assertNotNull($data['message']);
        $this->assertStringContainsString('fonctionnée', (string) $data['message']);
        $this->assertStringContainsString('<small>', (string) $data['message']);
        $this->assertStringContainsString('1.0.0', (string) $data['message']);
        $this->assertStringContainsString('model-1', (string) $data['message']);
        $this->assertStringContainsString('nomen-1', (string) $data['message']);
    }

    public function testSuccessWithoutVersionDetailsOmitsSmallTag(): void
    {
        $version = new VersionInfo(null, null, null);
        $result = HealthcheckResult::success($version, null);

        $json = json_encode($result);
        $data = json_decode((string) $json, true);

        $this->assertTrue($data['success']);
        $this->assertStringNotContainsString('<small>', (string) $data['message']);
    }

    public function testSuccessIncludesMaintenanceInfo(): void
    {
        $version = new VersionInfo('1.0', null, null);
        $maintenance = new MaintenanceInfo(true, 'PREVENTIVE', 'Maintenance planifiée', null, null);
        $result = HealthcheckResult::success($version, $maintenance);

        $json = json_encode($result);
        $data = json_decode((string) $json, true);

        $this->assertTrue($data['success']);
        $this->assertNotNull($data['maintenance']);
        $this->assertTrue($data['maintenance']['active']);
    }

    public function testPrevariscErrorFactory(): void
    {
        $result = HealthcheckResult::prevariscError('Token PISTE introuvable.');

        $json = json_encode($result);
        $data = json_decode((string) $json, true);

        $this->assertFalse($data['success']);
        $this->assertSame(HealthcheckErrorSource::PREVARISC, $data['errorSource']);
        $this->assertNull($data['maintenance']);
        $this->assertNull($data['version']);
        $this->assertNotNull($data['message']);
    }

    public function testPrevariscErrorEscapesHtmlInMessage(): void
    {
        $result = HealthcheckResult::prevariscError('<script>alert("xss")</script>');

        $json = json_encode($result);
        $data = json_decode((string) $json, true);

        $this->assertStringNotContainsString('<script>', (string) $data['message']);
        $this->assertStringContainsString('&lt;script&gt;', (string) $data['message']);
    }

    public function testPlatauErrorFactory(): void
    {
        $result = HealthcheckResult::platauError('État général dégradé.', null, null);

        $json = json_encode($result);
        $data = json_decode((string) $json, true);

        $this->assertFalse($data['success']);
        $this->assertSame(HealthcheckErrorSource::PLATAU, $data['errorSource']);
        $this->assertNull($data['maintenance']);
        $this->assertNull($data['version']);
    }

    public function testPlatauErrorIncludesVersionAndMaintenance(): void
    {
        $version = new VersionInfo('2.0', null, null);
        $maintenance = new MaintenanceInfo(false, null, null, '01/07/2026 à 22h00', 'Mise à jour majeure');
        $result = HealthcheckResult::platauError('BDD dégradée.', $version, $maintenance);

        $json = json_encode($result);
        $data = json_decode((string) $json, true);

        $this->assertFalse($data['success']);
        $this->assertSame(HealthcheckErrorSource::PLATAU, $data['errorSource']);
        $this->assertNotNull($data['version']);
        $this->assertSame('2.0', $data['version']['version']);
        $this->assertNotNull($data['maintenance']);
    }
}
