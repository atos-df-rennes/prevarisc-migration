<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

/**
 * Gestionnaire de déconnexion Symfony (Symfony 4.4, Guard).
 *
 * Appelé par le LogoutListener du firewall AVANT l'invalidation de la session PHP.
 * Effectue le nettoyage complémentaire : session Zend_Auth, username en session,
 * et logout CAS si le SSO CAS est actif.
 *
 * Enregistré via security.firewalls.main.logout.handlers dans security.php.
 */
class LogoutHandler implements LogoutHandlerInterface
{
    /** @var ZendSessionBridge */
    private $zendSessionBridge;

    /** @var bool */
    private $casEnabled;

    public function __construct(ZendSessionBridge $zendSessionBridge, bool $casEnabled)
    {
        $this->zendSessionBridge = $zendSessionBridge;
        $this->casEnabled = $casEnabled;
    }

    /**
     * Nettoyage à la déconnexion.
     *
     * @param Request        $request  La requête HTTP
     * @param Response       $response La réponse HTTP (modifiable, ex. redirect vers CAS)
     * @param TokenInterface $token    Le token Symfony en cours d'invalidation
     */
    public function logout(Request $request, Response $response, TokenInterface $token): void
    {
        // 1. Effacer la session Zend_Auth (compatibilité controllers legacy)
        $this->zendSessionBridge->clearAuthentication();

        // 2. Effacer le username stocké pour LegacySessionAuthenticator
        $request->getSession()->remove('username');

        // 3. Nettoyer la session phpCAS si activé.
        // phpCAS::client() n'est pas encore initialisé dans ce contexte (nouveau process mod_php),
        // donc on nettoie directement la clé de session phpCAS sans passer par l'API statique.
        if ($this->casEnabled && isset($_SESSION['phpCAS'])) {
            unset($_SESSION['phpCAS']);
        }
    }
}
