<?php

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\ThirdpartyPluginEnvVarProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Teste la détection de "Plugin_SDIS29" dans PREVARISC_THIRDPARTY_PLUGINS
 * (liste de classes Zend séparées par ';').
 */
final class ThirdpartyPluginEnvVarProcessorTest extends TestCase
{
    /** @var ThirdpartyPluginEnvVarProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new ThirdpartyPluginEnvVarProcessor();
    }

    /**
     * @dataProvider getEnvProvider
     */
    public function testGetEnv(string $rawValue, bool $expected): void
    {
        $result = $this->processor->getEnv(
            'sdis29_plugin_enabled',
            'PREVARISC_THIRDPARTY_PLUGINS',
            static function () use ($rawValue): string {
                return $rawValue;
            }
        );

        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function getEnvProvider(): array
    {
        return [
            'Plugin_SDIS29 présent parmi d\'autres' => ['Plugin_Foo;Plugin_SDIS29;Plugin_Bar', true],
            'Plugin_SDIS29 absent' => ['Plugin_Foo;Plugin_Bar', false],
            'variable vide' => ['', false],
        ];
    }
}
