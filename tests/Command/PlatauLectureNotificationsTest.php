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

    /** @var array<string, JsonMockResponse> Réponses à usage unique ; aucun autre appel n'est permis. */
    private array $reponses = [];

    /** @var list<string> */
    private array $imprevus = [];

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
        $container->set('http_client.transport', new MockHttpClient($this->repondre(...)));

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $this->console = new ApplicationTester($application);
    }

    public function testExigeUnOptInMemeDansLEnvironnementDeTest(): void
    {
        $code = $this->executer(['command' => 'lecture-notifications', '--offset' => '0']);

        $this->assertNotSame(Command::SUCCESS, $code);
        $message = mb_strtolower($this->console->getDisplay());
        foreach (['--recette', 'acteur', 'base', 'isol'] as $indication) {
            $this->assertStringContainsString($indication, $message);
        }

        $this->assertAucunImport();
    }

    public function testOffsetNonEntierRefuseAvantToutAppel(): void
    {
        $this->assertNotSame(Command::SUCCESS, $this->lecture('12.3'));
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
        $this->jeton();
        $this->reponses['GET notifications'] = new JsonMockResponse(['message' => 'detail-confidentiel-fictif'], ['http_code' => 403]);
        $this->assertNotSame(Command::SUCCESS, $this->lecture());
        $this->assertStringContainsString('403', $this->console->getDisplay());
        $this->assertStringNotContainsString('detail-confidentiel-fictif', $this->console->getDisplay());
        $this->assertAucunImport();
    }

    public function testFluxVideSansOffset(): void
    {
        $this->jeton();
        $this->notifications([]);
        $this->assertSame(Command::SUCCESS, $this->lecture(), $this->console->getDisplay());
        $this->assertAucunImport();
    }

    public function testConsultationDUnAutreIdentifiantRejeteeAvantRechercheDesActeurs(): void
    {
        $this->jeton();
        $this->notifications([$this->notification(self::ID, offset: 51)]);
        $this->page(self::ID, 0, [$this->information(self::AUTRE, acteurs: true)]);

        $this->assertSame(Command::SUCCESS, $this->lecture(), $this->console->getDisplay());
        $this->assertMatchesRegularExpression('/offset\D*51\b[^\n]*ne correspond pas/iu', $this->console->getDisplay());
        $this->assertAucunImport();
    }

    public function testFluxMixteNeVerseQueLaConsultationEmise(): void
    {
        $this->jeton();
        $this->notifications([
            $this->notification('PIE-AAA-001', 5, 71, 0),
            $this->notification('DOC-AAA-002', 31, 84, 1),
            $this->notification('IGN-AAA-003', 6, 15, 2),
            $this->notification('IGN-AAA-004', 5, 19, 3),
            $this->notification(self::ID, 6, 19, 4),
        ], '0');
        $this->page(self::ID, 0, [], 2);
        $this->page(self::ID, 1, [$this->information(self::ID, acteurs: true)], 2);
        foreach (['INS' => 'Service instructeur fictif', 'CST' => 'Service consultant fictif'] as $acteur => $designation) {
            $this->reponses['POST acteurs/recherche '.$acteur.' 0'] = new JsonMockResponse([
                'nombrePages' => 1, 'resultats' => [['idActeur' => $acteur, 'designationActeur' => $designation]],
            ]);
        }

        $this->assertSame(Command::SUCCESS, $this->lecture('0', 'lecture-notifications'), $this->console->getDisplay());
        $this->assertSame([self::ID], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM dossier'));
        $this->assertSame([self::ID], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM platauconsultation'));
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
        $this->jeton();
        $this->notifications([$this->notification(self::ID)]);

        $this->assertSame(Command::SUCCESS, $this->lecture(), $this->console->getDisplay());
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
        $lot = [$this->notification(self::ID, offset: 42), $this->notification(self::AUTRE, offset: 43)];
        $this->jeton();
        $this->notifications($lot, '42');
        $information = $this->information(self::ID, nature: $refusActeur ? 3 : 7);
        if ($refusActeur) {
            $information['dossier']['idServiceInstructeur'] = 'INS';
            $this->reponses['POST acteurs/recherche INS 0'] = new JsonMockResponse([], ['http_code' => 403]);
        }

        $this->page(self::ID, 0, [$information]);
        $this->page(self::AUTRE, 0, [$this->information(self::AUTRE)]);

        // Une erreur de traitement est locale à la notification, pas au lot.
        $this->assertSame(Command::SUCCESS, $this->lecture('42'), $this->console->getDisplay());
        $motif = $refusActeur ? 'accès refusé \(HTTP 403\)' : 'Nature Demande de transfert \(DT\) non supportée';
        $this->assertMatchesRegularExpression('/offset\D*42\b[^\n]*'.$motif.'/iu', $this->console->getDisplay());
        $this->assertSame([self::AUTRE], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM dossier'));
        $this->assertSame([self::AUTRE], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM platauconsultation'));
        $this->assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM dossierdocurba'));
        $this->assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM dossiernature'));

        // Même flux corrigé : seule la consultation en erreur doit être recherchée.
        $this->notifications($lot, '42');
        $this->page(self::ID, 0, [$this->information(self::ID)]);
        $this->assertSame(Command::SUCCESS, $this->lecture('42'), $this->console->getDisplay());
        $this->assertSame([self::ID, self::AUTRE], $this->db->fetchFirstColumn('SELECT ID_PLATAU FROM dossier ORDER BY ID_PLATAU'));
        $this->assertSame([self::ID, self::AUTRE], $this->db->fetchFirstColumn("SELECT ID_PLATAU FROM platauconsultation WHERE STATUT_PEC = 'awaiting' ORDER BY ID_PLATAU"));
    }

    private function lecture(?string $offset = null, string $commande = 'platau:lecture-notifications'): int
    {
        $arguments = ['command' => $commande, '--recette' => true];
        if (null !== $offset) {
            $arguments['--offset'] = $offset;
        }

        return $this->executer($arguments);
    }

    private function executer(array $arguments): int
    {
        $code = $this->console->run($arguments);
        $this->assertSame([], $this->imprevus, 'Appels HTTP non attendus.');
        $this->assertSame([], array_keys($this->reponses), $this->console->getDisplay());

        return $code;
    }

    private function assertAucunImport(): void
    {
        $this->assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM dossier'));
        $this->assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM platauconsultation'));
    }

    private function jeton(): void
    {
        $this->reponses['POST jeton'] = new JsonMockResponse(['access_token' => 'jeton-recette', 'expires_in' => 3600, 'token_type' => 'Bearer']);
    }

    private function notifications(array $lot, ?string $offset = null): void
    {
        $this->reponses['GET notifications'.(null === $offset ? '' : '?offset='.$offset)] = new JsonMockResponse(['notifications' => $lot]);
    }

    private function notification(string $id, int $objet = 6, int $evenement = 19, int $offset = 0): array
    {
        return ['idElementConcerne' => $id, 'idTypeObjetMetier' => $objet, 'idTypeEvenement' => $evenement, 'offset' => $offset, 'idDossier' => 'DOS-AAA-001'];
    }

    private function page(string $id, int $page, array $resultats, int $pages = 1): void
    {
        $this->reponses['POST consultations/recherche '.$id.' '.$page] = new JsonMockResponse(['nombrePages' => $pages, 'resultats' => $resultats]);
    }

    private function information(string $id, int $nature = 3, bool $acteurs = false): array
    {
        return ['dossier' => [
            'idDossier' => 'DOS-AAA-001', 'noVersion' => 1, 'txDescriptifGlobal' => 'Bâtiment fictif',
            'nomTypeDossier' => ['idNom' => $nature], 'noLocal' => 'DP-FICTIF', 'suffixeNoLocal' => '-M01',
            'idServiceInstructeur' => $acteurs ? 'INS' : null,
            'personnes' => [
                ['libDenomination' => 'Atelier fictif', 'roles' => [['nomRole' => ['idNom' => 1]]]],
                ['libDenomination' => 'Tiers fictif', 'roles' => [['nomRole' => ['idNom' => 2]]]],
            ],
            'consultations' => [[
                'idConsultation' => $id, 'noVersion' => 2,
                'dtConsultation' => '2026-03-09', 'dtEmission' => '2026-03-10',
                'delaiDeReponse' => 10, 'nomTypeDelai' => ['libNom' => 'Jours calendaires'],
                'nomTypeConsultation' => ['libNom' => 'Consultation fictive'],
                'nomEtatConsultation' => ['idNom' => 1, 'libNom' => 'Émise'],
                'txObjetDeLaConsultation' => 'Étude fictive', 'idServiceConsultant' => $acteurs ? 'CST' : null,
            ]],
        ]];
    }

    private function repondre(string $method, string $url, array $options): JsonMockResponse
    {
        $cible = $url;
        if (self::TOKEN_URL === $url) {
            $cible = 'jeton';
        } elseif (str_starts_with($url, self::API)) {
            $cible = substr($url, strlen(self::API));
            [$route, $query] = array_pad(explode('?', $cible, 2), 2, '');
            if (in_array($route, ['consultations/recherche', 'acteurs/recherche'], true)) {
                parse_str($query, $pagination);
                $body = json_decode($options['body'] ?? '', true);
                $id = 'acteurs/recherche' === $route ? ($body['idActeur'] ?? '?') : ($body['criteresSurConsultations']['idConsultation'] ?? '?');
                $cible = $route.' '.$id.' '.($pagination['numeroPage'] ?? '?');
            }
        }

        $cle = $method.' '.$cible;
        if (!isset($this->reponses[$cle])) {
            $this->imprevus[] = $cle;

            return new JsonMockResponse([], ['http_code' => 403]);
        }

        if (self::TOKEN_URL !== $url) {
            $this->assertContains('Authorization: Bearer jeton-recette', $options['headers']);
            $this->assertContains('Id-Acteur-Appelant: ACTEUR-TEST', $options['headers']);
        }

        $reponse = $this->reponses[$cle];
        unset($this->reponses[$cle]);

        return $reponse;
    }
}
