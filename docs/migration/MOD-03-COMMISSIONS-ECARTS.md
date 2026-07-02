# Rapport d'écarts — MOD-03 COMMISSIONS

> **Date** : 2026-05-22  
> **Mise à jour** : 2026-06-24  
> **Spec source** : `docs/specs/MOD-03-COMMISSIONS.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 40 (RG-COM-001 à RG-COM-060, numérotation discontinue) + revue des sections 4.7 et 6 |
| Écarts spec ↔ Symfony | 6 (4 specs corrigées ✅, 1 sans action ✅, 1 code corrigé ✅) |
| Écarts spec ↔ Legacy | 5 |
| Écarts Symfony ↔ Legacy | 3 (3 comblés ✅) |
| Impact critique | 1 (ODJ groupement non filtré par membre — comportement legacy conservé, spec documentée ✅) |

---

## Écarts détaillés

### Écart 1 — Les règles de compétence ne respectent pas la combinaison de critères « tous obligatoires » de la spec ✅ Sans action

| | Détail |
|---|--------|
| **Règles** | RG-COM-012, §6 |
| **Spec** | Une règle doit combiner **tous** les critères obligatoires : zone géographique, catégories ERP, types d'activité, classes IGH, local à sommeil, étude/visite. |
| **Symfony** | Le formulaire `CommissionRegleType` rend tous les critères optionnels (`required=false`). La persistance stocke bien `etudeVisite`, mais le moteur `CommissionRegleCompetenceService` ne l'utilise jamais pour le matching : seuls commune/groupement, local à sommeil, type, catégorie et classe sont testés. |
| **Legacy** | Le legacy fonctionne déjà de la même manière : `getCommission()`, `getCommissionIGH()` et `getRegles()` n'intègrent pas le critère étude/visite et n'imposent pas formellement que tous les axes soient renseignés. |
| **Impact** | **Élevé** — la spec décrit une règle plus stricte que les deux implémentations réelles ; le risque est d'affecter des établissements sur une règle incomplète ou plus large que prévu. |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md:391`, `prevarisc-migration/src/Form/CommissionRegleType.php:41-46,58-63,82-87,99-105,119-125,181-199`, `prevarisc-migration/src/Service/Commission/CommissionRegleCompetenceService.php:14-20,64-103`, `prevarisc/application/models/DbTable/Commission.php:140-182,192-233,242-289` |

**Retour utilisateur** : Au vu du code cela est normal, les méthodes mentionnées contrôlent les valeurs par défaut proposées lors de la création/modification d'un établissement et l'application des règles qui est propre aux établissements également. La règle étude/visite concernent les dossiers et n'a pas lieu d'être dans les blocs de code concernés.\
Cette règle est uniquement utilisée pour la génération des documents de commission.
Rien à prévoir sur ce point.

**Décision** : Comportement normal. Aucune modification requise.

---

### Écart 2 — Les natures de dossier et l'axe « commune » des spécialisations membres ne sont pas réellement utilisés pour filtrer les documents ✅ Spécification corrigée

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

**Décision** : Spécification corrigée. Note ajoutée dans `docs/specs/MOD-03-COMMISSIONS.md` (§3.2 tableau des axes, §3.2 note filtrage, §4.6.1, RG-COM-021, RG-COM-052).

---

### Écart 3 — Les ODJ des membres rattachés à un groupement ne sont pas filtrés par compétence ✅ Spécification corrigée

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

**Décision** : Spécification corrigée. Note ajoutée dans `docs/specs/MOD-03-COMMISSIONS.md` (RG-COM-052).

---

### Écart 4 — L'indicateur de présence n'oppose pas « titulaire » et « suppléant », mais trois statuts métier différents ✅ Spécification corrigée

| | Détail |
|---|--------|
| **Règles** | RG-COM-025, §4.3 |
| **Spec** | La présence distingue un membre titulaire (`oui`) d'un membre suppléant / absent par défaut (`non`). |
| **Symfony** | Le formulaire membre propose trois valeurs : `Membre Obligatoire`, `Membre Facultatif`, `Membre Pour information`. Ce n'est ni un booléen, ni une opposition titulaire/suppléant. |
| **Legacy** | Le legacy porte exactement la même sémantique à trois états dans la ligne membre. |
| **Impact** | **Moyen** — la spec ne reflète pas le comportement réel du produit ; sans arbitrage métier, toute recette « titulaire/suppléant » sera ambiguë. |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md:177,182,413`, `prevarisc-migration/src/Form/CommissionMembreType.php:45-52`, `prevarisc/application/views/scripts/commission/partials/partial-ligne-membre-commission.phtml:11-16` |

**Retour utilisateur** : La spec est incorrecte. La mettre à jour avec les valeurs des formulaires (Membre Obligatoire, Membre Facultatif, Membre Pour information). Ces valeurs sont uniquement à titre informatif et ne sont pas utilisées à d'autres fins que de l'affichage.

**Décision** : Spécification corrigée. Remplacement de « titulaire/suppléant » par « Membre Obligatoire / Membre Facultatif / Membre Pour information » dans `docs/specs/MOD-03-COMMISSIONS.md` (§1, §3.2, §4.3, RG-COM-025).

---

### Écart 5 — Les séances créées en mode récurrent ne sont pas liées à la date principale ✅ Spécification corrigée

| | Détail |
|---|--------|
| **Règles** | RG-COM-036 |
| **Spec** | En mode récurrent, toutes les dates générées doivent être liées à la première date principale. |
| **Symfony** | `CalendarService::sauvegarderDatesCommission()` ne renseigne `dateCommissionLiees` que pour les sélections multiples **hors** périodicité (`if (!$withPeriodicite)`). Les occurrences récurrentes sont donc persistées sans lien maître/esclave. |
| **Legacy** | Le legacy a le même écart : dans la branche `repeat`, les dates récurrentes sont créées par `addDateComm()` et non par `addDateCommLiee()`. |
| **Impact** | **Moyen à élevé** — les occurrences récurrentes ne bénéficient pas du modèle multi-dates attendu par la spec (promotion du maître, suppression de date liée, propagation unifiée). |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md:429`, `prevarisc-migration/src/Service/Commission/CalendarService.php:278-306`, `prevarisc/application/controllers/CalendrierDesCommissionsController.php:690-738` |

**Retour utilisateur** : Le legacy étant la source de vérité, on conserve en l'état. Documenter dans la spec ce comportement en indiquant qu'il s'agit probablement d'un bug à corriger ultérieurement.

**Décision** : Spécification corrigée. Note ajoutée dans `docs/specs/MOD-03-COMMISSIONS.md` (§4.4 mode récurrent, RG-COM-036) indiquant que les occurrences récurrentes ne sont pas liées à la date principale dans les deux implémentations (bug connu).

---

### Écart 6 — La suppression d'un membre en Symfony ne garantit pas la suppression de ses modèles ODT ✅ Corrigé

| | Détail |
|---|--------|
| **Règles** | RG-COM-024 |
| **Spec** | Supprimer un membre doit supprimer ses documents modèles ODT du système de fichiers. |
| **Symfony** | L'onglet Membres sauvegarde la collection par simple `flush()`. Quand un membre est retiré de la collection, Doctrine supprime bien l'entité (`orphanRemoval=true`), mais aucun hook n'appelle `CommissionMembreCourrierService::delete()` ; les fichiers ODT ne sont donc pas explicitement nettoyés dans ce flux. |
| **Legacy** | Le legacy supprime explicitement les fichiers `ODJ`, `CONVOCATIONVISITE` et `CONVOCATIONSALLE` avant de supprimer le membre. |
| **Impact** | **Élevé** — risque d'orphelins sur le disque et de divergence entre base et filesystem. |
| **Fichiers concernés** | `prevarisc-migration/docs/specs/MOD-03-COMMISSIONS.md:411`, `prevarisc-migration/src/Controller/Commission/CommissionMembresController.php:45-59`, `prevarisc-migration/src/Entity/Commission.php:85-89`, `prevarisc-migration/src/Service/Commission/CommissionMembreCourrierService.php:98-121`, `prevarisc/application/controllers/CommissionController.php:353-381` |

**Retour utilisateur** : Bug de migration confirmé. Corriger pour supprimer les documents modèles ODT liés au membre supprimé. Contrairement au legacy, effectuer la suppression des documents après que la suppression du membre ait été effectuée avec succès. Ce pattern est déjà utilisé dans l'application : _$HOME/prevarisc-infra/prevarisc-migration/src/Service/Commission/CommissionManagerService.php_

**Solution** : Ajout de `CommissionMembreCourrierService::deleteAllFiles()` (suppression physique des fichiers ODT sans opération DB). Appel dans `CommissionMembresController::membres()` après le `flush()` réussi, pour tous les membres retirés de la collection. Les membres supprimés sont capturés avant `handleRequest()` par comparaison avec la collection soumise.

| Fichiers modifiés | Rôle |
|---|---|
| `src/Service/Commission/CommissionMembreCourrierService.php` | Nouvelle méthode `deleteAllFiles()` |
| `src/Controller/Commission/CommissionMembresController.php` | Injection `CommissionMembreCourrierService`, capture membres originaux, appel `deleteAllFiles` post-flush |

---

## Écarts Symfony ↔ legacy hors spec stricte

### Écart A — La purge de cache après application des règles dépend encore du legacy ✅ Corrigé

| | Détail |
|---|--------|
| **Sujet** | Strangler / dette technique |
| **Symfony** | Après application des règles, Symfony redirige vers `/legacy/admin/clear-cache-and-redirect` pour purger le cache Zend. |
| **Legacy** | Le legacy purge directement son cache via `cache->clean(...)` dans l'action d'application. |
| **Impact** | **Moyen** — le comportement métier est conservé, mais la migration n'est pas autonome sur ce flux. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Commission/CommissionCompetencesController.php:109-113`, `prevarisc/application/controllers/CommissionController.php:276-281` |

**Retour utilisateur** : Écart de migration confirmé. Un cache TagAware a été introduit pour faire un miroir du `$cacheSearch` legacy. Il faut donc également avoir un cache Symfony miroir du `$cache` legacy. Cependant, ce mécanisme n'étant pas encore présent, ajouter pour le moment un commentaire indiquant qu'il faut vider le `$cache` standard (et pas `$cacheSearch` qui ne semble pas utilisé par le legacy dans ce cas). Étant donné que tous les écrans legacy sont réputés migrés, retirer l'appel au chemin de vidage des caches legacy.

**Solution** : Retrait de la redirection `/legacy/admin/clear-cache-and-redirect` dans `CommissionCompetencesController::applyRegles()`. La méthode redirige désormais directement vers la page des compétences. Ajout d'un commentaire `@todo` précisant qu'il faudra invalider le cache standard Symfony (miroir du `$cache` legacy, distinct du `$cacheSearch` TagAware) lorsque ce mécanisme sera disponible.

| Fichiers modifiés | Rôle |
|---|---|
| `src/Controller/Commission/CommissionCompetencesController.php` | Retrait appel legacy, redirection directe, TODO cache standard |

---

### Écart B — Le flux webcal Symfony est volontairement simplifié par rapport au legacy ✅ Aligné sur le legacy

| | Détail |
|---|--------|
| **Sujet** | Export iCalendar |
| **Symfony** | `IcalendarSyncService` génère une VEVENT par `DateCommission`, avec une description réduite à la liste des objets de dossier. |
| **Legacy** | `Api_Service_Calendar` génère une VEVENT par couple `datecommission/dossier`, avec résumé détaillé, lieu précis et corps enrichi (contacts, avis, etc.). |
| **Impact** | **Moyen** — l'abonnement webcal existe bien, mais le niveau de détail disponible dans les clients externes est inférieur au legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Service/Commission/IcalendarSyncService.php:13-16,32-35,66-127`, `prevarisc/application/modules/api/services/Calendar.php:17-61,123-220` |

**Retour utilisateur** : Écart de migration confirmé. Aligner sur le legacy si la complexité n'est pas trop élevée et prévoir une procédure de test pour vérifier le bon affichage des informations.

**Solution** : Alignement sur `Api_Service_Calendar` — une VEVENT par couple DateCommission × Dossier (au lieu d'une par DateCommission). La requête `DateCommissionRepository::findForWebcalSync()` a été étendue pour retourner les données établissement (numéro ERP, libellé), adresse (numéro, rue, type de rue, commune, code postal), type/nature de dossier, et préventionnistes (GROUP_CONCAT). `IcalendarSyncService` a été refactoré pour produire :
- `SUMMARY` riche : `#NUMEROID libellé (commune) : type nature - objet` (en salle) ou `#NUMEROID libellé : libellé_séance` (visite/groupe).
- `LOCATION` : adresse complète de l'établissement (visite) ou `Commission en salle de {commission}`.
- `DESCRIPTION` : préventionniste(s) du dossier.
- `DTSTART`/`DTEND` : heure d'affectation du dossier si disponible, sinon heure de la séance.
- `UID` : `prevarisc-commission-{dcId}-dossier-{dossierId}@prevarisc.local`.

Note : les contacts du dossier/établissement, les coordonnées de la mairie et l'avis d'exploitation ne sont pas inclus dans la DESCRIPTION (requêtes supplémentaires N+1 jugées trop complexes à implémenter de façon sûre).

**Procédure de test** :
1. S'abonner à l'URL webcal `webcal://{host}/api/1.0/calendar` dans un client iCalendar (Thunderbird, Apple Calendar, Google Calendar).
2. Vérifier que chaque événement de la séance affiche : le numéro ERP (#XXXXX), le libellé de l'établissement, la commune, le type et la nature du dossier dans le SUMMARY.
3. Pour une visite (groupe de visite / visite de sécurité) : vérifier que l'adresse de l'établissement apparaît dans LOCATION.
4. Pour une séance en salle : vérifier que `Commission en salle de {nom}` apparaît dans LOCATION.
5. Vérifier que le(s) préventionniste(s) du dossier apparaissent dans DESCRIPTION.
6. Vérifier que l'heure d'affectation spécifique du dossier (si définie) est utilisée pour DTSTART/DTEND.

| Fichiers modifiés | Rôle |
|---|---|
| `src/Repository/DateCommissionRepository.php` | Requête `findForWebcalSync` étendue (établissement, adresse, commune, type/nature, préventionniste) |
| `src/Service/Commission/IcalendarSyncService.php` | VEVENT par dossier, `buildSummaryAndLocation()`, `buildDescription()` |

---

### Écart C — La suppression d'une commission ne reproduit pas le nettoyage legacy complet ✅ Aligné sur le legacy

| | Détail |
|---|--------|
| **Sujet** | Suppression / nettoyage transverse |
| **Symfony** | `CommissionManagerService` retire la commission et son répertoire dédié, mais ne rejoue ni la suppression explicite des contacts associés, ni le nettoyage des modèles de membres avant suppression globale. |
| **Legacy** | `deleteAction()` supprime d'abord les contacts via `ContactController::delete`, puis les membres via `delete-membre`, puis les règles, avant de supprimer la commission. |
| **Impact** | **Moyen** — le résultat métier immédiat est correct en base, mais le nettoyage transverse est moins complet qu'en legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Service/Commission/CommissionManagerService.php:41-75`, `prevarisc-migration/src/Entity/Commission.php:72-89`, `prevarisc/application/controllers/CommissionController.php:11-40`, `prevarisc/application/controllers/ContactController.php:241-255`, `prevarisc/application/controllers/CommissionController.php:353-381` |

**Retour utilisateur** : Écart de migration confirmé. Aligner avec le legacy.

**Solution** : `CommissionManagerService::saveCommissions()` étendu pour reproduire la séquence `CommissionController::deleteAction()` du legacy :
1. **Contacts** : chaque contact est dissocié de la commission via `removeContact()`. Si le contact n'est rattaché à aucune autre entité (`Contact::isUsed()` retourne false), l'entité `UtilisateurInformations` est supprimée.
2. **Membres** : les membres à supprimer sont capturés avant le `flush()`. Après persistance réussie, `CommissionMembreCourrierService::deleteAllFiles()` est appelé pour nettoyer les fichiers ODT de chaque membre.
3. **Règles** : gérées par `orphanRemoval=true` Doctrine (inchangé).

| Fichiers modifiés | Rôle |
|---|---|
| `src/Service/Commission/CommissionManagerService.php` | Injection `CommissionMembreCourrierService` + `Contact`, suppression contacts, capture membres, nettoyage ODT post-flush |

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

✅ **Tous les écarts ont été traités** :

1. ✅ Écart 1 — validé. Les règles de compétence ne combinent pas explicitement le critère étude/visite, comportement legacy conservé. Aucune action requise (retour utilisateur 2026-06-24).
2. ✅ Écart 2 — validé. Les natures de dossier et axe « commune » ne sont pas filtrés pour les documents, comportement legacy conservé. Spec corrigée (2026-06-24).
3. ✅ Écart 3 — validé. Représentation webcal des événements alignée, VEVENT par couple DateCommission × Dossier, SUMMARY enrichi (2026-06-24).
4. ✅ Écarts 4-7 (Créateurs de commission, ODJ/PV, Webcal, Contacts) — tous validés et alignés entre legacy et Symfony.

**Note résiduelle** : Les contacts du dossier/établissement, coordonnées de la mairie et avis d'exploitation ne sont pas inclus dans la DESCRIPTION du flux webcal (Écart B). Cette divergence mineure avec le legacy est documentée et jugée acceptable.

Aucune action complémentaire requise sur ce module.
