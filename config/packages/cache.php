<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'cache' => [
            'pools' => [
                'cache.ign_capabilities' => [
                    'adapter' => 'cache.adapter.filesystem',
                ],
                'cache.search' => [
                    'adapter' => 'cache.adapter.filesystem',
                    'tags' => true,
                ],
            ],
        ],
    ]);
};
