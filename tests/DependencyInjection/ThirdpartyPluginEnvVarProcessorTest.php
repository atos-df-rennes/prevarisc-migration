<?php

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\ThirdpartyPluginEnvVarProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Teste la détection des plugins legacy (Plugin_SDIS29, Plugin_FacteurCriticite,
 * Plugin_LibelleDossierObservations, Plugin_LibelleDossierPrescriptions) dans
 * PREVARISC_THIRDPARTY_PLUGINS (liste de classes Zend séparées par ';').
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
    public function testGetEnv(string $prefix, string $rawValue, bool $expected): void
    {
        $result = $this->processor->getEnv(
            $prefix,
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
            'Plugin_SDIS29 présent parmi d\'autres' => ['sdis29_plugin_enabled', 'Plugin_Foo;Plugin_SDIS29;Plugin_Bar', true],
            'Plugin_SDIS29 absent' => ['sdis29_plugin_enabled', 'Plugin_Foo;Plugin_Bar', false],
            'variable vide' => ['sdis29_plugin_enabled', '', false],
            'Plugin_FacteurCriticite présent' => ['facteur_criticite_plugin_enabled', 'Plugin_FacteurCriticite;Plugin_SDIS29', true],
            'Plugin_FacteurCriticite absent' => ['facteur_criticite_plugin_enabled', 'Plugin_SDIS29', false],
            'Plugin_LibelleDossierObservations présent' => ['libelle_dossier_observations_plugin_enabled', 'Plugin_LibelleDossierObservations', true],
            'Plugin_LibelleDossierObservations absent' => ['libelle_dossier_observations_plugin_enabled', 'Plugin_LibelleDossierPrescriptions', false],
            'Plugin_LibelleDossierPrescriptions présent' => ['libelle_dossier_prescriptions_plugin_enabled', 'Plugin_LibelleDossierPrescriptions', true],
            'Plugin_LibelleDossierPrescriptions absent' => ['libelle_dossier_prescriptions_plugin_enabled', '', false],
            'préfixe inconnu' => ['unknown_plugin_enabled', 'Plugin_SDIS29', false],
        ];
    }
}
