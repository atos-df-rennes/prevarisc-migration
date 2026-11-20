<?php

declare(strict_types=1);

namespace App\Tests\Platau;

use App\Platau\ConsultationRetryStrategy;
use App\Platau\HttpConsultationReader;
use App\Platau\PisteTokenProvider;
use App\Platau\PlatauException;
use App\Platau\PlatauHttp;
use App\Service\ProxyConfigurationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\RetryableHttpClient;

final class ConsultationHttpTest extends TestCase
{
    private const array ROW = ['dossier' => ['consultations' => [['idConsultation' => 'ABC']]], 'autre' => null];
    private const string TOKEN_URL = 'https://piste.test/token';
    private const string BASE = 'https://platau.test/v13/dau/';

    public static function proxyModes(): iterable
    {
        yield 'proxy natif du transport' => [false];
        yield 'proxy explicite authentifié' => [true];
    }

    #[DataProvider('proxyModes')]
    public function testPostRequestsAndPaginationUntilFirstResult(bool $proxy): void
    {
        $http = $this->http($proxy);
        $calls = [];
        $transport = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls, $proxy): MockResponse {
            $this->assertSame('POST', $method);
            $this->assertSame($proxy ? 'socks5://user:p%40ss@proxy.test:8080' : null, $options['proxy'] ?? null);
            $this->assertSame($proxy ? '' : null, $options['no_proxy'] ?? null);
            if (self::TOKEN_URL === $url) {
                $this->assertSame('grant_type=client_credentials', $options['body']);
                $this->assertContains('Authorization: Basic '.base64_encode('client:secret'), $options['headers']);
                $this->assertArrayNotHasKey('id-acteur-appelant', $options['normalized_headers']);
                $calls[] = 'token';

                return $this->token('piste-token');
            }
            $this->assertSame(self::BASE.'consultations/recherche', explode('?', $url)[0]);
            $this->assertSame(['criteresSurConsultations' => ['idConsultation' => 'ABC']], json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR));
            $this->assertContains('Authorization: Bearer piste-token', $options['headers']);
            $this->assertContains('Id-Acteur-Appelant: actor', $options['headers']);
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $this->assertSame('DESC', $query['sensTri']);
            $this->assertSame('DT_LIMITE_DE_REPONSE', $query['colonneTri']);
            $calls[] = [$query['numeroPage'], $query['nbElementsParPage']];
            return new JsonMockResponse(['nombrePages' => 3, 'resultats' => '0' === $query['numeroPage'] ? [] : [self::ROW]]);
        }, self::BASE);
        $reader = new HttpConsultationReader($transport, $this->provider($transport, $http), $http, 'actor');

        $this->assertSame(self::ROW, $reader->details('ABC'));
        $this->assertSame(['token', ['0', '10'], ['1', '10']], $calls);
    }

    public function testTokenExpiryMarginAndEnvironmentIsolation(): void
    {
        $clock = new MockClock();
        $cache = new ArrayAdapter(clock: $clock);
        $transport = new MockHttpClient([$this->token('one', 302), $this->token('two'), $this->token('three', 30), $this->token('four')]);
        $http = $this->http();
        $prod = $this->provider($transport, $http, $cache);
        $prevision = $this->provider($transport, $http, $cache, 'prevision');
        $this->assertSame('one', $prod->token());
        $this->assertSame('two', $prevision->token());
        $clock->sleep(1);
        $this->assertSame('one', $prod->token());
        $clock->sleep(2);
        $this->assertSame('three', $prod->token());
        $this->assertSame('four', $prod->token());
        $this->assertSame('two', $prevision->token());
    }

    public function test401RenewsThenSucceeds(): void
    {
        $headers = [];
        $api = new MockHttpClient(static function (string $method, string $url, array $options) use (&$headers): MockResponse {
            $headers[] = $options['normalized_headers']['authorization'][0];

            return 1 === count($headers) ? new MockResponse('', ['http_code' => 401]) : new JsonMockResponse(['nombrePages' => 0, 'resultats' => [self::ROW]]);
        }, self::BASE);
        $http = $this->http();
        $provider = $this->provider(new MockHttpClient([$this->token('old'), $this->token('fresh')]), $http);
        $reader = new HttpConsultationReader($api, $provider, $http, 'actor');

        $this->assertSame(self::ROW, $reader->details('ABC'));
        $this->assertSame(['Authorization: Bearer old', 'Authorization: Bearer fresh'], $headers);
    }

    public function testSecond401IsFinalAndRejectedTokenIsNotCached(): void
    {
        $http = $this->http();
        $provider = $this->provider(new MockHttpClient([$this->token('old'), $this->token('fresh'), $this->token('next')]), $http);
        $api = new MockHttpClient([new MockResponse('', ['http_code' => 401]), new MockResponse('refus', ['http_code' => 401])], self::BASE);
        try {
            (new HttpConsultationReader($api, $provider, $http, 'actor'))->details('ABC');
            $this->fail('Le second refus doit être définitif.');
        } catch (PlatauException $exception) {
            $this->assertStringContainsString('HTTP 401 : refus', $exception->getMessage());
        }
        $this->assertSame('next', $provider->token());
    }

    public function testFiveRetriesRegardlessOfTokenRenewal(): void
    {
        $http = $this->http();
        $provider = $this->provider(new MockHttpClient([$this->token('old'), $this->token('fresh-token')]), $http);
        $responses = [];
        foreach ([429, 503, 500, 401, 503, 500] as $status) {
            $responses[] = new MockResponse('', ['http_code' => $status, 'response_headers' => ['Retry-After: 0']]);
        }
        $responses[] = new MockResponse(str_repeat('x', 17000).' fin fresh-token', ['http_code' => 503]);
        $api = new RetryableHttpClient(new MockHttpClient($responses, self::BASE), new ConsultationRetryStrategy(), ConsultationRetryStrategy::MAX_RETRIES);
        try {
            (new HttpConsultationReader($api, $provider, $http, 'actor'))->details('ABC');
            $this->fail('La sixième réponse transitoire doit être définitive.');
        } catch (PlatauException $exception) {
            $this->assertStringContainsString('HTTP 503 : '.str_repeat('x', 17000).' fin [masqué]', $exception->getMessage());
        }
    }

    public static function invalidTokenResponses(): iterable
    {
        yield 'statut avant décodage' => [403, 'refus secret', 'HTTP 403 : refus [masqué]'];
        yield 'ne pas exposer un jeton reçu par erreur' => [400, '{"access_token":"inattendu"}', 'HTTP 400 : {"access_token":"[masqué]"}'];
        yield 'JSON incorrect' => [200, 'illisible', 'PISTE : JSON invalide'];
        yield 'jeton vide' => [200, '{"access_token":""}', 'token utilisable'];
    }

    #[DataProvider('invalidTokenResponses')]
    public function testPisteFailure(int $status, string $body, string $expected): void
    {
        $provider = $this->provider(new MockHttpClient([new MockResponse($body, ['http_code' => $status])]), $this->http());
        $this->expectException(PlatauException::class);
        $this->expectExceptionMessage($expected);
        $provider->token();
    }

    public function testTransportFailureDoesNotLeakProxyCredentials(): void
    {
        $http = $this->http(true);
        $api = new MockHttpClient(static fn () => throw new TransportException('proxy http://user:p%40ss@proxy.test ; Bearer abc'), self::BASE);
        $reader = new HttpConsultationReader($api, $this->provider(new MockHttpClient([$this->token('abc')]), $http), $http, 'actor');
        $this->expectException(PlatauException::class);
        $this->expectExceptionMessage('proxy http://[masqué]@proxy.test ; Bearer [masqué]');
        $reader->details('ABC');
    }

    public static function invalidConsultationResponses(): iterable
    {
        yield 'aucun résultat' => [['nombrePages' => 1, 'resultats' => []], 'la consultation ABC est introuvable'];
        yield 'pagination absente' => [['message' => 'incorrect'], 'pagination'];
        yield 'résultat incomplet' => [['nombrePages' => 1, 'resultats' => [['dossier' => []]]], 'une consultation par dossier'];
    }

    #[DataProvider('invalidConsultationResponses')]
    public function testInvalidConsultationResponse(array $data, string $expected): void
    {
        $http = $this->http();
        $provider = $this->provider(new MockHttpClient([$this->token('abc')]), $http);
        $api = new MockHttpClient([new JsonMockResponse($data)], self::BASE);
        $this->expectException(PlatauException::class);
        $this->expectExceptionMessage($expected);
        (new HttpConsultationReader($api, $provider, $http, 'actor'))->details('ABC');
    }

    public static function incompleteConfigurations(): iterable
    {
        yield 'acteur manquant' => ['', 'client', 'secret', 'acteur'];
        yield 'client manquant' => ['actor', '', 'secret', 'identifiants client PISTE'];
        yield 'secret manquant' => ['actor', 'client', '', 'identifiants client PISTE'];
    }

    #[DataProvider('incompleteConfigurations')]
    public function testIncompleteConfigurationFailsBeforeHttp(string $actor, string $clientId, string $secret, string $expected): void
    {
        $http = $this->http();
        $transport = new MockHttpClient([]);
        $provider = new PisteTokenProvider($transport, $http, new ArrayAdapter(), $clientId, $secret, self::TOKEN_URL, 'prod');
        $this->expectException(PlatauException::class);
        $this->expectExceptionMessage($expected);
        (new HttpConsultationReader($transport, $provider, $http, $actor))->details('ABC');
    }

    private function http(bool $proxy = false): PlatauHttp
    {
        return new PlatauHttp(new ProxyConfigurationService($proxy, '5', 8080, 'proxy.test', 'user', 'p@ss'), 'client', 'secret');
    }

    private function provider(MockHttpClient $client, PlatauHttp $http, ?ArrayAdapter $cache = null, string $env = 'prod'): PisteTokenProvider
    {
        return new PisteTokenProvider($client, $http, $cache ?? new ArrayAdapter(), 'client', 'secret', self::TOKEN_URL, $env);
    }

    private function token(string $token, int $ttl = 3600): JsonMockResponse
    {
        return new JsonMockResponse(['access_token' => $token, 'expires_in' => $ttl]);
    }
}
