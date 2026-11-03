<?php

declare(strict_types=1);

use App\Doctrine\DossierSupprimeFilter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('doctrine', [
        'dbal' => [
            'url' => '%env(DATABASE_URL)%',
            'use_savepoints' => true,
            'mapping_types' => [
                'enum' => 'string',
            ],
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
            // Note : true déclenche une dépréciation Symfony 7.3 (LazyGhostTrait remplacé par les "native lazy
            // objects" PHP 8.4) ; false déclenche à son tour une dépréciation Doctrine ORM 2.20 ("not enabling
            // lazy ghost objects will not be supported in ORM 3.0"). Conflit amont ORM 2.x / Symfony 7.3+
            // (doctrine/orm#10837), non résolvable sans upgrade Doctrine ORM 3.x — hors périmètre de cette
            // migration. On garde `true`, la valeur recommandée par Doctrine pour la compatibilité ORM 3.0.
            'enable_lazy_ghost_objects' => true,
            'filters' => [
                'dossier_non_supprime' => [
                    'class' => DossierSupprimeFilter::class,
                    'enabled' => false,
                ],
            ]
        ],
    ]);
};
