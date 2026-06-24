# Rapport d'écarts — MOD-02 DOSSIERS

> **Date** : 2026-05-22 (mis à jour le 2026-06-24)
> **Spec source** : `docs/specs/MOD-02-DOSSIERS.md`
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|---|---|
| Règles de gestion analysées | 47 |
| Écarts spec ↔ Symfony | 5 |
| Écarts spec ↔ Legacy | 3 |
| Écarts Symfony ↔ Legacy | 2 |
| Écarts corrigés | 3 (Écarts 3, 5, Suppression) |
| Écarts documentés / non-régressions | 3 (Écarts 1, 2 & 4) |

> **Note Strangler résolue** : ~~la suppression d'un dossier reste volontairement branchée sur le legacy~~ — migration complète le 2026-06-24 (`DossierSuppressionService` + route `dossier_supprimer`).

---

## Écarts détaillés

### Écart 1 — Multi-natures non prises en charge

> **Statut** : 📋 Documenté — non-régression (ni le legacy ni Symfony n'implémentent la règle)

| | Détail |
|---|---|
| **Règles** | RG-DOS-003 |
| **Spec** | Les champs affichés doivent être l'union des champs de chaque nature lorsqu'un dossier porte plusieurs natures. |
| **Symfony** | Le modèle et le formulaire imposent une seule nature active : `Dossier::getNature()` retourne uniquement le premier élément, `setNature()` réinitialise la collection, et `DossierType` expose un `EntityType` simple, non multiple. |
| **Legacy** | Le contrôleur n'enregistre qu'un seul `selectNature` et les accès à la nature active utilisent `fetchRow()` / `getDossierNaturesId()` ; le comportement multi-natures n'est pas exploité non plus dans le legacy. |
| **Impact** | **Informatif** — ni le legacy ni Symfony n'implémentent cette règle : il ne s'agit pas d'une régression de migration. La spec décrit un comportement jamais réalisé dans aucune des deux bases de code. Toute évolution s'appuyant sur l'union de plusieurs natures nécessitera une refonte fonctionnelle et technique de zéro. |
| **Fichiers concernés** | `prevarisc-migration/src/Entity/Dossier.php`, `prevarisc-migration/src/Form/Dossier/DossierType.php`, `prevarisc/application/controllers/DossierController.php`, `prevarisc/application/models/DbTable/DossierNature.php` |

### Écart 2 — Masquage du champ `AVIS_COMMISSION` selon les cases cochées

> **Statut** : 📋 Documenté — non-régression (comportement ISO entre legacy et Symfony)

| | Détail |
|---|---|
| **Règles** | RG-DOS-015 |
| **Spec (à affiner)** | La spec indique que l'avis et la date de commission/visite doivent être masqués en cas d'avis différé. En réalité, seul le champ `AVIS_COMMISSION` est masqué (pas la date), et uniquement en **mode affichage** (consultation), pas en mode édition. Le masquage s'applique selon les cases cochées : différé, incomplet, hors délai, absence de quorum, ne peut se prononcer. |
| **Symfony** | La méthode `index()` calcule `$afficherAvis = !$dossier->hasAvisDiffere() && !$dossier->hasAbsenceDeQuorum() && !$dossier->isHorsDelai() && !$dossier->isIncomplet() && !$dossier->hasNePeutSePrononcer()` et passe ce flag au template de consultation — le champ `AVIS_COMMISSION` est bien masqué en affichage selon toutes les cases pertinentes. |
| **Legacy** | Le contrôleur calcule `$afficheAvis` avec `DIFFEREAVIS_DOSSIER` et les autres drapeaux, et le template `index.phtml` vérifie `1 == $this->afficheAvis` pour afficher `AVIS_COMMISSION` ; aucune logique de masquage n'est appliquée en mode édition dans aucun des deux systèmes. |
| **Impact** | **Aucun** — le comportement Symfony est ISO avec le legacy sur ce point. L'écart initial résultait d'une mauvaise lecture de la spec : la spec était trop large (« avis + date masqués en édition »), la réalité est plus précise (« `AVIS_COMMISSION` masqué en affichage uniquement »). Aucune correction de code requise. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Dossier/DossierController.php`, `prevarisc-migration/templates/dossier/index.html.twig`, `prevarisc/application/controllers/DossierController.php`, `prevarisc/application/views/scripts/dossier/index.phtml` |

### Écart 3 — Le verrouillage n'interdit pas toutes les modifications côté serveur

> **Statut** : ✅ Corrigé — gardes `hasVerrou()` ajoutés sur toutes les routes de modification manquantes

| | Détail |
|---|---|
| **Règles** | RG-DOS-021 |
| **Spec** | Quand un dossier est verrouillé, toute modification doit être interdite : formulaire principal, documents consultés, textes applicables, etc. |
| **Symfony (avant)** | Les prescriptions, contacts, liaisons, pièces jointes et effectifs avaient des gardes explicites, mais `DossierController::edit()`, `editDescriptif()`, `editTextesApplicables()` et le CRUD des documents consultés n'avaient pas de contrôle `hasVerrou()` ; l'UI masquait les boutons, sans blocage serveur homogène. |
| **Symfony (après)** | `DossierController::edit()`, `editDescriptif()`, `editTextesApplicables()` et les cinq méthodes de `DossierDocumentsConsultesController` (`addDocumentConsulte()`, `deleteDocumentConsulte()`, `editDocumentConsulte()`, `deleteDocumentAjoute()`, `editDocumentAjoute()`) lèvent désormais `AccessDeniedException` si `$dossier->hasVerrou()` est vrai. |
| **Legacy** | Même logique partielle : l'interface masque les actions quand `VERROU_DOSSIER = 1`, mais la sauvegarde principale recharge le dossier et persiste les champs sans contrôle serveur explicite sur le verrou. La correction Symfony va au-delà du legacy sur ce point. |
| **Impact** | ~~**Élevé**~~ — **Résolu** : les routes de modification sont désormais protégées côté serveur contre les accès directs ou POST forgés sur un dossier verrouillé. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Dossier/DossierController.php`, `prevarisc-migration/src/Controller/Dossier/DossierDocumentsConsultesController.php` |

### Écart 4 — Le champ CNE n'est pas exposé dans Symfony

> **Statut** : 📋 Documenté — non-régression (champ absent du formulaire legacy aussi)

| | Détail |
|---|---|
| **Règles** | RG-DOS-041 |
| **Spec** | Les booléens métier, dont `CNE`, doivent être explicitement positionnés à 0/1 à chaque sauvegarde. |
| **Symfony** | L'entité conserve `CNE_DOSSIER` (`celluleNonExploitee`), mais aucun champ Symfony dédié n'est déclaré dans `DossierType`, et la configuration d'affichage dynamique ne prévoit pas `CNE`. |
| **Legacy** | Bien que le contrôleur legacy référence `CNE_DOSSIER` (lines 910-912) et que le template `index.phtml` l'affiche (line 2053), ce champ n'est **jamais** répertorié dans `$listeChamps` (ligne 42 du contrôleur), quelle que soit la nature. Il n'est donc jamais affiché dans le formulaire legacy et n'est pas modifiable depuis l'UI legacy non plus. |
| **Impact** | **Aucun** — le comportement Symfony (entité présente, pas de champ dans le formulaire) est ISO avec le legacy. Il ne s'agit pas d'une régression de migration. La spec décrit un comportement jamais exposé via l'UI dans aucune des deux bases de code. |
| **Fichiers concernés** | `prevarisc-migration/src/Entity/Dossier.php`, `prevarisc-migration/src/Form/Dossier/DossierType.php`, `prevarisc/application/controllers/DossierController.php`, `prevarisc/application/views/scripts/dossier/index.phtml` |

### Écart 5 — L'accès à un dossier Plat'AU désactivé n'est pas bloqué en Symfony

> **Statut** : ✅ Corrigé — guard `$deactivatePlatau` ajouté dans `index()` et `edit()`

| | Détail |
|---|---|
| **Règles** | RG-DOS-044 |
| **Spec** | Si l'intégration Plat'AU est désactivée, l'accès à un dossier lié à Plat'AU doit générer une erreur. |
| **Symfony (avant)** | Le contrôleur dossier affichait les données Plat'AU dès qu'une consultation était liée, via `getPlatauDataForView()`, sans contrôle sur le flag global de désactivation. |
| **Symfony (après)** | `DossierController` reçoit le paramètre `bool $deactivatePlatau` (injecté depuis `%app.deactivate_platau%` via `services.php`). Les méthodes `index()` et `edit()` vérifient `$this->deactivatePlatau && null !== $dossier->getConsultationPlatau()` et lèvent une `\Exception` si l'intégration est désactivée et le dossier lié à Plat'AU, alignant le comportement avec le legacy. |
| **Legacy** | Le contrôleur legacy vérifie `PREVARISC_DEACTIVATE_PLATAU` et lève une exception si un dossier lié Plat'AU est consulté alors que l'intégration est désactivée. |
| **Impact** | ~~**Moyen**~~ — **Résolu** : la divergence de comportement est corrigée, le contrôleur Symfony réaligne RG-DOS-044 avec le legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Dossier/DossierController.php`, `prevarisc-migration/config/services.php` |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|---|---|---|
| RG-DOS-001, RG-DOS-002 | Type / nature obligatoires et natures ordonnées | ✅ `DossierType` impose type+nature et `DossierNatureListeRepository::findByTypeOrderedByOrdre()` aligne le filtrage/tri des natures. |
| RG-DOS-004, RG-DOS-005, RG-DOS-006 | Création depuis un établissement | ✅ Le créateur est renseigné, l'établissement d'origine est lié, et les contacts métier sont copiés à la création. |
| RG-DOS-009, RG-DOS-013, RG-DOS-014, RG-DOS-036 | Initialisation métier à la création | ✅ Symfony reprend prescriptions par défaut, textes applicables, documents de dernière VP et crée les 5 attestations de levée comme le legacy. |
| RG-DOS-011, RG-DOS-012 | Reprise de prescriptions / prescriptions levées | ✅ Les prescriptions reprises gardent `dossierReprise`, les levées et justificatifs sont affichés, et les prescriptions levées sont filtrées pour les documents générés. |
| RG-DOS-015 | Masquage `AVIS_COMMISSION` en consultation | ✅ `index()` calcule `$afficherAvis` selon les mêmes drapeaux que le legacy (différé, incomplet, hors délai, absence quorum, ne peut se prononcer) ; le champ n'est masqué qu'en affichage, pas en édition — ISO legacy. |
| RG-DOS-016, RG-DOS-017, RG-DOS-018, RG-DOS-019, RG-DOS-034, RG-DOS-035 | Dossier donnant avis et groupe de visite | ✅ L'affectation / désaffectation du dossier donnant avis, la répercussion aux établissements enfants et l'alerte VAO sur établissement en statut projet sont portées. |
| RG-DOS-022, RG-DOS-023 | Verrouillage : identité et droit par nature | ✅ Le verrou enregistre l'utilisateur et le droit `verrouillage_dossier` est bien résolu via les ressources spécialisées par nature. |
| RG-DOS-024, RG-DOS-025, RG-DOS-028, RG-DOS-046 | Suppression logique, rétablissement, cache | ✅ **Suppression désormais entièrement migrée** (2026-06-24) : `DossierSuppressionService` pose `DATESUPPRESSION_DOSSIER` + `DELETED_BY`, supprime les affectations commission via `DossierAffectationRepository::deleteAllAffectations()`, recalcule le dossier donnant avis pour tous les établissements liés. Cache Symfony invalidé via `searchCache->invalidateTags(['search'])` ; cache Zend nettoyé via `LegacyController::viderCacheEtablissementAction()`. Rétablissement inchangé via `ElementsSupprimesController`. |
| RG-DOS-029, RG-DOS-030, RG-DOS-031, RG-DOS-032 | Affectation commission / visite | ✅ `DossierAffectationService` gère la synchronisation des dates, la suppression des affectations et la remise à zéro des horaires/ordre lors d'un changement de date. |
| RG-DOS-037 | Numéros d'urbanisme et documents consultés | ✅ Les numéros d'urbanisme restent saisis en collection dédiée, affichés en consultation et exploités dans la recherche / génération documentaire. |
| RG-DOS-041 | Champ CNE | ✅ Le champ `CNE_DOSSIER` est absent du formulaire Symfony comme du formulaire legacy (`$listeChamps`) — comportement ISO, pas de régression. |
| RG-DOS-042 | Transfert / suppression des documents consultés | ✅ `DocumentConsulteTransferService` reproduit la compatibilité legacy entre types/natures et purge ou transfère les documents selon le changement de nature. |
| RG-DOS-043, RG-DOS-045, RG-DOS-047 | Affichage Plat'AU, export PJ, notifications | ✅ Le dossier Symfony affiche les statuts Plat'AU, marque les PJ à exporter et déclenche les flashs de notification lors d'un changement d'avis propagé. |

---

## Recommandations

1. ~~Corriger en priorité le verrouillage serveur (RG-DOS-021) sur les routes de modification encore ouvertes en accès direct.~~ **Résolu** (Écart 3).
2. ~~Ajouter un garde explicite sur la désactivation Plat'AU dans le module Dossier Symfony pour réaligner RG-DOS-044.~~ **Résolu** (Écart 5).
3. Mettre à jour la spec RG-DOS-015 pour distinguer explicitement le mode affichage (masquage de `AVIS_COMMISSION` selon les cases cochées) du mode édition (pas de masquage), et préciser que seul `AVIS_COMMISSION` est concerné, pas la date de commission/visite.
4. Documenter dans la spec ou le manifest que la suppression reste en strangler via le legacy, afin d'éviter de la signaler à tort comme non migrée lors des prochains audits.
5. Documenter dans la spec que RG-DOS-003 (multi-natures) et RG-DOS-041 (CNE) décrivent des comportements non implémentés dans aucune base de code, pour clarifier qu'il ne s'agit pas de régressions à traiter en migration.

---

## Suppression d'un dossier — Rapport d'écarts (2026-06-24)

### Fichiers analysés

**Legacy :**
- `prevarisc/application/controllers/DossierController.php` : `deleteAction()` (lignes 3248–3282)
- `prevarisc/application/services/Dossier.php` : `delete()` (lignes 919–944)
- `prevarisc/application/models/DbTable/DossierAffectation.php` : `deleteDateDossierAffect()`
- `prevarisc/application/models/DbTable/EtablissementDossier.php` : `getEtablissementListe()`
- `prevarisc/application/views/scripts/dossier/index.phtml` (modal de confirmation)

**Symfony :**
- `prevarisc-migration/src/Service/Dossier/DossierSuppressionService.php` (nouveau)
- `prevarisc-migration/src/Controller/Dossier/DossierController.php` : action `supprimer()`
- `prevarisc-migration/templates/dossier/index.html.twig` : modal mise à jour
- `prevarisc/application/controllers/LegacyController.php` : `viderCacheEtablissementAction()` (nouveau)

**Niveau de confiance global : ⚠️**

---

### ✅ PORT DIRECT — aucune vérification nécessaire

- Suppression logique : `$dossier->DATESUPPRESSION_DOSSIER = $date->format('Y-m-d')` → `setDateSuppression(new \DateTime())` (même sémantique)
- Auteur suppression : `Zend_Auth::getInstance()->getIdentity()['ID_UTILISATEUR']` → `$this->getUser()` + `setDeletedBy($user)` (même résultat)
- Suppression affectations commission : `deleteDateDossierAffect()` → `DossierAffectationRepository::deleteAllAffectations()` (même table `dossieraffectation`, même filtre `ID_DOSSIER_AFFECT = ?`)
- Flash message : `flashMessenger` → `addFlash()` (port mécanique Zend → Symfony)
- Redirection vers recherche : `/search/dossier?objet=&page=1` → `redirectToRoute('search_dossier')` (équivalent fonctionnel)

---

### ⚠️ ADAPTATIONS — vérifier ces points

**Écart S1 — Code mort non porté : `$date` et `$uniqueEtab`**

| | Détail |
|---|---|
| **Legacy** | `Service_Dossier::delete($idDossier, $date = null, $uniqueEtab = false)` — `$uniqueEtab=true` uniquement depuis `deleteByEtab()`, jamais depuis `deleteAction()`. |
| **Symfony** | Paramètres absents de `DossierSuppressionService::supprimer()`. |
| **Impact** | Aucune régression depuis la suppression unitaire. |
| **Scénario de test** | Supprimer un dossier depuis la page dossier → vérifier qu'il disparaît de la recherche. |

**Écart S2 — Recalcul dossier donnant avis : exclusion explicite vs filtre DB**

| | Détail |
|---|---|
| **Legacy** | `getDernierIdDossierDonnantAvis()` appelé APRÈS `delete()` : dossier supprimé exclu par `DATESUPPRESSION_DOSSIER IS NULL` en DB. |
| **Symfony** | `findLastDossierDonnantAvisForEtablissement($etab, $dossier)` appelé AVANT flush : dossier exclu explicitement via `excludeDossier`. Même résultat. |
| **Impact** | Fonctionnellement identique. |
| **Scénario de test** | Supprimer un dossier qui était `dossier donnant avis` d'un établissement → vérifier que `ID_DOSSIER_DONNANT_AVIS` est mis à jour (ou null) en BDD. |

**Écart S3 — Sécurité renforcée : CSRF + verrou + `view_doss`**

| | Détail |
|---|---|
| **Legacy** | GET sans CSRF, verrou non vérifié côté serveur, ACL Zend globale. |
| **Symfony** | POST avec CSRF (`dossier_suppression_<id>`), vérification `hasVerrou()`, `@Security("is_granted('delete_dossier') and is_granted('view_doss', dossier)")`. |
| **Impact** | Renforcement sécurité, non-régression fonctionnelle. |
| **Scénarios de test** | ① Dossier non verrouillé + privilège → suppression OK. ② Dossier verrouillé → 403. ③ Token CSRF invalide → 403. |

**Écart S4 — Invalidation cache : double canal**

| | Détail |
|---|---|
| **Legacy** | `cacheSearch->clean(ALL)` + `cache->remove('etablissement_id_<id>')`. |
| **Symfony** | `TagAwareCacheInterface::invalidateTags(['search'])` (Symfony) + redirect → `LegacyController::viderCacheEtablissementAction()` qui vide `etablissement_id_<id>` ET `cacheSearch` legacy. |
| **Impact** | Cohérence accrue ; les deux caches (Symfony et Zend) sont nettoyés. |
| **Scénario de test** | Supprimer un dossier → vérifier que `/rechercher/dossier` Symfony ne le liste plus. |
