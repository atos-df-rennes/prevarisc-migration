<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'mailer' => [
            // Utilise le processor custom pour construire DSN depuis PREVARISC_MAIL_*
            // N'utilise pas la variable MAILER_DSN pour le moment
            'dsn' => '%env(prevarisc_mailer_dsn:MAILER_DSN)%',
        ],
    ]);
};
