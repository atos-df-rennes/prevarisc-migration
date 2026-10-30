<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use App\Entity\Utilisateur;
use App\Service\LastActionUpdater;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Met à jour la date de dernière action de l'utilisateur connecté.
 *
 * Note : kernel.terminate s'exécute APRÈS l'envoi de la réponse HTTP. Lors d'un
 * logout, le token Symfony est invalidé avant cet événement ; c'est pourquoi la
 * mise à jour au logout est prise en charge directement par LogoutHandler (via
 * $token->getUser(), encore disponible à ce stade).
 */
class LastActionSubscriber
{
    public function __construct(private readonly Security $security, private readonly LastActionUpdater $lastActionUpdater)
    {
    }

    #[AsEventListener(event: 'kernel.terminate')]
    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $utilisateur = $this->security->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return;
        }

        $this->lastActionUpdater->update($utilisateur);
    }
}
