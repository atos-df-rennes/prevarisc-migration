━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RAPPORT D'ÉCARTS PAR RAPPORT AU LEGACY
Fonctionnalité : Authentification
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Fichiers legacy analysés :
  - prevarisc/application/controllers/SessionController.php
  - prevarisc/application/views/scripts/session/login.phtml (non analysée — remplacée)
  - prevarisc/library/Service/PassAuthAdapater.php (SSO adapter)

Fichiers migrés :
  - prevarisc-migration/src/Security/FormLoginAuthenticator.php  (nouveau)
  - prevarisc-migration/src/Security/Md5SaltedEncoder.php        (nouveau)
  - prevarisc-migration/src/Security/ZendIdentityBuilder.php     (nouveau)
  - prevarisc-migration/src/Security/ZendSessionBridge.php       (modifié)
  - prevarisc-migration/src/Security/LegacySessionAuthenticator.php (modifié)
  - prevarisc-migration/src/Controller/SessionController.php     (nouveau)
  - prevarisc-migration/src/Entity/Utilisateur.php               (modifié : getSalt)
  - prevarisc-migration/src/Controller/UtilisateursController.php (modifié : md5 → encoder)
  - prevarisc-migration/templates/security/login.html.twig       (nouveau)
  - prevarisc-migration/config/packages/security.php             (modifié)
  - prevarisc-migration/config/prevarisc.php                     (modifié)
  - prevarisc-migration/config/services.php                      (modifié)
  - prevarisc-migration/composer.json                            (modifié)

Niveau de confiance global : ⚠️

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ PORT DIRECT — aucune vérification nécessaire :

- **CAS (SessionController.php:20-21)** : `phpCAS::getUser()` → même appel dans
  `FormLoginAuthenticator::getCredentials()`. Logique identique.

- **NTLM parsing (SessionController.php:26-32)** : extraction `REMOTE_USER` →
  `explode('\\', ...)` → `[$domain, $username]` porté à l'identique.

- **Anti-brute-force (SessionController.php:72-83)** : conditions `ENFORCE_SECURITY`,
  `FAILED_LOGIN_ATTEMPTS >= MAX_LOGIN_ATTEMPTS`, comparaison IP portées tel quel.
  Constante `MAX_LOGIN_ATTEMPTS = 2` conservée.

- **Inactivité utilisateur (SessionController.php:52-58)** : vérification `ACTIF_UTILISATEUR`
  portée dans `getUser()` via `$user->getActif()`.

- **SSO passthrough (Service_PassAuthAdapater)** : la logique "CAS/NTLM → authenticate()
  retourne toujours true" est portée dans `checkCredentials()` :
  `if (null !== $credentials['sso_type']) { return true; }`.

- **SSO password=null (SessionController.php:66-69)** : les utilisateurs avec `sso=true`
  et `password=null` ne peuvent pas se connecter via formulaire (comportement conservé).

- **MD5 hashing (SessionController.php:88)** :
  `md5($username . PREVARISC_SECURITY_SALT . $password)` →
  `Md5SaltedEncoder::encodePassword($raw, $salt)` avec `$salt = $username`
  (via `Utilisateur::getSalt()`). Résultat identique.

- **Redirection `/accueil` par défaut (SessionController.php:110)** : même comportement,
  cible `accueil_index`.

- **Logout Zend (SessionController.php:124-138)** : `Zend_Auth::clearIdentity()` +
  `session_unset()` → `ZendSessionBridge::clearAuthentication()` + suppression
  clé `username`.

- **Logout CAS (SessionController.php:139-140)** : `phpCAS::logout()` appelé dans
  `SessionController::logout()` si `PREVARISC_CAS_ENABLED=1`.

- **Champs nommage formulaire** : `prevarisc_login_username` / `prevarisc_login_passwd`
  (Zend_Form) → `_username` / `_password` (convention Symfony). Les noms de champs
  ont changé : sans impact fonctionnel (formulaire Twig adapté en conséquence).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⚠️ ADAPTATIONS — vérifier ces points précis :

**1. [SessionController.php:110] Décodage de l'URL de redirection**
  - Legacy : `urldecode($this->_request->getParams()['redirect'])` — décoding explicite.
  - Symfony : `$request->query->get('redirect')` — Symfony décode automatiquement les
    paramètres de query string URL-encodés. Comportement équivalent mais via un chemin
    différent.
  - Sécurité ajoutée : validation que le redirect est un chemin relatif (commence par `/`
    et non `//`) pour prévenir l'open redirect. Le legacy n'avait pas cette protection.
  - ✅ **Amélioration** — à tester : se connecter avec `?redirect=%2Fdossier%2Fshow%2F42`,
    vérifier la redirection. Tester `?redirect=https://evil.com` → doit rediriger vers
    `/accueil` à la place.

**2. [SessionController.php:119] Compteur d'échecs de connexion**
  - Legacy : `$service_user->logFailedLogin($user)` est appelé dans le `catch` global,
    **même si l'utilisateur n'existe pas** (user=null).
  - Symfony : `onAuthenticationFailure()` incrémente `failedLoginAttempts` uniquement si
    `$this->lastAttemptedUser !== null` (utilisateur trouvé en base).
  - Impact : un `UsernameNotFoundException` ne déclenche pas d'incrémentation (mais
    l'utilisateur n'existe pas, donc sans objet).
  - ✅ **Comportement équivalent** pour les cas réels — à tester : tenter 3 connexions
    avec un mauvais mot de passe → vérifier `FAILED_LOGIN_ATTEMPTS_UTILISATEUR` en base.

**3. [SessionController.php:88] LDAP — ordre des adaptateurs**
  - Legacy : essai dans l'ordre `sso` → `dbtable` → `ldap`. LDAP est tenté **après**
    dbtable et réussit indépendamment.
  - Symfony : ordre `sso` → LDAP → dbtable (LDAP en fallback avant dbtable). Si LDAP
    échoue, on retombe sur dbtable MD5/bcrypt.
  - Différence : dans le legacy, un utilisateur LDAP dont le hash MD5 est correct peut
    s'authentifier via dbtable même si LDAP est activé. En Symfony, LDAP est tenté en
    premier et son échec déclenche le fallback dbtable — même résultat final mais ordre
    différent.
  - ⚠️ **À tester en env LDAP** : connexion avec credential LDAP valide, connexion avec
    credential dbtable valide quand LDAP est activé.

**4. [SessionController.php:91-103] LDAP — canonicalisation du compte**
  - Legacy : `Zend_Ldap::getCanonicalAccountName($username, $accountForm)` où
    `PREVARISC_LDAP_ACCOUNT_FORM` définit le format (DN, UPN, etc.).
  - Symfony : bind direct avec `$credentials['username']` (format UPN/DN) ou recherche
    via service account sur l'attribut `uid=`. La variable `PREVARISC_LDAP_ACCOUNT_FORM`
    est lue mais sert uniquement au fallback bind direct.
  - ⚠️ **À tester** : valider que le format de compte configuré est compatible avec la
    nouvelle implémentation `symfony/ldap`. Utiliser `PREVARISC_LDAP_ACCOUNT_FORM`
    pour le bind direct si `Zend_Ldap::ACCTNAME_FORM_DN` était utilisé.

**5. [SessionController.php:124-149] Logout NTLM**
  - Legacy : si l'utilisateur connecté en NTLM (`PASSWD_UTILISATEUR = null`), le layout
    passe en `error` (page d'erreur affichée).
  - Symfony : redirection vers `session_login` dans tous les cas non-CAS.
  - ⚠️ **Comportement légèrement différent** pour logout NTLM : l'utilisateur est redirigé
    vers la page de login au lieu de voir une page d'erreur. Acceptable car le navigateur
    NTLM re-authentifie automatiquement.
  - À tester : déconnexion depuis un poste NTLM → vérifier qu'il n'y a pas de boucle
    infinie (NTLM re-auth immédiate après logout).

**6. [SessionController.php:108] resetFailedLogin sur succès**
  - Legacy : `$service_user->resetFailedLogin($user)` dans la boucle d'adaptateurs
    (potentiellement appelé par le premier adaptateur qui réussit).
  - Symfony : `$utilisateur->setFailedLoginAttempts(0)` dans `onAuthenticationSuccess()`
    puis `flush()`. Comportement identique.

**7. [Nouveau] Propagation session Zend_Auth**
  - Legacy : `Zend_Auth::getInstance()->getStorage()->write($user)` écrit le tableau
    `$user` depuis `Service_User::findByUsername()`.
  - Symfony : `ZendIdentityBuilder::build()` reconstruit ce tableau depuis l'entité
    Doctrine et `ZendSessionBridge::writeAuthentication()` l'écrit dans
    `$_SESSION['Zend_Auth']['storage']`.
  - ⚠️ **À tester** : après connexion via Symfony, ouvrir une page Zend legacy et
    vérifier que `Zend_Auth::getInstance()->getIdentity()` retourne les bonnes valeurs
    (notamment `ID_UTILISATEUR`, `group['LIBELLE_GROUPE']`, `infos`, `preferences`).

**8. [Nouveau] Protection CSRF formulaire**
  - Legacy : aucune protection CSRF sur le formulaire de login (Zend_Form sans token CSRF).
  - Symfony : token `_csrf_token` généré dans `login.html.twig` et validé dans
    `FormLoginAuthenticator::getCredentials()` via `CsrfTokenManagerInterface`.
  - ✅ **Amélioration sécurité** — sans impact fonctionnel pour les utilisateurs légitimes.

**9. [Nouveau] Feature flag PREVARISC_SYMFONY_LOGIN**
  - La bascule est contrôlée par `PREVARISC_SYMFONY_LOGIN=1`.
  - À `0` (défaut) : `LegacySessionAuthenticator` gère tout, `FormLoginAuthenticator`
    ne prend en charge que les requêtes CAS/NTLM (s'ils sont activés).
  - À `1` : `FormLoginAuthenticator` gère `/session/login` POST.
  - ⚠️ **À tester** : basculer le flag et vérifier que l'authentification fonctionne dans
    les deux modes.

**10. [Transition] Rehachage MD5 → bcrypt intentionnellement désactivé**
  - `Md5SaltedEncoder::needsRehash()` retourne **toujours `false`** pendant la transition.
  - `UtilisateurRepository::upgradePassword()` est un **no-op** intentionnel.
  - Objectif : maintenir la réversibilité vers le mode legacy (`PREVARISC_SYMFONY_LOGIN=0`).
    Un mot de passe bcrypt est incompatible avec le hachage MD5 attendu par Zend.
  - ⚠️ **À activer une fois le legacy définitivement retiré** : passer `needsRehash()` à `true`
    et réactiver le corps de `upgradePassword()`.
  - ⚠️ **À tester** : vérifier que la modification d'un utilisateur depuis le formulaire admin
    conserve un hash MD5 (32 chars) et non bcrypt (`$2y$...`).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔴 LOGIQUE COMPLEXE — vérification approfondie recommandée :

**1. Logout CAS — cleanup via LogoutHandler + CasLogoutSuccessHandler**
  - Legacy : `phpCAS::logout()` dans `logoutAction()`.
  - Symfony : le firewall intercepte la route `session_logout` et invalide le token Symfony.
    Le cleanup complémentaire est délégué à deux handlers :
    - `LogoutHandler::logout()` : efface `ZendSessionBridge` (session Zend_Auth) et
      la clé `username` en session Symfony.
    - `CasLogoutSuccessHandler::onLogoutSuccess()` : redirige vers le serveur CAS pour
      invalider le TGT si CAS est activé, sinon vers `/session/login`.
  - ✅ Le contrôleur `SessionController::logout()` n'est PAS appelé (intercepté par le firewall).
  - À tester : déconnexion depuis un environnement CAS → vérifier la redirection vers CAS.

**2. updateLastActionDate manquant au logout**
  - Legacy (SessionController.php:133) : `$service_user->updateLastActionDate(...)` est
    appelé lors de la déconnexion.
  - Symfony : `SessionController::logout()` ne met pas à jour `LASTACTION_UTILISATEUR`.
  - 🔴 **Régression fonctionnelle** : la date de dernière action n'est pas mise à jour
    à la déconnexion. À implémenter si ce champ est utilisé à des fins de reporting ou
    d'audit.
  - Mitigation : `LASTACTION_UTILISATEUR` est mis à jour à chaque requête authentifiée
    via le legacy (via un plugin Zend) — la valeur sera correcte au dernier accès, pas
    forcément à la déconnexion.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Scénarios de test prioritaires
-------------------------------

1. **Formulaire standard** : se connecter avec login/mot de passe MD5 existant →
   vérifier connexion + hash en base toujours en MD5 (32 chars) + accès aux pages Zend legacy.

2. **Réversibilité** : avec `PREVARISC_SYMFONY_LOGIN=1`, modifier un utilisateur dans
   l'admin → vérifier que `PASSWD_UTILISATEUR` reste en MD5 → basculer sur `PREVARISC_SYMFONY_LOGIN=0`
   → vérifier que l'utilisateur peut toujours se connecter via Zend.

3. **Mauvais mot de passe x3** : 3 tentatives → vérifier compteur en base +
   blocage IP si `PREVARISC_ENFORCE_SECURITY=1`.

4. **Utilisateur inactif** : tentative avec `ACTIF_UTILISATEUR=0` → doit échouer.

5. **Redirection post-login** : connexion via `?redirect=/dossier/show/42` →
   vérifier redirection correcte. Test avec `?redirect=https://evil.com` →
   doit aller sur `/accueil`.

6. **Propagation Zend_Auth** : après connexion Symfony, accéder à une page Zend
   legacy → vérifier `Zend_Auth::getInstance()->getIdentity()` non null +
   `ID_UTILISATEUR`, `group['LIBELLE_GROUPE']`, `infos['NOM_UTILISATEURINFORMATIONS']`
   présents.

7. **Logout** : se déconnecter → vérifier session Zend effacée + redirection login.

8. **Feature flag OFF** : `PREVARISC_SYMFONY_LOGIN=0` → vérifier que
   `LegacySessionAuthenticator` prend le relais.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
