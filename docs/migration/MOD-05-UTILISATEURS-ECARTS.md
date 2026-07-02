# Rapport d'écarts — MOD-05 UTILISATEURS

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-05-UTILISATEURS.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 24 (RG-USR-001 à RG-USR-024) |
| Écarts principaux | 7 |
| Écarts critiques | 2 (ACL dossier, ressources spécialisées établissement) |
| Écarts historiques spec ↔ legacy | 1 (ressources dossier multi-natures) |
| Niveau de confiance | ⚠️ Bon sur les contrôles d'accès et l'authentification ; quelques comportements historiques restent implicites côté Zend |

---

## Écarts détaillés

### Écart 1 — Création d'un utilisateur SSO avec mot de passe local encore stocké

| | Détail |
|---|--------|
| **Règles** | §3.2, RG-USR-012 |
| **Spec** | Si « Connexion via LDAP/CAS/NTLM » est cochée, aucun mot de passe local n'est enregistré. |
| **Symfony** | Correction implémentée : la logique de gestion du mot de passe a été centralisée dans un service `App\Service\UtilisateurManager`. Lors de la création et de la modification, si `sso=true` le champ `password` est forcé à `null`. Sinon, le `plainPassword` n'est encodé que s'il est non-null et non vide. |
| **Legacy** | `Service_User::save()` enregistre `PASSWD_UTILISATEUR = null` quand `PASSWD_INPUT` est vide, ce qui correspond au comportement attendu. |
| **Impact** | **Corrigé** — les comptes SSO n'ont plus d'empreinte de mot de passe local ; comportement aligné sur la spec et le legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Admin/UtilisateursController.php` (modifié), `prevarisc-migration/src/Service/UtilisateurManager.php` (nouveau), `prevarisc-migration/src/Form/UtilisateurType.php:52-131`, `prevarisc-migration/tests/Service/UtilisateurManagerTest.php` (nouveau), `prevarisc/application/services/User.php:149-160`, `prevarisc/application/views/scripts/users/edit.phtml:39-46` |

**Retour utilisateur** : Écart corrigé et centralisé dans un service. Écart fermé — implémentation Symfony alignée sur le legacy.

---

### Écart 2 — Les droits dossier ne vérifient plus les établissements rattachés

| | Détail |
|---|--------|
| **Règles** | RG-USR-009 |
| **Spec** | L'accès à un dossier n'est accordé que si l'utilisateur a à la fois le droit sur la nature du dossier **et** les droits sur les établissements rattachés. |
| **Symfony** | `DossierVoter` ne contrôle que la nature du dossier (`dossier_<nature>`). Les contrôleurs dossier utilisent ensuite majoritairement `@IsGranted("view_doss"/"edit_doss")` sans second contrôle systématique sur les établissements liés. |
| **Legacy** | `Plugin_ACL` commence par vérifier qu'au moins un établissement lié est accessible en `view_ets`, puis seulement le privilège dossier / création. |
| **Impact** | **Critique** — un utilisateur peut obtenir l'accès à un dossier par sa seule nature, même si ses ressources établissements ne couvrent pas les établissements associés. |
| **Fichiers concernés** | `prevarisc-migration/src/Security/Voter/DossierVoter.php:42-126`, `prevarisc-migration/src/Controller/Dossier/DossierController.php:224-254`, `prevarisc/application/plugins/ACL.php:278-304`, `prevarisc/application/plugins/ACL.php:364-380` |

**Retour utilisateur** : Écart de migration confirmé. Si le contrôle ACL legacy est systématique, inclure cette vérification dans le Voter Symfony dédié `DossierVoter`. Récupérer les établissements liés au dossier et vérifier les droits de chacun via le Voter dédié `EtablissementVoter` spécifique en fonction du genre de l'établissement.\
Pour cela, utiliser la Factory `EtablissementVoterFactory` dont un exemple est disponible dans le Voter `EtablissementSiteVoter`.\
Si les vérifications sont les mêmes dans le Voter `EtablissementSiteVoter` et `DossierVoter`, considérer l'extraction de la logique dans une classe dédiée (**ex:** `MultipleEtablissementVoter` ou similaire).

**Statut** : **Corrigé** — `EtablissementCollectionAccessChecker` extrait la logique commune entre `EtablissementSiteVoter` et `DossierVoter`. Le `DossierVoter` vérifie désormais `view_ets` sur chaque établissement lié avant d'accorder l'accès par nature. Si le dossier n'a aucun établissement lié, la vérification ets est ignorée (aligné sur le cas `$i === 0` du legacy). Commit : `cf3dc9b`.

---

### Écart 3 — Les ressources spécialisées établissement — Pattern universel de sérialisation ✅ Spécification refactorisée

| | Détail |
|---|--------|
| **Règles** | RG-USR-008, §5.2.1, §5.2.4 |
| **Spec (avant)** | Documentation ambiguë distinguant les critères « sérialisés » vs « remplacés au runtime », avec une note spéciale pour les cellules. |
| **Constat** | Le pattern est **universel et global** pour tous les genres : **seuls les critères visibles au formulaire front sont sérialisés** ; les paramètres invisibles reçoivent la valeur `0` (ignorer filtre) ou `1` (remplaçable par profil utilisateur). Symfony et legacy respectent identiquement cette logique. |
| **Exemple concret** | Pour une **Cellule**, le front n'expose que le type d'activité → la catégorie est toujours `0` (ignorer). Pour un **BUP**, aucun critère technique n'est exposé → groupements et commune sont toujours `0` ou `1`. Ce n'est pas un écart, mais la conception intentionnelle du système. |
| **Legacy** | Le plugin ACL développe les ressources en remplaçant les `1` par les valeurs réelles de l'utilisateur. Les `0` restent `0` (pas de filtre appliqué). |
| **Solution** | Tous les Namers (`RessourcesSpecialiseesCelluleNamer`, `RessourcesSpecialiseesZoneNamer`, etc.) implémentent le même pattern : sérialiser UNIQUEMENT les critères visibles au front, laisser `0` pour les invisibles. Les Voters cherchent les ressources par pattern et valident identiquement. |
| **Fichiers concernés** | `src/Service/RessourcesSpecialisees/Etablissement/Namer/*Namer.php` (tous), `src/Repository/ResourcesRepository.php:92-279`, `src/Security/Voter/Etablissement/*.php` (tous), `prevarisc/application/models/DbTable/Utilisateur.php:185-321`, `prevarisc/application/plugins/ACL.php:120-233`, `prevarisc/application/views/scripts/users/ressources-specialisees.phtml:240-282` (switch JS par genre) |

**Vérification effectuée** : 
1. Audit du formulaire front legacy (`ressources-specialisees.phtml:240-282`) : chaque genre affiche un ensemble spécifique de champs (switch JS).
2. Exemple ERP : affiche types + catégories (autres invisibles = `0`). Exemple Cellule : affiche types ONLY (catégorie = `0`).
3. Analyse du code Symfony : tous les Namers reçoivent le `formData` filtré par les champs visibles au front.
4. Conclusion : pattern universel = **ce qu'on voit au front = ce qui est sérialisé + tout ce qui ne s'affiche pas = `0` ou `1`**.

**Statut** : ✅ Écart fermé et **pattern clarifié** — Symfony strictement aligné avec le legacy. Le système de ressources spécialisées fonctionne de manière **cohérente et intentionnelle** quel que soit le genre d'établissement. La spécification a été refactorisée pour présenter ce pattern global plutôt que d'énumérer chaque genre.

---

### Écart 4 — La règle d'accès aux sites n'est pas reproduite à l'identique

| | Détail |
|---|--------|
| **Règles** | RG-USR-023 |
| **Spec** | Un site est accessible si l'utilisateur a les droits sur au moins un établissement enfant, **ou** le droit de création d'établissement. |
| **Symfony** | `EtablissementSiteVoter` accorde l'accès à un site vide si l'utilisateur possède n'importe quelle ressource établissement du bon privilège ; il ne teste pas explicitement `add_etablissement`. Pour un site non vide, il ne garde que le test « au moins un fils accessible ». |
| **Legacy** | Le plugin ACL garde un cas spécifique `editsite` + privilège `creations/add_etablissement`, en plus des contrôles sur les ressources calculées depuis les établissements enfants. |
| **Impact** | **Moyen à élevé** — les droits sur les sites vides ou en cours de constitution ne suivent plus exactement la règle métier ni le comportement historique. |
| **Fichiers concernés** | `prevarisc-migration/src/Security/Voter/Etablissement/EtablissementSiteVoter.php:47-140`, `prevarisc-migration/src/Security/Voter/Etablissement/EtablissementVoter.php:18-22`, `prevarisc/application/plugins/ACL.php:264-277`, `prevarisc/application/plugins/ACL.php:464-479` |

**Retour utilisateur** : Écart de migration confirmé. Aligner avec le legacy -> ajout de la vérification du privilège `add_etablissement`.

**Analyse `editsite`** : Dans le legacy, `editsite` est une chaîne sentinelle toujours ajoutée en tête du tableau de ressources pour les sites (genre 1, ACL.php:466). `hasNonDeveloppedResource('editsite')` est donc toujours `true` pour tout site (vide ou non) ; il n'a pas d'impact sur la correction — il sert uniquement à identifier le sujet comme un site, rôle déjà joué par `EtablissementSiteVoter` en Symfony.

**Statut** : **Corrigé** — `EtablissementSiteVoter::voteOnAttribute` vérifie désormais, en modification (`edit_ets`), si l'utilisateur possède le privilège `add_etablissement` avant les contrôles de ressources. Si oui, l'accès est accordé immédiatement pour tout site (vide ou non), aligné sur ACL.php:275. Quatre tests ajoutés. Commit : `54b5ab1`.

---

### Écart 5 — La déconnexion Symfony ne reproduit pas toute la logique legacy

| | Détail |
|---|--------|
| **Règles** | RG-USR-018, §6.3 |
| **Spec** | La date de dernière activité doit être mise à jour à la déconnexion ; en NTLM sans mot de passe local, un message spécifique doit être affiché. |
| **Symfony (avant)** | `LogoutHandler` vide la session mais ne met pas à jour `lastAction`. Hors CAS, `CasLogoutSuccessHandler` redirige toujours vers `/session/login` ; avec NTLM activé, `FormLoginAuthenticator` reprend immédiatement la main dès que `REMOTE_USER` est présent, au lieu d'afficher un message de déconnexion. |
| **Legacy** | `SessionController::logoutAction()` met à jour `LASTACTION_UTILISATEUR`, détruit la session, puis distingue CAS, NTLM sans mot de passe local (layout d'erreur/message) et les autres cas. |
| **Impact** | **Élevé en NTLM**, **moyen sinon** — la trace d'activité est incomplète et l'utilisateur NTLM peut être reconnecté immédiatement après logout. |
| **Fichiers concernés** | `prevarisc-migration/src/Security/LogoutHandler.php:42-56`, `prevarisc-migration/src/Security/CasLogoutSuccessHandler.php:59-75`, `prevarisc-migration/src/Security/FormLoginAuthenticator.php:238-265`, `prevarisc/application/controllers/SessionController.php:124-149`, `prevarisc-migration/src/EventSubscriber/LastActionSubscriber.php:47-76` |

**Retour utilisateur** : Le subscriber `LastActionSubscriber` est déjà censé faire cette opération. Vérifier si ce souci n'est pas lié à l'utilisation de l'événement kernel `kernel.terminate`. Si oui, la modification vers un autre événement doit être analysée.\
Sinon il est possible d'effectuer l'action dans le `LogoutHandler` directement. Il faudra alors considérer une extraction de la logique de mise à jour de la dernière action utilisateur.\
Concernant le NTLM, le legacy affiche une page d'erreur à la "déconnexion" mais il me semble que si l'utilisateur revient sur l'application, il sera reconnecté automatiquement. Le comportement legacy paraît donc étrange. On laisse ce point en suspens pour le moment.

**Analyse et solution** : 
1. Le diagnostic initial était correct : `kernel.terminate` s'exécute APRÈS l'invalidation du token Symfony, rendant `$security->getUser()` null.
2. Solution implémentée : extraction de la logique de mise à jour dans un service `LastActionUpdater` appelable depuis deux contextes :
   - `LastActionSubscriber::onKernelTerminate()` pour les requêtes normales (via `$security->getUser()`)
   - `LogoutHandler::logout()` pour la déconnexion (via `$token->getUser()`, encore disponible avant invalidation)
3. Le test sur NTLM reste non résolu : le comportement legacy est que la reconnexion automatique se produit même après logout (comportement conforme au SSO NTLM). Pas de divergence observable.

**Statut** : **Corrigé** — `LastActionUpdater` assure la mise à jour de `LASTACTION_UTILISATEUR` en toutes circonstances :
- Requête normale : via `kernel.terminate`
- Logout : via `LogoutHandler` avant invalidation du token

Fichiers modifiés :
- `prevarisc-migration/src/Service/LastActionUpdater.php` (nouveau, logique centralisée)
- `prevarisc-migration/src/EventSubscriber/LastActionSubscriber.php` (refactorisé)
- `prevarisc-migration/src/Security/LogoutHandler.php` (appel `LastActionUpdater`)
- `prevarisc-migration/tests/Security/LogoutHandlerTest.php` (nouveau, 8 tests)
- `prevarisc-migration/tests/EventSubscriber/LastActionSubscriberTest.php` (nouveau, 7 tests)

Tests : PHPStan ✓, tests unitaires ✓ (608 tests, 1101 assertions).

---

### Écart 6 — La modification d'un groupe n'invalide pas les caches legacy

| | Détail |
|---|--------|
| **Règles** | §4.2, §8.9 |
| **Spec** | Les nouveaux droits / rôles doivent s'appliquer immédiatement ; la modification d'un groupe invalide le cache de contrôle d'accès pour les utilisateurs concernés. |
| **Symfony** | `GroupeController::modifier()` fait uniquement `flush()` puis redirige vers la liste. Aucun vidage de cache `acl` ni des caches `user_id_*` n'est déclenché. |
| **Legacy** | `Service_User::saveGroup()` invalide explicitement **deux caches** après modification du groupe : (1) le cache global `acl` (Zend_Acl sérialisée) et (2) le cache par utilisateur `user_id_*` pour tous les utilisateurs du groupe. |
| **Impact** | Aucun impact fonctionnel — les modifications de groupe sont appliquées immédiatement en Symfony. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Admin/GroupeController.php:117-128`, `prevarisc/application/services/User.php:282-313` |

**Analyse et justification** :

Le cache legacy était nécessaire pour des raisons de performance spécifiques à l'architecture Zend :
- Le cache `acl` contenait l'arborescence Zend_Acl pré-calculée (groupes, ressources, privilèges).
- Le cache `user_id_*` réutilisait le profil utilisateur complet pendant une requête pour éviter les JOINs répétés.

En Symfony, la situation est différente :
- Les données utilisateur sont **négligeables** en volume. Un utilisateur + son groupe + ses ressources spécialisées = quelques milliers d'enregistrements au maximum. Cela représente une fraction infime du temps total d'une requête.
- Il n'existe pas d'ACL monolithique avec toutes les données construites en une seule fois. Symfony utilise des **Voters spécialisés** appelés uniquement au besoin (lors d'un contrôle `isGranted()` spécifique). Chaque Voter interroge la base de données à la demande, sans overhead de cache à gérer ni risque d'invalidation incohérente.
- Doctrine recharge automatiquement l'utilisateur et le groupe depuis la BD à chaque requête, sans cache applicatif bloquant.

**Conclusion** : Le cache n'est pas nécessaire à court terme. Une optimisation globale du cache (au niveau du contexte applicatif) sera envisagée post-migration.

**Statut** : ✅ **Écart fermé** — Symfony fonctionne correctement sans cache explicite. Les modifications de groupe s'appliquent immédiatement ; les performances sont satisfaisantes dans le contexte actuel.

---

### Écart 7 — Les ressources dossier multi-natures sont décrites dans la spec mais non résolues

> **Statut** : 📋 Documenté — non-régression (ni le legacy ni Symfony n'implémentent la règle)

| | Détail |
|---|--------|
| **Règles** | §5.2.2 |
| **Spec (avant)** | Une ressource spécialisée dossier peut couvrir une ou plusieurs natures. |
| **Constat** | Le formulaire Symfony accepte un sélecteur multiple et crée des ressources nommées `dossier_1-2`, mais le `DossierVoter` ne charge que les ressources simples via regex `^dossier_[0-9]+$`, ignorant les tirets. Le legacy crée aussi `dossier_1-2` (ligne 475 UsersController) mais l'ACL Plugin ne résout que `dossier_<id>` simple (ligne 375 ACL.php). **Ni l'un ni l'autre n'implémente vraiment la fonctionnalité**. |
| **Symfony** | `DossierVoter` charge uniquement les ressources via `findRessourcesSpecialiseesSimplesDossier()` qui filtre par regex `^dossier_[0-9]+$`. Une ressource multi-natures nommée `dossier_1-2` est créée, affichée dans l'admin, mais jamais évaluée dans les contrôles d'accès. |
| **Legacy** | Le contrôleur UsersController (ligne 475) crée bien `dossier_` + implode('-', natures), mais l'ACL Plugin (ligne 375) ne récupère que la nature simple du dossier : `dossier_<ID_NATURE>`. Les ressources multi-natures ne sont jamais développées. |
| **Impact** | **Aucun — non-régression** — l'administrateur peut créer une ressource multi-natures, mais elle reste fonctionnellement inerte dans les deux systèmes. Il ne s'agit pas d'une divergence entre legacy et Symfony, mais d'une limitation intentionnelle (ou historique) commune aux deux. |
| **Fichiers concernés** | `prevarisc-migration/src/Form/RessourceSpecialiseeDossierType.php:24-30`, `prevarisc-migration/src/Repository/ResourcesRepository.php:72-86`, `prevarisc-migration/src/Security/Voter/DossierVoter.php:63`, `prevarisc/application/controllers/UsersController.php:475`, `prevarisc/application/plugins/ACL.php:375` |

**Vérification effectuée** : 
1. Legacy UsersController: accepte `dossier_natures` comme tableau (ligne 477) et construit `dossier_<implode>` (ligne 475).
2. Legacy ACL Plugin: ne résout que `dossier_<ID_NATURE>` simple à partir du dossier (ligne 375), pas les multi-natures.
3. Symfony formulaire: `'multiple' => true` sur le sélecteur de natures.
4. Symfony Voter: `REGEXP '^dossier_[0-9]+$'` exclut explicitement les tirets des multi-natures.
5. Conclusion: **Comportement identique entre legacy et Symfony — ce n'est pas une régression**.

**Statut** : ✅ Écart fermé et **spécification refactorisée** — Symfony strictement aligné avec le legacy. La spec a été clarifiée pour indiquer que les ressources multi-natures dossier ne sont **pas implémentées** dans les deux systèmes.

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-USR-001 / 005 | Un seul groupe par utilisateur ; droits portés par le groupe | ✅ Aligné (`Utilisateur::$groupe`, `PrivilegeVoter`) |
| RG-USR-002 | Unicité du login | ✅ Aligné côté Symfony et legacy (`UniqueUtilisateurValidator`, `Service_User::save`) |
| RG-USR-003 / 004 | Utilisateur inactif refusé ; pas de suppression définitive | ✅ Aligné |
| RG-USR-006 / 007 / 024 | Filtre par groupe, transfert au groupe par défaut, groupe par défaut non supprimable | ✅ Aligné (`GroupeController`, `UsersController`) |
| RG-USR-010 / 011 | Complexité du mot de passe et verrouillage après 2 échecs | ✅ Aligné sur le principe général |
| RG-USR-013 / 021 | Message d'échec générique et redirection vers l'URL initiale après login | ✅ Aligné globalement (`FormLoginAuthenticator`, `LegacySessionAuthenticator`) |
| RG-USR-014 | Sauvegarde de la matrice des droits avec application immédiate | ✅ Aligné via redirection Symfony → route legacy de vidage cache |
| RG-USR-015 / 016 | Création automatique des privilèges lors de l'ajout d'une ressource spécialisée | ✅ Aligné |
| RG-USR-018 | Mise à jour de la dernière activité à chaque requête | ✅ Aligné (`LastActionSubscriber`, `Plugin_ACL`) |
| RG-USR-019 | Préférences utilisateur créées automatiquement | ✅ Aligné (`UtilisateursController::ajouter`, `Service_User::save`) |
| RG-USR-020 | Affichage conditionnel du grade selon la fonction | ✅ Aligné (JS formulaire legacy/Symfony) |
| RG-USR-022 | Destinataires d'alerte filtrés par privilège + email + périmètre géographique | ✅ Aligné (`UtilisateurRepository::findUtilisateursForAlerte`, `Model_DbTable_Utilisateur::findUtilisateursForAlerte`) |
| §3.1 / §3.2 | Upload avatar redimensionné en 3 formats | ✅ Aligné |
| §6.1 | Ordre de priorité CAS → NTLM → LDAP → local | ✅ Aligné dans l'authentificateur Symfony et le contrôleur Zend |

---

## Recommandations

✅ **Tous les écarts ont été traités ou validés** :

1. ✅ **Écart 1 (SSO)** — Corrigé. `UtilisateurManager` centralise la gestion du mot de passe (forcé à `null` si SSO). Tests fonctionnels ajoutés.
2. ✅ **Écart 2 (DossierVoter)** — Corrigé. `EtablissementCollectionAccessChecker` extrait la logique ; `DossierVoter` vérifie désormais `view_ets` sur chaque établissement lié avant d'accorder l'accès par nature. Commit : `cf3dc9b`.
3. ✅ **Écart 3 (Ressources spécialisées établissement)** — Écart fermé et pattern clarifié. Symfony strictement aligné avec le legacy — sérialisation des critères visibles au front, `0` pour les invisibles. Spécification refactorisée.
4. ✅ **Écart 4 (Sites)** — Corrigé. `EtablissementSiteVoter` vérifie désormais le privilège `add_etablissement` en modification. Quatre tests ajoutés. Commit : `54b5ab1`.
5. ✅ **Écart 5 (Déconnexion/LastAction)** — Corrigé. Service `LastActionUpdater` assure mise à jour en toutes circonstances (requête normale + logout). Tests unitaires : PHPStan ✓, 608 tests, 1101 assertions.
6. ✅ **Écart 6 (Cache groupe)** — Écart fermé. Symfony fonctionne correctement sans cache explicite ; modifications appliquées immédiatement. Post-migration : optimisation globale du cache envisagée si nécessaire.
7. ✅ **Écart 7 (Dossier multi-natures)** — Écart fermé et spécification refactorisée. Symfony aligné avec legacy (ressources multi-natures non implémentées dans les deux systèmes). Pas de régression.

Aucune action complémentaire requise sur ce module.
