<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Gestionnaire de succès de déconnexion pour CAS.
 *
 * Redirige vers le serveur CAS pour invalider le TGT quand CAS est activé,
 * sinon redirige vers /session/login (géré par Symfony ou Zend selon PREVARISC_SYMFONY_LOGIN).
 */
final readonly class CasLogoutSuccessHandler implements EventSubscriberInterface
{
    public function __construct(private bool $casEnabled, private string $casHost, private int $casPort, private string $casPublicHost, private int $casPublicPort, private string $casContext, private bool $casNoSsl)
    {
    }

    public function onLogout(LogoutEvent $logoutEvent): void
    {
        if (null !== $logoutEvent->getResponse()) {
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

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => ['onLogout', 64]];
    }
}
