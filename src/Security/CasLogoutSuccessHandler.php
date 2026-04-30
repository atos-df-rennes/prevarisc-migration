<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Gestionnaire de succès de déconnexion pour CAS.
 *
 * Redirige vers le serveur CAS pour invalider le TGT quand CAS est activé,
 * sinon redirige vers la page de login standard.
 */
class CasLogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var bool */
    private $casEnabled;

    /** @var string */
    private $casHost;

    /** @var int */
    private $casPort;

    /** @var string */
    private $casContext;

    /** @var bool */
    private $casNoSsl;

    public function __construct(
        RouterInterface $router,
        bool $casEnabled,
        string $casHost,
        int $casPort,
        string $casContext,
        bool $casNoSsl
    ) {
        $this->router = $router;
        $this->casEnabled = $casEnabled;
        $this->casHost = $casHost;
        $this->casPort = $casPort;
        $this->casContext = $casContext;
        $this->casNoSsl = $casNoSsl;
    }

    public function onLogoutSuccess(Request $request): Response
    {
        if ($this->casEnabled) {
            $scheme = $this->casNoSsl ? 'http' : 'https';
            $context = ltrim($this->casContext, '/');
            $casLogoutUrl = sprintf('%s://%s:%d/%s/logout', $scheme, $this->casHost, $this->casPort, $context);

            return new RedirectResponse($casLogoutUrl);
        }

        return new RedirectResponse($this->router->generate('session_login'));
    }
}
