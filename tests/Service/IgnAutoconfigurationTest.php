<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\IgnAutoconfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class IgnAutoconfigurationTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/prevarisc_ign_'.uniqid();
        mkdir($this->tmpDir.'/public/ign', 0777, true);
        file_put_contents($this->tmpDir.'/public/ign/customConfig.json', '{}');
    }

    protected function tearDown(): void
    {
        unlink($this->tmpDir.'/public/ign/customConfig.json');
        rmdir($this->tmpDir.'/public/ign');
        rmdir($this->tmpDir.'/public');
        rmdir($this->tmpDir);
    }

    #[DataProvider('pathProvider')]
    public function testGetPath(string $configuredPath, string $expectedPath, bool $warning): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($warning ? $this->once() : $this->never())->method('warning');
        $service = new IgnAutoconfiguration($configuredPath, $this->tmpDir, $logger);

        $this->assertSame($expectedPath, $service->getPath());
    }

    /**
     * @return \Iterator<string, array{string, string, bool}>
     */
    public static function pathProvider(): \Iterator
    {
        yield 'configuration vide' => ['', '', false];
        yield 'fichier existant' => ['/ign/customConfig.json', '/ign/customConfig.json', false];
        yield 'fichier existant sans slash initial' => ['ign/customConfig.json', '/ign/customConfig.json', false];
        yield 'fichier introuvable' => ['/ign/missing.json', '', true];
        yield "répertoire au lieu d'un fichier" => ['/ign', '', true];
    }
}
