<?php

declare(strict_types=1);

use App\Entity\Utilisateur;
use App\Security\FormLoginAuthenticator;
use App\Security\Md5SaltedEncoder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('security', [
        'password_hashers' => [
            Utilisateur::class => [
                'algorithm' => 'auto',
                'migrate_from' => ['legacy_md5_prevarisc'],
            ],
            // @todo: à retirer (ainsi que le migrate_from) une fois les mots de passe réhashés
            'legacy_md5_prevarisc' => [
                'id' => Md5SaltedEncoder::class,
            ],
        ],
        'providers' => [
            'app_user_provider' => [
                'entity' => [
                    'class' => Utilisateur::class,
                    'property' => 'username',
                ],
            ],
        ],
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            // Firewall dédié pour l'endpoint iCalendar public (abonnements Webcal).
            // Les clients calendrier (Outlook, Thunderbird, Apple Calendar) ne gèrent pas
            // les redirections CAS interactives. On bypass totalement la sécurité sur ce path.
            'ical' => [
                'pattern' => '^/api/commissions/icalendar',
                'security' => false,
            ],
            'main' => [
                'lazy' => true,
                'provider' => 'app_user_provider',
                'custom_authenticators' => [
                    FormLoginAuthenticator::class,
                ],
                'entry_point' => FormLoginAuthenticator::class,
                'logout' => [
                    'path' => 'session_logout',
                ],
            ],
        ],
        'access_control' => [
            [
                'path' => '^/session/login',
                'roles' => 'PUBLIC_ACCESS',
            ],
            [
                'path' => '^/',
                'roles' => 'ROLE_USER',
            ],
        ],
    ]);
};
