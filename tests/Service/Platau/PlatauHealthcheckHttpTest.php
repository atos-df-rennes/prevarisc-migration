<?php

declare(strict_types=1);

namespace App\Tests\Service\Platau;

use App\Constant\Platau\HealthcheckErrorSource;
use App\Dto\Platau\HealthcheckStatus;
use App\Dto\Platau\MaintenanceInfo;
use App\Service\Platau\PisteTokenProvider;
use App\Service\Platau\PlatauGatewayConfig;
use App\Service\Platau\PlatauHealthcheckService;
use App\Service\Platau\PlatauHealthcheckStatusProvider;
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

    private string|false $previousPlatauPath;

    protected function setUp(): void
    {
        $this->previousPlatauPath = getenv('PREVARISC_PLATAU_PATH');
        $this->projectDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'prevarisc-platau-'.uniqid('', true);
        mkdir($this->projectDir.DIRECTORY_SEPARATOR.'var', 0777, true);
        mkdir($this->projectDir.DIRECTORY_SEPARATOR.'platau', 0777, true);
    }

    protected function tearDown(): void
    {
        putenv(false === $this->previousPlatauPath ? 'PREVARISC_PLATAU_PATH' : 'PREVARISC_PLATAU_PATH='.$this->previousPlatauPath);
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
    private function buildService(MockHttpClient $client, array $options = [], ?ProxyConfigurationService $proxy = null, ?ArrayAdapter $tokenCache = null): PlatauHealthcheckService
    {
        $platauDir = $this->projectDir.DIRECTORY_SEPARATOR.'platau';
        $config = ['platau.options' => ['PISTE_CLIENT_ID' => 'id', 'PISTE_CLIENT_SECRET' => 'secret'] + $options];
        file_put_contents($platauDir.'/config.json', json_encode($config));

        putenv('PREVARISC_PLATAU_PATH='.$platauDir);

        $logger = new NullLogger();
        $config = new PlatauGatewayConfig(
            'config.json',
            $this->projectDir,
            $logger,
            $proxy ?? new ProxyConfigurationService(false, '0', 0, '', '', '')
        );

        return new PlatauHealthcheckService(
            $config,
            new PisteTokenProvider($config, $client, $tokenCache ?? new ArrayAdapter(), $logger),
            $client,
            $logger
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
        yield 'HTTP 503 avec maintenance' => [503, '{"maintenance":true,"version":"1.0"}', false, HealthcheckErrorSource::PLATAU, true];
        yield 'HTTP 503 non JSON' => [503, '<html>Indisponible</html>', false, HealthcheckErrorSource::PLATAU, false];
        yield 'HTTP 503 scalaire JSON' => [503, 'null', false, HealthcheckErrorSource::PLATAU, false];
        yield 'HTTP 503 vide' => [503, '', false, HealthcheckErrorSource::PREVARISC, false];
        yield 'HTTP 302 non JSON' => [302, 'redirect', false, HealthcheckErrorSource::PLATAU, false];
        yield 'HTTP 201' => [201, '{"etatGeneral":true,"etatBdd":true}', true, null, false];
        yield 'objet vide' => [200, '{}', false, HealthcheckErrorSource::PLATAU, false];
        yield 'liste JSON' => [200, '[{"etatGeneral":true,"etatBdd":true}]', false, HealthcheckErrorSource::PLATAU, false];
        yield 'liste vide' => [200, '[]', false, HealthcheckErrorSource::PLATAU, false];
        yield 'scalaire JSON' => [200, 'true', false, HealthcheckErrorSource::PREVARISC, false];
        yield 'états non booléens' => [200, '{"etatGeneral":1,"etatBdd":true}', false, HealthcheckErrorSource::PLATAU, false];
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
        $this->assertSame($hasMaintenance, $result->getMaintenanceInfo() instanceof MaintenanceInfo);
        if ($hasMaintenance) {
            $this->assertSame(200 === $httpCode ? null : '1.0', $result->getVersionInfo()?->getVersion());
        }
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

    public function testValidCachedTokenIsReused(): void
    {
        $cache = new ArrayAdapter();
        $item = $cache->getItem('platau_piste_token');
        $item->set('old')->expiresAfter(3300);
        $cache->save($item);
        $count = 0;
        $client = new MockHttpClient(function () use (&$count): MockResponse {
            ++$count;

            return new MockResponse('{"etatGeneral":true,"etatBdd":true}');
        });

        $result = $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/'], tokenCache: $cache)->executeHealthcheck();

        $this->assertTrue($result->isSuccess());
        $this->assertSame(1, $count);
    }

    public function testProxyAndTimeoutsAreUsedForBothRequests(): void
    {
        $requests = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = $options;

            return 1 === count($requests) ? self::tokenResponse() : new MockResponse('{"etatGeneral":true,"etatBdd":true}');
        });
        $proxy = new ProxyConfigurationService(true, 'HTTP', 8080, 'proxy.test', '', '');

        $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/', 'PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token'], $proxy)->executeHealthcheck();

        $this->assertCount(2, $requests);
        foreach ($requests as $options) {
            $this->assertSame('http://proxy.test:8080', $options['proxy']);
            $this->assertEqualsWithDelta(5.0, $options['timeout'], PHP_FLOAT_EPSILON);
            $this->assertEqualsWithDelta(5.0, $options['max_duration'], PHP_FLOAT_EPSILON);
            $this->assertSame(0, $options['max_redirects']);
        }
    }

    public function testRejectedTokenIsRenewedAndHealthcheckRetriedOnce(): void
    {
        $cache = new ArrayAdapter();
        $tokens = ['old', 'new'];
        $authorizations = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$tokens, &$authorizations): MockResponse {
            if ('POST' === $method) {
                return new MockResponse((string) json_encode(['access_token' => array_shift($tokens), 'expires_in' => 3600]));
            }

            $authorizations[] = $options['normalized_headers']['authorization'][0];

            return 1 === count($authorizations)
                ? new MockResponse('{"message":"expired"}', ['http_code' => 401])
                : new MockResponse('{"etatGeneral":true,"etatBdd":true}');
        });

        $result = $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/', 'PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token'], tokenCache: $cache)->executeHealthcheck();

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['Authorization: Bearer old', 'Authorization: Bearer new'], $authorizations);
    }

    public function testRejectedTokenIsRetriedOnlyOnce(): void
    {
        $healthchecks = 0;
        $client = new MockHttpClient(function (string $method) use (&$healthchecks): MockResponse {
            if ('POST' === $method) {
                return self::tokenResponse();
            }

            ++$healthchecks;

            return new MockResponse('{}', ['http_code' => 401]);
        });

        $result = $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/', 'PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token'])->executeHealthcheck();

        $this->assertSame(HealthcheckErrorSource::PLATAU, $result->getErrorSource());
        $this->assertSame(2, $healthchecks);
    }

    /**
     * @return iterable<string, array{0: MockResponse}>
     */
    public static function transportErrors(): iterable
    {
        yield 'avant les headers' => [new MockResponse('', ['error' => 'boom'])];
        yield 'pendant le corps' => [new MockResponse([new TransportException('boom')])];
    }

    #[DataProvider('transportErrors')]
    public function testLazyTransportErrorOnHealthcheckIsPrevariscError(MockResponse $response): void
    {
        $client = new MockHttpClient([self::tokenResponse(), $response]);

        $result = $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/', 'PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token'])->executeHealthcheck();

        $this->assertSame(HealthcheckErrorSource::PREVARISC, $result->getErrorSource());
        $this->assertStringContainsString('La réponse Plat&#039;AU n&#039;a pas pu être récupérée.', (string) $result->getMessage());
    }

    public function testTokenFailureIsNotCached(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"error":"invalid_client"}', ['http_code' => 401]),
            self::tokenResponse(),
            new MockResponse('{"etatGeneral":true,"etatBdd":true}'),
        ]);
        $service = $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/', 'PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token']);

        $this->assertSame(HealthcheckErrorSource::PREVARISC, $service->executeHealthcheck()->getErrorSource());
        $this->assertTrue($service->executeHealthcheck()->isSuccess());
        $this->assertSame(3, $client->getRequestsCount());
    }

    public function testUnreadableTokenKeepsItsErrorMessage(): void
    {
        $client = new MockHttpClient([new MockResponse('{"access_token":42,"expires_in":3600}')]);
        $result = $this->buildService($client, ['PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token'])->executeHealthcheck();

        $this->assertSame(HealthcheckErrorSource::PREVARISC, $result->getErrorSource());
        $this->assertStringContainsString('L&#039;identifiant de connexion à PISTE n&#039;est pas lisible.', (string) $result->getMessage());
    }

    public function testCachedStatusNeverTriggersHealthcheck(): void
    {
        $count = 0;
        $client = new MockHttpClient(function () use (&$count): MockResponse {
            ++$count;

            return 1 === $count ? self::tokenResponse() : new MockResponse('{"etatGeneral":true,"etatBdd":true}');
        });
        $service = $this->buildService($client, ['PLATAU_URL' => 'https://platau.test/', 'PISTE_ACCESS_TOKEN_URL' => 'https://piste.test/token']);
        $provider = new PlatauHealthcheckStatusProvider($service, new ArrayAdapter(), 300);

        $this->assertNull($provider->getCachedStatus());
        $this->assertSame(0, $count);

        $first = $provider->getStatus();

        $this->assertEquals($first, $provider->getCachedStatus());
        $this->assertSame(2, $count);
        $this->assertSame(HealthcheckStatus::STATUS_OK, $first->getStatus());
        $this->assertSame("Plat'AU est disponible", $first->getLabel());
        $this->assertSame(300, $first->getExpiresAt()->getTimestamp() - $first->getCheckedAt()->getTimestamp());
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
