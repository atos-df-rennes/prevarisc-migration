<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Constant\Platau\HealthcheckErrorSource;
use App\Dto\Platau\HealthcheckStatus;
use App\Service\PlatauHealthcheckService;
use App\Service\PlatauHealthcheckStatusProvider;
use App\Service\ProxyConfigurationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PlatauHealthcheckHttpTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'prevarisc-platau-'.uniqid('', true);
        mkdir($this->projectDir.DIRECTORY_SEPARATOR.'var', 0777, true);
        mkdir($this->projectDir.DIRECTORY_SEPARATOR.'platau', 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->projectDir.'/var/*') ?: [] as $file) {
            unlink($file);
        }

        foreach (glob($this->projectDir.'/platau/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->projectDir.'/var');
        rmdir($this->projectDir.'/platau');
        rmdir($this->projectDir);
    }

    /**
     * @param array<string, string> $options
     */
    private function buildService(MockHttpClient $client, array $options = [], ?ProxyConfigurationService $proxy = null): PlatauHealthcheckService
    {
        $platauDir = $this->projectDir.DIRECTORY_SEPARATOR.'platau';
        $config = ['platau.options' => ['PISTE_CLIENT_ID' => 'id', 'PISTE_CLIENT_SECRET' => 'secret'] + $options];
        file_put_contents($platauDir.'/config.json', json_encode($config));

        putenv('PREVARISC_PLATAU_PATH='.$platauDir);

        return new PlatauHealthcheckService(
            'config.json',
            $this->projectDir,
            'test',
            new NullLogger(),
            $proxy ?? new ProxyConfigurationService(false, '0', 0, '', '', ''),
            $client
        );
    }

    private static function tokenResponse(): MockResponse
    {
        return new MockResponse((string) json_encode(['access_token' => 'tok', 'expires_in' => 3600]));
    }

    /**
     * @return iterable<string, array{0: int, 1: string, 2: bool, 3: int|null, 4: bool}>
     */
    public static function healthcheckResponses(): iterable
    {
        yield 'succès' => [200, '{"etatGeneral":true,"etatBdd":true,"version":"1.0"}', true, null, false];
        yield 'succès avec maintenance' => [200, '{"etatGeneral":true,"etatBdd":true,"maintenance":true,"typeMaintenance":"Technique"}', true, null, true];
        yield 'état général dégradé' => [200, '{"etatGeneral":false,"etatBdd":true}', false, HealthcheckErrorSource::PLATAU, false];
        yield 'bdd dégradée' => [200, '{"etatGeneral":true,"etatBdd":false}', false, HealthcheckErrorSource::PLATAU, false];
        yield 'HTTP 503' => [503, '{"etatGeneral":false}', false, HealthcheckErrorSource::PLATAU, false];
        yield 'JSON invalide' => [200, 'pas du json', false, HealthcheckErrorSource::PREVARISC, false];
        yield 'corps vide' => [200, '', false, HealthcheckErrorSource::PREVARISC, false];
    }

    #[DataProvider('healthcheckResponses')]
    public function testHealthcheckResponses(int $httpCode, string $body, bool $success, ?int $errorSource, bool $hasMaintenance): void
    {
        $client = new MockHttpClient([self::tokenResponse(), new MockResponse($body, ['http_code' => $httpCode])]);

        $result = $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/', 'PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token'])->executeHealthcheck();

        $this->assertSame($success, $result->isSuccess());
        $this->assertSame($errorSource, $result->getErrorSource());
        $this->assertSame($hasMaintenance, null !== $result->getMaintenanceInfo());
    }

    public function testUrlOverridesAndBearerAreUsed(): void
    {
        $requests = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [$method, $url, $options['headers']];

            return 1 === count($requests) ? self::tokenResponse() : new MockResponse('{"etatGeneral":true,"etatBdd":true}');
        });

        $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/', 'PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token'])->executeHealthcheck();

        $this->assertSame(['POST', 'https://piste.test/token'], array_slice($requests[0], 0, 2));
        $this->assertSame(['GET', 'https://platau.test/healthcheck'], array_slice($requests[1], 0, 2));
        $this->assertContains('Authorization: Bearer tok', $requests[1][2]);
    }

    public function testTransportErrorOnTokenIsPrevariscError(): void
    {
        $client = new MockHttpClient(static fn (): MockResponse => throw new TransportException('boom'));

        $result = $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/', 'PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token'])->executeHealthcheck();

        $this->assertFalse($result->isSuccess());
        $this->assertSame(HealthcheckErrorSource::PREVARISC, $result->getErrorSource());
    }

    public function testValidStoredTokenIsReused(): void
    {
        file_put_contents($this->projectDir.'/var/piste-test.json', json_encode(['access_token' => 'old', 'expires_in' => 3600, 'request_time' => time()]));
        $count = 0;
        $client = new MockHttpClient(function () use (&$count): MockResponse {
            ++$count;

            return new MockResponse('{"etatGeneral":true,"etatBdd":true}');
        });

        $result = $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/'])->executeHealthcheck();

        $this->assertTrue($result->isSuccess());
        $this->assertSame(1, $count);
    }

    public function testStatusIsCachedAndColored(): void
    {
        $count = 0;
        $client = new MockHttpClient(function () use (&$count): MockResponse {
            ++$count;

            return 1 === $count ? self::tokenResponse() : new MockResponse('{"etatGeneral":true,"etatBdd":true}');
        });
        $service = $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/', 'PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token']);
        $provider = new PlatauHealthcheckStatusProvider($service, new ArrayAdapter(), 300);

        $first = $provider->getStatus();
        $second = $provider->getStatus();

        $this->assertSame(2, $count);
        $this->assertEquals($first, $second);
        $this->assertSame(HealthcheckStatus::STATUS_OK, $first->getStatus());
        $this->assertSame(300, $first->getNextCheckAt()->getTimestamp() - $first->getCheckedAt()->getTimestamp());
    }

    /**
     * @return iterable<string, array{0: bool, 1: string, 2: string, 3: string, 4: array{proxy?: string}}>
     */
    public static function proxyConfigurations(): iterable
    {
        yield 'désactivé' => [false, 'HTTP', '', '', []];
        yield 'http sans auth' => [true, 'HTTP', '', '', ['proxy' => 'http://proxy.test:8080']];
        yield 'socks5 avec auth' => [true, '5', 'us er', 'p@ss', ['proxy' => 'socks5://us%20er:p%40ss@proxy.test:8080']];
    }

    /**
     * @param array{proxy?: string} $expected
     */
    #[DataProvider('proxyConfigurations')]
    public function testProxyHttpClientOptions(bool $enabled, string $protocol, string $user, string $password, array $expected): void
    {
        $proxy = new ProxyConfigurationService($enabled, $protocol, 8080, 'proxy.test', $user, $password);

        $this->assertSame($expected, $proxy->getHttpClientOptions());
    }
}
