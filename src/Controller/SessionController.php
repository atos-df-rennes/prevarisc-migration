<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SessionController extends AbstractController
{
    /**
     * @var AuthenticationUtils
     */
    private $authenticationUtils;

    public function __construct(AuthenticationUtils $authenticationUtils)
    {
        $this->authenticationUtils = $authenticationUtils;
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
     * Point de terminaison de déconnexion.
     *
     * Cette action n'est JAMAIS exécutée directement : le firewall Symfony intercepte
     * la route `session_logout` via LogoutListener avant d'atteindre ce contrôleur.
     * Le nettoyage complémentaire (Zend_Auth, session username, phpCAS) est
     * délégué à App\Security\LogoutHandler (enregistré dans security.php).
     *
     * @Route("/session/logout", name="session_logout", methods={"POST"})
     */
    public function logout(): void
    {
        throw new \RuntimeException('La déconnexion est gérée par le firewall Symfony (LogoutListener). Ce code ne doit jamais être atteint.');
    }
}
