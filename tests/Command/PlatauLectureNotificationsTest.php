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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
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
#[WithEnvironmentVariable('PREVARISC_REAL_DATA_PATH', '')]
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

    private string $stockage;

    protected function setUp(): void
    {
        $this->stockage = sys_get_temp_dir().'/platau-recette-'.bin2hex(random_bytes(12));
        $_ENV['PREVARISC_REAL_DATA_PATH'] = $this->stockage;
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
        $container->set('platau.piece_transport', $this->appels);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $this->console = new ApplicationTester($application);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->stockage);
        parent::tearDown();
    }

    public function testAliasEtOptionsDeLaCommande(): void
    {
        foreach (['platau:lecture-notifications', 'lecture-notifications'] as $commande) {
            $this->assertSame(Command::SUCCESS, $this->console->run(['command' => 'help', 'command_name' => $commande]));
            $message = $this->console->getDisplay();
            $this->assertStringContainsString("Lit les notifications qui n'ont pas encore été consommées.", $message);
            $this->assertStringContainsString('--offset', $message);
            $this->assertStringNotContainsString('--recette', $message);
            $this->assertStringNotContainsString('--config', $message);
        }

        $this->assertNotSame(Command::SUCCESS, $this->console->run(['command' => 'lecture-notifications', '--recette' => true]));
        $this->assertCount(0, $this->appels->getTracedRequests());
        $this->assertAucunImport();
    }

    public function testOffsetNonEntierRefuseAvantToutAppel(): void
    {
        $this->assertNotSame(Command::SUCCESS, $this->lecture(offset: '12.3'));
        $this->assertStringContainsString('La valeur de l\'option offset doit être un entier. "12.3" donné.', $this->console->getDisplay());
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
        $this->assertMatchesRegularExpression('/^\[\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}\] Lecture des nouvelles notifications \.\.\.\R\[\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}\] Aucune nouvelle notification\.\R$/', $this->console->getDisplay());
        $this->assertAucunImport();
    }

    public function testOffsetNegatifTransmis(): void
    {
        $flux = new JsonMockResponse(['notifications' => []]);
        $this->assertSame(Command::SUCCESS, $this->lecture([$this->jeton(), $flux], offset: '-12'));
        $this->assertSame(self::API.'notifications?offset=-12', $flux->getRequestUrl());
    }

    public function testNotificationsMalFormeesEtObjetInconnuContinuentSurLaSortieStandard(): void
    {
        $flux = new JsonMockResponse(['notifications' => [
            null,
            ['offset' => 7, 'idTypeObjetMetier' => '6', 'idTypeEvenement' => 19],
            ['offset' => 8, 'idTypeObjetMetier' => 6, 'idTypeEvenement' => 19],
            $this->notification('AUTRE-001', 99, 19, 9),
        ]]);
        $this->assertSame(Command::SUCCESS, $this->lecture([$this->jeton(), $flux, ...$this->nomenclatures(99, 19)], separerSorties: true));
        $sortie = $this->console->getDisplay();
        $this->assertMatchesRegularExpression('/^\[\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}\] \[Offset: inconnu\] Notification incorrecte\.$/m', $sortie);
        $this->assertStringContainsString('[Offset: 7] Notification incorrecte.', $sortie);
        $this->assertStringContainsString('[Offset: 8] Identifiant de consultation manquant ou incorrect.', $sortie);
        $this->assertStringContainsString("La notification Objet inconnu pour l'élément d'identifiant AUTRE-001 n'est pas prise en compte par la passerelle actuellement", $sortie);
        $this->assertStringNotContainsString('Objet inconnu - Versée', $sortie);
        $this->assertSame('', $this->console->getErrorOutput());
        $this->assertAucunImport();
    }

    /** @return iterable<string, array{array<mixed>, string}> */
    public static function nomenclaturesIncorrectes(): iterable
    {
        yield 'identifiant absent' => [[['idNom' => 5, 'libNom' => 'Pièce']], "Aucune nomenclature trouvée pour l'identifiant 6 et le code TYPE_OBJET_METIER"];
        yield 'libellé absent' => [[['idNom' => 6]], 'Un problème a eu lieu dans la récupération des résultats de recherche de nomenclature : clé libNom introuvable'];
        yield 'libellé invalide' => [[['idNom' => 6, 'libNom' => 123]], 'Le format de la valeur de la clé libNom est incorrect'];
        yield 'réponse incorrecte' => [['erreur' => 'invalide'], 'Un problème a eu lieu dans la récupération des résultats de recherche de nomenclatures : le résultat est incorrect'];
    }

    #[DataProvider('nomenclaturesIncorrectes')]
    public function testEchecNomenclatureIsole(array $reponse, string $message): void
    {
        $flux = new JsonMockResponse(['notifications' => [
            $this->notification(self::ID, offset: 42),
            $this->notification(self::AUTRE, offset: 43),
        ]]);
        $this->assertSame(Command::SUCCESS, $this->lecture([
            $this->jeton(), $flux, new JsonMockResponse($reponse),
            ...$this->nomenclatures(6, 19), $this->consultation(self::AUTRE), new JsonMockResponse([]),
        ], separerSorties: true));
        $sortie = $this->console->getDisplay();
        $this->assertStringContainsString('[Offset: 42] '.$message, $sortie);
        $this->assertStringContainsString('Consultation '.self::AUTRE.' récupérée et stockée dans Prevarisc !', $sortie);
        $this->assertSame('', $this->console->getErrorOutput());
        $this->assertSame([self::AUTRE], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM dossier'));
    }

    public function testEchecNomenclatureEvenementEtTraitementSontDetaillesSurStdout(): void
    {
        $flux = new JsonMockResponse(['notifications' => [
            $this->notification(self::ID, offset: 10),
            $this->notification(self::AUTRE, offset: 11),
        ]]);
        $objet = $this->nomenclatures(6, 19)[0];
        $this->assertSame(Command::SUCCESS, $this->lecture([
            $this->jeton(), $flux, $objet,
            new JsonMockResponse(['message' => 'detail-confidentiel-fictif'], ['http_code' => 403]),
            ...$this->nomenclatures(6, 19), $this->consultation(self::AUTRE, nature: 7),
        ], separerSorties: true));
        $sortie = $this->console->getDisplay();
        $this->assertStringContainsString("[Offset: 10] Plat'AU : accès refusé (HTTP 403).", $sortie);
        $this->assertStringNotContainsString('detail-confidentiel-fictif', $sortie);
        $this->assertStringContainsString('[Offset: 11] Problème lors du traitement de la consultation : Nature Demande de transfert (DT) non supportée', $sortie);
        $this->assertSame('', $this->console->getErrorOutput());
        $this->assertAucunImport();
    }

    public function testConsultationDUnAutreIdentifiantRejeteeAvantRechercheDesActeurs(): void
    {
        $code = $this->lecture([
            $this->jeton(),
            new JsonMockResponse(['notifications' => [$this->notification(self::ID, offset: 51)]]),
            ...$this->nomenclatures(6, 19),
            $this->consultation(self::AUTRE, instructeur: 'INS'),
        ]);

        $this->assertSame(Command::SUCCESS, $code, $this->console->getDisplay());
        $this->assertMatchesRegularExpression('/offset\D*51\b[^\n]*ne correspond pas/iu', $this->console->getDisplay());
        $this->assertAucunImport();
    }

    public function testFluxMixteNeVerseQueLaConsultationEmise(): void
    {
        $flux = new JsonMockResponse(['notifications' => [
            $this->notification('DOC-AAA-002', 31, 84, 1),
            $this->notification('IGN-AAA-003', 6, 15, 2),
            $this->notification('IGN-AAA-004', 5, 19, 3),
            $this->notification(self::ID, 6, 19, 4),
        ]]);
        $premierePage = new JsonMockResponse(['nombrePages' => 2, 'resultats' => []]);
        $secondePage = JsonMockResponse::fromFile(__DIR__.'/../Platau/fixtures/recherche-consultation-emise.json');
        $instructeur = new JsonMockResponse(['resultats' => [['idActeur' => 'INS', 'designationActeur' => 'Service instructeur fictif']]]);
        $consultant = new JsonMockResponse(['resultats' => [['idActeur' => 'CST', 'designationActeur' => 'Service consultant fictif']]]);
        $nomenclatures = [
            ...$this->nomenclatures(31, 84),
            ...$this->nomenclatures(6, 15),
            ...$this->nomenclatures(5, 19),
            ...$this->nomenclatures(6, 19),
        ];
        $listeVide = new JsonMockResponse([]);
        $code = $this->lecture([$this->jeton(), $flux, ...$nomenclatures, $premierePage, $secondePage, $instructeur, $consultant, $listeVide], offset: '0', commande: 'lecture-notifications');

        $this->assertSame(Command::SUCCESS, $code, $this->console->getDisplay());
        $this->assertSame('GET', $flux->getRequestMethod());
        $this->assertSame(self::API.'notifications?offset=0', $flux->getRequestUrl());
        $this->assertStringContainsString('numeroPage=0', $premierePage->getRequestUrl());
        $this->assertStringContainsString('numeroPage=1', $secondePage->getRequestUrl());
        foreach ($nomenclatures as $index => $response) {
            $this->assertSame('GET', $response->getRequestMethod());
            $this->assertSame(self::API.'nomenclatures/'.(0 === $index % 2 ? 'TYPE_OBJET_METIER' : 'TYPE_EVENEMENT'), $response->getRequestUrl());
        }
        $this->assertSame(self::API.'dossiers/DOS-AAA-001/pieces', $listeVide->getRequestUrl());
        $this->assertStringContainsString("La notification Document - Succès pour l'élément d'identifiant DOC-AAA-002 n'est pas prise en compte par la passerelle actuellement", $this->console->getDisplay());
        $this->assertStringContainsString("Traitement de la notification Consultation - Versée pour l'élément d'identifiant ".self::ID, $this->console->getDisplay());
        $this->assertStringContainsString('Consultation '.self::ID.' récupérée et stockée dans Prevarisc !', $this->console->getDisplay());

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

        $this->assertSame(Command::SUCCESS, $this->lecture([$this->jeton(), $flux, ...$this->nomenclatures(6, 19)]), $this->console->getDisplay());
        $this->assertStringContainsString('Consultation '.self::ID.' déjà existante dans Prevarisc', $this->console->getDisplay());
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
        $reponses = [$this->jeton(), new JsonMockResponse($lot), ...$this->nomenclatures(6, 19)];
        if ($refusActeur) {
            $reponses[] = $this->consultation(self::ID, instructeur: 'INS');
            $reponses[] = new JsonMockResponse([], ['http_code' => 403]);
        } else {
            $reponses[] = $this->consultation(self::ID, nature: 7);
        }

        array_push($reponses, ...$this->nomenclatures(6, 19));
        $reponses[] = $this->consultation(self::AUTRE);
        $reponses[] = new JsonMockResponse([]);

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
        $this->assertSame(Command::SUCCESS, $this->lecture([$rejeu, ...$this->nomenclatures(6, 19), $corrigee, new JsonMockResponse([]), ...$this->nomenclatures(6, 19)], offset: '42'), $this->console->getDisplay());
        $this->assertSame(self::API.'notifications?offset=42', $rejeu->getRequestUrl());
        $this->assertJsonStringEqualsJsonString('{"criteresSurConsultations":{"idConsultation":"AAA-BBB-001"}}', $corrigee->getRequestOptions()['body']);
        $this->assertSame([self::ID, self::AUTRE], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM dossier ORDER BY ID_PLATAU'));
        $this->assertSame([self::ID, self::AUTRE], $this->db->fetchFirstColumn("SELECT ID_PLATAU FROM platauconsultation WHERE STATUT_PEC = 'awaiting' ORDER BY ID_PLATAU"));
    }

    public function testPiecesInitialesContinuentApresUnRefusDeTelechargement(): void
    {
        $piece = $this->piece();
        $refusee = ['idPiece' => 'PIE-AAA-002'] + $piece;
        $binaire = $this->telechargement();
        $this->lecture([
            $this->jeton(), new JsonMockResponse(['notifications' => [$this->notification(self::ID, offset: 8)]]),
            ...$this->nomenclatures(6, 19), $this->consultation(self::ID),
            new JsonMockResponse([$refusee, $piece]), new MockResponse('message tiers', ['http_code' => 403]), $binaire,
        ]);
        $this->assertMatchesRegularExpression('/Offset: 8[^\n]*HTTP 403/', $this->console->getDisplay());
        $this->assertStringNotContainsString('message tiers', $this->console->getDisplay());
        $this->assertStringNotContainsString('ticket-fictif', $this->console->getDisplay());
        $this->assertSame('GET', $binaire->getRequestMethod());
        $this->assertSame($piece['url'], $binaire->getRequestUrl());
        $headers = implode("\n", $binaire->getRequestOptions()['headers']);
        $this->assertStringContainsString('Authorization: Bearer ticket-fictif', $headers);
        $this->assertStringNotContainsString('Id-Acteur-Appelant', $headers);
        $this->assertStringNotContainsString('jeton-recette', $headers);

        $row = $this->db->fetchAssociative('SELECT p.*, d.ID_PLATAU AS CONSULTATION FROM piecejointe p JOIN dossierpj l USING (ID_PIECEJOINTE) JOIN dossier d USING (ID_DOSSIER)');
        $this->assertIsArray($row);
        $this->assertSame(self::ID, $row['CONSULTATION']);
        $this->assertSame($piece['idPiece'], $row['ID_PLATAU']);
        $this->assertSame(2, (int) $row['no_version']);
        $this->assertSame('plan.pdf-v2', $row['NOM_PIECEJOINTE']);
        $this->assertSame('Plan', $row['TYPE']);
        $this->assertSame('Coupe', $row['SOUS_TYPE']);
        $this->assertSame('Graphique', $row['NATURE']);
        $this->assertSame('2026-11-01', $row['DATE_DEPOT']);
        $this->assertNotNull($row['DATE_NOTIFICATION']);
        $this->assertSame('.pdf', $row['EXTENSION_PIECEJOINTE']);
        $this->assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM piecejointe'));
        $this->assertSame("octets\0fictifs", file_get_contents($this->stockage.'/uploads/pieces-jointes/'.$row['ID_PIECEJOINTE'].'.pdf'));
    }

    public function testNotificationDePieceRetenteLeStockageSansDupliquerLesRattachements(): void
    {
        foreach ([self::ID, self::AUTRE] as $id) {
            $this->db->insert('platauconsultation', ['ID_PLATAU' => $id]);
            $this->db->insert('dossier', ['ID_PLATAU' => $id, 'TYPE_DOSSIER' => 1, 'DATEINSERT_DOSSIER' => '2026-11-01']);
        }
        $piece = $this->piece();
        $dossier = static fn (string ...$ids): array => ['dossier' => [
            'idDossier' => 'DOS-AAA-001', 'consultations' => array_map(static fn (string $id): array => ['idConsultation' => $id], $ids),
        ]];
        $flux = ['notifications' => [$this->notification('PIE-AAA-001', 5, 17, 12)]];
        $reponses = fn (): array => [
            new JsonMockResponse($flux), ...$this->nomenclatures(5, 17),
            new JsonMockResponse([['idPiece' => 'PIE-AAA-002'] + $piece, $piece]),
            new JsonMockResponse(['nombrePages' => 2, 'resultats' => [$dossier(self::ID, 'AAA-BBB-003')]]),
            new JsonMockResponse(['nombrePages' => 2, 'resultats' => [$dossier(self::AUTRE)]]),
        ];
        // Un fichier à la place du répertoire simule une erreur même sous root.
        new Filesystem()->dumpFile($this->stockage.'/uploads', 'obstacle');
        $this->lecture([$this->jeton(), ...$reponses(), $this->telechargement()], offset: '12');
        $this->assertMatchesRegularExpression('/Offset: 12[^\n]*stockage/', $this->console->getDisplay());
        $this->assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM piecejointe'));
        $this->assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM dossierpj'));
        unlink($this->stockage.'/uploads');

        $rejeu = $reponses();
        $this->lecture([...$rejeu, $this->telechargement()], offset: '12');
        $this->assertJsonStringEqualsJsonString('{"criteresSurDossiers":{"idDossier":"DOS-AAA-001"}}', $rejeu[4]->getRequestOptions()['body']);
        $this->assertStringContainsString('numeroPage=1', $rejeu[5]->getRequestUrl());
        $this->lecture($reponses(), offset: '12');
        $rows = $this->db->fetchAllKeyValue('SELECT d.ID_PLATAU, p.ID_PIECEJOINTE FROM dossier d JOIN dossierpj l USING (ID_DOSSIER) JOIN piecejointe p USING (ID_PIECEJOINTE) ORDER BY d.ID_PLATAU');
        $this->assertSame([self::ID, self::AUTRE], array_keys($rows));
        $this->assertSame(2, (int) $this->db->fetchOne('SELECT COUNT(*) FROM piecejointe'));
        $this->assertSame(2, (int) $this->db->fetchOne('SELECT COUNT(*) FROM dossierpj'));
        foreach ($rows as $pjId) {
            $this->assertSame("octets\0fictifs", file_get_contents($this->stockage.'/uploads/pieces-jointes/'.$pjId.'.pdf'));
        }
    }

    /** @return iterable<string, array{int, string}> */
    public static function nomsHistoriques(): iterable
    {
        yield 'nom original versionné' => [15, 'plan.pdf-v2'];
        yield 'ancien nom technique' => [71, 'PLATAU-PIE-AAA-001-PC1-v2'];
    }

    #[DataProvider('nomsHistoriques')]
    public function testPieceHistoriqueSansVersionEnBaseNestPasDupliquee(int $evenement, string $nom): void
    {
        $this->db->insert('platauconsultation', ['ID_PLATAU' => self::ID]);
        $this->db->insert('dossier', ['ID_PLATAU' => self::ID, 'TYPE_DOSSIER' => 1, 'DATEINSERT_DOSSIER' => '2026-11-01']);

        $idDossier = $this->db->lastInsertId();
        $this->db->insert('piecejointe', ['NOM_PIECEJOINTE' => $nom, 'EXTENSION_PIECEJOINTE' => '.pdf']);
        $idPiece = $this->db->lastInsertId();
        $this->db->insert('dossierpj', ['ID_DOSSIER' => $idDossier, 'ID_PIECEJOINTE' => $idPiece, 'PJ_COMMISSION' => 0]);
        $chemin = $this->stockage.'/uploads/pieces-jointes/'.$idPiece.'.pdf';
        new Filesystem()->dumpFile($chemin, 'contenu historique');
        $this->lecture([
            $this->jeton(), new JsonMockResponse(['notifications' => [$this->notification('PIE-AAA-001', 5, $evenement)]]),
            ...$this->nomenclatures(5, $evenement), new JsonMockResponse([$this->piece()]), $this->consultation(self::ID),
        ]);
        $this->assertSame([$nom], $this->db->fetchFirstColumn('SELECT NOM_PIECEJOINTE FROM piecejointe'));
        $this->assertSame('contenu historique', file_get_contents($chemin));
    }

    /** @return array<string, mixed> */
    private function piece(): array
    {
        return [
            'idPiece' => 'PIE-AAA-001', 'idDossier' => 'DOS-AAA-001', 'noPiece' => 'PC1', 'noVersion' => 2,
            'txFileName' => 'plan.pdf', 'nomTypePiece' => ['libNom' => 'Plan'], 'libAutreTypePiece' => 'Coupe',
            'nomNaturePiece' => ['libNom' => 'Graphique'], 'dtDepot' => '2026-11-01',
            'url' => 'https://stockage.example.test/plan', 'token' => 'ticket-fictif',
        ];
    }

    private function telechargement(): MockResponse
    {
        return new MockResponse("octets\0fictifs", ['response_headers' => ['Content-Disposition: attachment; filename="plan.pdf"']]);
    }

    /** @param list<MockResponse> $reponses Réponses HTTP dans l'ordre des appels attendus. */
    private function lecture(array $reponses = [], ?string $offset = null, string $commande = 'platau:lecture-notifications', bool $separerSorties = false): int
    {
        $this->transport->setResponseFactory($reponses);
        $avant = count($this->appels->getTracedRequests());
        $arguments = ['command' => $commande];
        if (null !== $offset) {
            $arguments['--offset'] = $offset;
        }

        $code = $this->console->run($arguments, ['capture_stderr_separately' => $separerSorties]);
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

    /** @return list<JsonMockResponse> */
    private function nomenclatures(int $objet, int $evenement): array
    {
        $objets = [5 => 'Pièce', 6 => 'Consultation', 31 => 'Document', 99 => 'Objet inconnu'];
        $evenements = [15 => 'Complémentaire', 17 => 'Modificative', 19 => 'Versée', 71 => 'Initiale', 84 => 'Succès'];

        return [
            new JsonMockResponse([['idNom' => (string) $objet, 'libNom' => $objets[$objet]]]),
            new JsonMockResponse([['idNom' => $evenement, 'libNom' => $evenements[$evenement]]]),
        ];
    }

    private function notification(string $id, int $objet = 6, int $evenement = 19, int $offset = 0): array
    {
        return ['idElementConcerne' => $id, 'idTypeObjetMetier' => $objet, 'idTypeEvenement' => $evenement, 'offset' => $offset, 'idDossier' => 'DOS-AAA-001'];
    }

    private function consultation(string $id, int $nature = 3, ?string $instructeur = null): JsonMockResponse
    {
        return new JsonMockResponse(['nombrePages' => 1, 'resultats' => [['dossier' => [
            'idDossier' => 'DOS-AAA-001',
            'nomTypeDossier' => ['idNom' => $nature],
            'noLocal' => 'DP-FICTIF',
            'idServiceInstructeur' => $instructeur,
            'consultations' => [['idConsultation' => $id]],
        ]]]]);
    }
}
