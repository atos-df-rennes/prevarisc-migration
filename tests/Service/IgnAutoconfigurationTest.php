<?php

namespace App\Tests\Service;

use App\Service\IgnAutoconfiguration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\EnvVarProcessor;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

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
            'fichier existant sans slash initial' => ['ign/customConfig.json', 'ign/customConfig.json', false],
            'fichier introuvable' => ['/ign/missing.json', '', true],
            'répertoire au lieu d\'un fichier' => ['/ign', '', true],
        ];
    }

    /**
     * @dataProvider environmentProvider
     */
    public function testSymfonyReadsOptionalApacheEnvironment(?string $value, bool $useGetenv): void
    {
        $name = 'PREVARISC_IGN_AUTOCONF';
        $previousServer = $_SERVER[$name] ?? null;
        $previousEnv = $_ENV[$name] ?? null;
        $previousGetenv = getenv($name);

        try {
            unset($_SERVER[$name], $_ENV[$name]);
            putenv($name);
            if (null !== $value) {
                if ($useGetenv) {
                    putenv($name.'='.$value);
                } else {
                    $_SERVER[$name] = $value;
                }
            }

            $container = new Container(new ParameterBag(['env('.$name.')' => '']));
            $processor = new EnvVarProcessor($container);
            $path = $processor->getEnv('string', $name, static function (): void {
                self::fail('Unexpected environment indirection.');
            });
            $logger = $this->createMock(LoggerInterface::class);
            $logger->expects(self::never())->method('warning');
            $service = new IgnAutoconfiguration($path, $this->tmpDir, $logger);

            self::assertSame($value ?? '', $service->getPath());
        } finally {
            if (null !== $previousServer) {
                $_SERVER[$name] = $previousServer;
            } else {
                unset($_SERVER[$name]);
            }
            if (null !== $previousEnv) {
                $_ENV[$name] = $previousEnv;
            } else {
                unset($_ENV[$name]);
            }
            putenv(false === $previousGetenv ? $name : $name.'='.$previousGetenv);
        }
    }

    /**
     * @return array<string, array{?string, bool}>
     */
    public function environmentProvider(): array
    {
        return [
            'variable absente' => [null, false],
            'variable Apache vide' => ['', false],
            'variable Apache via $_SERVER' => ['/ign/customConfig.json', false],
            'variable Apache via getenv' => ['/ign/customConfig.json', true],
        ];
    }
}
