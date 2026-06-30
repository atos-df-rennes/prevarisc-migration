<?php

namespace App\Tests\Service;

use App\Constant\Platau\HealthcheckErrorSource;
use App\Dto\Platau\HealthcheckResult;
use App\Service\PlatauHealthcheckService;
use App\Service\ProxyConfigurationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests unitaires pour PlatauHealthcheckService.
 *
 * La méthode executeHealthcheck() repose sur cURL et le système de fichiers.
 * Les scénarios testés ici couvrent les chemins qui n'ont pas besoin de cURL :
 * configuration absente (fichiers inexistants) → prevariscError.
 *
 * Les scénarios dépendant de cURL (appels réseau réels) sont couverts en intégration.
 */
class PlatauHealthcheckServiceTest extends TestCase
{
    /**
     * Crée une instance du service avec des chemins de fichiers inexistants,
     * ce qui simule l'absence de configuration Plat'AU.
     */
    private function buildService(): PlatauHealthcheckService
    {
        $proxy = $this->createMock(ProxyConfigurationService::class);
        $proxy->method('getCurlOptions')->willReturn([]);

        return new PlatauHealthcheckService(
            'platau.config.json',
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'prevarisc-test-inexistant',
            'test',
            new NullLogger(),
            $proxy
        );
    }

    public function testReturnsPrevariscErrorWhenConfigFileMissing(): void
    {
        $service = $this->buildService();

        $result = $service->executeHealthcheck();

        $this->assertFalse($result->isSuccess());
        $this->assertSame(HealthcheckErrorSource::PREVARISC, $result->getErrorSource());
        $this->assertNotNull($result->getMessage());
        $this->assertNull($result->getMaintenance());
        $this->assertNull($result->getVersion());
    }

    public function testPrevariscErrorMessageMentionsLogs(): void
    {
        $service = $this->buildService();

        $result = $service->executeHealthcheck();

        // Le message doit guider l'utilisateur vers la documentation
        $this->assertStringContainsString("Veuillez consulter les logs de l'application", (string) $result->getMessage());
    }

    public function testResultIsJsonSerializable(): void
    {
        $service = $this->buildService();

        $result = $service->executeHealthcheck();
        $json = json_encode($result);

        $this->assertNotFalse($json);

        $data = json_decode((string) $json, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('errorSource', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('maintenance', $data);
    }
}
