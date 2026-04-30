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
            'main' => [
                'anonymous' => 'lazy',
                'provider'  => 'app_user_provider',
                'guard'     => [
                    'authenticators' => [
                        'App\Security\FormLoginAuthenticator',
                        'App\Security\LegacySessionAuthenticator',
                    ],
                    'entry_point' => 'App\Security\FormLoginAuthenticator',
                ],
                // Logout géré par le firewall : invalide le token Symfony et la session PHP.
                // Le nettoyage complémentaire (Zend_Auth, username, phpCAS) est délégué à LogoutHandler.
                'logout' => [
                    'path'                 => 'session_logout',
                    'success_handler'      => 'App\Security\CasLogoutSuccessHandler',
                    'csrf_token_generator' => 'security.csrf.token_manager',
                    'handlers'             => ['App\Security\LogoutHandler'],
                ],
            ],
        ],
        'access_control' => [
            [
                'path'  => '^/session/login',
                'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
            ],
            [
                'path'  => '^/api/commissions/icalendar',
                'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY',
            ],
            [
                'path'  => '^/',
                'roles' => 'ROLE_USER',
            ],
        ],
    ]);
};
