<?php

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/config/bootstrap.php';

/*
 * The kernel will always be available globally, allowing you to
 * access it from your existing application and through it the
 * service container. This allows for introducing new features in
 * the existing application.
 */
global $kernel;

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);

// Strangler Pattern : Fallback vers legacy si route Symfony pas trouvée
// Note : On vérifie le header X-Symfony-Route-Found pour distinguer :
// - Route pas trouvée dans Symfony (pas de header) → fallback legacy
// - Route trouvée mais 404 métier (header présent) → retourner la 404 Symfony
$isSymfonyRouteFound = $response->headers->has('X-Symfony-Route-Found');

if (!$isSymfonyRouteFound && true === $response->isNotFound()) {
    $prevariscPath = $_SERVER['PREVARISC_PATH'] ?? $_ENV['PREVARISC_PATH'] ?? '/home/prv/current/prevarisc';

    require realpath($prevariscPath.'/public/index.php');
} else {
    // Supprimer le header technique avant d'envoyer la réponse
    $response->headers->remove('X-Symfony-Route-Found');
    $response->send();
}

$kernel->terminate($request, $response);
