<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'session' => [
            'handler_id' => null,
            'cookie_secure' => 'auto',
            'cookie_samesite' => 'lax',
        ],
        'esi' => true,
        'fragments' => [
            'path' => '/_fragment',
        ],
        'php_errors' => [
            'log' => true,
            'throw' => false,
        ],
    ]);
};
