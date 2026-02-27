<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ajoute un header HTTP pour indiquer qu'une route Symfony a été trouvée.
 * Permet au Strangler Pattern de distinguer :
 * - 404 "route Symfony pas trouvée" → fallback vers legacy
 * - 404 "route Symfony trouvée mais ressource inexistante" → pas de fallback.
 */
final class SymfonyRouteFoundSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 10],
        ];
    }

    /**
     * Ajoute le header X-Symfony-Route-Found sur toutes les réponses.
     * Ce header indique que Symfony a trouvé une route (même si elle retourne 404/500).
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // Vérifier si une route a été trouvée
        // En Symfony 4.4, si une route est trouvée, l'attribut _route existe
        $routeName = $request->attributes->get('_route');

        if (null === $routeName) {
            // Pas de route trouvée → ne pas ajouter le header (fallback legacy)
            return;
        }

        // Route Symfony trouvée → ajouter le header
        $response->headers->set('X-Symfony-Route-Found', '1');
    }
}
