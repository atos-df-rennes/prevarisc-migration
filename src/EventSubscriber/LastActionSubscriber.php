<?php

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use App\Service\LastActionUpdater;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Security\Core\Security;

/**
 * Met à jour la date de dernière action de l'utilisateur connecté.
 *
 * Note : kernel.terminate s'exécute APRÈS l'envoi de la réponse HTTP. Lors d'un
 * logout, le token Symfony est invalidé avant cet événement ; c'est pourquoi la
 * mise à jour au logout est prise en charge directement par LogoutHandler (via
 * $token->getUser(), encore disponible à ce stade).
 */
class LastActionSubscriber implements EventSubscriberInterface
{
    /** @var Security */
    private $security;

    /** @var LastActionUpdater */
    private $lastActionUpdater;

    public function __construct(Security $security, LastActionUpdater $lastActionUpdater)
    {
        $this->security = $security;
        $this->lastActionUpdater = $lastActionUpdater;
    }

    /**
     * @todo SF 5.3: remplacer isMasterRequest() par isMainRequest() (déprécié en 5.3, supprimé en 7.0)
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $utilisateur = $this->security->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return;
        }

        $this->lastActionUpdater->update($utilisateur);
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.terminate' => 'onKernelTerminate',
        ];
    }
}
