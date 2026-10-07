<?php

declare(strict_types=1);

namespace App\Tests\Service\Platau;

use App\Constant\Platau\HealthcheckErrorSource;
use App\Service\Platau\PisteTokenProvider;
use App\Service\Platau\PlatauGatewayConfig;
use App\Service\Platau\PlatauHealthcheckService;
use App\Service\ProxyConfigurationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;

/**
 * Tests unitaires pour PlatauHealthcheckService.
 *
 * La méthode executeHealthcheck() repose sur le client HTTP et le système de fichiers.
 * Les scénarios testés ici couvrent les chemins qui n'ont pas besoin d'appel réseau :
 * configuration absente (fichiers inexistants) → prevariscError.
 *
 * Les scénarios HTTP sont couverts dans PlatauHealthcheckHttpTest.
 */
final class PlatauHealthcheckServiceTest extends TestCase
{
    /**
     * Crée une instance du service avec des chemins de fichiers inexistants,
     * ce qui simule l'absence de configuration Plat'AU.
     */
    private function buildService(): PlatauHealthcheckService
    {
        $proxy = $this->createStub(ProxyConfigurationService::class);
        $logger = new NullLogger();
        $client = new MockHttpClient();
        $config = new PlatauGatewayConfig(
            'platau.config.json',
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'prevarisc-test-inexistant',
            $logger,
            $proxy
        );

        return new PlatauHealthcheckService(
            $config,
            new PisteTokenProvider($config, $client, new ArrayAdapter(), $logger),
            $client,
            $logger
        );
    }

    public function testReturnsPrevariscErrorWhenConfigFileMissing(): void
    {
        $service = $this->buildService();

        $result = $service->executeHealthcheck();

        $this->assertFalse($result->isSuccess());
        $this->assertSame(HealthcheckErrorSource::PREVARISC, $result->getErrorSource());
        $this->assertNotNull($result->getMessage());
        $this->assertNull($result->getMaintenanceInfo());
        $this->assertNull($result->getVersionInfo());
    }

    public function testPrevariscErrorMessageMentionsLogs(): void
    {
        $service = $this->buildService();

        $result = $service->executeHealthcheck();

        // Le message doit guider l'utilisateur vers la documentation
        $this->assertStringContainsString('Veuillez consulter les logs', (string) $result->getMessage());
    }

    public function testResultIsJsonSerializable(): void
    {
        $service = $this->buildService();

        $result = $service->executeHealthcheck();
        $json = json_encode($result);

        $this->assertNotFalse($json);

        $data = json_decode($json, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('errorSource', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('maintenance', $data);
    }
}
