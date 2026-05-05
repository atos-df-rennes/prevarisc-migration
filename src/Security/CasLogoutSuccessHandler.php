<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Gestionnaire de succès de déconnexion pour CAS.
 *
 * Redirige vers le serveur CAS pour invalider le TGT quand CAS est activé,
 * sinon redirige vers /session/login (géré par Symfony ou Zend selon PREVARISC_SYMFONY_LOGIN).
 */
class CasLogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /** @var bool */
    private $casEnabled;

    /** @var string */
    private $casHost;

    /** @var int */
    private $casPort;

    /** @var string */
    private $casPublicHost;

    /** @var int */
    private $casPublicPort;

    /** @var string */
    private $casContext;

    /** @var bool */
    private $casNoSsl;

    public function __construct(
        bool $casEnabled,
        string $casHost,
        int $casPort,
        string $casPublicHost,
        int $casPublicPort,
        string $casContext,
        bool $casNoSsl
    ) {
        $this->casEnabled = $casEnabled;
        $this->casHost = $casHost;
        $this->casPort = $casPort;
        $this->casPublicHost = $casPublicHost;
        $this->casPublicPort = $casPublicPort;
        $this->casContext = $casContext;
        $this->casNoSsl = $casNoSsl;
    }

    public function onLogoutSuccess(Request $request): Response
    {
        if ($this->casEnabled) {
            $scheme = $this->casNoSsl ? 'http' : 'https';
            $context = ltrim($this->casContext, '/');

            // Utiliser le hostname public si défini (reverse proxy, Docker) pour que
            // le navigateur soit redirigé vers une adresse qu'il peut atteindre.
            $host = !empty($this->casPublicHost) ? $this->casPublicHost : $this->casHost;
            $port = (!empty($this->casPublicHost) && $this->casPublicPort > 0) ? $this->casPublicPort : $this->casPort;
            $casLogoutUrl = sprintf('%s://%s:%d/%s/logout', $scheme, $host, $port, $context);

            return new RedirectResponse($casLogoutUrl);
        }

        return new RedirectResponse('/session/login');
    }
}
