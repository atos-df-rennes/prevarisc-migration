<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * Détermine si un plugin tiers "legacy" est activé, à partir de la variable
 * PREVARISC_THIRDPARTY_PLUGINS (liste de classes Zend séparées par ';').
 *
 * Permet d'utiliser %env(sdis29_plugin_enabled:PREVARISC_THIRDPARTY_PLUGINS)%
 * pour piloter, côté Symfony, les portions de code équivalentes à
 * Plugin_SDIS29 (postDispatch legacy), en respectant la même variable
 * d'environnement que le legacy Zend (un seul déploiement partagé par tous
 * les SDIS, différencié uniquement par les variables d'environnement).
 *
 * Équivalent legacy : Bootstrap::run() (chargement des plugins tiers).
 */
class ThirdpartyPluginEnvVarProcessor implements EnvVarProcessorInterface
{
    private const PLUGIN_CLASS = 'Plugin_SDIS29';

    public static function getProvidedTypes(): array
    {
        return [
            'sdis29_plugin_enabled' => 'bool',
        ];
    }

    /**
     * @param mixed    $prefix Préfixe (sdis29_plugin_enabled)
     * @param mixed    $name   Nom de la variable (PREVARISC_THIRDPARTY_PLUGINS)
     * @param \Closure $getEnv Closure permettant de récupérer la variable d'environnement brute
     */
    public function getEnv($prefix, $name, \Closure $getEnv): bool
    {
        $rawValue = (string) $getEnv($name);

        if ('' === $rawValue) {
            return false;
        }

        $plugins = array_map('trim', explode(';', $rawValue));

        return in_array(self::PLUGIN_CLASS, $plugins, true);
    }
}
