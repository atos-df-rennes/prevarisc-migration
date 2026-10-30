<?php

declare(strict_types=1);

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\ThirdpartyPluginEnvVarProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Teste la détection des plugins legacy (Plugin_SDIS29, Plugin_FacteurCriticite,
 * Plugin_LibelleDossierObservations, Plugin_LibelleDossierPrescriptions) dans
 * PREVARISC_THIRDPARTY_PLUGINS (liste de classes Zend séparées par ';').
 */
final class ThirdpartyPluginEnvVarProcessorTest extends TestCase
{
    private ThirdpartyPluginEnvVarProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new ThirdpartyPluginEnvVarProcessor();
    }

    #[DataProvider('getEnvProvider')]
    public function testGetEnv(string $prefix, string $rawValue, bool $expected): void
    {
        $result = $this->processor->getEnv(
            $prefix,
            'PREVARISC_THIRDPARTY_PLUGINS',
            static fn (): string => $rawValue
        );

        $this->assertSame($expected, $result);
    }

    /**
     * @return \Iterator<string, array<mixed>>
     */
    public static function getEnvProvider(): \Iterator
    {
        yield "Plugin_SDIS29 présent parmi d'autres" => ['sdis29_plugin_enabled', 'Plugin_Foo;Plugin_SDIS29;Plugin_Bar', true];
        yield 'Plugin_SDIS29 absent' => ['sdis29_plugin_enabled', 'Plugin_Foo;Plugin_Bar', false];
        yield 'variable vide' => ['sdis29_plugin_enabled', '', false];
        yield 'Plugin_FacteurCriticite présent' => ['facteur_criticite_plugin_enabled', 'Plugin_FacteurCriticite;Plugin_SDIS29', true];
        yield 'Plugin_FacteurCriticite absent' => ['facteur_criticite_plugin_enabled', 'Plugin_SDIS29', false];
        yield 'Plugin_LibelleDossierObservations présent' => ['libelle_dossier_observations_plugin_enabled', 'Plugin_LibelleDossierObservations', true];
        yield 'Plugin_LibelleDossierObservations absent' => ['libelle_dossier_observations_plugin_enabled', 'Plugin_LibelleDossierPrescriptions', false];
        yield 'Plugin_LibelleDossierPrescriptions présent' => ['libelle_dossier_prescriptions_plugin_enabled', 'Plugin_LibelleDossierPrescriptions', true];
        yield 'Plugin_LibelleDossierPrescriptions absent' => ['libelle_dossier_prescriptions_plugin_enabled', '', false];
        yield 'préfixe inconnu' => ['unknown_plugin_enabled', 'Plugin_SDIS29', false];
    }
}
