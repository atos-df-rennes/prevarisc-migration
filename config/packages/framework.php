<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'session' => [
            'handler_id' => null,
            'storage_factory_id' => 'session.storage.factory.native',
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
        'http_method_override' => true,
    ]);
};
