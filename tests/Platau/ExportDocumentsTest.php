<?php

declare(strict_types=1);

namespace App\Tests\Platau;

use App\Entity\Dossier;
use App\Entity\DossierPieceJointe;
use App\Entity\DossierType;
use App\Entity\PieceJointe;
use App\Entity\PieceJointeStatut;
use App\Platau\ExportDocuments;
use App\Platau\PisteTokenProvider;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\WithEnvironmentVariable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Zenstruck\Foundry\Attribute\ResetDatabase;

use function Zenstruck\Foundry\Persistence\persist;
use function Zenstruck\Foundry\Persistence\repository;

#[ResetDatabase]
#[Group('database')]
#[WithEnvironmentVariable('PREVARISC_REAL_DATA_PATH', '')]
#[WithEnvironmentVariable('PLATAU_ID_ACTEUR_APPELANT', 'ACTEUR-TEST')]
#[WithEnvironmentVariable('PISTE_CLIENT_ID', 'consultation')]
#[WithEnvironmentVariable('PISTE_CLIENT_SECRET', 'test-lecture')]
#[WithEnvironmentVariable('PISTE_ACCESS_TOKEN_URL', 'https://oauth.test/lecture')]
#[WithEnvironmentVariable('SYNCPLICITY_PISTE_CLIENT_ID', 'documents')]
#[WithEnvironmentVariable('SYNCPLICITY_PISTE_CLIENT_SECRET', 'test-depot')]
#[WithEnvironmentVariable('SYNCPLICITY_PISTE_ACCESS_TOKEN_URL', 'https://oauth.test/depot')]
#[WithEnvironmentVariable('SYNCPLICITY_URL', 'https://api.test/upload/')]
final class ExportDocumentsTest extends KernelTestCase
{
    private string $directory;

    private Connection $db;

    private MockHttpClient $transport;

    private Dossier $dossier;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/pec-'.bin2hex(random_bytes(12));
        $_ENV['PREVARISC_REAL_DATA_PATH'] = $this->directory;
        self::bootKernel();
        $container = self::getContainer();
        $this->db = $container->get(Connection::class);
        $this->transport = new MockHttpClient([]);
        $container->set('http_client.transport', $this->transport);
        $container->set('platau.piece_transport', $this->transport);

        $this->dossier = $this->newDossier();
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->directory);
        parent::tearDown();
    }

    public static function issuesPec(): iterable
    {
        yield 'acceptation' => [true];
        yield 'refus' => [false];
    }

    #[DataProvider('issuesPec')]
    public function testErreurPuisSuccesSansDecalageNiMelangeDeJetons(bool $accepted): void
    {
        $error = $this->piece($this->dossier);
        $success = $this->piece($this->dossier);
        $ignored = $this->piece($this->dossier, 'not_exported');
        $other = $this->piece($this->newDossier());
        $filename = sprintf('plan joint_%d.pdf', $success);
        $ticket = [
            'VirtualFolderId' => 29,
            'Folder_Name' => 'PEC',
            'Storage_URL' => 'https://storage.test/base/ticket',
            'Authorization_for_upload' => 'ticket-de-test',
            'AppKey' => 'cle-de-test',
        ];
        $this->transport->setResponseFactory([
            $this->token('lecture', 'consultation:test-lecture'),
            $this->token('depot', 'documents:test-depot'),
            function (string $method, string $url, array $options): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertSame('https://api.test/upload/pre-upload', $url);
                $this->assertSame(['Authorization: Bearer jeton-depot'], $options['normalized_headers']['authorization']);
                $this->assertArrayNotHasKey('id-acteur-appelant', $options['normalized_headers']);
                $this->assertArrayNotHasKey('appkey', $options['normalized_headers']);

                return new MockResponse('contenu confidentiel', ['http_code' => 403]);
            },
            new JsonMockResponse($ticket),
            function (string $method, string $url, array $options) use ($filename): JsonMockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame('https://storage.test/base/v2/mime/files', explode('?', $url)[0]);
                parse_str(parse_url($url, \PHP_URL_QUERY), $query);
                $this->assertSame('PEC/'.urlencode($filename), $query['filepath']);
                $this->assertSame(['Authorization: ticket-de-test'], $options['normalized_headers']['authorization']);
                $this->assertContains('AppKey: cle-de-test', $options['headers']);
                $this->assertArrayNotHasKey('id-acteur-appelant', $options['normalized_headers']);
                $this->assertSame(0, $options['max_redirects']);
                $body = '';
                while ('' !== $chunk = $options['body'](16372)) {
                    $body .= $chunk;
                }
                foreach (['fileData', 'sha256', 'sessionKey', 'virtualFolderId', 'filename'] as $field) {
                    $this->assertStringContainsString('name='.$field, str_replace('"', '', $body));
                }
                foreach (['contenu du plan', $filename, 'ticket-de-test', hash('sha256', 'contenu du plan')] as $value) {
                    $this->assertStringContainsString($value, $body);
                }
                $this->assertStringNotContainsString('jeton-depot', $body);

                return new JsonMockResponse(['data_file_id' => 151, 'data_file_version_id' => 3]);
            },
        ]);
        $lecture = self::getContainer()->get(PisteTokenProvider::class);
        $this->assertSame('jeton-lecture', $lecture->token());
        $service = $this->service();
        $reports = [];
        $report = static function (string $message) use (&$reports): void {
            $reports[] = $message;
        };
        $prepared = $service->prepare($this->dossier->getIdOrFail(), $report, 47);
        $this->assertSame([$success], array_keys($prepared));
        $this->assertSame([
            'fileId' => '151',
            'folderId' => '29',
            'dtProduction' => date('Y-m-d'),
            'idActeurProducteur' => 'ACTEUR-TEST',
            'algoHash' => 'SHA-512',
            'hash' => hash('sha512', 'contenu du plan'),
            'nomTypeDocument' => 47,
            'nomTypeProducteurDoc' => 1,
        ], $prepared[$success]);
        $this->assertSame(['awaiting_status', null, null], $this->state($success));
        if ($accepted) {
            $service->accepted($prepared, [['idDocument' => 'DOC-PEC-001']], $report);
            $this->assertSame(['awaiting_status', 'DOC-PEC-001', null], $this->state($success));
        } else {
            $service->failed($prepared);
            $this->assertSame(['to_be_exported', null, null], $this->state($success));
        }
        $reason = 'Pré-dépôt Syncplicity : accès refusé (HTTP 403).';
        $this->assertSame(['on_error', null, $reason], $this->state($error));
        $this->assertSame([sprintf('Pièce %d : %s', $error, $reason)], $reports);
        $this->assertSame(['not_exported', null, null], $this->state($ignored));
        $this->assertSame(['to_be_exported', null, null], $this->state($other));
        $this->assertSame('jeton-lecture', $lecture->token());
    }

    public function testLibelleAvecSeparateursEtRenouvellementDuTicketAvantDepot(): void
    {
        $id = $this->piece($this->dossier, name: 'Plan RDC/R+1\\coupe');
        $first = new MockResponse('', ['http_code' => 401]);
        $second = new JsonMockResponse(['Storage_URL' => 'https://storage.test/prefix/', 'VirtualFolderId' => 1,
            'Folder_Name' => 'PEC', 'Authorization_for_upload' => 'ticket', 'AppKey' => 'cle']);
        $upload = new JsonMockResponse(['data_file_id' => 3, 'data_file_version_id' => 1]);
        $this->transport->setResponseFactory([
            new JsonMockResponse(['access_token' => 'ancien', 'expires_in' => 3600]), $first,
            new JsonMockResponse(['access_token' => 'nouveau', 'expires_in' => 3600]), $second, $upload,
        ]);
        $prepared = $this->service()->prepare($this->dossier->getIdOrFail(), $this->fail(...), 47);
        $this->assertSame([$id], array_keys($prepared));
        $this->assertContains('Authorization: Bearer ancien', $first->getRequestOptions()['headers']);
        $this->assertContains('Authorization: Bearer nouveau', $second->getRequestOptions()['headers']);
        $this->assertSame('GET', $second->getRequestMethod());
        $this->assertSame($first->getRequestUrl(), $second->getRequestUrl());
        $this->assertSame('https://storage.test/prefix/v2/mime/files', explode('?', $upload->getRequestUrl())[0]);
        parse_str(parse_url($upload->getRequestUrl(), \PHP_URL_QUERY), $query);
        $this->assertSame('PEC/'.urlencode('Plan RDC-R+1-coupe_'.$id.'.pdf'), $query['filepath']);
        $this->assertSame('Plan RDC/R+1\\coupe', $this->db->fetchOne('SELECT NOM_PIECEJOINTE FROM piecejointe WHERE ID_PIECEJOINTE = ?', [$id]));
    }

    public function testSecondRefusDuTicketSansDepot(): void
    {
        $id = $this->piece($this->dossier);
        $this->transport->setResponseFactory([
            $this->token('depot', 'documents:test-depot'), new MockResponse('', ['http_code' => 401]),
            $this->token('depot', 'documents:test-depot'), new MockResponse('', ['http_code' => 401]),
        ]);
        $messages = [];
        $this->assertSame([], $this->service()->prepare($this->dossier->getIdOrFail(), static function (string $message) use (&$messages): void {
            $messages[] = $message;
        }, 47));
        $this->assertStringContainsString('HTTP 401', $messages[0]);
        $this->assertSame('on_error', $this->state($id)[0]);
    }

    public function testConfigurationAbsenteLaisseLaPieceEnAttente(): void
    {
        $_ENV['SYNCPLICITY_PISTE_CLIENT_SECRET'] = '';
        $id = $this->piece($this->dossier);
        $this->assertSame([], $this->service()->prepare($this->dossier->getIdOrFail(), $this->fail(...), 47));
        $this->assertSame(['to_be_exported', null, null], $this->state($id));
    }

    public static function documentsAmbigus(): iterable
    {
        yield 'document manquant' => [[['idDocument' => 'DOC-PEC-001']]];
        yield 'document surnuméraire' => [[['idDocument' => 'DOC-PEC-001'], ['idDocument' => 'DOC-PEC-002'], ['idDocument' => 'DOC-PEC-003']]];
    }

    #[DataProvider('documentsAmbigus')]
    public function testPecAccepteeSansAppariementAmbiguNiRelance(array $documents): void
    {
        $first = $this->piece($this->dossier, 'awaiting_status');
        $second = $this->piece($this->dossier, 'awaiting_status');
        $reports = [];
        $this->service()->accepted([$first => ['fileId' => '101'], $second => ['fileId' => '102']], $documents, static function (string $message) use (&$reports): void {
            $reports[] = $message;
        });
        $this->assertCount(1, $reports);
        $this->assertStringContainsString('Aucun identifiant associé', $reports[0]);
        $this->assertSame(['awaiting_status', null, null], $this->state($first));
        $this->assertSame(['awaiting_status', null, null], $this->state($second));
    }

    public function testFichierAbsentSansDivulguerLeChemin(): void
    {
        $id = $this->piece($this->dossier, write: false);
        $messages = [];
        $this->assertSame([], $this->service()->prepare($this->dossier->getIdOrFail(), static function (string $message) use (&$messages): void {
            $messages[] = $message;
        }, 47));
        $reason = 'Impossible de récupérer le contenu du fichier.';
        $this->assertSame([sprintf('Pièce %d : %s', $id, $reason)], $messages);
        $this->assertSame(['on_error', null, $reason], $this->state($id));
    }

    private function token(string $scope, string $credentials): \Closure
    {
        return function (string $method, string $url, array $options) use ($scope, $credentials): JsonMockResponse {
            $this->assertSame('POST', $method);
            $this->assertSame('https://oauth.test/'.$scope, $url);
            $this->assertSame(['Authorization: Basic '.base64_encode($credentials)], $options['normalized_headers']['authorization']);
            $this->assertSame('grant_type=client_credentials', $options['body']);

            return new JsonMockResponse(['access_token' => 'jeton-'.$scope, 'expires_in' => 3600]);
        };
    }

    private function service(): ExportDocuments
    {
        return self::getContainer()->get(ExportDocuments::class);
    }

    private function newDossier(): Dossier
    {
        return persist(Dossier::class, ['type' => repository(DossierType::class)->findOrFail(1), 'dateInsert' => new \DateTime()]);
    }

    private function piece(Dossier $dossier, string $status = 'to_be_exported', bool $write = true, string $name = 'plan joint'): int
    {
        $piece = persist(PieceJointe::class, [
            'nom' => $name, 'extension' => '.pdf',
            'statut' => repository(PieceJointeStatut::class)->findOneBy(['nomStatut' => $status]),
        ]);
        persist(DossierPieceJointe::class, ['dossier' => $dossier, 'pieceJointe' => $piece, 'pieceJointeCommission' => false]);
        $id = $piece->getId();
        $this->assertNotNull($id);
        if ($write) {
            new Filesystem()->dumpFile($this->directory.'/uploads/pieces-jointes/'.$id.'.pdf', 'contenu du plan');
        }

        return $id;
    }

    private function state(int $id): array
    {
        return $this->db->fetchNumeric(
            'SELECT s.NOM_STATUT, p.ID_PLATAU, p.MESSAGE_ERREUR FROM piecejointe p
             JOIN piecejointestatut s ON s.ID_PIECEJOINTESTATUT = p.ID_PIECEJOINTESTATUT WHERE ID_PIECEJOINTE = ?', [$id],
        );
    }
}
