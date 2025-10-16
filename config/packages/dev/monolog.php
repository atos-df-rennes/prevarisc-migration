<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('monolog', [
        'handlers' => [
            'main' => [
                'type' => 'rotating_file',
                'path' => '%kernel.logs_dir%/%kernel.environment%.log',
                'level' => 'debug',
                'max_files' => 5,
                'channels' => [
                    '!event',
                ],
            ],
            'console' => [
                'type' => 'console',
                'process_psr_3_messages' => false,
                'channels' => [
                    '!event',
                    '!doctrine',
                    '!console',
                ],
            ],
        ],
    ]);
};
