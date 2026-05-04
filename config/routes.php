<?php

// config/routes.php
//
// Route session_login : conditionnelle selon PREVARISC_SYMFONY_LOGIN.
// Quand désactivé (=0), /session/login est géré par le legacy Zend (Strangler Pattern fallback).
// Nécessite un `cache:clear` lors d'un changement de cette variable d'environnement.
//
// Route session_logout : toujours enregistrée — le firewall Symfony doit intercepter la
// déconnexion même quand le login natif est désactivé (CAS, NTLM, LegacySessionAuthenticator).

declare(strict_types=1);

use App\Controller\SessionController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    if ('1' === ($_ENV['PREVARISC_SYMFONY_LOGIN'] ?? '0')) {
        $routes->add('session_login', '/session/login')
            ->controller([SessionController::class, 'login'])
            ->methods(['GET', 'POST']);
    }

    $routes->add('session_logout', '/session/logout')
        ->controller([SessionController::class, 'logout'])
        ->methods(['GET', 'POST']);
};