<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('monolog', [
        'handlers' => [
            'authentication' => [
                'type' => 'rotating_file',
                'path' => '%kernel.logs_dir%/authentication.log',
                'level' => 'notice',
                'max_files' => 30,
                'channels' => ['authentication'],
            ],
            'main' => [
                'type' => 'fingers_crossed',
                'action_level' => 'error',
                'handler' => 'nested',
                'excluded_http_codes' => [
                    404,
                    405,
                ],
                'buffer_size' => 50,
            ],
            'nested' => [
                'type' => 'rotating_file',
                'path' => '%kernel.logs_dir%/%kernel.environment%.log',
                'level' => 'debug',
                'max_files' => 30,
            ],
            'datastore' => [
                'type' => 'rotating_file',
                'path' => '%kernel.logs_dir%/datastore.log',
                'level' => 'warning',
                'max_files' => 5,
                'channels' => [
                    'datastore',
                ],
            ],
            'rapport' => [
                'type' => 'rotating_file',
                'path' => '%kernel.logs_dir%/rapport.log',
                'level' => 'debug',
                'max_files' => 1,
                'channels' => [
                    'rapport',
                ],
            ],
            'console' => [
                'type' => 'console',
                'process_psr_3_messages' => false,
                'channels' => [
                    '!event',
                    '!doctrine',
                ],
            ],
        ],
        'channels' => [
            'datastore',
            'rapport',
        ]
    ]);
};
