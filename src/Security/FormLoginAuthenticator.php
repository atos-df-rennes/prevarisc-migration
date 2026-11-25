<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Utilisateur;
use App\Enum\LdapEncryption;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Authentificateur Symfony natif pour Prévarisc.
 *
 * Gère quatre mécanismes d'authentification dans l'ordre de priorité :
 *   1. CAS  (phpCAS) — si PREVARISC_CAS_ENABLED=1
 *   2. NTLM ($_SERVER['REMOTE_USER']) — si PREVARISC_NTLM_ENABLED=1
 *   3. Formulaire : LDAP pour les comptes SSO, base locale pour les autres, sans fallback
 */
class FormLoginAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface, InteractiveAuthenticatorInterface
{
    use TargetPathTrait;

    public const MAX_LOGIN_ATTEMPTS = 2;

    /**
     * Dernier utilisateur tentant une authentification (pour onAuthenticationFailure).
     */
    private ?Utilisateur $lastAttemptedUser = null;

    /**
     * Indicateur d'initialisation phpCAS (static car phpCAS est une classe statique).
     */
    private static bool $casInitialized = false;

    /**
     * @param string    $ldapFilter      Filtre de recherche LDAP pour retrouver le DN d'un utilisateur (format sprintf avec %s pour le nom d'utilisateur : '(uid=%s)' pour OpenLDAP, '(sAMAccountName=%s)' pour Active Directory)
     * @param string    $ldapAccountForm Format du compte LDAP pour le bind direct (fallback sans service-account, format sprintf avec %s : '%s@domain.tld' pour UPN, 'DOMAIN\%s' pour pre-Win2000, '' pour pas de transformation)
     * @param Ldap|null $ldap            Instance LDAP partagée (connexion lazy, créée une seule fois par requête)
     */
    public function __construct(
        private LoggerInterface $logger,
        private UserPasswordHasherInterface $passwordEncoder,
        private RouterInterface $router,
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private bool $casEnabled,
        private string $casHost,
        private int $casPort,
        private string $casPublicHost,
        private int $casPublicPort,
        private string $casContext,
        private bool $casNoServerValidation,
        private bool $casNoSsl,
        private string $casVersion,
        private bool $ntlmEnabled,
        private bool $ldapEnabled,
        private string $ldapHost,
        private int $ldapPort,
        private LdapEncryption $ldapEncryption,
        private string $ldapUsername,
        private string $ldapPassword,
        private string $ldapBaseDn,
        private string $ldapFilter,
        private string $ldapAccountForm,
        private bool $enforceSecurity,
        private ?Ldap $ldap = null
    ) {
    }

    /**
     * Détermine si cet authentificateur doit prendre en charge la requête.
     *
     * Priorités :
     *   - CAS activé → true sauf si l'utilisateur est déjà authentifié en session
     *   - NTLM activé + REMOTE_USER présent → true
     *   - POST sur /session/login → true
     *   - Sinon → false
     */
    public function supports(Request $request): ?bool
    {
        if (
            $this->casEnabled
            || ($this->ntlmEnabled && isset($_SERVER['REMOTE_USER']))
        ) {
            // Ne pas relancer le pipeline d'auth si un token Symfony existe déjà en session.
            // Note : avec un firewall lazy, le tokenStorage n'est pas encore peuplé lors de
            // supports() — le token est chargé paresseusement et getToken() retourne null même
            // si le token existe en session. On vérifie donc la session directement en complément.
            $token = $this->tokenStorage->getToken();
            if ($token?->getUser() instanceof UserInterface) {
                return false;
            }

            return !$request->getSession()->has('_security_main');
        }

        return $request->isMethod('POST')
            && '/session/login' === $request->getPathInfo();
    }

    public function authenticate(Request $request): Passport
    {
        $this->lastAttemptedUser = null;
        $credentials = $this->getCredentials($request);

        return new Passport(
            new UserBadge(
                $credentials['username'],
                fn (string $identifier): UserInterface => $this->loadUserByIdentifier($identifier)
            ),
            new CustomCredentials($this->checkCredentials(...), $credentials)
        );
    }

    /**
     * @return array{username: string, password: string|null, sso_type: string|null}
     */
    private function getCredentials(Request $request): array
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
        if ($this->ntlmEnabled && isset($_SERVER['REMOTE_USER'])) {
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
        $username = is_string($rawUsername) ? $rawUsername : '';
        $password = is_string($rawPassword) ? $rawPassword : '';

        if ('' === $username || '' === $password) {
            $this->logger->notice('Auth formulaire: identifiant ou mot de passe vide');
            throw new BadCredentialsException();
        }

        return [
            'username' => $username,
            'password' => $password,
            'sso_type' => null,
        ];
    }

    /**
     * Charge l'utilisateur et vérifie qu'il est actif.
     */
    private function loadUserByIdentifier(string $username): UserInterface
    {
        $user = $this->entityManager
            ->getRepository(Utilisateur::class)
            ->findOneBy(['username' => $username]);

        if (!$user instanceof Utilisateur) {
            $this->logger->error("Auth: utilisateur inexistant en base de données Prevarisc. Veuillez l'ajouter.");
            throw new UserNotFoundException();
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
     * Ordre de vérification :
     *   1. SSO (CAS/NTLM) → true immédiat
     *   2. Utilisateur SSO sur formulaire → false (configuration incohérente)
     *   3. Anti-brute-force si ENFORCE_SECURITY + IP différente
     *   4. Compte SSO → LDAP uniquement ; compte local → passwordEncoder (MD5→bcrypt)
     */
    public function checkCredentials(mixed $credentials, UserInterface $user): bool
    {
        /** @var array{username: string, password: string|null, sso_type: string|null} $credentials */

        /** @var Utilisateur $utilisateur */
        $utilisateur = $user;

        // 1. SSO : on fait confiance à CAS/NTLM
        if (null !== $credentials['sso_type']) {
            return true;
        }

        if ('' === $credentials['username'] || null === $credentials['password'] || '' === $credentials['password']) {
            $this->logger->notice('Auth formulaire: identifiant ou mot de passe vide');

            return false;
        }

        if (true === $utilisateur->getSso() && !$this->ldapEnabled) {
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

        // 4. Le compte choisit un seul mécanisme, sans repli vers la base locale.
        if ($utilisateur->getSso()) {
            try {
                // Utiliser l'instance LDAP injectée (connexion lazy, partagée sur la requête)
                // ou en créer une à la volée si non injectée (fallback de compatibilité).
                $ldap = $this->ldap ?? LdapClientFactory::create($this->ldapHost, $this->ldapPort, $this->ldapEncryption);

                // Tentative de bind en deux étapes si un compte de service est configuré :
                // 1) Bind service account → 2) Recherche DN utilisateur → 3) Bind utilisateur
                // Le bloc est isolé dans son propre try/catch : un échec du bind service account
                // (ex. username non valide comme un simple CN sans domaine) n'avorte pas le
                // fallback direct bind ci-dessous.
                if (!empty($this->ldapUsername) && !empty($this->ldapBaseDn)) {
                    try {
                        $ldap->bind($this->ldapUsername, $this->ldapPassword);
                        $escapedUser = str_replace(['\\', '*', '(', ')', "\0"], ['\\5c', '\\2a', '\\28', '\\29', '\\00'], $credentials['username']);
                        $query = $ldap->query($this->ldapBaseDn, sprintf($this->ldapFilter, $escapedUser));
                        $results = $query->execute()->toArray();

                        if ([] !== $results) {
                            $entry = $results[0];
                            $ldap->bind($entry->getDn(), (string) $credentials['password']);
                            $this->logger->info(sprintf("Auth LDAP (service-account): succès pour '%s'", $credentials['username']));

                            return true;
                        }

                        $this->logger->notice("Auth LDAP: utilisateur non trouvé dans l'annuaire, fallback direct bind", [
                            'username' => $credentials['username'],
                        ]);
                    } catch (\Exception $e) {
                        $this->logger->notice('Auth LDAP: bind service account échoué, fallback direct bind', [
                            'service_account' => $this->ldapUsername,
                            'exception' => $e->getMessage(),
                        ]);
                    }
                }

                // Bind direct (format UPN user@domain ou DN complet)
                // Appliquer ldapAccountForm uniquement si le format contient un placeholder %s
                // (ex: '%s@domain.tld', 'DOMAIN\%s'). Les valeurs legacy sans %s (ex: '1')
                // sont ignorées — elles servaient de sentinel dans Zend_Ldap et n'ont pas
                // d'équivalent dans le composant Symfony.
                $bindUsername = (str_contains($this->ldapAccountForm, '%s'))
                    ? sprintf($this->ldapAccountForm, $credentials['username'])
                    : $credentials['username'];
                $ldap->bind($bindUsername, (string) $credentials['password']);
                $this->logger->info(sprintf("Auth LDAP (direct): succès pour '%s'", $credentials['username']));

                return true;
            } catch (\Exception $e) {
                $this->logger->notice('Auth LDAP: échec sans repli local', [
                    'username' => $credentials['username'],
                    'exception_class' => $e::class,
                ]);

                return false;
            }
        }

        if (null === $utilisateur->getPassword() || '' === $utilisateur->getPassword()) {
            $this->logger->notice('Auth BDD: compte local sans mot de passe', [
                'username' => $utilisateur->getUsername(),
                'ldap_enabled' => $this->ldapEnabled,
            ]);

            return false;
        }

        // 5. Vérification dbtable — passwordEncoder gère migrate_from (MD5 → bcrypt) automatiquement
        $isValid = $this->passwordEncoder->isPasswordValid($utilisateur, (string) $credentials['password']);

        if (!$isValid) {
            $this->logger->notice('Auth BDD: échec sans repli LDAP', [
                'username' => $utilisateur->getUsername(),
                'ldap_enabled' => $this->ldapEnabled,
            ]);
        }

        // Migration MD5 → bcrypt/argon2 : le CustomCredentials utilisé ci-dessus ne déclenche pas
        // le mécanisme standard PasswordUpgradeBadge de Symfony, donc l'upgrade est déclenché
        // manuellement ici. Le hash legacy MD5 est irréversiblement remplacé dès la première
        // connexion réussie de l'utilisateur.
        if ($isValid && $this->passwordEncoder->needsRehash($utilisateur)) {
            $newHashedPassword = $this->passwordEncoder->hashPassword($utilisateur, (string) $credentials['password']);

            /** @var UtilisateurRepository $repository */
            $repository = $this->entityManager->getRepository(Utilisateur::class);
            $repository->upgradePassword($utilisateur, $newHashedPassword);
        }

        return $isValid;
    }

    /**
     * Callback appelé après une authentification réussie.
     *
     * Met à jour l'IP et les compteurs en base, propage vers la session Zend,
     * puis redirige vers la page demandée ou l'accueil.
     *
     * @param Request        $request      La requête HTTP
     * @param TokenInterface $token        Le token de sécurité Symfony
     * @param string         $firewallName La clé du firewall
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $token->getUser();

        // Mettre à jour les données de connexion en base
        $utilisateur->setIp($request->getClientIp());
        $utilisateur->setFailedLoginAttempts(0);

        $this->entityManager->flush();

        $this->logger->info(sprintf("Auth: connexion réussie pour '%s'", $utilisateur->getUsername()));

        // Redirection post-login : priorité au target path Symfony (mécanisme natif),
        // sinon fallback sur le paramètre query ?redirect= (mitigation open redirect).
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        if (null !== $targetPath) {
            return new RedirectResponse($targetPath);
        }

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
        if ($this->lastAttemptedUser instanceof Utilisateur) {
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
     * Sauvegarde l'URL courante comme target path (mécanisme natif Symfony),
     * puis redirige vers la page de login.
     *
     * @param Request                      $request       La requête HTTP
     * @param AuthenticationException|null $authException L'exception éventuelle
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // Ne sauvegarder le target path que pour les requêtes GET non-XHR.
        // Les requêtes POST/AJAX rejouées en GET après login aboutiraient à un 405.
        if ('/session/login' !== $request->getPathInfo()
            && $request->isMethod('GET')
            && !$request->isXmlHttpRequest()
        ) {
            $this->saveTargetPath($request->getSession(), 'main', $request->getRequestUri());
        }

        return new RedirectResponse($this->router->generate('session_login'));
    }

    public function isInteractive(): bool
    {
        return true;
    }

    /**
     * Initialise phpCAS (idempotent — la bibliothèque phpCAS est statique).
     *
     * Note : avec mod_php, les statics PHP persistent entre requêtes dans le même worker.
     * phpCAS::client() ne peut être appelé qu'une fois par processus. En revanche,
     * setServerLoginURL/setServerServiceValidateURL doivent être appliqués à chaque requête
     * pour garantir les URLs http:// quand casNoSsl est actif, et le bon hostname public
     * pour la login URL navigateur (casPublicHost peut différer de casHost en dev).
     */
    private function initCas(): void
    {
        if (!self::$casInitialized) {
            $version = !empty($this->casVersion) ? $this->casVersion : CAS_VERSION_2_0;
            \phpCAS::client($version, $this->casHost, $this->casPort, $this->casContext, $this->getServiceBaseUrl());
            self::$casInitialized = true;
        }

        if ($this->casNoSsl) {
            // Forcer http:// sur la login URL et la validation URL
            $loginUrl = str_replace('https://', 'http://', \phpCAS::getServerLoginURL());
            \phpCAS::setServerLoginURL((string) $loginUrl);

            // serviceValidate URL : appel serveur→CAS → toujours utiliser casHost (joignable depuis le conteneur)
            $validateUrl = sprintf('http://%s:%d/%s/serviceValidate', $this->casHost, $this->casPort, ltrim($this->casContext, '/'));
            \phpCAS::setServerServiceValidateURL($validateUrl);
        }

        // Réécriture du hostname public pour la login URL navigateur.
        // Indépendante du SSL : en prod HTTPS avec reverse proxy, casPublicHost peut différer
        // de casHost (interne). Sans cette réécriture, le navigateur ne peut pas joindre CAS.
        if (!empty($this->casPublicHost)) {
            $currentLoginUrl = \phpCAS::getServerLoginURL();
            $publicPort = $this->casPublicPort > 0 ? $this->casPublicPort : $this->casPort;
            $scheme = $this->casNoSsl ? 'http' : 'https';
            $newLoginUrl = preg_replace(
                '#https?://[^/]+/#',
                sprintf('%s://%s:%d/', $scheme, $this->casPublicHost, $publicPort),
                $currentLoginUrl
            );
            \phpCAS::setServerLoginURL((string) $newLoginUrl);
        }

        if ($this->casNoServerValidation) {
            \phpCAS::setNoCasServerValidation();
        }
    }

    private function getServiceBaseUrl(): string
    {
        $https = $_SERVER['HTTPS'] ?? null;
        $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? null;
        $isHttps = ('on' === $https
            || '1' === $https
            || (is_string($requestScheme) && 'https' === strtolower($requestScheme)));
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $host = is_string($host) ? $host : 'localhost';

        return sprintf('%s://%s', $scheme, $host);
    }
}
