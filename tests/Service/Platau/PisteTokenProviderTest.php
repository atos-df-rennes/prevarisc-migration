<?php

declare(strict_types=1);

namespace App\Tests\Service\Platau;

use App\Exception\PisteTokenException;
use App\Service\Platau\PisteTokenProvider;
use App\Service\Platau\PlatauGatewayConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\Cache\ItemInterface;

final class PisteTokenProviderTest extends TestCase
{
    private function buildProvider(MockHttpClient $client, ?ArrayAdapter $cache = null): PisteTokenProvider
    {
        $config = $this->createStub(PlatauGatewayConfig::class);
        $config->method('getPisteClientId')->willReturn('id');
        $config->method('getPisteClientSecret')->willReturn('secret');
        $config->method('getPisteTokenUrl')->willReturn('https://piste.test/token');
        $config->method('getHttpClientOptions')->willReturn(['timeout' => 5, 'max_duration' => 10]);

        return new PisteTokenProvider($config, $client, $cache ?? new ArrayAdapter(), new NullLogger());
    }

    public function testTokenIsCachedUntilFiveMinutesBeforeExpiration(): void
    {
        $clock = new MockClock();
        $cache = new ArrayAdapter(clock: $clock);
        $client = new MockHttpClient([
            new MockResponse('{"access_token":"first","expires_in":3600}'),
            new MockResponse('{"access_token":"second","expires_in":3600}'),
        ]);
        $provider = $this->buildProvider($client, $cache);
        $before = microtime(true);

        $this->assertSame('first', $provider->getToken());
        $expiry = $cache->getItem('platau_piste_token')->getMetadata()[ItemInterface::METADATA_EXPIRY];
        $this->assertGreaterThanOrEqual($before + 3300, $expiry);
        $this->assertLessThanOrEqual(microtime(true) + 3300, $expiry);

        $clock->sleep(3299);
        $this->assertSame('first', $provider->getToken());
        $this->assertSame(1, $client->getRequestsCount());

        $clock->sleep(2);
        $this->assertSame('second', $provider->getToken());
        $this->assertSame(2, $client->getRequestsCount());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function nonCacheableExpirations(): iterable
    {
        yield 'expiration absente' => [''];
        yield 'expiration invalide' => [',"expires_in":"invalid"'];
        yield 'déjà expiré' => [',"expires_in":0'];
        yield 'dans la marge' => [',"expires_in":300'];
    }

    #[DataProvider('nonCacheableExpirations')]
    public function testTokenWithoutUsableExpirationIsNotCached(string $expiration): void
    {
        $cache = new ArrayAdapter();
        $client = new MockHttpClient([
            new MockResponse('{"access_token":"first"'.$expiration.'}'),
            new MockResponse('{"access_token":"second","expires_in":3600}'),
        ]);
        $provider = $this->buildProvider($client, $cache);

        $this->assertSame('first', $provider->getToken());
        $this->assertFalse($cache->hasItem('platau_piste_token'));
        $this->assertSame('second', $provider->getToken());
        $this->assertSame(2, $client->getRequestsCount());
    }

    /**
     * @return iterable<string, array{0: MockResponse}>
     */
    public static function failures(): iterable
    {
        yield 'erreur OAuth' => [new MockResponse('{"error":"invalid_client"}', ['http_code' => 401])];
        yield 'JSON invalide' => [new MockResponse('not JSON')];
        yield 'corps vide' => [new MockResponse('')];
        yield 'scalaire JSON' => [new MockResponse('true')];
        yield 'token absent' => [new MockResponse('{}')];
        yield 'token invalide' => [new MockResponse('{"access_token":42,"expires_in":3600}')];
        yield 'transport avant les headers' => [new MockResponse('', ['error' => 'boom'])];
        yield 'transport pendant le corps' => [new MockResponse([new TransportException('boom')])];
    }

    #[DataProvider('failures')]
    public function testFailureIsNotCachedAndNextCallRetries(MockResponse $failure): void
    {
        $cache = new ArrayAdapter();
        $client = new MockHttpClient([$failure, new MockResponse('{"access_token":"tok","expires_in":3600}')]);
        $provider = $this->buildProvider($client, $cache);

        try {
            $provider->getToken();
            $this->fail('Une erreur PISTE doit être signalée.');
        } catch (PisteTokenException $pisteTokenException) {
            $this->assertStringContainsString('Veuillez consulter les logs', $pisteTokenException->getMessage());
        }

        $this->assertFalse($cache->hasItem('platau_piste_token'));
        $this->assertSame('tok', $provider->getToken());
        $this->assertSame(2, $client->getRequestsCount());
    }

    public function testNumericStringExpirationIsAccepted(): void
    {
        $client = new MockHttpClient([new MockResponse('{"access_token":"tok","expires_in":"3600"}')]);
        $provider = $this->buildProvider($client);

        $this->assertSame('tok', $provider->getToken());
        $this->assertSame('tok', $provider->getToken());
        $this->assertSame(1, $client->getRequestsCount());
    }

    public function testInvalidateRenewsToken(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"access_token":"old","expires_in":3600}'),
            new MockResponse('{"access_token":"new","expires_in":3600}'),
        ]);
        $provider = $this->buildProvider($client);

        $this->assertSame('old', $provider->getToken());
        $provider->invalidate();
        $this->assertSame('new', $provider->getToken());
    }

    public function testClearingCacheRenewsToken(): void
    {
        $cache = new ArrayAdapter();
        $client = new MockHttpClient([
            new MockResponse('{"access_token":"first","expires_in":3600}'),
            new MockResponse('{"access_token":"second","expires_in":3600}'),
        ]);
        $provider = $this->buildProvider($client, $cache);

        $this->assertSame('first', $provider->getToken());
        $cache->clear();
        $this->assertSame('second', $provider->getToken());
    }
}
