<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Authentificateur Guard Symfony basé sur la session Zend héritée.
 *
 * Délègue l'accès à $_SESSION['Zend_Auth'] au ZendSessionBridge injectable.
 *
 * @todo SF 5.1+: migrer vers AbstractAuthenticator (Guard supprimé en SF 6.0).
 *   La logique de credentials est déjà isolée dans ZendSessionBridge pour faciliter cette migration.
 */
class LegacySessionAuthenticator extends AbstractGuardAuthenticator
{
    use TargetPathTrait;

    /** @var LoggerInterface */
    private $logger;

    /** @var ZendSessionBridge */
    private $zendSessionBridge;

    /** @var bool */
    private $symfonyLoginEnabled;

    public function __construct(LoggerInterface $logger, ZendSessionBridge $zendSessionBridge, bool $symfonyLoginEnabled)
    {
        $this->logger = $logger;
        $this->zendSessionBridge = $zendSessionBridge;
        $this->symfonyLoginEnabled = $symfonyLoginEnabled;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     *
     * On a pas accès à la variable $_SESSION ici.
     * Pour contourner, on laisse passer et on fait la vérification dans la méthode getCredentials().
     * Si l'utilisateur n'est pas connecté on throw une AuthenticationException et on redirige dans la méthode onAuthenticationFailure().
     *
     * Exclut /session/login uniquement si PREVARISC_SYMFONY_LOGIN=1 pour éviter une boucle
     * de redirection infinie quand le formulaire de login natif Symfony est actif.
     * Avec PREVARISC_SYMFONY_LOGIN=0, la route /session/login est gérée par le legacy Zend.
     */
    public function supports(Request $request): bool
    {
        // Exclure /session/login uniquement quand le login Symfony natif est actif :
        // sinon cela provoquerait une boucle infinie (plus aucun authenticator ne traiterait le POST).
        if ($this->symfonyLoginEnabled && '/session/login' === $request->getPathInfo()) {
            return false;
        }

        return null === $request->getSession()->get('username');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        $username = $this->zendSessionBridge->getAuthenticatedUsername();

        if (null === $username) {
            $this->logger->notice("L'utilisateur n'est pas connecté");

            throw new AuthenticationException();
        }

        $request->getSession()->set('username', $username);

        return $username;
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        if (!is_string($credentials)) {
            $this->logger->error("Une chaîne contenant le nom d'utilisateur est attendue comme identifiant.");

            throw new AuthenticationException();
        }

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
     * Si l'authentification ne fonctionne pas, c'est que l'utilisateur n'est pas connecté.
     * On le redirige vers le formulaire de login Zend.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse('/session/login');
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * Redirige vers le formulaire de login Zend.
     * Non appelé pour le moment, la redirection est fait par la méthode onAuthenticationFailure().
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse('/session/login');
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
