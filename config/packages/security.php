<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('security', [
        'enable_authenticator_manager' => true,
        'password_hashers' => [
            'App\Entity\Utilisateur' => [
                'algorithm'    => 'auto',
                'migrate_from' => ['legacy_md5_prevarisc'],
            ],
            'legacy_md5_prevarisc' => [
                'id' => 'App\Security\Md5SaltedEncoder',
            ],
        ],
        'providers' => [
            'app_user_provider' => [
                'entity' => [
                    'class'    => 'App\Entity\Utilisateur',
                    'property' => 'username',
                ],
            ],
        ],
        'firewalls' => [
            'dev' => [
                'pattern'  => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            // Firewall dédié pour l'endpoint iCalendar public (abonnements Webcal).
            // Les clients calendrier (Outlook, Thunderbird, Apple Calendar) ne gèrent pas
            // les redirections CAS interactives. On bypass totalement la sécurité sur ce path.
            'ical' => [
                'pattern'  => '^/api/commissions/icalendar',
                'security' => false,
            ],
            'main' => [
                'lazy' => true,
                'provider'  => 'app_user_provider',
                'custom_authenticators' => [
                    'App\Security\FormLoginAuthenticator',
                ],
                'entry_point' => 'App\Security\FormLoginAuthenticator',
                'logout' => [
                    'path' => 'session_logout',
                ],
            ],
        ],
        'access_control' => [
            [
                'path'  => '^/session/login',
                'roles' => 'PUBLIC_ACCESS',
            ],
            [
                'path'  => '^/',
                'roles' => 'ROLE_USER',
            ],
        ],
    ]);
};
