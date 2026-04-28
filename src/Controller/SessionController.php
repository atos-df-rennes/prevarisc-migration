<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\ZendSessionBridge;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SessionController extends AbstractController
{
    /**
     * @var AuthenticationUtils
     */
    private $authenticationUtils;

    /**
     * @var ZendSessionBridge
     */
    private $zendSessionBridge;

    public function __construct(AuthenticationUtils $authenticationUtils, ZendSessionBridge $zendSessionBridge)
    {
        $this->authenticationUtils = $authenticationUtils;
        $this->zendSessionBridge = $zendSessionBridge;
    }

    /**
     * Page de connexion Symfony.
     *
     * GET  : affiche le formulaire (erreurs et dernier username depuis AuthenticationUtils).
     * POST : délégué à FormLoginAuthenticator — le controller n'est pas rappelé.
     *
     * @Route("/session/login", name="session_login", methods={"GET", "POST"})
     */
    public function login(): Response
    {
        $error = $this->authenticationUtils->getLastAuthenticationError();
        $lastUsername = $this->authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'sso_enabled' => $this->getParameter('app.cas_enabled') || $this->getParameter('app.ntlm_enabled'),
        ]);
    }

    /**
     * Déconnexion de l'utilisateur.
     *
     * Efface la session Zend_Auth, supprime le username Symfony de la session,
     * effectue un logout CAS si activé, puis redirige vers la page de login.
     *
     * Note : Symfony invalide automatiquement le token de sécurité via le
     * firewall (logout.path = session_logout dans security.php). Cette action
     * est exécutée APRÈS la gestion du firewall (session invalidée) pour
     * le cleanup complémentaire (phpCAS logout).
     *
     * @Route("/session/logout", name="session_logout", methods={"GET", "POST"})
     *
     * @param Request $request La requête HTTP
     */
    public function logout(Request $request): Response
    {
        // 1. Effacer la session Zend_Auth
        $this->zendSessionBridge->clearAuthentication();

        // 2. Effacer le username stocké pour LegacySessionAuthenticator
        $request->getSession()->remove('username');

        // 3. Logout CAS si activé (redirection vers le serveur CAS)
        if ($this->getParameter('app.cas_enabled')) {
            \phpCAS::logout();
        }

        // 4. Redirection vers la page de login
        return $this->redirectToRoute('session_login');
    }
}
