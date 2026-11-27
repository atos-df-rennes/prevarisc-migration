<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\TraceableHttpClient;

abstract class PlatauExportTestCase extends KernelTestCase
{
    protected const string ID = 'AAA-BBB-001';

    protected Connection $db;

    protected ApplicationTester $console;

    protected MockHttpClient $http;

    protected TraceableHttpClient $calls;

    protected string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/export-platau-'.bin2hex(random_bytes(12));
        $_ENV['PREVARISC_REAL_DATA_PATH'] = $this->directory;
        $application = new Application(self::bootKernel());
        $application->setAutoExit(false);

        $this->console = new ApplicationTester($application);
        $this->db = self::getContainer()->get(Connection::class);
        self::getContainer()->set('cache.platau_consultation_tokens', new ArrayAdapter());
        $this->http = new MockHttpClient([]);
        $this->calls = new TraceableHttpClient($this->http);
        self::getContainer()->set('http_client.transport', $this->calls);
        self::getContainer()->set('platau.piece_transport', $this->calls);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->directory);
        parent::tearDown();
    }

    protected function dossier(?int $incomplet, string $statut = 'awaiting', string $id = self::ID): int
    {
        $this->db->insert('platauconsultation', ['ID_PLATAU' => $id, 'STATUT_PEC' => $statut, 'DATE_PEC' => '2026-10-28']);
        $this->db->insert('dossier', ['ID_PLATAU' => $id, 'TYPE_DOSSIER' => 1, 'INCOMPLET_DOSSIER' => $incomplet, 'DATEINSERT_DOSSIER' => '2026-10-01']);

        return (int) $this->db->lastInsertId();
    }

    protected function token(string $value = 'jeton-recette'): JsonMockResponse
    {
        return new JsonMockResponse(['access_token' => $value, 'expires_in' => 3600]);
    }

    protected function piece(int $dossier): int
    {
        $this->db->insert('piecejointe', ['NOM_PIECEJOINTE' => 'notice', 'EXTENSION_PIECEJOINTE' => '.pdf', 'ID_PIECEJOINTESTATUT' => 2]);
        $piece = (int) $this->db->lastInsertId();
        $this->db->insert('dossierpj', ['ID_DOSSIER' => $dossier, 'ID_PIECEJOINTE' => $piece, 'PJ_COMMISSION' => 0]);

        return $piece;
    }
}
