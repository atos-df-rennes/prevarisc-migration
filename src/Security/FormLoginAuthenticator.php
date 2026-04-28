<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Authentificateur Symfony natif pour Prévarisc.
 *
 * Gère quatre mécanismes d'authentification dans l'ordre de priorité du legacy :
 *   1. CAS  (phpCAS) — si PREVARISC_CAS_ENABLED=1
 *   2. NTLM ($_SERVER['REMOTE_USER']) — si PREVARISC_NTLM_ENABLED=1
 *   3. LDAP (Symfony\Component\Ldap) — si PREVARISC_LDAP_ENABLED=1 (fallback du dbtable)
 *   4. dbtable MD5 → bcrypt via migrate_from — cas par défaut
 *
 * Activé conditionnellement via PREVARISC_SYMFONY_LOGIN=1.
 * Propage la session Zend_Auth pour compatibilité avec les contrôleurs legacy.
 *
 * @todo SF 5.1+: migrer vers AbstractAuthenticator (Guard supprimé en SF 6.0).
 */
class FormLoginAuthenticator extends AbstractGuardAuthenticator
{
    public const MAX_LOGIN_ATTEMPTS = 2;

    /** @var LoggerInterface */
    private $logger;

    /** @var ZendSessionBridge */
    private $zendSessionBridge;

    /** @var ZendIdentityBuilder */
    private $zendIdentityBuilder;

    /** @var UserPasswordEncoderInterface */
    private $passwordEncoder;

    /** @var RouterInterface */
    private $router;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var bool */
    private $symfonyLoginEnabled;

    /** @var bool */
    private $casEnabled;

    /** @var string */
    private $casHost;

    /** @var int */
    private $casPort;

    /** @var string */
    private $casContext;

    /** @var bool */
    private $casNoServerValidation;

    /** @var string */
    private $casVersion;

    /** @var bool */
    private $ntlmEnabled;

    /** @var bool */
    private $ldapEnabled;

    /** @var string */
    private $ldapHost;

    /** @var int */
    private $ldapPort;

    /** @var string */
    private $ldapUsername;

    /** @var string */
    private $ldapPassword;

    /** @var string */
    private $ldapBaseDn;

    /** @var bool */
    private $enforceSecurity;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

    /**
     * Dernier utilisateur tentant une authentification (pour onAuthenticationFailure).
     *
     * @var Utilisateur|null
     */
    private $lastAttemptedUser;

    /**
     * Indicateur d'initialisation phpCAS (static car phpCAS est une classe statique).
     *
     * @var bool
     */
    private static $casInitialized = false;

    public function __construct(
        LoggerInterface $logger,
        ZendSessionBridge $zendSessionBridge,
        ZendIdentityBuilder $zendIdentityBuilder,
        UserPasswordEncoderInterface $passwordEncoder,
        RouterInterface $router,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        CsrfTokenManagerInterface $csrfTokenManager,
        bool $symfonyLoginEnabled,
        bool $casEnabled,
        string $casHost,
        int $casPort,
        string $casContext,
        bool $casNoServerValidation,
        string $casVersion,
        bool $ntlmEnabled,
        bool $ldapEnabled,
        string $ldapHost,
        int $ldapPort,
        string $ldapUsername,
        string $ldapPassword,
        string $ldapBaseDn,
        bool $enforceSecurity
    ) {
        $this->logger = $logger;
        $this->zendSessionBridge = $zendSessionBridge;
        $this->zendIdentityBuilder = $zendIdentityBuilder;
        $this->passwordEncoder = $passwordEncoder;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->symfonyLoginEnabled = $symfonyLoginEnabled;
        $this->casEnabled = $casEnabled;
        $this->casHost = $casHost;
        $this->casPort = $casPort;
        $this->casContext = $casContext;
        $this->casNoServerValidation = $casNoServerValidation;
        $this->casVersion = $casVersion;
        $this->ntlmEnabled = $ntlmEnabled;
        $this->ldapEnabled = $ldapEnabled;
        $this->ldapHost = $ldapHost;
        $this->ldapPort = $ldapPort;
        $this->ldapUsername = $ldapUsername;
        $this->ldapPassword = $ldapPassword;
        $this->ldapBaseDn = $ldapBaseDn;
        $this->enforceSecurity = $enforceSecurity;
    }

    /**
     * Détermine si cet authentificateur doit prendre en charge la requête.
     *
     * Priorités (miroir du legacy) :
     *   - CAS activé → true sauf si l'utilisateur est déjà authentifié en session
     *   - NTLM activé + REMOTE_USER présent → true
     *   - $symfonyLoginEnabled + POST sur /session/login → true
     *   - Sinon → false (LegacySessionAuthenticator prend le relais)
     */
    public function supports(Request $request): bool
    {
        if ($this->casEnabled) {
            // Ne pas relancer le pipeline d'auth si un token Symfony existe déjà en session
            $token = $this->tokenStorage->getToken();
            if (null !== $token && $token->isAuthenticated()) {
                return false;
            }

            return true;
        }

        if ($this->ntlmEnabled && isset($_SERVER['REMOTE_USER'])) {
            return true;
        }

        return $this->symfonyLoginEnabled
            && $request->isMethod('POST')
            && '/session/login' === $request->getPathInfo();
    }

    /**
     * Extrait les identifiants de la requête selon le mécanisme d'auth actif.
     *
     * @return array<string, mixed>
     */
    public function getCredentials(Request $request)
    {
        // --- CAS ---
        if ($this->casEnabled) {
            $this->initCas();
            \phpCAS::forceAuthentication();

            return [
                'username' => \phpCAS::getUser(),
                'password' => null,
                'sso_type' => 'cas',
            ];
        }

        // --- NTLM ---
        if ($this->ntlmEnabled) {
            $rawRemoteUser = $_SERVER['REMOTE_USER'] ?? null;
            $remoteUser = is_string($rawRemoteUser) ? $rawRemoteUser : '';
            $cred = explode('\\', $remoteUser);

            if (1 === count($cred)) {
                array_unshift($cred, null);
            }

            /** @var array{0: string|null, 1: string} $cred */
            [, $username] = $cred;

            if (empty($username)) {
                $this->logger->error('Auth NTLM: REMOTE_USER vide ou mal formé', ['remote_user' => $remoteUser]);
                throw new AuthenticationException('Authentification NTLM invalide.');
            }

            return [
                'username' => $username,
                'password' => null,
                'sso_type' => 'ntlm',
            ];
        }

        // --- Formulaire standard ---
        $csrfToken = $request->request->get('_csrf_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', is_string($csrfToken) ? $csrfToken : ''))) {
            throw new InvalidCsrfTokenException('Jeton CSRF invalide.');
        }

        $rawUsername = $request->request->get('_username');
        $rawPassword = $request->request->get('_password');

        return [
            'username' => is_string($rawUsername) ? $rawUsername : '',
            'password' => is_string($rawPassword) ? $rawPassword : '',
            'sso_type' => null,
        ];
    }

    /**
     * Charge l'utilisateur depuis le UserProvider et vérifie qu'il est actif.
     *
     * @param array{username: string, password: string|null, sso_type: string|null} $credentials
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $username = $credentials['username'];

        $user = $userProvider->loadUserByUsername($username);

        if (!$user instanceof Utilisateur) {
            $this->logger->error('Auth: type utilisateur inattendu', ['class' => get_class($user)]);
            throw new AuthenticationException('Authentification invalide.');
        }

        if (!$user->getActif()) {
            $this->logger->notice(sprintf("Auth: utilisateur inactif '%s'", $username));
            throw new AuthenticationException('Authentification invalide.');
        }

        $this->lastAttemptedUser = $user;

        return $user;
    }

    /**
     * Vérifie les identifiants selon le mécanisme d'auth actif.
     *
     * Ordre de vérification (port direct du legacy) :
     *   1. SSO (CAS/NTLM) → true immédiat
     *   2. Utilisateur SSO sur formulaire → false (configuration incohérente)
     *   3. Anti-brute-force si ENFORCE_SECURITY + IP différente
     *   4. LDAP si activé (fallback sur dbtable si échec)
     *   5. Vérification dbtable via passwordEncoder (gère migrate_from MD5→bcrypt)
     *
     * @param array{username: string, password: string|null, sso_type: string|null} $credentials
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $user;

        // 1. SSO : on fait confiance à CAS/NTLM
        if (null !== $credentials['sso_type']) {
            return true;
        }

        // 2. Utilisateur SSO essayant le formulaire → configuration incohérente
        if (true === $utilisateur->getSso()) {
            $this->logger->error(sprintf(
                "Auth: utilisateur SSO '%s' tentant une connexion par formulaire (CAS/NTLM non activé)",
                $utilisateur->getUsername()
            ));

            return false;
        }

        // 3. Anti-brute-force (port direct du legacy)
        $remoteAddr = is_string($_SERVER['REMOTE_ADDR'] ?? null) ? $_SERVER['REMOTE_ADDR'] : '';
        if ($this->enforceSecurity
            && null !== $utilisateur->getFailedLoginAttempts()
            && $utilisateur->getFailedLoginAttempts() >= self::MAX_LOGIN_ATTEMPTS
            && null !== $utilisateur->getIp()
            && '' !== $utilisateur->getIp()
            && $utilisateur->getIp() !== $remoteAddr
        ) {
            $this->logger->error(sprintf(
                "Auth: trop d'essais infructueux, denying IP %s which does not match last login IP %s",
                $remoteAddr,
                $utilisateur->getIp()
            ));

            return false;
        }

        // 4. LDAP (si activé) : authentification via bind Symfony\Component\Ldap
        if ($this->ldapEnabled) {
            try {
                $ldap = Ldap::create('ext_ldap', [
                    'connection_string' => sprintf('ldap://%s:%d', $this->ldapHost, $this->ldapPort),
                ]);

                // Tentative de bind en deux étapes si un compte de service est configuré :
                // 1) Bind service account → 2) Recherche DN utilisateur → 3) Bind utilisateur
                if (!empty($this->ldapUsername) && !empty($this->ldapBaseDn)) {
                    $ldap->bind($this->ldapUsername, $this->ldapPassword);
                    $escapedUser = str_replace(['\\', '*', '(', ')', "\0"], ['\\5c', '\\2a', '\\28', '\\29', '\\00'], $credentials['username']);
                    $query = $ldap->query($this->ldapBaseDn, sprintf('(uid=%s)', $escapedUser));
                    /** @var Entry[] $results */
                    $results = $query->execute();

                    if (count($results) > 0) {
                        $entry = $results[0];
                        $ldap->bind($entry->getDn(), (string) $credentials['password']);
                        $this->logger->info(sprintf("Auth LDAP (service-account): succès pour '%s'", $credentials['username']));

                        return true;
                    }

                    $this->logger->notice('Auth LDAP: utilisateur non trouvé dans l\'annuaire, fallback direct bind', [
                        'username' => $credentials['username'],
                    ]);
                }

                // Bind direct (format UPN user@domain ou DN complet)
                $ldap->bind($credentials['username'], (string) $credentials['password']);
                $this->logger->info(sprintf("Auth LDAP (direct): succès pour '%s'", $credentials['username']));

                return true;
            } catch (\Exception $e) {
                $this->logger->notice('Auth LDAP: échec, fallback sur dbtable', [
                    'username' => $credentials['username'],
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        // 5. Vérification dbtable — passwordEncoder gère migrate_from (MD5 → bcrypt) automatiquement
        return $this->passwordEncoder->isPasswordValid($utilisateur, (string) $credentials['password']);
    }

    /**
     * Callback appelé après une authentification réussie.
     *
     * Met à jour l'IP et les compteurs en base, propage vers la session Zend,
     * puis redirige vers la page demandée ou l'accueil.
     *
     * @param Request        $request     La requête HTTP
     * @param TokenInterface $token       Le token de sécurité Symfony
     * @param string         $providerKey La clé du firewall
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $token->getUser();

        // Mettre à jour les données de connexion en base
        $utilisateur->setIp($request->getClientIp());
        $utilisateur->setFailedLoginAttempts(0);

        $this->entityManager->flush();

        // Propager vers la session Zend_Auth (compatibilité controllers legacy)
        $this->zendSessionBridge->writeAuthentication(
            $this->zendIdentityBuilder->build($utilisateur, $request->getClientIp() ?? '')
        );

        // Set session username (compatibilité LegacySessionAuthenticator)
        $request->getSession()->set('username', $utilisateur->getUsername());

        $this->logger->info(sprintf("Auth: connexion réussie pour '%s'", $utilisateur->getUsername()));

        // Redirection post-login : accepter uniquement les chemins relatifs (mitigation open redirect)
        $rawRedirect = $request->query->get('redirect');
        $redirectUrl = $this->router->generate('accueil_index');
        if (is_string($rawRedirect)
            && '' !== $rawRedirect
            && '/' === ($rawRedirect[0] ?? '')
            && '/' !== ($rawRedirect[1] ?? '')
        ) {
            $redirectUrl = $rawRedirect;
        }

        return new RedirectResponse($redirectUrl);
    }

    /**
     * Callback appelé après un échec d'authentification.
     *
     * Incrémente le compteur d'échecs si un utilisateur a été identifié,
     * puis redirige vers la page de login avec un message d'erreur.
     *
     * @param Request                 $request   La requête HTTP
     * @param AuthenticationException $exception L'exception d'authentification
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Incrémenter le compteur d'échecs si l'utilisateur existe
        if (null !== $this->lastAttemptedUser) {
            $attempts = (int) $this->lastAttemptedUser->getFailedLoginAttempts();
            $this->lastAttemptedUser->setFailedLoginAttempts($attempts + 1);
            $this->entityManager->flush();
            $this->logger->notice(sprintf(
                "Auth: échec pour '%s' (tentative %d)",
                $this->lastAttemptedUser->getUsername(),
                $attempts + 1
            ));
        }

        // Flash message d'erreur (lu par le template de login)
        /** @var Session $session */
        $session = $request->getSession();
        $session->getFlashBag()->add('danger', "Erreur d'authentification : identifiants incorrects.");

        return new RedirectResponse($this->router->generate('session_login'));
    }

    /**
     * Point d'entrée appelé quand une authentification est requise mais absente.
     * Redirige vers la page de login avec l'URL courante en paramètre.
     *
     * @param Request                      $request       La requête HTTP
     * @param AuthenticationException|null $authException L'exception éventuelle
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $loginUrl = $this->router->generate('session_login');

        if ('/session/login' !== $request->getPathInfo()) {
            $loginUrl .= '?redirect='.urlencode($request->getRequestUri());
        }

        return new RedirectResponse($loginUrl);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * Initialise phpCAS (idempotent — la bibliothèque phpCAS est statique).
     */
    private function initCas(): void
    {
        if (self::$casInitialized) {
            return;
        }

        $version = !empty($this->casVersion) ? $this->casVersion : CAS_VERSION_2_0;
        \phpCAS::client($version, $this->casHost, $this->casPort, $this->casContext);

        if ($this->casNoServerValidation) {
            \phpCAS::setNoCasServerValidation();
        }

        self::$casInitialized = true;
    }
}
