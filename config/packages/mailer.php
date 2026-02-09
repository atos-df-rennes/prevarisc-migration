<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'mailer' => [
            // Utilise le processor custom pour construire DSN depuis PREVARISC_MAIL_*
            // Le processor retourne la DSN construite ou fallback sur MAILER_DSN si erreur
            'dsn' => '%env(prevarisc_mailer_dsn:MAILER_DSN)%',
        ],
    ]);
};
