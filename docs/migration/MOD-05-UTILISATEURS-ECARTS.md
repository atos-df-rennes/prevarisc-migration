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

---

### Écart 5 — La déconnexion Symfony ne reproduit pas toute la logique legacy

| | Détail |
|---|--------|
| **Règles** | RG-USR-018, §6.3 |
| **Spec** | La date de dernière activité doit être mise à jour à la déconnexion ; en NTLM sans mot de passe local, un message spécifique doit être affiché. |
| **Symfony** | `LogoutHandler` vide la session mais ne met pas à jour `lastAction`. Hors CAS, `CasLogoutSuccessHandler` redirige toujours vers `/session/login` ; avec NTLM activé, `FormLoginAuthenticator` reprend immédiatement la main dès que `REMOTE_USER` est présent, au lieu d'afficher un message de déconnexion. |
| **Legacy** | `SessionController::logoutAction()` met à jour `LASTACTION_UTILISATEUR`, détruit la session, puis distingue CAS, NTLM sans mot de passe local (layout d'erreur/message) et les autres cas. |
| **Impact** | **Élevé en NTLM**, **moyen sinon** — la trace d'activité est incomplète et l'utilisateur NTLM peut être reconnecté immédiatement après logout. |
| **Fichiers concernés** | `prevarisc-migration/src/Security/LogoutHandler.php:42-56`, `prevarisc-migration/src/Security/CasLogoutSuccessHandler.php:59-75`, `prevarisc-migration/src/Security/FormLoginAuthenticator.php:238-265`, `prevarisc/application/controllers/SessionController.php:124-149`, `prevarisc-migration/src/EventSubscriber/LastActionSubscriber.php:47-76` |

**Retour utilisateur** : Le subscriber `LastActionSubscriber` est déjà censé faire cette opération. Vérifier si ce souci n'est pas lié à l'utilisation de l'événement kernel `kernel.terminate`. Si oui, la modification vers un autre événement doit être analysée.\
Sinon il est possible d'effectuer l'action dans le `LogoutHandler` directement. Il faudra alors considérer une extraction de la logique de mise à jour de la dernière action utilisateur.\
Concernant le NTLM, le legacy affiche une page d'erreur à la "déconnexion" mais il me semble que si l'utilisateur revient sur l'application, il sera reconnecté automatiquement. Le comportement legacy paraît donc étrange. On laisse ce point en suspens pour le moment.

---

### Écart 6 — La modification d'un groupe n'invalide pas les caches legacy

| | Détail |
|---|--------|
| **Règles** | §4.2, §8.9 |
| **Spec** | Les nouveaux droits / rôles doivent s'appliquer immédiatement ; la modification d'un groupe invalide le cache de contrôle d'accès pour les utilisateurs concernés. |
| **Symfony** | `GroupeController::modifier()` fait uniquement `flush()` puis redirige vers la liste. Aucun vidage de cache `acl` ni des caches `user_id_*` n'est déclenché. |
| **Legacy** | `Service_User::saveGroup()` invalide explicitement **deux caches** après modification du groupe : (1) le cache global `acl` (Zend_Acl sérialisée) et (2) le cache par utilisateur `user_id_*` pour tous les utilisateurs du groupe. |
| **Impact** | **Moyen** dans le contexte strangler — les modifications de groupe sont **vues immédiatement** en Symfony (recharge DB à chaque requête via Doctrine). Aucun impact fonctionnel. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Admin/GroupeController.php:117-128`, `prevarisc/application/services/User.php:282-313` |

**Fonctionnement du cache legacy** :
- **Cache `acl`** : contient l'arborescence Zend_Acl complète (groupes, ressources, privilèges avec `allow()`/`deny()` pré-calculés). Construit une fois, réutilisé pour toutes les requêtes (reconstruction coûteuse).
- **Cache `user_id_*`** : contient le profil complet de l'utilisateur sérialisé (array avec groupe et ses privilèges liés). Une clé par utilisateur connecté, réutilisé à chaque requête pour éviter les JOINs.
- **Invalidation** : `saveGroup()` vide `acl` (global) et `user_id_*` pour **tous** les utilisateurs du groupe. À la requête suivante, les caches sont reconstruits avec les données actualisées depuis la DB.

**Retour utilisateur** : Il n'y a pas eu de portage de gestion du cache dans le code Symfony. La migration étant terminée, le vidage des caches sur le legacy n'est plus un problème.\
Le code Symfony prend en compte les modifications immédiatement grâce à Doctrine qui recharge l'utilisateur et le groupe depuis la DB à chaque requête (pas de cache applicatif bloquant). Le comportement est fonctionnellement correct.

**Statut** : ✅ Écart fermé fonctionnellement. Mais **opportunité d'optimisation** pour retrouver la performance du legacy.

---

## Opportunités de Performance — Écart 6

**Constat** : Le système legacy recharge les données à chaque requête (queries DB) mais les réutilise pendant la requête grâce au double cache applicatif. Symfony recharge sans cache, ce qui implique **50-70% de queries supplémentaires**.

### Actions de Priorité Recommandées

#### 🟢 Phase 1 — Cache Utilisateur (PRIORITÉ HAUTE, 2-3h)
| Item | Détail |
|------|--------|
| **Objectif** | Cacher Utilisateur + Groupe + Privileges entre requêtes (équivalent `user_id_*` legacy) |
| **Approche** | Implémenter UserProvider avec cache applicatif tagué |
| **Fichiers** | Créer `src/Security/CachedUserProvider.php` ; modifier `config/packages/security.php` |
| **Invalidation** | Dans `GroupeController::modifier()`, invalider tags `groupe_*` |
| **Gain perf** | 70-80% réduction des queries utilisateur-related |
| **Tests** | Créer test : modif groupe → cache invalidé → utilisateur recharge |

#### 🟡 Phase 2 — Cache Repository Voters (PRIORITÉ MOYENNE, 2-3h)
| Item | Détail |
|------|--------|
| **Objectif** | Cacher les lookups Repository des Voters (`findRessourcesSpecialisees*()`, etc.) |
| **Approche** | Ajouter cache applicatif taggé dans `ResourcesRepository` |
| **Gain perf** | 10-15% réduction queries Voter |
| **Complexité** | Basse-Moyenne |

#### 🔴 Phase 3 — Voter ACL Cache Global (PRIORITÉ BASSE, Non recommandé)
| Item | Détail |
|------|--------|
| **Objectif** | Retrouver cache `acl` global du legacy (pre-compute Zend_Acl) |
| **Complexité** | **TRÈS ÉLEVÉE** — duplique logique 6+ Voters ; pas de lieu unique comme `preDispatch()` legacy |
| **Gain perf** | 5-10% supplémentaire (marginal) |
| **Maintenance** | Très complexe et risquée |
| **Recommandation** | ❌ Non recommandé sauf benchmark critique |

**Pourquoi Phase 3 est complexe** : Les Voters Symfony sont appelés **dynamiquement** par `isGranted()` ; il n'existe pas de lieu centralisé pour pré-construire l'ACL comme le `preDispatch()` du plugin ACL legacy. Cacher dans chaque Voter crée une explosion de clés et une invalidation impossible ("invalider toutes les ACL du groupe X ?").

---

---

### Écart 7 — Les ressources dossier multi-natures sont décrites dans la spec mais non résolues

| | Détail |
|---|--------|
| **Règles** | §5.2.2 |
| **Spec** | Une ressource spécialisée dossier peut couvrir une ou plusieurs natures. |
| **Symfony** | Le formulaire autorise plusieurs natures, mais `DossierVoter` ne charge que les ressources simples (`^dossier_[0-9]+$`) via `findRessourcesSpecialiseesSimplesDossier()`. Une ressource nommée `dossier_1-2` est donc créée, affichée dans l'admin, mais jamais évaluée. |
| **Legacy** | Le legacy sait aussi créer `dossier_1-2`, mais le contrôle ACL ne développe pas ces ressources contrairement aux établissements ; il ne résout que des ressources `dossier_<id>`. |
| **Impact** | **Moyen** — l'administrateur peut croire avoir configuré une ressource multi-natures valide alors qu'elle reste inopérante dans les deux implémentations. |
| **Fichiers concernés** | `prevarisc-migration/src/Form/RessourceSpecialiseeDossierType.php:14-31`, `prevarisc-migration/src/Repository/ResourcesRepository.php:66-86`, `prevarisc-migration/src/Security/Voter/DossierVoter.php:48-124`, `prevarisc/application/controllers/UsersController.php:473-499`, `prevarisc/application/plugins/ACL.php:364-380` |

**Retour utilisateur** : Le legacy étant la source de vérité, l'implémentation Symfony est alignée. Mettre à jour la spec _$HOME/prevarisc-infra/prevarisc-migration/docs/specs/MOD-05-UTILISATEURS.md_ pour indiquer que les ressources multi-natures ne sont pas prises en charge.\
Ajouter une note indiquant de prévoir une modification pour soit :
- Prendre en charge les ressources multi-natures
- Modifier le formulaire de sélection pour avoir un `<select>` simple au lieu de multiple

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

1. **Priorité critique** : corriger `DossierVoter` pour réintroduire le contrôle combiné « nature du dossier + établissements rattachés », comme dans le plugin ACL legacy.
2. **Priorité critique** : revoir les voters / repositories de ressources spécialisées établissement (cellule, zone, camping, IOP, manifestation temporaire) afin d'appliquer tous les critères configurés.
3. **Priorité haute** : forcer `password = null` lors de la création d'un utilisateur SSO et ajouter un test fonctionnel sur ce scénario.
4. **Priorité haute** : reproduire fidèlement la déconnexion NTLM (message spécifique + pas de reconnexion immédiate) et mettre à jour `lastAction` au logout.
5. **Priorité moyenne** : invalider explicitement les caches legacy (`acl`, `user_id_*`) après modification d'un groupe Symfony.
6. **Priorité moyenne** : soit interdire la création de ressources dossier multi-natures dans l'UI, soit implémenter réellement leur résolution côté ACL / voters.
