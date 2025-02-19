<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class LegacySessionAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     * On n'hydrate pas l'utilisateur via Symfony s'il n'est pas connecté via Zend.
     */
    public function supports(Request $request): bool
    {
        return isset($_SESSION['Zend_Auth']) ? true : false;
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     * A ce stade il y a forcément une valeur dans la session suite à la vérification de la méthode supports().
     */
    public function getCredentials(Request $request)
    {
        return $_SESSION['Zend_Auth']['storage']['USERNAME_UTILISATEUR'];
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        return $userProvider->loadUserByUsername($credentials);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $user->getUsername() === $credentials;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        // on success, let the request continue
        return null;
    }

    /**
     * L'authentification est gérée via Zend.
     * On ne devrait donc jamais appeler cette méthode.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * Redirige vers le formulaire de login Zend.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse('/session/login');
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}