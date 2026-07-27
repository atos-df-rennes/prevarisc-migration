<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Utilisateur;
use App\Service\LastActionUpdater;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Gestionnaire de déconnexion Symfony.
 *
 * Appelé par le LogoutListener du firewall AVANT l'invalidation de la session PHP.
 * Effectue le nettoyage complémentaire : logout CAS si le SSO CAS est actif.
 *
 * La mise à jour de LASTACTION_UTILISATEUR est effectuée ici (et non uniquement dans
 * LastActionSubscriber) car kernel.terminate se déclenche APRÈS l'invalidation du token,
 * rendant $security->getUser() null au moment du logout.
 *
 * Enregistré automatiquement comme subscriber du LogoutEvent.
 */
class LogoutHandler implements EventSubscriberInterface
{
    public function __construct(private LastActionUpdater $lastActionUpdater, private bool $casEnabled)
    {
    }

    public function onLogout(LogoutEvent $logoutEvent): void
    {
        $token = $logoutEvent->getToken();
        if (null !== $token) {
            // Mettre à jour la dernière action avant l'invalidation du token.
            // $token->getUser() est encore disponible à ce stade, contrairement à
            // kernel.terminate où le token a déjà été invalidé.
            $utilisateur = $token->getUser();
            if ($utilisateur instanceof Utilisateur) {
                $this->lastActionUpdater->update($utilisateur);
            }
        }

        // Nettoyer la session phpCAS si activé.
        // phpCAS::client() n'est pas encore initialisé dans ce contexte (nouveau process mod_php),
        // donc on nettoie directement la clé de session phpCAS sans passer par l'API statique.
        if ($this->casEnabled && isset($_SESSION['phpCAS'])) {
            unset($_SESSION['phpCAS']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }
}
