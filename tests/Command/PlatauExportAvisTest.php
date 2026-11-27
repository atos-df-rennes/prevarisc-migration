<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Constant\Avis;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\WithEnvironmentVariable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
#[Group('database')]
#[WithEnvironmentVariable('PISTE_CLIENT_ID', 'client-recette')]
#[WithEnvironmentVariable('PISTE_CLIENT_SECRET', 'secret-fictif')]
#[WithEnvironmentVariable('PISTE_ACCESS_TOKEN_URL', 'https://oauth.example.test/token')]
#[WithEnvironmentVariable('PLATAU_URL', 'https://api.example.test/dau/')]
#[WithEnvironmentVariable('PLATAU_ID_ACTEUR_APPELANT', 'ACTEUR-TEST')]
#[WithEnvironmentVariable('SYNCPLICITY_PISTE_CLIENT_ID', 'client-depot')]
#[WithEnvironmentVariable('SYNCPLICITY_PISTE_CLIENT_SECRET', 'secret-fictif-depot')]
#[WithEnvironmentVariable('SYNCPLICITY_PISTE_ACCESS_TOKEN_URL', 'https://oauth.example.test/depot')]
#[WithEnvironmentVariable('SYNCPLICITY_URL', 'https://depot.example.test/')]
#[WithEnvironmentVariable('PREVARISC_REAL_DATA_PATH', '')]
final class PlatauExportAvisTest extends PlatauExportTestCase
{
    /** @var list<array<string, mixed>> Avis effectivement reçus sur POST /dau/avis. */
    private array $envois = [];

    /** @var list<string> Chemins vus au transport, y compris les tentatives qui échouent. */
    private array $chemins = [];

    /** @var array<string, int> */
    private array $etats = [];

    public static function naturesAvis(): iterable
    {
        yield 'favorable sans prescriptions' => [Avis::FAVORABLE, false, 1];
        yield 'favorable avec prescriptions' => [Avis::FAVORABLE, true, 2];
        yield 'défavorable' => [Avis::DEFAVORABLE, false, 3];
        yield 'pas d’avis' => [6, false, 6];
    }

    #[DataProvider('naturesAvis')]
    public function testNouvelAvisEnLotSansPiece(int $commission, bool $prescriptions, int $nature): void
    {
        $dossier = $this->dossierAvis($commission);
        if ($prescriptions) {
            foreach (['Baliser les issues', 'Dégager les accès'] as $index => $libelle) {
                $this->db->insert('prescriptiondossier', ['ID_DOSSIER' => $dossier, 'NUM_PRESCRIPTION_DOSSIER' => $index + 1,
                    'TYPE_PRESCRIPTION_DOSSIER' => 2, 'LIBELLE_PRESCRIPTION_DOSSIER' => $libelle]);
            }
        }
        $this->simulerPlatau([self::ID => 3]);
        $this->exporter();

        $this->assertCount(1, $this->envois);
        $avis = $this->envois[0];
        $this->assertSame(self::ID, $avis['idConsultation']);
        $this->assertFalse($avis['boEstTacite']);
        $this->assertSame(1, $avis['nomTypeAvis']);
        $this->assertSame($nature, $avis['nomNatureAvisRendu']);
        $this->assertSame('ACTEUR-TEST', $avis['idActeurAuteur']);
        $this->assertSame('Avis Prevarisc. Prescriptions données : '.($prescriptions ? 'Baliser les issues, Dégager les accès' : 'RAS'), $avis['txAvis']);
        $this->assertSame(date('Y-m-d'), $avis['dtAvis']);
        $this->assertSame([], $avis['documents']);
        $this->assertStatuts('treated', date('Y-m-d'));
    }

    public function testLotReunitLesDeuxSourcesSansDoubleVersement(): void
    {
        $introuvable = 'AAA-BBB-000';
        $local = 'AAA-BBB-002';
        $distant = 'AAA-BBB-003';
        $erreur = 'AAA-BBB-004';
        $this->dossierAvis(statut: 'in_error'); // À la fois en erreur locale et dans la recherche distante.
        $this->dossierAvis(statut: 'in_error', id: $introuvable);
        $this->dossierAvis(statut: 'to_export', date: '2026-10-31', id: $local);
        $this->dossierAvis(id: $distant);
        $this->dossierAvis(statut: 'in_error', id: $erreur);
        $this->simulerPlatau([self::ID => 3, $local => 6, $distant => 3, $erreur => 6]);
        $this->exporter();

        $this->assertStringContainsString("Consultation $introuvable : reprise ignorée", $this->console->getDisplay());
        $this->assertStatuts('in_error', null, $introuvable);
        $this->assertEqualsCanonicalizing([self::ID, $local, $distant, $erreur], array_column($this->envois, 'idConsultation'));
        $this->assertSame('2026-10-31', array_column($this->envois, 'dtAvis', 'idConsultation')[$local]);
        foreach ([self::ID, $local, $distant, $erreur] as $id) {
            $this->assertStatuts('treated', date('Y-m-d'), $id);
        }
    }

    public function testNouvelAvisCibleNeTraitePasLeVoisinEligible(): void
    {
        $autre = 'AAA-BBB-002';
        $this->dossierAvis();
        $this->dossierAvis(id: $autre);
        $this->simulerPlatau([self::ID => 3, $autre => 3]);
        $this->exporter(self::ID);

        $this->assertSame([self::ID], array_column($this->envois, 'idConsultation'));
        $this->assertStatuts('treated', date('Y-m-d'));
        $this->assertStatuts('in_progress', null, $autre);
    }

    public static function renvois(): iterable
    {
        yield 'relance avec date locale' => ['to_export', '2026-10-31', '2026-10-31'];
        yield 'relance sans date locale' => ['to_export', null, '2026-11-02'];
        yield 'avis en erreur, pas une relance manuelle' => ['in_error', '2026-10-31', null];
    }

    #[DataProvider('renvois')]
    public function testRenvoiCibleEnEtatTraite(string $statut, ?string $dateLocale, ?string $dateAttendue): void
    {
        $this->dossierAvis(statut: $statut, date: $dateLocale);
        $this->simulerPlatau([self::ID => 6]);
        $this->exporter(self::ID);

        $this->assertCount(1, $this->envois);
        $this->assertSame($dateAttendue ?? date('Y-m-d'), $this->envois[0]['dtAvis']);
        $this->assertStatuts('treated', date('Y-m-d'));
    }

    public static function nonEligibles(): iterable
    {
        yield 'commission non renseignée' => [3, null, 'in_progress'];
        yield 'déjà traité sans relance' => [6, Avis::FAVORABLE, 'treated'];
        yield 'relance mais consultation refusée' => [2, Avis::FAVORABLE, 'to_export'];
    }

    #[DataProvider('nonEligibles')]
    public function testAucunDepotSiIneligibleMemeAvecPiece(int $etat, ?int $commission, string $statut): void
    {
        $date = 'in_progress' === $statut ? null : '2026-10-31';
        $piece = $this->pieceSurDisque($this->dossierAvis($commission, $statut, $date), 'Plan');
        $this->simulerPlatau([self::ID => $etat]);
        $this->exporter(self::ID);

        $this->assertAucunDepot();
        $this->assertPiece($piece, 'to_be_exported', null);
        $this->assertStatuts($statut, $date);
    }

    public function testConsultationDevenueIneligibleAuDetailAvantLeTicket(): void
    {
        $piece = $this->pieceSurDisque($this->dossierAvis(), 'Notice');
        $this->simulerPlatau([self::ID => 3], etatAuDetail: 6);
        $this->exporter();

        $this->assertGreaterThanOrEqual(2, count(array_keys($this->chemins, '/dau/consultations/recherche', true)));
        $this->assertAucunDepot();
        $this->assertPiece($piece, 'to_be_exported', null);
        $this->assertStatuts('in_progress', null);
    }

    public static function reponsesApresDepots(): iterable
    {
        yield 'acceptation : seul le second document est associé' => [200];
        yield 'refus : seule la seconde pièce revient en attente' => [403];
    }

    #[DataProvider('reponsesApresDepots')]
    public function testPremierDepotEchouePuisSecondReussit(int $codeAvis): void
    {
        $dossier = $this->dossierAvis();
        $premier = $this->pieceSurDisque($dossier, 'Premier plan');
        $second = $this->pieceSurDisque($dossier, 'Second plan');
        $this->simulerPlatau([self::ID => 3], [403, 200], ['DOC-002'], $codeAvis);
        $this->exporter();

        $recherches = array_keys($this->chemins, '/dau/consultations/recherche', true);
        $ticket = array_search('/pre-upload', $this->chemins, true);
        $this->assertIsInt($ticket);
        $this->assertGreaterThanOrEqual(2, count($recherches));
        $this->assertLessThan($ticket, max($recherches), 'La dernière vérification de l’état et de la version précède le premier ticket.');
        $this->assertCount(1, $this->envois);
        $documents = $this->envois[0]['documents'];
        $this->assertCount(1, $documents);
        $this->assertSame('35', $documents[0]['fileId']);
        $this->assertSame('29', $documents[0]['folderId']);
        $this->assertSame(9, $documents[0]['nomTypeDocument']);
        $this->assertSame(hash('sha512', 'Second plan'), $documents[0]['hash']);
        $this->assertPiece($premier, 'on_error', null);
        if (200 === $codeAvis) {
            $this->assertPiece($second, 'awaiting_status', 'DOC-002');
            $this->assertStatuts('treated', date('Y-m-d'));
        } else {
            $this->assertPiece($second, 'to_be_exported', null);
            $this->assertStatuts('in_error', null);
        }
    }

    public function testAvisAccepteSansDocumentRetourneNeRepartPas(): void
    {
        $piece = $this->pieceSurDisque($this->dossierAvis(), 'Notice');
        $this->simulerPlatau([self::ID => 3], [200], []);
        $this->exporter();

        $this->assertCount(1, $this->envois);
        $this->assertCount(1, $this->envois[0]['documents']);
        $this->assertPiece($piece, 'awaiting_status', null);
        $this->assertStatuts('treated', date('Y-m-d'));

        $this->etats[self::ID] = 6;
        $this->chemins = [];
        $this->exporter(self::ID);
        $this->assertCount(1, $this->envois);
        $this->assertSame([], array_diff($this->chemins, ['/token', '/dau/consultations/recherche']));
        $this->assertPiece($piece, 'awaiting_status', null);
        $this->assertStatuts('treated', date('Y-m-d'));
    }

    private function exporter(?string $id = null): void
    {
        $input = null === $id ? ['command' => 'platau:export-avis'] : ['command' => 'export-avis', '--consultation-id' => $id];
        $this->assertSame(Command::SUCCESS, $this->console->run($input), $this->console->getDisplay());
    }

    private function dossierAvis(?int $commission = Avis::FAVORABLE, string $statut = 'in_progress', ?string $date = null, string $id = self::ID): int
    {
        $dossier = $this->dossier(0, 'taken_into_account', $id);
        $this->db->update('platauconsultation', ['STATUT_AVIS' => $statut, 'DATE_AVIS' => $date], ['ID_PLATAU' => $id]);
        $this->db->update('dossier', ['AVIS_DOSSIER_COMMISSION' => $commission], ['ID_DOSSIER' => $dossier]);

        return $dossier;
    }

    private function pieceSurDisque(int $dossier, string $contenu): int
    {
        $piece = $this->piece($dossier);
        new Filesystem()->dumpFile($this->directory.'/uploads/pieces-jointes/'.$piece.'.pdf', $contenu);

        return $piece;
    }

    private function assertStatuts(string $avis, ?string $date, string $id = self::ID): void
    {
        $this->assertSame(['taken_into_account', '2026-10-28', $avis, $date], $this->db->fetchNumeric(
            'SELECT STATUT_PEC, DATE_PEC, STATUT_AVIS, DATE_AVIS FROM platauconsultation WHERE ID_PLATAU = ?', [$id],
        ), $this->console->getDisplay());
    }

    private function assertAucunDepot(): void
    {
        $this->assertSame([], array_diff($this->chemins, ['/token', '/dau/consultations/recherche', '/dau/avis/recherche']));
        $this->assertSame([], $this->envois);
    }

    private function assertPiece(int $id, string $statut, ?string $document): void
    {
        $this->assertSame([$statut, $document], $this->db->fetchNumeric(
            'SELECT s.NOM_STATUT, p.ID_PLATAU FROM piecejointe p JOIN piecejointestatut s USING (ID_PIECEJOINTESTATUT) WHERE p.ID_PIECEJOINTE = ?', [$id],
        ));
    }

    /**
     * Répond selon la route et les critères, pas selon l'ordre de découverte des consultations.
     *
     * @param array<string, int> $etats     État distant par identifiant
     * @param list<int>          $uploads   Codes HTTP successifs du stockage tiers
     * @param list<string>       $documents Identifiants dans l'ordre du retour V13
     */
    private function simulerPlatau(array $etats, array $uploads = [], array $documents = [], int $codeAvis = 200, ?int $etatAuDetail = null): void
    {
        $this->etats = $etats;
        $this->http->setResponseFactory(function (string $method, string $url, array $options) use (&$uploads, $documents, $codeAvis, $etatAuDetail): JsonMockResponse {
            $this->chemins[] = (string) parse_url($url, \PHP_URL_PATH);
            $route = $method.' '.explode('?', $url)[0];
            switch ($route) {
                case 'POST https://oauth.example.test/token':
                    return $this->token();
                case 'POST https://oauth.example.test/depot':
                    $this->assertNotEmpty($uploads, 'Demande de jeton de dépôt inattendue.');

                    return $this->token('jeton-depot');
                case 'GET https://depot.example.test/pre-upload':
                    $this->assertNotEmpty($uploads, 'Demande de ticket inattendue.');
                    $this->assertSame(['Authorization: Bearer jeton-depot'], $options['normalized_headers']['authorization']);

                    return new JsonMockResponse(['Storage_URL' => 'https://storage.example.test', 'Authorization_for_upload' => 'ticket-tiers',
                        'AppKey' => 'cle', 'VirtualFolderId' => 29, 'Folder_Name' => 'AVIS']);
                case 'POST https://storage.example.test/v2/mime/files':
                    $this->assertNotEmpty($uploads, 'Upload inattendu.');
                    $this->assertSame(['Authorization: ticket-tiers'], $options['normalized_headers']['authorization']);
                    $this->assertArrayNotHasKey('id-acteur-appelant', $options['normalized_headers']);
                    $code = array_shift($uploads);

                    return new JsonMockResponse(200 === $code ? ['data_file_id' => 35, 'data_file_version_id' => 1] : [], ['http_code' => $code]);
                case 'POST https://api.example.test/dau/consultations/recherche':
                    $criteria = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR)['criteresSurConsultations'];

                    return $this->consultations($criteria, $etatAuDetail);
                case 'POST https://api.example.test/dau/avis/recherche':
                    $id = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR)['criteresSurConsultations']['idConsultation'];
                    $this->assertArrayHasKey($id, $this->etats);

                    return new JsonMockResponse(['nombrePages' => 1, 'resultats' => [['dossier' => [
                        'idDossier' => $this->idDossierDistant($id), 'avis' => [
                            ['idConsultation' => $id, 'idAvis' => 'AV1', 'dtAvis' => '2026-10-29'],
                            ['idConsultation' => $id, 'idAvis' => 'AV2', 'dtAvis' => '2026-11-02'],
                        ],
                    ]]]]);
                case 'POST https://api.example.test/dau/avis':
                    $body = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);

                    return $this->versement($body, $documents, $codeAvis);
                default:
                    $this->fail('Requête HTTP inattendue : '.$route);
            }
        });
    }

    /** @param array{idConsultation?: string, nomEtatConsultation?: list<int>} $criteria */
    private function consultations(array $criteria, ?int $etatAuDetail): JsonMockResponse
    {
        $id = $criteria['idConsultation'] ?? null;
        if (null === $id) {
            $this->assertSame([3], $criteria['nomEtatConsultation']);
        }
        $rows = [];
        foreach ($this->etats as $consultation => $etat) {
            if (null === $id ? 3 !== $etat : $id !== $consultation) {
                continue;
            }
            $rows[] = ['dossier' => [
                'idDossier' => $this->idDossierDistant($consultation),
                'noVersion' => null === $id ? 3 : 8,
                'consultations' => [['idConsultation' => $consultation, 'noVersion' => 2,
                    'nomEtatConsultation' => ['idNom' => null === $id ? $etat : ($etatAuDetail ?? $etat)]]],
            ]];
        }

        return new JsonMockResponse(['nombrePages' => [] === $rows ? 0 : 1, 'resultats' => $rows]);
    }

    /**
     * @param list<array<string, mixed>> $body
     * @param list<string>               $documents
     */
    private function versement(array $body, array $documents, int $code): JsonMockResponse
    {
        $this->assertCount(1, $body);
        $this->assertCount(1, $body[0]['avis']);
        $avis = $body[0]['avis'][0];
        $this->assertArrayHasKey($avis['idConsultation'], $this->etats);
        $this->assertSame($this->idDossierDistant($avis['idConsultation']), $body[0]['idDossier']);
        $this->assertSame(8, $body[0]['noVersion'], 'Version du détail, et non celle de la recherche en lot.');
        $this->envois[] = $avis;
        if (200 !== $code) {
            return new JsonMockResponse([], ['http_code' => $code]);
        }

        return new JsonMockResponse([[
            'idDossier' => $body[0]['idDossier'], 'noVersion' => 9,
            'avis' => [['idAvis' => 'AV3', 'noVersion' => 1, 'documents' => array_map(
                static fn (string $doc): array => ['idDocument' => $doc, 'noVersion' => 1],
                $documents,
            )]],
        ]]);
    }

    private function idDossierDistant(string $consultation): string
    {
        return 'DOS-BBB-'.substr($consultation, -3);
    }
}
