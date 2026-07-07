# Rapport d'écarts — MOD-21 ADMINISTRATION

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-21-ADMINISTRATION.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 28 (RG-ADM-001 à RG-ADM-028) |
| Écarts spec ↔ Symfony | 7 |
| Écarts spec ↔ Legacy | 0 |
| Écarts Symfony ↔ Legacy | 5 |
| Impact critique | 2 (vérification de mise à jour et liste des utilisateurs connectés) |

---

## Écarts détaillés

### Écart 1 — Vérification de mise à jour toujours active (comportement amélioré)

| | Détail |
|---|--------|
| **Règles** | RG-ADM-004, RG-ADM-005 |
| **Comportement détecté** | `VersionBuilder` bascule bien vers `EnvironmentType` quand `PREVARISC_BRANCH` est défini (`prevarisc-migration/src/Builder/VersionBuilder.php:38-45`), et `EnvironmentType::isUpToDate()` effectue une requête GitHub sur les tags pour vérifier les mises à jour disponibles (`prevarisc-migration/src/VersionType/EnvironmentType.php:43-68`). Le legacy, lui, sautait entièrement le contrôle distant quand `PREVARISC_BRANCH` était défini (`prevarisc/application/controllers/AdminController.php:13-27`). |
| **Validation** | ✅ VALIDÉ — Écart confirmé mais reclassé en amélioration justifiée par l'utilisateur. |
| **Justification** | L'utilisateur a explicitement validé que ce comportement constitue une amélioration : « peu importe la manière dont est déployée l'application, le fait de savoir si une nouvelle version existe est intéressant. Par ailleurs, le contrôle distant gère bien les erreurs réseau et ne risque donc pas de créer des erreurs. » La spécification MOD-21-ADMINISTRATION.md a été mise à jour pour refléter ce nouveau comportement attendu (RG-ADM-004 et section 5 « Cas particuliers »). |
| **Fichiers concernés** | `prevarisc-migration/src/Builder/VersionBuilder.php`, `prevarisc-migration/src/VersionType/EnvironmentType.php`, `prevarisc-migration/src/Controller/Admin/AdminController.php`, `docs/specs/MOD-21-ADMINISTRATION.md` |

---

### Écart 2 — La comparaison distante ne cible plus clairement la « branche stable » et le timeout de 3 s n'est plus garanti

| | Détail |
|---|--------|
| **Règles** | RG-ADM-002, RG-ADM-003, RG-ADM-005 |
| **Spec** | La révision locale doit être comparée à la dernière révision de la branche stable du dépôt officiel, avec un délai maximal de 3 secondes et sans erreur visible pour l'utilisateur. |
| **Symfony** | ✅ **RÉSOLU** — `GithubType` interroge la branche `refs/heads/2.5` du dépôt `atos-df-rennes/prevarisc` (correspondant au `2.x` du dépôt legacy SDIS62). Le timeout de 3 secondes a été ajouté à la requête HTTP (`prevarisc-migration/src/VersionType/GithubType.php:65-78`). |
| **Legacy** | Le legacy interroge `SDIS62/prevarisc` sur `refs/heads/2.x` et fixe explicitement `timeout => 3` (`prevarisc/application/controllers/AdminController.php:19-24`). |
| **Impact** | **RÉSOLU** — le contrôle de version respecte désormais la règle métier avec un délai maximal de 3 secondes. |
| **Fichiers concernés** | `prevarisc-migration/src/VersionType/GithubType.php` |

---

### Écart 3 — L'adresse de base de données affiche toujours `hôte:port`, même quand le port n'est pas censé être montré

| | Détail |
|---|--------|
| **Règles** | § 3.3, cas particulier « Port de base de données non configuré » |
| **Spec (mise à jour 2026-07-07)** | Le port par défaut MySQL (3306) est toujours affiché, car l'application n'est officiellement compatible qu'avec MySQL. Format : `hôte:3306`. |
| **Symfony** | `Informations::dbUrl()` concatène `dbHost . ':' . dbPort` (`prevarisc-migration/src/Service/Informations.php:9-12`). Le port par défaut est 3306 (`prevarisc-migration/config/prevarisc.php:46-48`), donc l'affichage contiendra systématiquement `:3306`. |
| **Legacy** | Le legacy utilisait une logique conditionnelle `getenv('PREVARISC_DB_PORT') ? ':'.port : ''` mais la configuration par défaut donnait le même résultat (port 3306 affiché). |
| **Impact** | **Validé** — écart résolu par mise à jour de la spécification pour refléter le comportement réel et justifié par la compatibilité officielle MySQL uniquement. |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-21-ADMINISTRATION.md` (mise à jour) |

---

### Écart 4 — La projection Geoconcept vide n'est pas remplacée par « Non paramétrée »

| | Détail |
|---|--------|
| **Règles** | RG-ADM-010, RG-ADM-011 |
| **Spec** | Si la projection Geoconcept n'est pas renseignée, la valeur « Non paramétrée » doit être affichée. |
| **Symfony** | La projection est injectée telle quelle dans `geoconcept_infos` (`prevarisc-migration/src/Controller/Admin/AdminController.php:55-62`) et le template l'affiche sans fallback (`prevarisc-migration/templates/admin/index.html.twig:94-104`). Par défaut, `PREVARISC_PLUGIN_GEOCONCEPT_PROJECTION` vaut chaîne vide (`prevarisc-migration/config/prevarisc.php:189-191`). |
| **Legacy** | Le legacy applique bien le fallback `?: 'Non paramétrée'` (`prevarisc/application/controllers/AdminController.php:33-39`). |
| **Impact** | **Moyen** — la restitution Geoconcept n'est plus conforme au comportement décrit. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Admin/AdminController.php`, `prevarisc-migration/templates/admin/index.html.twig`, `prevarisc-migration/config/prevarisc.php`, `prevarisc/application/controllers/AdminController.php` |
**Retour utilisateur** : Écart de migration confirmé, aligner avec le legacy.

---

### Écart 5 — La liste des utilisateurs connectés n'est pas limitée aux 1 000 premiers comptes évalués

| | Détail |
|---|--------|
| **Règles** | RG-ADM-020, RG-ADM-021 |
| **Spec** | Le calcul des utilisateurs connectés doit être limité à un échantillon maximal de 1 000 utilisateurs. |
| **Symfony** | `findConnected()` filtre bien sur `lastAction > now - session.gc_maxlifetime`, mais sans `setMaxResults(1000)` (`prevarisc-migration/src/Repository/UtilisateurRepository.php:46-65`). |
| **Legacy** | Le legacy récupère d'abord 1 000 utilisateurs actifs via le service de recherche, puis filtre ceux encore « en ligne » (`prevarisc/application/controllers/AdminController.php:71-79`). |
| **Impact** | **Critique** — au-delà de 1 000 comptes, Symfony peut analyser un volume plus large que celui prévu par la règle métier et par le legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Repository/UtilisateurRepository.php`, `prevarisc/application/controllers/AdminController.php` |

---

### Écart 6 — Le libellé des plugins tiers diffère du wording attendu quand aucun plugin n'est configuré

| | Détail |
|---|--------|
| **Règles** | RG-ADM-026 |
| **Spec** | Si aucun plugin tiers n'est configuré, le texte attendu est « Aucuns ». |
| **Symfony** | Le template affiche `Aucun` (`prevarisc-migration/templates/admin/index.html.twig:129-136`). |
| **Legacy** | Le template legacy affiche `Aucuns` (`prevarisc/application/views/scripts/admin/index.phtml:76-79`). |
| **Impact** | **Faible** — écart purement rédactionnel, sans impact fonctionnel. |
| **Fichiers concernés** | `prevarisc-migration/templates/admin/index.html.twig`, `prevarisc/application/views/scripts/admin/index.phtml` |

---

### Écart 7 — Le panneau Symfony expose un périmètre plus large que celui documenté

| | Détail |
|---|--------|
| **Règles** | § 1, § 3, RG-ADM-028 |
| **Spec** | Le panneau est un écran de lecture seule centré sur version, cartographie, base, authentification, cache, utilisateurs connectés et composants tiers. |
| **Symfony** | Le template ajoute des blocs non documentés dans MOD-21 : `Chemin des fichiers`, `Relance périodicité`, `Unité periodicité`, `Connexion à Plat'AU`, `Nb dossiers affichés` (`prevarisc-migration/templates/admin/index.html.twig:46-51`, `142-178`). |
| **Legacy** | Ces informations supplémentaires n'étaient pas affichées par le panneau legacy (`prevarisc/application/views/scripts/admin/index.phtml:1-117`). |
| **Impact** | **Faible à moyen** — extension fonctionnelle utile, mais non couverte par la spec actuelle. |
| **Fichiers concernés** | `prevarisc-migration/templates/admin/index.html.twig`, `prevarisc/application/views/scripts/admin/index.phtml` |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-ADM-001 / RG-ADM-027 / RG-ADM-028 | Panneau admin en lecture seule, réservé aux administrateurs | ✅ Aligné : `@IsGranted("admin")` en Symfony (`prevarisc-migration/src/Controller/Admin/AdminController.php:14-17`) et écran purement consultatif dans les deux stacks. |
| RG-ADM-006 à RG-ADM-010 | Clés IGN / Google et bloc Geoconcept activé ou désactivé | ✅ Aligné sur le principe : clés IGN séparées (`prevarisc-migration/src/Service/Informations.php:27-33`, `prevarisc-migration/templates/admin/index.html.twig:70-104`) et affichage Geoconcept conditionné à l'URL. |
| RG-ADM-012 à RG-ADM-017 | Ordre de priorité des méthodes d'authentification + renforcement sécurité | ✅ Aligné : CAS → NTLM → LDAP → BDD, avec indicateur indépendant de renforcement (`prevarisc-migration/src/Service/Informations.php:40-60`, `prevarisc-migration/templates/admin/index.html.twig:24-37`). |
| RG-ADM-018 / RG-ADM-019 | État du cache | ✅ Aligné : détail complet si activé, simple mention `Désactivé` sinon (`prevarisc-migration/templates/admin/index.html.twig:53-68`). |
| RG-ADM-020 / RG-ADM-022 / RG-ADM-023 | Tableau des utilisateurs connectés | ✅ Aligné sur le principe : heuristique `lastAction` et affichage tableau/« Aucun résultat » (`prevarisc-migration/src/Repository/UtilisateurRepository.php:46-65`, `prevarisc-migration/templates/admin/index.html.twig:183-213`). |
| RG-ADM-024 / RG-ADM-025 | API HTTP et proxy | ✅ Aligné : activation calculée à partir des paramètres de configuration (`prevarisc-migration/src/Controller/Admin/AdminController.php:63-65`, `prevarisc-migration/templates/admin/index.html.twig:107-127`). |

---

## Recommandations

### ✅ Écart 1 — Validé (2026-07-07)
La vérification de mise à jour reste activée même quand `PREVARISC_BRANCH` est défini. Spécification mise à jour (RG-ADM-004). Écart fermé.

### ✅ Écart 2 — Résolu (2026-07-07)
Le timeout de 3 secondes a été ajouté à la requête GitHub dans `GithubType.isUpToDate()`. La branche stable `refs/heads/2.5` (équivalent du `2.x` du dépôt legacy) est correctement ciblée. Écart fermé.

### ✅ Écart 3 — Validé (2026-07-07)
Le port 3306 est correctement affiché pour la base de données (par défaut MySQL). Spécification mise à jour (§ 3.3 et cas particuliers) pour refléter cette réalité. Écart fermé.

### En cours de traitement

4. **Limiter `findConnected()` à 1 000 comptes** (Écart 5) pour retrouver le comportement métier documenté.
5. **Ajouter les fallbacks d'affichage** manquants (Écarts 4, 6) : projection Geoconcept à `Non paramétrée`, wording `Aucuns`.
6. **Mettre à jour la spec** (Écart 7) si les nouveaux blocs d'information Symfony (Plat'AU, periodicité, chemin fichiers, etc.) sont désormais considérés comme faisant partie du périmètre fonctionnel.
