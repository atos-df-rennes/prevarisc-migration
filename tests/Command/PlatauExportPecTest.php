<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\WithEnvironmentVariable;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
#[Group('database')]
#[WithEnvironmentVariable('PISTE_CLIENT_ID', 'client-recette')]
#[WithEnvironmentVariable('PISTE_CLIENT_SECRET', 'secret-fictif')]
#[WithEnvironmentVariable('PISTE_ACCESS_TOKEN_URL', 'https://oauth.example.test/token')]
#[WithEnvironmentVariable('PLATAU_URL', 'https://api.example.test/dau/')]
#[WithEnvironmentVariable('PLATAU_ID_ACTEUR_APPELANT', 'ACTEUR-TEST')]
#[WithEnvironmentVariable('SYNCPLICITY_PISTE_CLIENT_ID', '')]
#[WithEnvironmentVariable('SYNCPLICITY_PISTE_CLIENT_SECRET', '')]
final class PlatauExportPecTest extends KernelTestCase
{
    private const string ID = 'AAA-BBB-001';

    private Connection $db;

    private ApplicationTester $console;

    private MockHttpClient $http;

    protected function setUp(): void
    {
        $application = new Application(self::bootKernel());
        $application->setAutoExit(false);
        $this->console = new ApplicationTester($application);
        $this->db = self::getContainer()->get(Connection::class);
        self::getContainer()->set('cache.platau_consultation_tokens', new ArrayAdapter());
        $this->http = new MockHttpClient([]);
        self::getContainer()->set('http_client.transport', $this->http);
    }

    public function testRenvoiNegatifAvecDateInitialeEtDelaiForce(): void
    {
        $id = $this->dossier(1, 'to_export');
        $this->db->insert('dossierdocmanquant', ['ID_DOSSIER' => $id, 'NUM_DOCSMANQUANT' => '1', 'DOCMANQUANT' => 'Notice illisible']);
        $accepted = new JsonMockResponse([[
            'idDossier' => 'DOS', 'noVersion' => 4, 'consultations' => [[
                'idConsultation' => self::ID, 'pecMetier' => ['idPecMetier' => 'PEC', 'documents' => []],
            ]],
        ]]);
        $this->http->setResponseFactory([$this->token(), $this->consultation(2), $this->consultation(2), $accepted]);
        $this->assertSame(Command::SUCCESS, $this->console->run(['command' => 'export-pec', '--consultation-id' => self::ID, '--delai-reponse' => '10']), $this->console->getDisplay());
        $this->assertSame('https://api.example.test/dau/pecMetier/consultations', $accepted->getRequestUrl());
        $body = json_decode($accepted->getRequestOptions()['body'], true, flags: JSON_THROW_ON_ERROR);
        $pec = $body[0]['consultations'][0]['pecMetier'];
        $this->assertSame('2026-10-28', $pec['dtPecMetier']);
        $this->assertSame('2026-11-07', $pec['dtLimiteReponse']);
        $this->assertSame(2, $pec['nomStatutPecMetier']);
        $this->assertSame('Notice illisible', $pec['txObservations']);
        $this->assertSame([], $pec['documents']);
        $this->assertSame(['STATUT_PEC' => 'taken_into_account', 'DATE_PEC' => date('Y-m-d'), 'STATUT_AVIS' => 'in_progress'], $this->statuts());
    }

    /** @return iterable<string, array{?int, int}> */
    public static function nonEligibles(): iterable
    {
        yield 'complétude non renseignée' => [null, 1];
        yield 'consultation refusée sans relance' => [0, 2];
    }

    #[DataProvider('nonEligibles')]
    public function testAucunEnvoiSansEligibilite(?int $incomplet, int $etat): void
    {
        $this->dossier($incomplet);
        $this->http->setResponseFactory([$this->token(), $this->consultation($etat)]);
        $this->assertSame(Command::SUCCESS, $this->console->run(['command' => 'platau:export-pec']));
        $this->assertSame(2, $this->http->getRequestsCount());
        $this->assertSame('awaiting', $this->statuts()['STATUT_PEC']);
    }

    public function testRejetDeLaPecPositiveSansAvancerLAvis(): void
    {
        $this->dossier(0);
        $rejet = new JsonMockResponse([], ['http_code' => 403]);
        $this->http->setResponseFactory([$this->token(), $this->consultation(), $this->consultation(), $rejet]);
        $this->assertSame(Command::SUCCESS, $this->console->run(['command' => 'platau:export-pec']));
        $this->assertStringContainsString('403', $this->console->getDisplay());
        $this->assertSame('in_error', $this->statuts()['STATUT_PEC']);
        $this->assertNull($this->statuts()['STATUT_AVIS']);
    }

    private function dossier(?int $incomplet, string $statut = 'awaiting'): int
    {
        $this->db->insert('platauconsultation', ['ID_PLATAU' => self::ID, 'STATUT_PEC' => $statut, 'DATE_PEC' => '2026-10-28']);
        $this->db->insert('dossier', ['ID_PLATAU' => self::ID, 'TYPE_DOSSIER' => 1, 'INCOMPLET_DOSSIER' => $incomplet, 'DATEINSERT_DOSSIER' => '2026-10-01']);

        return (int) $this->db->lastInsertId();
    }

    private function token(): JsonMockResponse
    {
        return new JsonMockResponse(['access_token' => 'jeton-recette', 'expires_in' => 3600]);
    }

    private function consultation(int $etat = 1): JsonMockResponse
    {
        return new JsonMockResponse(['nombrePages' => 1, 'resultats' => [['dossier' => [
            'idDossier' => 'DOS', 'noVersion' => 3,
            'consultations' => [['idConsultation' => self::ID, 'noVersion' => 1, 'nomEtatConsultation' => ['idNom' => $etat],
                'delaiDeReponse' => 1, 'nomTypeDelai' => ['idNom' => 1, 'libNom' => 'Mois']]],
        ]]]]);
    }

    /** @return array<string, mixed> */
    private function statuts(): array
    {
        $row = $this->db->fetchAssociative('SELECT STATUT_PEC, DATE_PEC, STATUT_AVIS FROM platauconsultation WHERE ID_PLATAU = ?', [self::ID]);
        $this->assertIsArray($row);

        return $row;
    }
}
