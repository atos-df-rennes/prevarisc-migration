<?php

declare(strict_types=1);

use App\Platau\ConsultationRetryStrategy;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'http_client' => [
            'scoped_clients' => [
                'syncplicity.piste_client' => [
                    'base_uri' => '%env(SYNCPLICITY_PISTE_ACCESS_TOKEN_URL)%',
                    'timeout' => 30,
                    'max_redirects' => 0,
                ],
                'piste.client' => [
                    'base_uri' => '%env(PISTE_ACCESS_TOKEN_URL)%',
                    'timeout' => 30,
                    'max_redirects' => 5,
                ],
                'platau.client' => [
                    'base_uri' => '%env(PLATAU_URL)%',
                    'timeout' => 30,
                    'max_duration' => 30,
                    'max_redirects' => 5,
                    'retry_failed' => [
                        'retry_strategy' => ConsultationRetryStrategy::class,
                        'max_retries' => ConsultationRetryStrategy::MAX_RETRIES,
                    ],
                ],
            ],
        ],
        'cache' => [
            'pools' => [
                'cache.syncplicity_tokens' => [
                    'adapter' => 'test' === $container->env() ? 'cache.adapter.array' : 'cache.adapter.filesystem',
                ],
                'cache.platau_consultation_tokens' => [
                    'adapter' => 'test' === $container->env() ? 'cache.adapter.array' : 'cache.adapter.filesystem',
                ],
            ],
        ],
    ]);
};
