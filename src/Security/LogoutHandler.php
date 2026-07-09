<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Utilisateur;
use App\Service\LastActionUpdater;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

/**
 * Gestionnaire de déconnexion Symfony (Symfony 4.4, Guard).
 *
 * Appelé par le LogoutListener du firewall AVANT l'invalidation de la session PHP.
 * Effectue le nettoyage complémentaire : username en session et logout CAS si le SSO CAS est actif.
 *
 * La mise à jour de LASTACTION_UTILISATEUR est effectuée ici (et non uniquement dans
 * LastActionSubscriber) car kernel.terminate se déclenche APRÈS l'invalidation du token,
 * rendant $security->getUser() null au moment du logout.
 *
 * Enregistré via security.firewalls.main.logout.handlers dans security.php.
 */
class LogoutHandler implements LogoutHandlerInterface
{
    /** @var LastActionUpdater */
    private $lastActionUpdater;

    /** @var bool */
    private $casEnabled;

    public function __construct(
        LastActionUpdater $lastActionUpdater,
        bool $casEnabled
    ) {
        $this->lastActionUpdater = $lastActionUpdater;
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
        // Mettre à jour la dernière action avant l'invalidation du token.
        // $token->getUser() est encore disponible à ce stade, contrairement à
        // kernel.terminate où le token a déjà été invalidé.
        $utilisateur = $token->getUser();
        if ($utilisateur instanceof Utilisateur) {
            $this->lastActionUpdater->update($utilisateur);
        }

        // 1. Effacer le username stocké en session
        $request->getSession()->remove('username');

        // 2. Nettoyer la session phpCAS si activé.
        // phpCAS::client() n'est pas encore initialisé dans ce contexte (nouveau process mod_php),
        // donc on nettoie directement la clé de session phpCAS sans passer par l'API statique.
        if ($this->casEnabled && isset($_SESSION['phpCAS'])) {
            unset($_SESSION['phpCAS']);
        }
    }
}
