<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('security', [
        'encoders' => [
            'App\Entity\Utilisateur' => [
                'algorithm' => 'auto',
            ],
        ],
        'providers' => [
            'app_user_provider' => [
                'entity' => [
                    'class' => 'App\Entity\Utilisateur',
                    'property' => 'username',
                ],
            ],
        ],
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            'main' => [
                'anonymous' => 'lazy',
                'provider' => 'app_user_provider',
                'guard' => [
                    'authenticators' => [
                        'App\Security\LegacySessionAuthenticator',
                    ],
                    'entry_point' => 'App\Security\LegacySessionAuthenticator',
                ],
            ],
        ],
        'access_control' => [
            [
                'path' => '^/session/login',
                'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
            ],
            [
                'path' => '^/api/commissions/icalendar',
                'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
            ],
            [
                'path' => '^/',
                'roles' => 'ROLE_USER',
            ],
        ],
    ]);
};
