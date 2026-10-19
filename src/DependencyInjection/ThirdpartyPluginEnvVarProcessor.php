<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * Détermine si un plugin tiers "legacy" est activé, à partir de la variable
 * PREVARISC_THIRDPARTY_PLUGINS (liste de classes Zend séparées par ';').
 *
 * Permet d'utiliser %env(sdis29_plugin_enabled:PREVARISC_THIRDPARTY_PLUGINS)%
 * (ou l'un des autres préfixes déclarés dans self::PLUGIN_CLASSES) pour
 * piloter, côté Symfony, les portions de code équivalentes aux plugins
 * legacy correspondants (postDispatch legacy), en respectant la même
 * variable d'environnement que le legacy Zend (un seul déploiement partagé
 * par tous les SDIS, différencié uniquement par les variables
 * d'environnement).
 *
 * Équivalent legacy : Bootstrap::run() (chargement des plugins tiers).
 */
final class ThirdpartyPluginEnvVarProcessor implements EnvVarProcessorInterface
{
    /**
     * Association préfixe env(...) => nom de classe Plugin_* legacy détectée
     * dans PREVARISC_THIRDPARTY_PLUGINS.
     */
    private const array PLUGIN_CLASSES = [
        'sdis29_plugin_enabled' => 'Plugin_SDIS29',
        'facteur_criticite_plugin_enabled' => 'Plugin_FacteurCriticite',
        'libelle_dossier_observations_plugin_enabled' => 'Plugin_LibelleDossierObservations',
        'libelle_dossier_prescriptions_plugin_enabled' => 'Plugin_LibelleDossierPrescriptions',
    ];

    public static function getProvidedTypes(): array
    {
        return array_fill_keys(array_keys(self::PLUGIN_CLASSES), 'bool');
    }

    /**
     * @param mixed    $prefix Préfixe (une des clés de self::PLUGIN_CLASSES)
     * @param mixed    $name   Nom de la variable (PREVARISC_THIRDPARTY_PLUGINS)
     * @param \Closure $getEnv Closure permettant de récupérer la variable d'environnement brute
     */
    public function getEnv($prefix, $name, \Closure $getEnv): bool
    {
        $prefix = is_scalar($prefix) ? (string) $prefix : '';

        if (!isset(self::PLUGIN_CLASSES[$prefix])) {
            return false;
        }

        $rawValue = $getEnv($name);
        $rawValue = is_scalar($rawValue) ? (string) $rawValue : '';

        if ('' === $rawValue) {
            return false;
        }

        $plugins = array_map(trim(...), explode(';', $rawValue));

        return in_array(self::PLUGIN_CLASSES[$prefix], $plugins, true);
    }
}
