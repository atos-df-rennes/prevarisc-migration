<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Gestionnaire de succès de déconnexion pour CAS.
 *
 * Redirige vers le serveur CAS pour invalider le TGT quand CAS est activé,
 * sinon redirige vers /session/login (géré par Symfony ou Zend selon PREVARISC_SYMFONY_LOGIN).
 */
class CasLogoutSuccessHandler
{
    public function __construct(private readonly bool $casEnabled, private readonly string $casHost, private readonly int $casPort, private readonly string $casPublicHost, private readonly int $casPublicPort, private readonly string $casContext, private readonly bool $casNoSsl)
    {
    }

    #[AsEventListener(event: LogoutEvent::class, priority: 64)]
    public function onLogout(LogoutEvent $logoutEvent): void
    {
        if ($logoutEvent->getResponse() instanceof Response) {
            return;
        }

        if ($this->casEnabled) {
            $scheme = $this->casNoSsl ? 'http' : 'https';
            $context = ltrim($this->casContext, '/');

            // Utiliser le hostname public si défini (reverse proxy, Docker) pour que
            // le navigateur soit redirigé vers une adresse qu'il peut atteindre.
            $host = !empty($this->casPublicHost) ? $this->casPublicHost : $this->casHost;
            $port = (!empty($this->casPublicHost) && $this->casPublicPort > 0) ? $this->casPublicPort : $this->casPort;
            $casLogoutUrl = sprintf('%s://%s:%d/%s/logout', $scheme, $host, $port, $context);

            $logoutEvent->setResponse(new RedirectResponse($casLogoutUrl));

            return;
        }

        $logoutEvent->setResponse(new RedirectResponse('/session/login'));
    }
}
