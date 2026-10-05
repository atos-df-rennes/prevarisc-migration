<?php

namespace App\Tests\Service;

use App\Service\IgnAutoconfiguration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class IgnAutoconfigurationTest extends TestCase
{
    /** @var string */
    private $tmpDir;

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

    /**
     * @dataProvider pathProvider
     */
    public function testGetPath(string $configuredPath, string $expectedPath, bool $warning): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($warning ? self::once() : self::never())->method('warning');
        $service = new IgnAutoconfiguration($configuredPath, $this->tmpDir, $logger);

        self::assertSame($expectedPath, $service->getPath());
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public function pathProvider(): array
    {
        return [
            'configuration vide' => ['', '', false],
            'fichier existant' => ['/ign/customConfig.json', '/ign/customConfig.json', false],
            'fichier existant sans slash initial' => ['ign/customConfig.json', '/ign/customConfig.json', false],
            'fichier introuvable' => ['/ign/missing.json', '', true],
            'répertoire au lieu d\'un fichier' => ['/ign', '', true],
        ];
    }
}
