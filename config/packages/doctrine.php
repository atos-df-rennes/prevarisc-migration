<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('doctrine', [
        'dbal' => [
            'url' => '%env(DATABASE_URL)%',
            'use_savepoints' => true,
        ],
        'orm' => [
            'auto_generate_proxy_classes' => true,
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
            'auto_mapping' => true,
            'report_fields_where_declared' => true,
            'mappings' => [
                'App' => [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/src/Entity',
                    'prefix' => 'App\Entity',
                    'alias' => 'App',
                ],
            ],
            'enable_lazy_ghost_objects' => true,
            'filters' => [
                'dossier_non_supprime' => [
                    'class' => \App\Doctrine\DossierSupprimeFilter::class,
                    'enabled' => false,
                ],
            ]
        ],
    ]);
};
