<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Tests\Factory\UtilisateurFactory;
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
use Symfony\Component\HttpClient\TraceableHttpClient;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
#[Group('database')]
#[WithEnvironmentVariable('PISTE_CLIENT_ID', 'client-recette')]
#[WithEnvironmentVariable('PISTE_CLIENT_SECRET', 'secret-fictif')]
#[WithEnvironmentVariable('PISTE_ACCESS_TOKEN_URL', self::TOKEN_URL)]
#[WithEnvironmentVariable('PLATAU_URL', self::API)]
#[WithEnvironmentVariable('PLATAU_ID_ACTEUR_APPELANT', 'ACTEUR-TEST')]
#[WithEnvironmentVariable('PREVARISC_DB_PLATAU_USER_ID', '')]
final class PlatauLectureNotificationsTest extends KernelTestCase
{
    private const string TOKEN_URL = 'https://oauth.example.test/token';

    private const string API = 'https://api.example.test/v13/dau/';

    private const string ID = 'AAA-BBB-001';

    private const string AUTRE = 'AAA-BBB-002';

    private Connection $db;

    private int $createur;

    private ApplicationTester $console;

    private MockHttpClient $transport;

    private TraceableHttpClient $appels;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();
        $this->db = $container->get(Connection::class);
        $id = UtilisateurFactory::createOne(['username' => 'robot-recette'])->getId();
        $this->assertNotNull($id);
        $this->createur = $id;
        // L'attribut PHPUnit restaure la variable après chaque scénario.
        $_ENV['PREVARISC_DB_PLATAU_USER_ID'] = (string) $id;

        $container->set('cache.platau_consultation_tokens', new ArrayAdapter());
        $this->transport = new MockHttpClient([]);
        $this->appels = new TraceableHttpClient($this->transport);
        $container->set('http_client.transport', $this->appels);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $this->console = new ApplicationTester($application);
    }

    public function testExigeUnOptInMemeDansLEnvironnementDeTest(): void
    {
        $code = $this->lecture(recette: false);

        $this->assertNotSame(Command::SUCCESS, $code);
        $message = mb_strtolower($this->console->getDisplay());
        foreach (['--recette', 'acteur', 'base', 'isol'] as $indication) {
            $this->assertStringContainsString($indication, $message);
        }

        $this->assertAucunImport();
    }

    public function testOffsetNonEntierRefuseAvantToutAppel(): void
    {
        $this->assertNotSame(Command::SUCCESS, $this->lecture(offset: '12.3'));
        $this->assertStringContainsString('offset', $this->console->getDisplay());
        $this->assertAucunImport();
    }

    public function testCreateurNonConfigureRefuseAvantToutAppel(): void
    {
        $_ENV['PREVARISC_DB_PLATAU_USER_ID'] = '';

        $this->assertNotSame(Command::SUCCESS, $this->lecture());
        $diagnostic = $this->console->getDisplay();
        $this->assertStringContainsString('PREVARISC_DB_PLATAU_USER_ID', $diagnostic);
        $this->assertStringContainsString('utilisateur', mb_strtolower($diagnostic));
        $this->assertAucunImport();
    }

    public function testIdentifiantDeCreateurSansUtilisateurRefuseAvantToutAppel(): void
    {
        $_ENV['PREVARISC_DB_PLATAU_USER_ID'] = (string) $this->db->fetchOne('SELECT MAX(ID_UTILISATEUR) + 1 FROM utilisateur');

        $this->assertNotSame(Command::SUCCESS, $this->lecture());
        $diagnostic = mb_strtolower($this->console->getDisplay());
        $this->assertStringContainsString('utilisateur', $diagnostic);
        $this->assertStringContainsString('introuvable', $diagnostic);
        $this->assertAucunImport();
    }

    public function testUnRefusDuFluxNestPasUneListeVide(): void
    {
        $refus = new JsonMockResponse(['message' => 'detail-confidentiel-fictif'], ['http_code' => 403]);
        $this->assertNotSame(Command::SUCCESS, $this->lecture([$this->jeton(), $refus]));
        $this->assertStringContainsString('403', $this->console->getDisplay());
        $this->assertStringNotContainsString('detail-confidentiel-fictif', $this->console->getDisplay());
        $this->assertAucunImport();
    }

    public function testFluxVideSansOffset(): void
    {
        $flux = new JsonMockResponse(['notifications' => []]);
        $this->assertSame(Command::SUCCESS, $this->lecture([$this->jeton(), $flux]), $this->console->getDisplay());
        $this->assertSame(self::API.'notifications', $flux->getRequestUrl());
        $this->assertStringContainsString('Aucune nouvelle notification', $this->console->getDisplay());
        $this->assertAucunImport();
    }

    public function testConsultationDUnAutreIdentifiantRejeteeAvantRechercheDesActeurs(): void
    {
        $code = $this->lecture([
            $this->jeton(),
            new JsonMockResponse(['notifications' => [$this->notification(self::ID, offset: 51)]]),
            $this->consultation(self::AUTRE, instructeur: 'INS'),
        ]);

        $this->assertSame(Command::SUCCESS, $code, $this->console->getDisplay());
        $this->assertMatchesRegularExpression('/offset\D*51\b[^\n]*ne correspond pas/iu', $this->console->getDisplay());
        $this->assertAucunImport();
    }

    public function testFluxMixteNeVerseQueLaConsultationEmise(): void
    {
        $flux = new JsonMockResponse(['notifications' => [
            $this->notification('PIE-AAA-001', 5, 71, 0),
            $this->notification('DOC-AAA-002', 31, 84, 1),
            $this->notification('IGN-AAA-003', 6, 15, 2),
            $this->notification('IGN-AAA-004', 5, 19, 3),
            $this->notification(self::ID, 6, 19, 4),
        ]]);
        $premierePage = new JsonMockResponse(['nombrePages' => 2, 'resultats' => []]);
        $secondePage = JsonMockResponse::fromFile(__DIR__.'/../Platau/fixtures/recherche-consultation-emise.json');
        $instructeur = new JsonMockResponse(['resultats' => [['idActeur' => 'INS', 'designationActeur' => 'Service instructeur fictif']]]);
        $consultant = new JsonMockResponse(['resultats' => [['idActeur' => 'CST', 'designationActeur' => 'Service consultant fictif']]]);
        $code = $this->lecture([$this->jeton(), $flux, $premierePage, $secondePage, $instructeur, $consultant], offset: '0', commande: 'lecture-notifications');

        $this->assertSame(Command::SUCCESS, $code, $this->console->getDisplay());
        $this->assertSame('GET', $flux->getRequestMethod());
        $this->assertSame(self::API.'notifications?offset=0', $flux->getRequestUrl());
        $this->assertStringContainsString('numeroPage=0', $premierePage->getRequestUrl());
        $this->assertStringContainsString('numeroPage=1', $secondePage->getRequestUrl());

        foreach (['INS' => $instructeur, 'CST' => $consultant] as $id => $acteur) {
            $this->assertSame('POST', $acteur->getRequestMethod());
            $this->assertStringStartsWith(self::API.'acteurs/recherche?', $acteur->getRequestUrl());
            $this->assertJsonStringEqualsJsonString(json_encode(['idActeur' => $id], \JSON_THROW_ON_ERROR), $acteur->getRequestOptions()['body']);
        }

        $this->assertContains('Authorization: Bearer jeton-recette', $flux->getRequestOptions()['headers']);
        $this->assertContains('Id-Acteur-Appelant: ACTEUR-TEST', $instructeur->getRequestOptions()['headers']);

        $this->assertSame([self::ID], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM dossier'));
        $dossier = $this->db->fetchAssociative('SELECT * FROM dossier WHERE ID_PLATAU = ?', [self::ID]);
        $this->assertIsArray($dossier);
        $this->assertSame(1, (int) $dossier['TYPE_DOSSIER']);
        $this->assertSame($this->createur, (int) $dossier['CREATEUR_DOSSIER']);
        $this->assertNull($dossier['INCOMPLET_DOSSIER']);
        $this->assertNotNull($dossier['DATE_NOTIFICATION']);
        $this->assertNotNull($dossier['DATESDIS_DOSSIER']);
        $this->assertSame('2026-03-10 00:00:00', $dossier['DATEINSERT_DOSSIER']);
        $this->assertSame('Objet de la consultation : Étude fictive ; Bâtiment fictif', $dossier['OBJET_DOSSIER']);
        $this->assertSame('Atelier fictif', $dossier['DEMANDEUR_DOSSIER']);
        $this->assertSame('servInstGrp', $dossier['TYPESERVINSTRUC_DOSSIER']);
        $this->assertSame('Service instructeur fictif', $dossier['SERVICEINSTRUC_DOSSIER']);
        $this->assertStringContainsString('Service consultant fictif', (string) $dossier['OBSERVATION_DOSSIER']);
        $params = [$dossier['ID_DOSSIER']];
        $this->assertSame([30], array_map(intval(...), $this->db->fetchFirstColumn('SELECT ID_NATURE FROM dossiernature WHERE ID_DOSSIER = ?', $params)));
        $this->assertSame(['DP-FICTIF-M01'], $this->db->fetchFirstColumn('SELECT NUM_DOCURBA FROM dossierdocurba WHERE ID_DOSSIER = ?', $params));
        $this->assertSame([], $this->db->fetchFirstColumn('SELECT ID_ETABLISSEMENT FROM etablissementdossier WHERE ID_DOSSIER = ?', $params));
        $this->assertSame([
            'DATE_REPONSE_ATTENDUE' => '2026-03-20',
            'STATUT_PEC' => 'awaiting', 'DATE_PEC' => null, 'STATUT_AVIS' => null, 'DATE_AVIS' => null,
        ], $this->db->fetchAssociative('SELECT DATE_REPONSE_ATTENDUE, STATUT_PEC, DATE_PEC, STATUT_AVIS, DATE_AVIS FROM platauconsultation WHERE ID_PLATAU = ?', [self::ID]));
        $this->assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM piecejointe'));
        $this->assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM dossierpj'));
    }

    /** @return iterable<string, array{?string}> */
    public static function statutsExistants(): iterable
    {
        yield 'PEC déjà transmise' => ['taken_into_account'];
        yield 'création achevée mais PEC non initialisée' => [null];
    }

    #[DataProvider('statutsExistants')]
    public function testRelectureNeRepareNiNeReimporteUnDossierExistant(?string $pec): void
    {
        // Le statut nul représente l'état entre les deux écritures, sans hook sur la transaction.
        $this->db->insert('platauconsultation', ['ID_PLATAU' => self::ID, 'STATUT_PEC' => $pec]);
        $this->db->insert('dossier', [
            'TYPE_DOSSIER' => 1, 'DATEINSERT_DOSSIER' => '2026-03-01 09:00:00',
            'ID_PLATAU' => self::ID, 'OBJET_DOSSIER' => 'Objet conservé', 'INCOMPLET_DOSSIER' => null,
        ]);
        $avant = $this->db->fetchAssociative('SELECT * FROM dossier WHERE ID_PLATAU = ?', [self::ID]);
        $flux = new JsonMockResponse(['notifications' => [$this->notification(self::ID)]]);

        $this->assertSame(Command::SUCCESS, $this->lecture([$this->jeton(), $flux]), $this->console->getDisplay());
        $this->assertSame([$avant], $this->db->fetchAllAssociative('SELECT * FROM dossier'));
        $this->assertSame([$pec], $this->db->fetchFirstColumn('SELECT STATUT_PEC FROM platauconsultation WHERE ID_PLATAU = ?', [self::ID]));
    }

    /** @return iterable<string, array{bool}> */
    public static function erreursIndividuelles(): iterable
    {
        yield 'refus HTTP avant la création' => [true];
        yield 'nature non supportée pendant la création' => [false];
    }

    #[DataProvider('erreursIndividuelles')]
    public function testErreurSansImportPartielPuisRejeuDepuisLOffset(bool $refusActeur): void
    {
        $lot = ['notifications' => [$this->notification(self::ID, offset: 42), $this->notification(self::AUTRE, offset: 43)]];
        $reponses = [$this->jeton(), new JsonMockResponse($lot)];
        if ($refusActeur) {
            $reponses[] = $this->consultation(self::ID, instructeur: 'INS');
            $reponses[] = new JsonMockResponse([], ['http_code' => 403]);
        } else {
            $reponses[] = $this->consultation(self::ID, nature: 7);
        }

        $reponses[] = $this->consultation(self::AUTRE);

        // Une erreur de traitement est locale à la notification, pas au lot.
        $this->assertSame(Command::SUCCESS, $this->lecture($reponses, offset: '42'), $this->console->getDisplay());
        $motif = $refusActeur ? 'accès refusé \(HTTP 403\)' : 'Nature Demande de transfert \(DT\) non supportée';
        $this->assertMatchesRegularExpression('/offset\D*42\b[^\n]*'.$motif.'/iu', $this->console->getDisplay());
        $this->assertSame([self::AUTRE], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM dossier'));
        $this->assertSame([self::AUTRE], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM platauconsultation'));
        $this->assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM dossierdocurba'));
        $this->assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM dossiernature'));

        // Même flux corrigé : seule la consultation en erreur doit être recherchée.
        $rejeu = new JsonMockResponse($lot);
        $corrigee = $this->consultation(self::ID);
        $this->assertSame(Command::SUCCESS, $this->lecture([$rejeu, $corrigee], offset: '42'), $this->console->getDisplay());
        $this->assertSame(self::API.'notifications?offset=42', $rejeu->getRequestUrl());
        $this->assertJsonStringEqualsJsonString('{"criteresSurConsultations":{"idConsultation":"AAA-BBB-001"}}', $corrigee->getRequestOptions()['body']);
        $this->assertSame([self::ID, self::AUTRE], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM dossier ORDER BY ID_PLATAU'));
        $this->assertSame([self::ID, self::AUTRE], $this->db->fetchFirstColumn("SELECT ID_PLATAU FROM platauconsultation WHERE STATUT_PEC = 'awaiting' ORDER BY ID_PLATAU"));
    }

    /** @param list<JsonMockResponse> $reponses Réponses HTTP dans l'ordre des appels attendus. */
    private function lecture(array $reponses = [], ?string $offset = null, bool $recette = true, string $commande = 'platau:lecture-notifications'): int
    {
        $this->transport->setResponseFactory($reponses);
        $avant = count($this->appels->getTracedRequests());
        $arguments = ['command' => $commande, '--recette' => $recette];
        if (null !== $offset) {
            $arguments['--offset'] = $offset;
        }

        $code = $this->console->run($arguments);
        $this->assertCount($avant + count($reponses), $this->appels->getTracedRequests(), $this->console->getDisplay());

        return $code;
    }

    private function assertAucunImport(): void
    {
        $this->assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM dossier'));
        $this->assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM platauconsultation'));
    }

    private function jeton(): JsonMockResponse
    {
        return new JsonMockResponse(['access_token' => 'jeton-recette', 'expires_in' => 3600, 'token_type' => 'Bearer']);
    }

    private function notification(string $id, int $objet = 6, int $evenement = 19, int $offset = 0): array
    {
        return ['idElementConcerne' => $id, 'idTypeObjetMetier' => $objet, 'idTypeEvenement' => $evenement, 'offset' => $offset, 'idDossier' => 'DOS-AAA-001'];
    }

    private function consultation(string $id, int $nature = 3, ?string $instructeur = null): JsonMockResponse
    {
        return new JsonMockResponse(['nombrePages' => 1, 'resultats' => [['dossier' => [
            'nomTypeDossier' => ['idNom' => $nature],
            'noLocal' => 'DP-FICTIF',
            'idServiceInstructeur' => $instructeur,
            'consultations' => [['idConsultation' => $id]],
        ]]]]);
    }
}
