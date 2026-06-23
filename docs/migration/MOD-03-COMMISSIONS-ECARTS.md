# Rapport d'écarts — MOD-03 COMMISSIONS

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-03-COMMISSIONS.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 40 (RG-COM-001 à RG-COM-060, numérotation discontinue) + revue des sections 4.7 et 6 |
| Écarts spec ↔ Symfony | 6 |
| Écarts spec ↔ Legacy | 5 |
| Écarts Symfony ↔ Legacy | 3 |
| Impact critique | 1 (ODJ groupement non filtré par membre dans les deux implémentations) |

---

## Écarts détaillés

### Écart 1 — Les règles de compétence ne respectent pas la combinaison de critères « tous obligatoires » de la spec

| | Détail |
|---|--------|
| **Règles** | RG-COM-012, §6 |
| **Spec** | Une règle doit combiner **tous** les critères obligatoires : zone géographique, catégories ERP, types d'activité, classes IGH, local à sommeil, étude/visite. |
| **Symfony** | Le formulaire `CommissionRegleType` rend tous les critères optionnels (`required=false`). La persistance stocke bien `etudeVisite`, mais le moteur `CommissionRegleCompetenceService` ne l'utilise jamais pour le matching : seuls commune/groupement, local à sommeil, type, catégorie et classe sont testés. |
| **Legacy** | Le legacy fonctionne déjà de la même manière : `getCommission()`, `getCommissionIGH()` et `getRegles()` n'intègrent pas le critère étude/visite et n'imposent pas formellement que tous les axes soient renseignés. |
| **Impact** | **Élevé** — la spec décrit une règle plus stricte que les deux implémentations réelles ; le risque est d'affecter des établissements sur une règle incomplète ou plus large que prévu. |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md:391`, `prevarisc-migration/src/Form/CommissionRegleType.php:41-46,58-63,82-87,99-105,119-125,181-199`, `prevarisc-migration/src/Service/Commission/CommissionRegleCompetenceService.php:14-20,64-103`, `prevarisc/application/models/DbTable/Commission.php:140-182,192-233,242-289` |

**Retour utilisateur** : Au vu du code cela est normal, les méthodes mentionnées contrôlent les valeurs par défaut proposées lors de la création/modification d'un établissement et l'application des règles qui est propres aux établissements également. La règle étude/visite concernent les dossiers et n'a pas lieu d'être dans les blocs de code concernés.\
Cette règle est uniquement utilisée pour la génération des documents de commission.
Rien à prévoir sur ce point.

---

### Écart 2 — Les natures de dossier et l'axe « commune » des spécialisations membres ne sont pas réellement utilisés pour filtrer les documents

| | Détail |
|---|--------|
| **Règles** | RG-COM-021, RG-COM-052 |
| **Spec** | Les membres peuvent être spécialisés sur les natures de dossier ; les convocations et ODJ sont filtrés par membre selon catégories, types d'activité, classes IGH, types de dossier **et communes**. |
| **Symfony** | Le formulaire membre expose bien `naturesDossier`, mais `CommissionMembreEligibilityChecker` ne filtre que sur `manifestation`, `categories`, `types`, `classes` et `types de dossier`. Aucun test sur les natures de dossier, et aucun axe de spécialisation « commune » n'est porté par le prédicat de filtrage. |
| **Legacy** | Même constat côté legacy : l'écran membre propose les natures de dossier, mais les templates `generationconvoc.phtml` et `generationodj.phtml` filtrent uniquement sur manifestation / catégorie+type / classe + type de dossier. |
| **Impact** | **Élevé** — la spécialisation réelle des membres est plus pauvre que celle décrite par la spec ; des dossiers peuvent être inclus alors qu'ils devraient être exclus selon leur nature ou leur commune. |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md:405,453`, `prevarisc-migration/src/Form/CommissionMembreType.php:120-199`, `prevarisc-migration/src/Service/Commission/CommissionMembreEligibilityChecker.php:13-21,30-76`, `prevarisc/application/views/scripts/commission/partials/partial-ligne-membre-commission.phtml:124-156`, `prevarisc/application/views/scripts/calendrier-des-commissions/generationconvoc.phtml:42-57,96-111,124-139,184-199`, `prevarisc/application/views/scripts/calendrier-des-commissions/generationodj.phtml:74-89` |

**Retour utilisateur** : Le legacy étant la source de vérité, on conserve les filtres en l'état. Pas d'ajout de filtre sur les natures de dossier. Le filtre sur la commune semble lui bien être utilisé avec une vérification sur la commune de l'établissement lié au dossier. Rien à prévoir sur ce point.\
Documenter dans la spec (_$HOME/prevarisc-infra/prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md_) que le filtre nature de dossier est pris en compte en base mais qu'il n'est pas utilisé pour filtrer les dossiers lors de la génération.

---

### Écart 3 — Les ODJ des membres rattachés à un groupement ne sont pas filtrés par compétence

| | Détail |
|---|--------|
| **Règles** | RG-COM-052, RG-COM-053 |
| **Spec** | Chaque membre ne doit recevoir que les dossiers correspondant à ses spécialisations ; pour un membre de groupement, l'organisation du document change, pas la règle de filtrage métier. |
| **Symfony** | `generateOdjParGroupement()` injecte **tous** les dossiers dans le segment `grpLoop` sans appeler `CommissionMembreEligibilityChecker`, avec un commentaire explicite indiquant l'absence de filtrage. |
| **Legacy** | Le template legacy ODJ groupement parcourt lui aussi tous les dossiers sans test d'éligibilité membre. |
| **Impact** | **Critique** — un membre de groupement peut recevoir un ordre du jour contenant des dossiers qui ne relèvent pas de son périmètre. |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md:453-455`, `prevarisc-migration/src/Service/Commission/CommissionDocumentGenerationService.php:1319-1383`, `prevarisc/application/views/scripts/calendrier-des-commissions/generationodj.phtml:107-145` |

**Retour utilisateur** : Le legacy étant la source de vérité, on conserve l'absence du test d'éligibilité. Rien à prévoir sur ce point.\
Documenter dans la spec que le test d'éligibilité n'est pas effectué pour la génération par groupement.

---

### Écart 4 — L'indicateur de présence n'oppose pas « titulaire » et « suppléant », mais trois statuts métier différents

| | Détail |
|---|--------|
| **Règles** | RG-COM-025, §4.3 |
| **Spec** | La présence distingue un membre titulaire (`oui`) d'un membre suppléant / absent par défaut (`non`). |
| **Symfony** | Le formulaire membre propose trois valeurs : `Membre Obligatoire`, `Membre Facultatif`, `Membre Pour information`. Ce n'est ni un booléen, ni une opposition titulaire/suppléant. |
| **Legacy** | Le legacy porte exactement la même sémantique à trois états dans la ligne membre. |
| **Impact** | **Moyen** — la spec ne reflète pas le comportement réel du produit ; sans arbitrage métier, toute recette « titulaire/suppléant » sera ambiguë. |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md:177,182,413`, `prevarisc-migration/src/Form/CommissionMembreType.php:45-52`, `prevarisc/application/views/scripts/commission/partials/partial-ligne-membre-commission.phtml:11-16` |

**Retour utilisateur** : La spec est incorrecte. La mettre à jour avec les valeurs des formulaires (Membre Obligatoire, Membre Facultatif, Membre Pour information). Ces valeurs sont uniquement à titre informatif et ne sont pas utilisées à d'autres fins que de l'affichage.

---

### Écart 5 — Les séances créées en mode récurrent ne sont pas liées à la date principale

| | Détail |
|---|--------|
| **Règles** | RG-COM-036 |
| **Spec** | En mode récurrent, toutes les dates générées doivent être liées à la première date principale. |
| **Symfony** | `CalendarService::sauvegarderDatesCommission()` ne renseigne `dateCommissionLiees` que pour les sélections multiples **hors** périodicité (`if (!$withPeriodicite)`). Les occurrences récurrentes sont donc persistées sans lien maître/esclave. |
| **Legacy** | Le legacy a le même écart : dans la branche `repeat`, les dates récurrentes sont créées par `addDateComm()` et non par `addDateCommLiee()`. |
| **Impact** | **Moyen à élevé** — les occurrences récurrentes ne bénéficient pas du modèle multi-dates attendu par la spec (promotion du maître, suppression de date liée, propagation unifiée). |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md:429`, `prevarisc-migration/src/Service/Commission/CalendarService.php:278-306`, `prevarisc/application/controllers/CalendrierDesCommissionsController.php:690-738` |

**Retour utilisateur** : Le legacy étant la source de vérité, on conserve en l'état. Documenter dans la spec ce comportement en indiquant qu'il s'agit probablement d'un bug à corriger ultérieurement.

---

### Écart 6 — La suppression d'un membre en Symfony ne garantit pas la suppression de ses modèles ODT

| | Détail |
|---|--------|
| **Règles** | RG-COM-024 |
| **Spec** | Supprimer un membre doit supprimer ses documents modèles ODT du système de fichiers. |
| **Symfony** | L'onglet Membres sauvegarde la collection par simple `flush()`. Quand un membre est retiré de la collection, Doctrine supprime bien l'entité (`orphanRemoval=true`), mais aucun hook n'appelle `CommissionMembreCourrierService::delete()` ; les fichiers ODT ne sont donc pas explicitement nettoyés dans ce flux. |
| **Legacy** | Le legacy supprime explicitement les fichiers `ODJ`, `CONVOCATIONVISITE` et `CONVOCATIONSALLE` avant de supprimer le membre. |
| **Impact** | **Élevé** — risque d'orphelins sur le disque et de divergence entre base et filesystem. |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md:411`, `prevarisc-migration/src/Controller/Commission/CommissionMembresController.php:45-59`, `prevarisc-migration/src/Entity/Commission.php:85-89`, `prevarisc-migration/src/Service/Commission/CommissionMembreCourrierService.php:98-121`, `prevarisc/application/controllers/CommissionController.php:353-381` |

**Retour utilisateur** : Bug de migration confirmé. Corriger pour supprimer les documents modèles ODT liés au membre supprimé. Contrairement au legacy, effectuer la suppression des documents après que la suppression du membre ait été effectuée avec succès. Ce pattern est déjà utilisé dans l'application : _$HOME/prevarisc-infra/prevarisc-migration/src/Service/Commission/CommissionManagerService.php_

---

## Écarts Symfony ↔ legacy hors spec stricte

### Écart A — La purge de cache après application des règles dépend encore du legacy

| | Détail |
|---|--------|
| **Sujet** | Strangler / dette technique |
| **Symfony** | Après application des règles, Symfony redirige vers `/legacy/admin/clear-cache-and-redirect` pour purger le cache Zend. |
| **Legacy** | Le legacy purge directement son cache via `cache->clean(...)` dans l'action d'application. |
| **Impact** | **Moyen** — le comportement métier est conservé, mais la migration n'est pas autonome sur ce flux. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Commission/CommissionCompetencesController.php:109-113`, `prevarisc/application/controllers/CommissionController.php:276-281` |

**Retour utilisateur** : Écart de migration confirmé. Un cache TagAware a été introduit dans la branche **fix/cache-recherche** (non encore mergée) pour faire un miroir du `$cacheSearch` legacy. Il faut donc également avoir un cache Symfony miroir du `$cache` legacy. Vérifier également si la suppression du `$cache` legacy entraîne également la suppression du `$cacheSearch` ou si les deux sont indépendants.

---

### Écart B — Le flux webcal Symfony est volontairement simplifié par rapport au legacy

| | Détail |
|---|--------|
| **Sujet** | Export iCalendar |
| **Symfony** | `IcalendarSyncService` génère une VEVENT par `DateCommission`, avec une description réduite à la liste des objets de dossier. |
| **Legacy** | `Api_Service_Calendar` génère une VEVENT par couple `datecommission/dossier`, avec résumé détaillé, lieu précis et corps enrichi (contacts, avis, etc.). |
| **Impact** | **Moyen** — l'abonnement webcal existe bien, mais le niveau de détail disponible dans les clients externes est inférieur au legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Service/Commission/IcalendarSyncService.php:13-16,32-35,66-127`, `prevarisc/application/modules/api/services/Calendar.php:17-61,123-220` |

**Retour utilisateur** : Écart de migration confirmé. Aligner sur le legacy si la complexité n'est pas trop élévée et prévoir une procédure de test pour vérifier le bon affichage des informations.

---

### Écart C — La suppression d'une commission ne reproduit pas le nettoyage legacy complet

| | Détail |
|---|--------|
| **Sujet** | Suppression / nettoyage transverse |
| **Symfony** | `CommissionManagerService` retire la commission et son répertoire dédié, mais ne rejoue ni la suppression explicite des contacts associés, ni le nettoyage des modèles de membres avant suppression globale. |
| **Legacy** | `deleteAction()` supprime d'abord les contacts via `ContactController::delete`, puis les membres via `delete-membre`, puis les règles, avant de supprimer la commission. |
| **Impact** | **Moyen** — le résultat métier immédiat est correct en base, mais le nettoyage transverse est moins complet qu'en legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Service/Commission/CommissionManagerService.php:41-75`, `prevarisc-migration/src/Entity/Commission.php:72-89`, `prevarisc/application/controllers/CommissionController.php:11-40`, `prevarisc/application/controllers/ContactController.php:241-255`, `prevarisc/application/controllers/CommissionController.php:353-381` |

**Retour utilisateur** : Écart de migration confirmé. Aligner avec le legacy.

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-COM-001 à RG-COM-004 | Administration des commissions | ✅ Aligné sur le cœur fonctionnel : type et libellé obligatoires, création/modification/suppression en masse, et gestion du répertoire de commission (`src/Entity/Commission.php:29-54`, `src/Service/Commission/CommissionManagerService.php:41-75`, legacy `application/controllers/GestionDesCommissionsController.php`, `application/controllers/CommissionController.php:11-40`) |
| RG-COM-010 / RG-COM-011 | Zone commune vs groupement selon le type de commission | ✅ Aligné : `CommissionRegleType` bascule entre `commune` et `groupement`, comme le legacy. |
| RG-COM-015 / RG-COM-016 | Action explicite « appliquer les règles » | ✅ Aligné : les deux applications proposent une action dédiée d'affectation des établissements correspondant aux règles. |
| RG-COM-020 | Un membre rattaché à une seule commission | ✅ Aligné via la relation `Commission` → `CommissionMembre` et la structure legacy équivalente. |
| RG-COM-022 / RG-COM-023 | Distinction commune / groupement et exclusion des groupements du PV | ✅ Aligné : la génération groupement est spécifique et le PV ignore les membres rattachés à un groupement (`src/Service/Commission/CommissionDocumentGenerationService.php:409-433`, legacy `generationpv.phtml:167-175`). |
| RG-COM-030 à RG-COM-035 | Gestion des séances multi-dates | ✅ Aligné : séance liée, propagation du libellé/type, promotion de la date maître et suppression restreinte aux dates liées (`src/Service/Commission/CalendarService.php:315-487`, legacy `application/models/DbTable/DateCommission.php:11-38,138-178`) |
| RG-COM-037 | Déplacement d'une séance et mise à jour des dossiers affectés | ✅ Aligné entre `CalendarService` / `DateCommissionRepository` et le legacy. |
| RG-COM-040 à RG-COM-044 | Gestion de l'ODJ, durée par défaut 5 min, mode horaire/séquentiel, réordonnancement | ✅ Aligné : même durée initiale, même remise à plat des horaires lors des changements de mode et de l'ordre. |
| RG-COM-050 / RG-COM-057 / RG-COM-058 | Documents modèles ODT, remplacement de l'ancien fichier, message si modèle absent | ✅ Aligné : upload ODT, suppression de l'ancien modèle lors du remplacement, et messages d'erreur si un modèle manque. |
| RG-COM-051 / RG-COM-054 / RG-COM-055 / RG-COM-056 | Fusion documentaire, dérogations multi-niveaux, prescriptions du PV, compte-rendu unique par commission | ✅ Aligné : port direct confirmé dans `CommissionDocumentGenerationService`, y compris dérogations dossier/établissement/enfants et prescriptions 3×3. |
| RG-COM-060 | Pièces jointes sur une date de commission | ✅ Aligné : upload, suppression et nettoyage physique à la suppression de la séance. |
| §4.7 | URL de synchronisation `webcal://` / `webcals://` conditionnée à la clé applicative | ✅ Aligné sur la disponibilité de la fonctionnalité et la construction de l'URL. |

---

## Recommandations

1. **Priorité critique** : corriger la génération des **ODJ groupement** pour réappliquer le filtrage métier par membre avant fusion ODT.
2. **Priorité haute** : décider avec le métier si la spec doit être corrigée ou si l'application doit réellement réintroduire les axes **étude/visite**, **nature de dossier** et **commune** dans les règles / spécialisations.
3. **Priorité haute** : sécuriser la suppression Symfony des membres (et idéalement des commissions) en branchant un nettoyage filesystem systématique des modèles ODT.
4. **Priorité moyenne** : aligner le comportement de **récurrence** avec RG-COM-036 en liant chaque occurrence générée à la date principale.
5. **Priorité moyenne** : documenter explicitement la divergence fonctionnelle entre la spec et le produit réel sur l'indicateur de **présence** (booléen vs 3 statuts métier).
6. **Priorité moyenne** : supprimer la dépendance de purge de cache vers le legacy pour rendre le module commissions totalement autonome.
7. **Priorité basse à moyenne** : arbitrer le niveau de détail attendu pour le flux **webcal** Symfony ; si l'objectif est l'iso-fonctionnalité legacy, il faut revenir à une VEVENT par dossier.
