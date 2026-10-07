<?php

declare(strict_types=1);

namespace App\Tests\Service\Platau;

use App\Service\Platau\PlatauGatewayConfig;
use App\Service\ProxyConfigurationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class PlatauGatewayConfigTest extends TestCase
{
    private string $directory;

    private string|false $previousPlatauPath;

    protected function setUp(): void
    {
        $this->previousPlatauPath = getenv('PREVARISC_PLATAU_PATH');
        $this->directory = sys_get_temp_dir().'/prevarisc-platau-config-'.uniqid('', true);
        mkdir($this->directory.'/src/Service', 0777, true);
        putenv('PREVARISC_PLATAU_PATH='.$this->directory);
    }

    protected function tearDown(): void
    {
        putenv(false === $this->previousPlatauPath ? 'PREVARISC_PLATAU_PATH' : 'PREVARISC_PLATAU_PATH='.$this->previousPlatauPath);
        foreach ([$this->directory.'/config.json', $this->directory.'/src/Service/PlatauAbstract.php'] as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        rmdir($this->directory.'/src/Service');
        rmdir($this->directory.'/src');
        rmdir($this->directory);
    }

    private function buildConfig(?LoggerInterface $logger = null): PlatauGatewayConfig
    {
        return new PlatauGatewayConfig(
            'config.json',
            $this->directory,
            $logger ?? new NullLogger(),
            new ProxyConfigurationService(false, 'HTTP', 0, '', '', '')
        );
    }

    public function testConfigurationIsReadLazilyAndOnlyOnce(): void
    {
        $config = $this->buildConfig();
        file_put_contents($this->directory.'/config.json', '{"platau.options":{"PISTE_CLIENT_ID":"id","PISTE_CLIENT_SECRET":"secret","PLATAU_URL":"https://platau.test/","PISTE_ACCESS_TOKEN_URL":"https://piste.test/token"}}');

        $this->assertSame('id', $config->getPisteClientId());
        $this->assertSame('secret', $config->getPisteClientSecret());
        $this->assertSame('https://platau.test/', $config->getPlatauUrl());
        $this->assertSame('https://piste.test/token', $config->getPisteTokenUrl());

        unlink($this->directory.'/config.json');
        $this->assertSame('id', $config->getPisteClientId());
        $this->assertSame('https://platau.test/', $config->getPlatauUrl());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidConfigurations(): iterable
    {
        yield 'JSON invalide' => ['not JSON'];
        yield 'options absentes' => ['{}'];
        yield 'options invalides' => ['{"platau.options":true}'];
        yield 'secret absent' => ['{"platau.options":{"PISTE_CLIENT_ID":"id"}}'];
        yield 'identifiants invalides' => ['{"platau.options":{"PISTE_CLIENT_ID":42,"PISTE_CLIENT_SECRET":"secret"}}'];
    }

    #[DataProvider('invalidConfigurations')]
    public function testInvalidConfigurationIsLogged(string $content): void
    {
        file_put_contents($this->directory.'/config.json', $content);
        $this->writeFallbackUrls();
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $config = $this->buildConfig($logger);

        $this->assertNull($config->getPisteClientId());
        $this->assertNull($config->getPisteClientSecret());
    }

    public function testMissingConfigurationIsLogged(): void
    {
        $this->writeFallbackUrls();
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $config = $this->buildConfig($logger);

        $this->assertNull($config->getPisteClientId());
        $this->assertNull($config->getPisteClientSecret());
    }

    public function testMissingDefaultUrlsAreLogged(): void
    {
        file_put_contents($this->directory.'/config.json', '{"platau.options":{"PISTE_CLIENT_ID":"id","PISTE_CLIENT_SECRET":"secret"}}');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('error');

        $config = $this->buildConfig($logger);

        $this->assertNull($config->getPlatauUrl());
        $this->assertNull($config->getPisteTokenUrl());
    }

    public function testEmptyOverridesUseDefaultUrls(): void
    {
        file_put_contents($this->directory.'/config.json', '{"platau.options":{"PISTE_CLIENT_ID":"id","PISTE_CLIENT_SECRET":"secret","PLATAU_URL":"","PISTE_ACCESS_TOKEN_URL":null}}');
        $this->writeFallbackUrls();

        $config = $this->buildConfig();

        $this->assertSame('https://default-platau.test/', $config->getPlatauUrl());
        $this->assertSame('https://default-piste.test/token', $config->getPisteTokenUrl());
    }

    public function testOverridesTakePrecedenceOverDefaults(): void
    {
        file_put_contents($this->directory.'/config.json', '{"platau.options":{"PISTE_CLIENT_ID":"id","PISTE_CLIENT_SECRET":"secret","PLATAU_URL":"https://override.test/","PISTE_ACCESS_TOKEN_URL":"https://override.test/token"}}');
        $this->writeFallbackUrls();

        $config = $this->buildConfig();

        $this->assertSame('https://override.test/', $config->getPlatauUrl());
        $this->assertSame('https://override.test/token', $config->getPisteTokenUrl());
    }

    private function writeFallbackUrls(): void
    {
        file_put_contents($this->directory.'/src/Service/PlatauAbstract.php', <<<'PHP'
            <?php
            abstract class PlatauAbstract
            {
                private const string PLATAU_URL = 'https://default-platau.test/';
                private const PISTE_ACCESS_TOKEN_URL = "https://default-piste.test/token";
            }
            PHP);
    }
}
