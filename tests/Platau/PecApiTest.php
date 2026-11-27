<?php

declare(strict_types=1);

namespace App\Tests\Platau;

use App\Platau\ConsultationRetryStrategy;
use App\Platau\PecApi;
use App\Platau\PisteTokenProvider;
use App\Platau\PlatauException;
use App\Platau\PlatauHttp;
use App\Service\ProxyConfigurationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\RetryableHttpClient;

final class PecApiTest extends TestCase
{
    use ClockSensitiveTrait;

    private const string BASE = 'https://platau.test/v13/dau/';

    private const array AUTHOR = ['prenomAuteur' => 'Anne', 'nomAuteur' => 'Martin', 'emailAuteur' => null, 'telephoneAuteur' => '0102030405'];

    private const array CONSULTATION = ['idConsultation' => 'C', 'noVersion' => 8, 'nomEtatConsultation' => ['idNom' => 2], 'delaiDeReponse' => 1, 'nomTypeDelai' => ['libNom' => 'Mois']];

    private const array PAYLOAD = [['idDossier' => 'D', 'noVersion' => 3, 'consultations' => [['idConsultation' => 'C', 'noVersion' => 8, 'pecMetier' => ['documents' => []]]]]];

    public function testGlobalCandidatesAreCollectedBeforeFirstYield(): void
    {
        $pages = [];
        $api = $this->api(function (string $method, string $url, array $options) use (&$pages): JsonMockResponse {
            $this->assertSame(self::BASE.'consultations/recherche', explode('?', $url)[0]);
            $this->assertSame('POST', $method);
            $this->assertSame(['criteresSurConsultations' => ['nomEtatConsultation' => [1, 2]]], json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR));
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $this->assertSame('DT_LIMITE_DE_REPONSE', $query['colonneTri']);
            $this->assertSame('DESC', $query['sensTri']);
            $pages[] = $query['numeroPage'];

            return match ($query['numeroPage']) {
                '0' => $this->page([
                    self::CONSULTATION,
                    ['idConsultation' => 'hors-lot', 'nomEtatConsultation' => ['idNom' => 3]],
                    ['idConsultation' => 'B', 'nomEtatConsultation' => ['idNom' => 1]],
                ], 3),
                '1' => new JsonMockResponse(['resultats' => [], 'nombrePages' => 3]),
                '2' => $this->page([self::CONSULTATION, ['idConsultation' => 'Z', 'nomEtatConsultation' => ['idNom' => 2]]], 3),
            };
        });

        $result = [];
        foreach ($api->candidates(null) as $candidate) {
            $this->assertSame(['0', '1', '2'], $pages);
            $result[] = $candidate;
        }
        $this->assertSame([['id' => 'C', 'state' => 2], ['id' => 'B', 'state' => 1], ['id' => 'Z', 'state' => 2]], $result);
    }

    public function testTargetedCandidateAndFreshPayloadUseTheRequestedConsultation(): void
    {
        $searches = 0;
        $api = $this->api(function (string $method, string $url, array $options) use (&$searches): JsonMockResponse {
            $this->assertSame(['criteresSurConsultations' => ['idConsultation' => 'C']], json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR));

            return $this->page([
                ['idConsultation' => 'other'],
                ['noVersion' => ++$searches, 'nomEtatConsultation' => ['idNom' => 3]] + self::CONSULTATION,
            ]);
        });
        $this->assertSame([['id' => 'C', 'state' => 3]], iterator_to_array($api->candidates('C')));
        $payload = $api->payload('C', false, 'Plan manquant', self::AUTHOR, '2025-01-31', null);
        $this->assertSame('D', $payload[0]['idDossier']);
        $this->assertSame(3, $payload[0]['noVersion']);
        $consultation = $payload[0]['consultations'][0];
        $this->assertSame('C', $consultation['idConsultation']);
        $this->assertSame(2, $consultation['noVersion']);
        $this->assertSame([
            'dtPecMetier' => '2025-01-31', 'dtLimiteReponse' => '2025-03-03',
            'nomStatutPecMetier' => 2, 'idActeurEmetteur' => 'actor',
            'txObservations' => 'Plan manquant', 'documents' => [],
            'prenomAuteur' => 'Anne', 'nomAuteur' => 'Martin', 'telephoneAuteur' => '0102030405',
        ], $consultation['pecMetier']);
    }

    public static function delays(): iterable
    {
        yield 'jours calendaires' => [null, 'Jours calendaires', '2024-03-01'];
        yield 'priorité au délai explicite en jours' => [1, 'Mois', '2024-02-29'];
        yield 'zéro jour' => [0, null, '2024-02-28'];
    }

    #[DataProvider('delays')]
    public function testDeadlineFromCurrentDate(?int $delay, ?string $unit, string $deadline): void
    {
        self::mockTime('2024-02-28 12:00:00');
        $api = $this->api([$this->page([['nomTypeDelai' => ['libNom' => $unit], 'delaiDeReponse' => 2] + self::CONSULTATION])]);
        $pec = $api->payload('C', true, null, self::AUTHOR, null, $delay)[0]['consultations'][0]['pecMetier'];
        $this->assertSame(1, $pec['nomStatutPecMetier']);
        $this->assertSame('', $pec['txObservations']);
        $this->assertSame('2024-02-28', $pec['dtPecMetier']);
        $this->assertSame($deadline, $pec['dtLimiteReponse']);
    }

    public static function invalidDetails(): iterable
    {
        yield 'date impossible' => ['2025-02-29', null, [], 'Date de PEC'];
        yield 'délai personnalisé négatif' => [null, -1, [], 'Délai'];
        yield 'délai distant absent' => [null, null, ['delaiDeReponse' => null], 'Délai'];
        yield 'unité inconnue' => [null, null, ['nomTypeDelai' => ['libNom' => 'Jours ouvrés']], 'Type de délai'];
        yield 'version manquante' => [null, null, ['noVersion' => null], 'versions'];
        yield 'autre consultation' => [null, null, ['idConsultation' => 'autre'], 'introuvable'];
    }

    #[DataProvider('invalidDetails')]
    public function testInvalidDetailFailsDuringPreparation(?string $date, ?int $delay, array $overrides, string $reason): void
    {
        $api = $this->api([$this->page([$overrides + self::CONSULTATION])]);
        $this->expectException(PlatauException::class);
        $this->expectExceptionMessageIsOrContains($reason);
        $api->payload('C', true, null, self::AUTHOR, $date, $delay);
    }

    public function testSendPreservesDocumentOrderAndReturnsRemoteDocuments(): void
    {
        $documents = [25 => ['fileId' => 'one'], 12 => ['fileId' => 'two']];
        $remote = [['idDocument' => 'A', 'noVersion' => 1], ['idDocument' => 'B']];
        $api = $this->api(function (string $method, string $url, array $options) use ($documents, $remote): JsonMockResponse {
            $this->assertSame('POST', $method);
            $this->assertSame(self::BASE.'pecMetier/consultations', $url);
            $this->assertContains('Id-Acteur-Appelant: actor', $options['headers']);
            $expected = self::PAYLOAD;
            $expected[0]['consultations'][0]['pecMetier']['documents'] = array_values($documents);
            $this->assertSame($expected, json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR));

            return new JsonMockResponse([['idDossier' => 'D', 'erreurs' => [], 'consultations' => [['idConsultation' => 'C', 'pecMetier' => ['documents' => $remote]]]]]);
        });
        $this->assertSame($remote, $api->send(self::PAYLOAD, $documents));
    }

    public static function invalidResponses(): iterable
    {
        yield 'erreurs métier malgré le HTTP 200' => [[['erreurs' => [['codeErreur' => 400]]] + self::PAYLOAD[0]]];
        yield 'autre dossier' => [[['idDossier' => 'autre'] + self::PAYLOAD[0]]];
        yield 'consultation absente' => [[['consultations' => []]]];
        yield 'PEC absente' => [[['consultations' => [['idConsultation' => 'C']]]]];
    }

    #[DataProvider('invalidResponses')]
    public function testInvalidAcknowledgement(array $response): void
    {
        $api = $this->api([new JsonMockResponse($response)]);
        $this->expectException(PlatauException::class);
        $api->send(self::PAYLOAD, []);
    }

    public static function incompleteDocumentLists(): iterable
    {
        yield 'liste absente' => [[], []];
        yield 'nombre différent' => [['documents' => [['idDocument' => 'B']]], [['idDocument' => 'B']]];
        yield 'première position sans identifiant' => [['documents' => [['noVersion' => 1], ['idDocument' => 'B']]], [['noVersion' => 1], ['idDocument' => 'B']]];
    }

    #[DataProvider('incompleteDocumentLists')]
    public function testIncompleteDocumentMappingDoesNotRejectAcceptedPec(array $fields, array $expected): void
    {
        $response = [['consultations' => [['idConsultation' => 'C', 'pecMetier' => ['idPecMetier' => 'PEC'] + $fields]]]];
        $api = $this->api([new JsonMockResponse($response)]);
        $this->assertSame($expected, $api->send(self::PAYLOAD, [54 => ['fileId' => 'first'], 12 => ['fileId' => 'second']]));
    }

    public static function writeFailures(): iterable
    {
        yield '401 définitif' => [[401, 401], 'HTTP 401'];
        yield '503 après renouvellement' => [[401, 503], 'HTTP 503'];
        yield '500 sans renouvellement' => [[500], 'HTTP 500'];
        yield 'connexion perdue' => [[0], "Impossible de communiquer avec Plat'AU"];
        yield '202 ne confirme pas la PEC' => [[202], 'HTTP 202'];
    }

    #[DataProvider('writeFailures')]
    public function testOnlyRefusedAuthenticationPermitsReplayingAWrite(array $statuses, string $reason): void
    {
        $calls = 0;
        $api = $this->api(function () use ($statuses, &$calls): MockResponse {
            $this->assertArrayHasKey($calls, $statuses, 'Rejeu inattendu du dépôt.');
            $status = $statuses[$calls++];

            return 0 === $status ? new MockResponse('', ['error' => 'connexion perdue'])
                : new JsonMockResponse(self::PAYLOAD, ['http_code' => $status, 'response_headers' => ['Retry-After: 0']]);
        });
        try {
            $api->send(self::PAYLOAD, []);
            $this->fail('Le dépôt ne doit pas être confirmé.');
        } catch (PlatauException $platauException) {
            $this->assertStringContainsString($reason, $platauException->getMessage());
            $this->assertCount($calls, $statuses);
        }
    }

    private function page(array $consultations, int $pages = 1): JsonMockResponse
    {
        return new JsonMockResponse(['nombrePages' => $pages, 'resultats' => [['dossier' => [
            'idDossier' => 'D', 'noVersion' => 3, 'consultations' => $consultations,
        ]]]]);
    }

    private function api(array|callable $responses): PecApi
    {
        $http = new PlatauHttp(new ProxyConfigurationService(false, '0', 0, '', '', ''));
        $tokens = new PisteTokenProvider(new MockHttpClient(static fn (): JsonMockResponse => new JsonMockResponse(['access_token' => 'token', 'expires_in' => 3600])), $http, new ArrayAdapter(), 'client', 'secret', 'https://piste.test/token', 'test');
        $client = new RetryableHttpClient(new MockHttpClient($responses, self::BASE), new ConsultationRetryStrategy());

        return new PecApi($client, $tokens, $http, 'actor');
    }
}
