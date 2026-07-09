<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('security', [
        'encoders' => [
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
                'anonymous' => 'lazy',
                'provider'  => 'app_user_provider',
                'guard'     => [
                    'authenticators' => [
                        'App\Security\FormLoginAuthenticator',
                    ],
                    'entry_point' => 'App\Security\FormLoginAuthenticator',
                ],
                // Logout géré par le firewall : invalide le token Symfony et la session PHP.
                // Le nettoyage complémentaire (username, phpCAS) est délégué à LogoutHandler.
                // CSRF non requis pour permettre la déconnexion via lien GET (pages legacy non encore migrées).
                'logout' => [
                    'path'            => 'session_logout',
                    'success_handler' => 'App\Security\CasLogoutSuccessHandler',
                    'handlers'        => ['App\Security\LogoutHandler'],
                ],
            ],
        ],
        'access_control' => [
            [
                'path'  => '^/session/login',
                'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
            ],
            [
                'path'  => '^/',
                'roles' => 'ROLE_USER',
            ],
        ],
    ]);
};
