<?php

namespace App\Tests\Service;

use App\Service\IgnAutoconfiguration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\EnvVarProcessor;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class IgnAutoconfigurationTest extends TestCase
{
    /** @var string */
    private $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/prevarisc_ign_'.uniqid();
        mkdir($this->tmpDir);
        mkdir($this->tmpDir.'/public');
        mkdir($this->tmpDir.'/apache');
        file_put_contents($this->tmpDir.'/public/customConfig.json', '{}');
        file_put_contents($this->tmpDir.'/apache/customConfig.json', '{}');
    }

    protected function tearDown(): void
    {
        unlink($this->tmpDir.'/public/customConfig.json');
        unlink($this->tmpDir.'/apache/customConfig.json');
        rmdir($this->tmpDir.'/public');
        rmdir($this->tmpDir.'/apache');
        rmdir($this->tmpDir);
    }

    /**
     * @dataProvider pathProvider
     */
    public function testResolvePath(string $configuredPath, ?string $documentRoot, bool $withRequest, string $expectedPath, bool $warning): void
    {
        $requests = new RequestStack();
        if ($withRequest) {
            $requests->push(new Request([], [], [], [], [], null === $documentRoot ? [] : [
                'DOCUMENT_ROOT' => $this->tmpDir.$documentRoot,
            ]));
        }

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($warning ? self::once() : self::never())->method('warning');
        $service = new IgnAutoconfiguration($configuredPath, $this->tmpDir, $requests, $logger);

        self::assertSame($expectedPath, $service->getPath());
    }

    public function pathProvider(): array
    {
        return [
            'empty configuration' => ['', '/apache', true, '', false],
            'Apache document root' => ['/customConfig.json', '/apache', true, '/customConfig.json', false],
            'root with trailing slash' => ['/customConfig.json', '/apache/', true, '/customConfig.json', false],
            'root with trailing Windows separator' => ['/customConfig.json', '/apache\\', true, '/customConfig.json', false],
            'relative path' => ['customConfig.json', '/apache', true, '/customConfig.json', false],
            'Windows web path separators' => ['\\customConfig.json', '/apache', true, '/customConfig.json', false],
            'no HTTP request' => ['/customConfig.json', null, false, '/customConfig.json', false],
            'no document root' => ['/customConfig.json', null, true, '/customConfig.json', false],
            'missing file' => ['/missing.json', '/apache', true, '', true],
            'directory is not a file' => ['/', '/apache', true, '', true],
            'no fallback to Symfony when Apache root differs' => ['/customConfig.json', '', true, '', true],
        ];
    }

    public function testUsesMasterRequestDocumentRoot(): void
    {
        $requests = new RequestStack();
        $requests->push(new Request([], [], [], [], [], ['DOCUMENT_ROOT' => $this->tmpDir.'/apache']));
        $requests->push(new Request([], [], [], [], [], ['DOCUMENT_ROOT' => $this->tmpDir.'/missing']));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');
        $service = new IgnAutoconfiguration('/customConfig.json', $this->tmpDir, $requests, $logger);

        self::assertSame('/customConfig.json', $service->getPath());
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
            $requests = new RequestStack();
            $requests->push(new Request([], [], [], [], [], ['DOCUMENT_ROOT' => $this->tmpDir.'/apache']));
            $logger = $this->createMock(LoggerInterface::class);
            $logger->expects(self::never())->method('warning');
            $service = new IgnAutoconfiguration($path, $this->tmpDir, $requests, $logger);

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

    public function environmentProvider(): array
    {
        return [
            'absent variable' => [null, false],
            'empty Apache variable' => ['', false],
            'Apache server variable' => ['/customConfig.json', false],
            'Apache getenv variable' => ['/customConfig.json', true],
        ];
    }
}
