# Rapport d'audit — Refactorisation Symfony 4.4

> **Projet :** prevarisc-migration (Zend 1.12 → Symfony 4.4 / PHP 7.1)
> **Date :** Juillet 2026
> **Périmètre :** Code migré dans `prevarisc-migration/src/`, `templates/` et `config/`
> **Objectif :** Identifier les refactorisations structurelles sûres qui améliorent l'idiomaticité Symfony et facilitent les futures montées de version, sans changement fonctionnel.
> **Contexte équipe :** Développeur unique. La migration Zend → Symfony reste la priorité principale. Les worktrees de refactor sont parallélisables mais impliquent un coût de relecture et de test à intégrer dans les estimations.

---

## Résumé exécutif

Le "port direct" depuis Zend a produit un code fonctionnel et bien structuré sur plusieurs aspects (injection de dépendances par constructeur, routing par annotations, voters de sécurité, event subscribers). Cependant, **57 occurrences de `getConnection()` avec du SQL brut**, **5 services monolithiques de plus de 500 lignes**, un **Guard Authenticator déprécié**, et des **templates avec des chemins hardcodés** constituent les principaux freins à une montée de version sereine vers Symfony 5/6.

### Bilan quantitatif

| Métrique | Valeur |
|----------|--------|
| Fichiers analysés | ~450 (PHP) + ~185 (Twig) + ~15 (config) |
| Opportunités identifiées | 22 |
| Critiques (bloquent montée Symfony 5+) | 3 |
| Haute priorité | 7 |
| Moyenne priorité | 8 |
| Basse priorité | 4 |
| Non applicable (SF 4.4) | 1 (REF-CFG-01) |
| À vérifier sur `develop` | 1 (REF-SVC-01) |
| Estimation totale (jours-développeur, hors relecture) | 40–55 jours |

### Ce qui est déjà bien fait ✅

- **Zéro `getDoctrine()`** : EntityManagerInterface est systématiquement injecté.
- **Zéro `$this->get('service')`** : Aucun service locator, pas de `ContainerAwareTrait`.
- **Zéro `public: true`** dans `services.php` : Hygiène du conteneur respectée.
- **219 routes par annotations `@Route`** : Routing idiomatique.
- **60+ annotations `@IsGranted` / `@Security`** : Sécurité déclarative bien adoptée.
- **4 Event Subscribers** focalisés et bien structurés.
- **9 extensions Twig** modulaires.
- **Autowiring + Autoconfiguration** activés.

> **Note :** Ce rapport exclut les items déjà couverts par le `RAPPORT_MODERNISATION.md` (dépendances obsolètes, typed properties PHP 8+, PHPExcel → PhpSpreadsheet, etc.).

---

## Table des matières

1. [Sécurité et authentification](#1-sécurité-et-authentification)
2. [Controllers](#2-controllers)
3. [Services](#3-services)
4. [Repositories et requêtes SQL](#4-repositories-et-requêtes-sql)
5. [Formulaires](#5-formulaires)
6. [Templates Twig](#6-templates-twig)
7. [Event Subscribers](#7-event-subscribers)
8. [Configuration et DI](#8-configuration-et-di)
9. [Roadmap worktrees suggérée](#9-roadmap-worktrees-suggérée)
10. [Tableau de synthèse](#10-tableau-de-synthèse)

---

## 1. Sécurité et authentification

### REF-SEC-01 — Guard Authenticator déprécié

| | |
|---|---|
| **Priorité** | 🔴 Haute |
| **Complexité** | Modérée |
| **Impact montée version** | Bloquant Symfony 6 (supprimé) |
| **Worktree** | `refactor/authenticator-migration` |

**Problème :** `LegacySessionAuthenticator` étend `AbstractGuardAuthenticator`, déprécié depuis Symfony 5.4 et supprimé en 6.0.

**Fichiers concernés :**
- `src/Security/LegacySessionAuthenticator.php` (lignes 13, 22)
- `config/packages/security.php` (section `guard`)

**Code actuel :**
```php
// src/Security/LegacySessionAuthenticator.php:13,22
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class LegacySessionAuthenticator extends AbstractGuardAuthenticator
{
    // ...
}
```

```php
// config/packages/security.php
'guard' => [
    'authenticators' => ['App\Security\LegacySessionAuthenticator'],
    'entry_point' => 'App\Security\LegacySessionAuthenticator',
]
```

**Solution :** Migrer vers `AuthenticatorInterface` (disponible dans `symfony/security-http` 5.1+). En attendant la montée Symfony 5, préparer l'architecture en extrayant la logique de credentials dans une classe séparée.

> **✅ Validé** — La migration complète vers `AbstractAuthenticator` est une action Symfony 5+. Sur SF 4.4, l'action à mener est l'extraction de la logique de credentials dans un service `ZendSessionBridge` distinct (voir REF-SEC-02), ce qui rendra la migration ultérieure triviale.

**Code cible (Symfony 5.1+) :**
```php
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class LegacySessionAuthenticator extends AbstractAuthenticator
{
    public function authenticate(Request $request): Passport
    {
        $username = $this->extractUsernameFromZendSession($request);
        return new SelfValidatingPassport(new UserBadge($username));
    }
}
```

---

### REF-SEC-02 — Accès direct à `$_SESSION` dans l'Authenticator

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Simple |
| **Impact montée version** | Fragilité, non testable unitairement |
| **Worktree** | `refactor/authenticator-migration` (même tâche que REF-SEC-01) |

**Problème :** L'Authenticator accède directement à `$_SESSION['Zend_Auth']` (ligne 52), contournant l'abstraction Symfony `SessionInterface`.

**Fichier :** `src/Security/LegacySessionAuthenticator.php` (lignes 52, 59)

```php
// Ligne 52-59
if (!isset($_SESSION['Zend_Auth'])) {
    throw new AuthenticationException();
}
$zendAuth = $_SESSION['Zend_Auth'];
```

**Solution :** Encapsuler l'accès Zend_Auth dans un service `ZendSessionBridge` injectable et testable.

```php
// src/Security/ZendSessionBridge.php
class ZendSessionBridge
{
    /**
     * @return string|null
     */
    public function getAuthenticatedUsername()
    {
        if (!isset($_SESSION['Zend_Auth']['storage']['USERNAME_UTILISATEUR'])) {
            return null;
        }
        return $_SESSION['Zend_Auth']['storage']['USERNAME_UTILISATEUR'];
    }
}
```

> **✅ Validé**

---

### REF-SEC-03 — Appel direct au Voter dans l'EventSubscriber

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Simple |
| **Impact montée version** | Anti-pattern, contourne le système de sécurité |
| **Worktree** | `refactor/subscriber-authchecker` |

**Problème :** `EtablissementLateralNavigationSubscriber` appelle directement `$this->privilegeVoter->vote()` au lieu d'utiliser `AuthorizationCheckerInterface`.

**Fichier :** `src/EventSubscriber/EtablissementLateralNavigationSubscriber.php` (lignes 82-83)

```php
// Ligne 82-83
$isAllowedDeleteEtablissementVote = $this->privilegeVoter->vote($token, $etablissement, ['delete_etablissement']);
$isAllowedDeleteEtablissement = VoterInterface::ACCESS_GRANTED === $isAllowedDeleteEtablissementVote;
```

**Solution :**
```php
// Injecter AuthorizationCheckerInterface au lieu de PrivilegeVoter
$isAllowedDeleteEtablissement = $this->authorizationChecker->isGranted('delete_etablissement', $etablissement);
```

> **✅ Validé** — La solution passe bien par `AuthorizationCheckerInterface`, qui délègue en interne au `PrivilegeVoter` custom déjà en place. Aucun changement fonctionnel.

---

## 2. Controllers

### REF-CTL-01 — CalendrierDesCommissionsController monolithique (1494 lignes)

| | |
|---|---|
| **Priorité** | 🔴 Haute |
| **Complexité** | Complexe |
| **Impact montée version** | Maintenabilité critique, testabilité nulle |
| **Worktree** | `refactor/calendrier-controller-split` |

**Problème :** Le plus gros controller du projet contient :
- Une méthode `dialogComm()` de ~500 lignes avec un mega-switch de 20+ branches
- 5 appels à `$this->entityManager->getConnection()` pour du SQL brut (lignes 170, 512, 875, 1407, 1451)
- De la logique métier complexe directement dans le controller

**Fichier :** `src/Controller/Commission/CalendrierDesCommissionsController.php`

**Solution en 3 étapes :**
1. Extraire chaque branche du switch `dialogComm()` vers des méthodes privées puis vers un service `CalendarCommandHandler`
2. Migrer les 5 requêtes SQL brutes vers `DateCommissionRepository`
3. Créer des routes dédiées pour chaque action au lieu du mega-switch

> **✅ Validé**

---

### REF-CTL-02 — Méthodes d'action sans return type hint

| | |
|---|---|
| **Priorité** | 🔴 Haute |
| **Complexité** | Simple |
| **Impact montée version** | PHP 8.x strict mode, Symfony 6+ |
| **Worktree** | — |

**État actuel :** Toutes les méthodes publiques des controllers disposent d'un return type explicite (`: Response`, `: JsonResponse`, `: RedirectResponse`, `: void`, etc.). Le problème initial de 72 méthodes sans type hint a été résolu dans le code en place — aucune action supplémentaire requise.

> **✅ Terminé** — Vérifié le 2026-05-07 : 0 méthode sans return type dans `src/Controller/`.

---

### REF-CTL-03 — `EntityManager->find()` manuel au lieu de ParamConverter (41 occurrences)

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Modérée |
| **Impact montée version** | Idiomaticité Symfony, DRY |
| **Worktree** | `refactor/paramconverter` |

**Problème :** 41 occurrences de `$repository->find($id)` ou `$entityManager->find(Entity::class, $id)` suivi systématiquement d'un `if (null === $entity) { throw $this->createNotFoundException(); }`. Ce pattern boilerplate est remplacé par `@ParamConverter` ou le typehint automatique.

**Fichiers les plus concernés :**
- `src/Controller/ContactController.php` — lignes 51, 61, 71, 88 (4 find manuels dans un switch)
- `src/Controller/DossierController.php` — lignes 256, 529
- `src/Controller/DossierLiaisonsController.php` — lignes 136, 167, 215
- `src/Controller/AlerteController.php` — lignes 78, 84
- `src/Controller/EtablissementController.php` — ligne 424

**Exemple (`ContactController.php`, lignes 49-54) :**
```php
// AVANT
switch ($elementLie) {
    case Contact::ETABLISSEMENT:
        $entity = $entityManager->find(Etablissement::class, $elementId);
        if (null === $entity) {
            throw $this->createNotFoundException('Établissement non trouvé.');
        }
        $this->denyAccessUnlessGranted(EtablissementVoter::MODIFICATION, $entity);
        break;
    // ... 4 cases similaires
}
```

```php
// APRÈS — route dédiée par type d'entité avec ParamConverter implicite
/**
 * @Route("/contact/{id}/modifier/etablissement/{etablissement}", name="contact_edit_etablissement")
 * @IsGranted(EtablissementVoter::MODIFICATION, subject="etablissement")
 */
public function editForEtablissement(UtilisateurInformations $contact, Etablissement $etablissement): Response
{
    // Logique simplifiée sans switch ni find manuel
}
```

> **Note :** Pour les cas où le switch est indispensable (type d'entité dynamique), conserver le pattern actuel est acceptable.

> **⚠️ À analyser avant implémentation** — Le ParamConverter est applicable uniquement quand le `find()` n'est pas conditionnel. Pour le `ContactController`, le pattern switch/find est **intentionnel** : les actions sont structurellement similaires quel que soit le type d'entité. Si une séparation est envisagée, elle devrait passer par un **service commun** gérant les actions génériques, mais uniquement si l'abstraction apporte une valeur réelle sans complexifier inutilement. À évaluer au cas par cas lors de l'implémentation.

---

### REF-CTL-04 — `json_encode()` manuel dans les controllers

| | |
|---|---|
| **Priorité** | 🟢 Basse |
| **Complexité** | Simple |
| **Impact montée version** | Cohérence, sérialiseur centralisé |
| **Worktree** | `refactor/json-responses` |

**Problème :** 5 occurrences de `json_encode()` dans des controllers pour construire des données JSON, au lieu d'utiliser `$this->json()` ou `JsonResponse`.

**Fichiers :**
- `src/Controller/DossierController.php` — lignes 354, 554 : `json_encode($this->dossierFieldsService->getAllFieldsByNature())`
- `src/Controller/Api/UtilisateurPreferencesController.php` — ligne 65 : `json_encode($blocs)`
- `src/Controller/CouchesCartographiquesController.php` — ligne 116 : `$this->serializer->deserialize(json_encode($coucheCartoData), ...)`

**Solution :**
```php
// AVANT (DossierController:354)
$fieldsConfig = json_encode($this->dossierFieldsService->getAllFieldsByNature());
// Passé au template comme string JSON

// APRÈS — passer directement le tableau, encoder dans Twig si nécessaire
$fieldsConfig = $this->dossierFieldsService->getAllFieldsByNature();
// Dans le template : {{ fieldsConfig|json_encode|raw }}
```

> **✅ Validé**

---

## 3. Services

### REF-SVC-01 — Service Search monolithique (1386 lignes, SQL brut pur)

| | |
|---|---|
| **Priorité** | 🔴 Haute |
| **Complexité** | Complexe |
| **Impact montée version** | Non testable, non maintenable, couplage DBAL |
| **Worktree** | `refactor/search-service-split` |

**Problème :** Le service `Search` est le plus gros du projet avec 1386 lignes, entièrement basé sur du SQL brut via `Connection`. Il contient 9 appels `executeQuery()` et des méthodes de 300+ lignes.

**Fichier :** `src/Service/Search.php`

| Méthode | Lignes | Description |
|---------|--------|-------------|
| `etablissements()` | 60–364 (305 lignes) | Recherche établissements |
| `dossiers()` | 366–786 (420 lignes) | Recherche dossiers |
| `extractionEtablissements()` | 789–1058 (269 lignes) | Export établissements |
| `extractionDossiers()` | 1061–1386 (325 lignes) | Export dossiers |

```php
// src/Service/Search.php:1,19-24
class Search
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
}
```

**Solution proposée :**
1. Scinder en `EtablissementSearchService` et `DossierSearchService`
2. Extraire les builders SQL en classes dédiées `EtablissementSearchQueryBuilder` et `DossierSearchQueryBuilder`
3. À terme, migrer vers le QueryBuilder Doctrine si possible (complexité élevée vu la nature des requêtes)

> **⚠️ À vérifier sur `develop`** — Cette analyse a été faite sur la branche courante. Les méthodes d'export ont potentiellement déjà été extraites vers un service dédié sur `develop`. **Vérifier l'état actuel de `Search.php` sur `develop` avant d'initier ce worktree.** Si l'extraction export est déjà faite, la complexité de ce point est réduite.

---

### REF-SVC-02 — Service Etablissement : trop de responsabilités (795 lignes, 10 dépendances)

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Modérée |
| **Impact montée version** | SRP, testabilité |
| **Branche** | `refactor/etablissement-service-split` |

## ✅ Terminé

**Fichiers créés / modifiés :**

| Fichier | Rôle | Lignes |
|---|---|---|
| `src/Service/Etablissement.php` | Résiduel (consultation, descriptif, restore) | 490 |
| `src/Service/EtablissementHistoriqueService.php` | Historique informations + avis | 250 |
| `src/Service/EtablissementPieceJointeService.php` | Pièces jointes (CRUD + filtrage) | 232 |

**Dépendances extraites de `EtablissementService` :**
`EtablissementPieceJointeRepository`, `DataStore`, `datastoreLogger`, `PieceJointeRepository`, `Image`, `Filesystem` → portées dans `EtablissementPieceJointeService`.

**Consumers mis à jour :**
- `EtablissementController` : injecte les 3 services
- `DossierController` : remplace `EtablissementService` par `EtablissementHistoriqueService` (seule méthode utilisée : `getLastAvisLibelle`)
- `config/services.php` : binding `$datastoreLogger` déplacé vers `EtablissementPieceJointeService`

---

### REF-SVC-03 — Services manipulant directement le Request (5 services)

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Simple |
| **Impact montée version** | Testabilité, séparation des couches |
| **Worktree** | `refactor/services-no-request` |

**Problème :** 5 services acceptent un objet `Request` et en extraient les données manuellement. Cela couple le service à la couche HTTP et rend les tests unitaires difficiles.

**Fichiers concernés :**

| Service | Fichier | Méthode | Ligne |
|---------|---------|---------|-------|
| `CalendarService` | `src/Service/Commission/CalendarService.php` | `createDatesDtoFromRequest(Request $request)` | 215 |
| `RessourcesSpecialiseesDossierNameGenerator` | `src/Service/RessourcesSpecialisees/...NameGenerator.php` | `fromRequest(Request $request)` | 12 |
| `RessourcesSpecialiseesDossierTextGenerator` | `src/Service/RessourcesSpecialisees/...TextGenerator.php` | `fromRequest(Request $request)` | 25 |
| `RessourcesSpecialiseesEtablissementNameGenerator` | `src/Service/RessourcesSpecialisees/...NameGenerator.php` | `fromRequest(Request $request)` | 22 |
| `RessourcesSpecialiseesEtablissementTextGenerator` | `src/Service/RessourcesSpecialisees/...TextGenerator.php` | `fromRequest(Request $request)` | 25 |

**Exemple (`CalendarService.php`, lignes 215-248) :**
```php
// AVANT
public function createDatesDtoFromRequest(Request $request): CommissionDatesDto
{
    $libelle = $request->query->get('libelle');
    $idCommission = $request->query->getInt('commission');
    $start = $request->query->get('start');
    $end = $request->query->get('end');
    // ...
}
```

```php
// APRÈS — accepter des scalaires, extraction dans le controller
public function createDatesDto(
    ?string $libelle,
    int $idCommission,
    int $idTypeEvenement,
    string $start,
    string $end
): CommissionDatesDto {
    // Même logique, mais sans dépendance HTTP
}
```

> **💬 À étudier** — Pour le `CalendarService`, la séparation est pertinente (service de domaine qui ne devrait pas dépendre de HTTP). En revanche, pour les `RessourcesSpecialisees*Generator`, le pattern `static fromRequest()` est une **factory statique sur un DTO**, ce qui est un pattern classique quand un DTO peut être construit depuis plusieurs sources. Ce n'est pas nécessairement problématique dans ce contexte. Évaluer au cas par cas : si le seul point d'entrée est la `Request`, la factory statique est acceptable ; si d'autres sources sont prévues, la séparation du `CalendarService` est le modèle à suivre.

---

### REF-SVC-04 — Classes utilitaires entièrement statiques

| | |
|---|---|
| **Priorité** | 🟢 Basse |
| **Complexité** | Simple |
| **Impact montée version** | Testabilité, inversion de dépendances |
| **Worktree** | `refactor/static-to-services` |

**Problème :** Plusieurs classes n'utilisent que des méthodes statiques, rendant le mocking impossible et contournant le conteneur de services.

**Fichiers :**
- `src/Service/Informations.php` — 5 méthodes statiques (lignes 9-61)
- `src/Service/RessourcesSpecialisees/RessourcesSpecialiseesDossierNameGenerator.php` — `static function fromRequest()` (ligne 12)
- `src/Service/RessourcesSpecialisees/RessourcesSpecialiseesEtablissementNameGenerator.php` — `static function fromRequest()` et `createNamer()` (lignes 22, 33)

**Exemple (`RessourcesSpecialiseesEtablissementNameGenerator.php`, lignes 33-61) :**
```php
private static function createNamer(int $genreId): RessourcesSpecialiseesEtablissementNamer
{
    switch ($genreId) {
        case 2: $namer = new RessourcesSpecialiseesErpNamer(); break;
        case 3: $namer = new RessourcesSpecialiseesCelluleNamer(); break;
        case 4: $namer = new RessourcesSpecialiseesHabitationNamer(); break;
        // ... 7 autres cas avec `new` direct
    }
}
```

**Solution :** Convertir en service injectable avec un `NamerFactory` utilisant le tagged service locator de Symfony.

**Plus-value concrète :** Les méthodes statiques contournent le conteneur de services Symfony, ce qui a deux conséquences : (1) il est impossible de les remplacer par un mock dans les tests (pas de `$this->createMock()`), et (2) si ces classes ont besoin de dépendances futures (config, logger, etc.), il faudra les passer en paramètre manuellement au lieu de bénéficier de l'autowiring. Le tagged service locator permet en plus d'ajouter de nouveaux "namers" sans modifier la factory (Open/Closed Principle).

> **✅ Validé** — Priorité basse, à faire quand la testabilité de ces générateurs devient un besoin concret.

---

### REF-SVC-05 — DashboardService (635 lignes) et CalendarService (532 lignes) — SRP

| | |
|---|---|
| **Priorité** | 🟢 Basse |
| **Complexité** | Modérée |
| **Impact montée version** | Maintenabilité |
| **Worktree** | `refactor/dashboard-calendar-split` |

**Problème :** Ces deux services sont volumineux mais moins critiques que Search et Etablissement.

**Fichiers :**
- `src/Service/Dashboard/DashboardService.php` — 635 lignes, 6 dépendances
- `src/Service/Commission/CalendarService.php` — 532 lignes, 15 méthodes publiques

**`CalendarService` mélange :**
- Parsing de dates JavaScript (lignes 70-155) → extraire en `DateParser`
- Opérations CRUD sur DateCommission (lignes 162-468) → garder dans CalendarService
- Construction de DTO depuis Request (ligne 215) → voir REF-SVC-03

> **✅ Validé** — Priorité basse.

---

## 4. Repositories et requêtes SQL

### REF-REP-01 — SQL brut massif dans les Repositories (57 occurrences de `getConnection()`)

| | |
|---|---|
| **Priorité** | 🔴 Haute |
| **Complexité** | Complexe |
| **Impact montée version** | Doctrine 3.x supprime certaines API DBAL, maintenabilité |
| **Worktree** | `refactor/repositories-querybuilder` (à découper par repository) |

**Problème :** 57 appels à `getConnection()` répartis dans 8 fichiers pour exécuter du SQL brut au lieu d'utiliser le QueryBuilder Doctrine ou DQL.

**Répartition par fichier :**

| Repository | Occurrences `getConnection()` | Lignes totales |
|---|---|---|
| `DossierRepository.php` | 27 | 1491 |
| `EtablissementRepository.php` | 8 | 618 |
| `EtablissementInformationsRepository.php` | 7 | ~280 |
| `DateCommissionRepository.php` | 9 | 345 |
| `CommissionRepository.php` | 1 | — |
| `PeriodiciteRepository.php` | 1 | — |
| `DossierAffectationRepository.php` | 1 | — |
| `CalendrierDesCommissionsController.php` | 5 | — (controller !) |

**Exemple typique (`DossierRepository.php`, lignes 737-791) :**
```php
public function findDossierDateCommissionEchu(array $idsCommission, int $sinceDays = 10, int $untilDays = 100): array
{
    $conn = $this->getEntityManager()->getConnection();
    $sql = 'SELECT d.*, dossierlie.*,
            dossiernatureliste.LIBELLE_DOSSIERNATURE, ...
        FROM dossier d
        LEFT JOIN dossierlie ON d.ID_DOSSIER = dossierlie.ID_DOSSIER2
        JOIN dossiernature ON dossiernature.ID_DOSSIER = d.ID_DOSSIER
        -- ... 15 JOINs, sous-requêtes corrélées ...
        WHERE DATEDIFF(CURDATE(), datecommission.DATE_COMMISSION) >= :sinceDays';
    // 50+ lignes de SQL
}
```

**Impact :**
- Retourne `array<int, array<string, mixed>>` au lieu d'entités typées
- Pas de lazy loading ni d'hydratation Doctrine
- Impossible de bénéficier du Second Level Cache
- Certains construisent des `IN(...)` avec concaténation (risque injection si non intval)

**Stratégie de migration recommandée :**
1. **Phase 1 :** Migrer les requêtes simples (SELECT/UPDATE simples) vers QueryBuilder
2. **Phase 2 :** Pour les requêtes complexes (sous-requêtes corrélées, GROUP BY + agrégats), garder du SQL natif mais via `NativeQuery` avec `ResultSetMapping` pour obtenir des entités typées
3. **Phase 3 :** Remplacer les requêtes de tableau de bord par des DTOs dédiés

> **✅ Validé**

---

### REF-REP-02 — `findAll()` sans pagination (29 appels dans les controllers)

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Simple |
| **Impact montée version** | Performance prévisible, conformité règles projet |
| **Worktree** | `refactor/findall-pagination` |

**Problème :** 29 appels à `findAll()` dans les controllers, sans pagination. Si les tables de référence grossissent, cela causera des problèmes de mémoire.

**Fichiers les plus concernés :**

| Controller | Appels | Entités |
|---|---|---|
| `DossierController.php` | 6 | `DossierType`, `Avis`, `CommissionType` (dans boucle !) |
| `GroupementController.php` | 3 | `GroupementType`, `Commune` (⚠️ peut être volumineuse) |
| `DocumentsController.php` | 2 | `Commission` |
| `TableauDesPeriodicitesController.php` | 4 | `Periodicite`, `EtablissementType`, `EtablissementCategorie`, `IghClasse` |
| `SearchController.php` | 1 | `DossierType` |
| `CoucheCartoRepository.php` | 1 | `CoucheCarto` (tri en PHP !) |

**Exemple problématique (`GroupementController.php`, ligne 72) :**
```php
$communes = $this->communeRepository->findAll(); // ⚠️ ~36 000 communes en France
```

**Solution :** Pour les tables de référence petites (types, catégories) : tolérable. Pour `Commune` : ajouter un autocomplete AJAX ou une pagination.

> **✅ Validé** — Pour les filtres/sélections (contexte habituel de ces appels), **préférer un autocomplete AJAX** plutôt qu'une pagination classique : meilleure UX, pas de rechargement de page, et solution cohérente avec le fait qu'il s'agit de sélecteurs et non de vraies listes paginées.

**Cas du tri en PHP (`CoucheCartoRepository.php`, ligne 55) :**
```php
// AVANT
$couchesCarto = $this->findAll();
usort($couchesCarto, function ($a, $b): int {
    return ($a->getOrdre() ?? 0) - ($b->getOrdre() ?? 0);
});

// APRÈS
return $this->createQueryBuilder('c')
    ->orderBy('c.ordre', 'ASC')
    ->getQuery()
    ->getResult();
```

---

### REF-REP-03 — Retours `array<string, mixed>` au lieu de DTOs typés

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Modérée |
| **Impact montée version** | PHPStan, autocomplétion, maintenabilité |
| **Worktree** | `refactor/repository-dtos` |

**Problème :** 20+ méthodes de repository retournent `array<int, array<string, mixed>>` car elles utilisent du SQL brut. Les consommateurs (controllers, services, templates) accèdent aux données par clés de chaîne non typées.

**Méthodes principales :**
- `DossierRepository` : `findDossierDateCommissionEchu()`, `findDossierAvecAvisDiffere()`, `findCourrierSansReponse()`, `findLeveePresc()`, `findAbsenceQuorum()`, `findNpsp()`, `findDossiersIncomplets()`, `findDossiersSuivisNonVerrouilles()`, `findDossiersSuivisSansAvis()` (9 méthodes)
- `EtablissementRepository` : `findERPOuvertsSousAvisDefavorable()`, `findERPOuvertsSansProchainesVisitePeriodiques()`, `findERPSansPreventionniste()`, `findERPSuivisByUser()` (4 méthodes)
- `DateCommissionRepository` : `findNextCommissionsForDashboard()`, `findDossierAffectForCommission()`, `findDossierNonAffectForCommission()` (3 méthodes)

**Solution :** Créer des DTOs read-only (`DossierDashboardDto`, `EtablissementAlertDto`, etc.) et hydrater dans le repository.

```php
// APRÈS — exemple
/**
 * @return DossierAvisDiffereDto[]
 */
public function findDossierAvecAvisDiffere(array $idsCommission): array
{
    // ... SQL ...
    return array_map(function (array $row): DossierAvisDiffereDto {
        return DossierAvisDiffereDto::fromArray($row);
    }, $rows);
}
```

> **✅ Validé**

---

## 5. Formulaires

### REF-FORM-01 — DataTransformers manquants (conversions manuelles dans controllers)

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Modérée |
| **Impact montée version** | Séparation des couches, testabilité formulaires |
| **Worktree** | `refactor/form-datatransformers` |

**Problème :** Un seul DataTransformer existe (`DateCommissionTransformer`). Plusieurs controllers effectuent des conversions manuelles post-submission.

**Fichier existant :** `src/Form/DataTransformer/DateCommissionTransformer.php`

**Conversions manuelles détectées :**

| Controller | Ligne | Conversion |
|---|---|---|
| `TextesApplicablesController` | 63, 87 | `filter_var($form->get('prescription')->getData(), FILTER_VALIDATE_BOOLEAN)` |
| `DossierController` | 474 | `(bool) $form->get('repercuterAvis')->getData()` |
| `DossierController` | 287, 289 | `$form->get('idAffectationDossierCommission')->getData()` — ID extraction |
| `GroupementController` | — | Boolean & collection handling post-submit |

**Solution — créer `BooleanStringTransformer` :**
```php
class BooleanStringTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return $value ? '1' : '0';
    }

    public function reverseTransform($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
```

> **✅ Validé**

---

### REF-FORM-02 — Contraintes de validation insuffisantes dans les formulaires

| | |
|---|---|
| **Priorité** | 🟢 Basse |
| **Complexité** | Simple |
| **Impact montée version** | Robustesse, validation côté serveur |
| **Worktree** | `refactor/form-constraints` |

**Problème :** Seuls 7 formulaires sur 71 utilisent des `constraints` dans leur configuration. La plupart se reposent uniquement sur les contraintes d'entité ou aucune validation.

**Formulaires avec contraintes (7) :** `CommissionDocumentCrType`, `CommissionMembreCourrierUploadType`, `DocumentType`, `DossierAjouterEtablissementType`, `DossierLiaisonsType`, `PrescriptionDossierType`, `PrescriptionTypeArticleType`.

**Formulaires sans contraintes (64) :** Tous les autres, y compris `DossierType` (434 lignes), `ContactType`, `CommissionType`, etc.

**Solution :** Ajouter des contraintes `@Assert\NotBlank`, `@Assert\Length`, etc. sur les entités ET/OU dans les formulaires pour les cas non couverts.

> **✅ Validé avec prudence** — Risque de régression par rapport au comportement legacy. Approche recommandée :
> - Ajouter uniquement les contraintes **certaines** au regard du legacy (champs obligatoires clairement identifiés côté legacy)
> - Pour les contraintes incertaines, ajouter un commentaire `// @todo: vérifier la contrainte legacy avant d'ajouter @Assert\...`
> - Ne pas viser l'exhaustivité en une seule passe

---

## 6. Templates Twig

### REF-TWIG-01 — Chemins d'assets hardcodés (`src='/images/...'`)

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Simple |
| **Impact montée version** | Webpack Encore, cache busting, CDN |
| **Worktree** | `refactor/twig-asset-paths` |

**Problème :** 7 occurrences de `src='/images/...'` hardcodés dans les templates au lieu d'utiliser la fonction Twig `{{ asset() }}`.

**Fichiers :**
- `templates/search/results/_etablissement.html.twig` — lignes 14, 49
- `templates/search/results/_dossier_tab.html.twig` — ligne 29
- `templates/calendrier_des_commissions/gestionodj.html.twig` — lignes 285, 307, 329, 351

```twig
{# AVANT #}
<img src='/images/types/b/icone-type-{{ item.LIBELLE_TYPE }}.png' />
<img src='/images/load.gif' />

{# APRÈS #}
<img src='{{ asset("images/types/b/icone-type-" ~ item.LIBELLE_TYPE ~ ".png") }}' />
<img src='{{ asset("images/load.gif") }}' />
```

> **✅ Validé**

---

### REF-TWIG-02 — Liens hardcodés (`href='/...'`) au lieu de `path()`

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Simple |
| **Impact montée version** | Flexibilité routing, préfixe d'URL |
| **Worktree** | `refactor/twig-route-paths` |

**Problème :** 8+ occurrences de `href='/...'` hardcodés au lieu d'utiliser `{{ path('route_name') }}`.

**Fichiers :**
- `templates/_header.html.twig` — lignes 22, 40 : `href='/etablissement/add'`, `href='/statistiques'`
- `templates/search/results/_etablissement.html.twig` — ligne 23 : `href='/etablissement/{{ item.ID_ETABLISSEMENT }}/informations'`
- `templates/search/results/_dossier_tab.html.twig` — lignes 21, 41 : `href='/dossier/...'`, `href='/etablissement/...'`
- `templates/etablissement/dossiers/_item.html.twig` — ligne 15 : `href='/dossier/piece-jointe/id/...'`
- `templates/bundles/TwigBundle/Exception/error403.html.twig` — ligne 14 : `href='/'`
- `templates/bundles/TwigBundle/Exception/error.html.twig` — ligne 26 : `href='/'`

> **Note :** Le commentaire dans `_header.html.twig` (ligne 1) confirme que c'est un TODO connu : `{# @todo: Modifier les href pour utiliser le helper route() de SF une fois les routes ajoutées #}`

**Solution :**
```twig
{# AVANT #}
<a href='/etablissement/{{ item.ID_ETABLISSEMENT }}/informations'>

{# APRÈS #}
<a href='{{ path("etablissement_informations", {id: item.ID_ETABLISSEMENT}) }}'>
```

> Certains liens pointent vers des routes Zend non encore migrées (`/statistiques`). Pour ceux-là, il faut soit migrer la route, soit garder le lien hardcodé avec un commentaire.

> **✅ Validé avec périmètre** — Appliquer `path()` uniquement pour les routes **déjà migrées dans Symfony**. Pour les routes encore Zend, conserver le lien hardcodé en ajoutant `{# @todo: utiliser path() une fois la route migrée #}` (le TODO est déjà en place dans `_header.html.twig`).

---

### REF-TWIG-03 — Logique métier dans les templates

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Modérée |
| **Impact montée version** | Maintenabilité, testabilité |
| **Worktree** | `refactor/twig-business-logic` |

**Problème :** Des chaînes conditionnelles complexes encodent de la logique métier directement dans les templates Twig.

**Exemple le plus flagrant (`templates/search/results/_dossier_tab.html.twig`, lignes 38-66) :**
```twig
{% if item.VERROU_DOSSIER == 1 %}
    ...
{% endif %}
{% if item.INCOMPLET_DOSSIER == 1 %}
    <span ...>Dossier incomplet</span>
{% elseif item.DIFFEREAVIS_DOSSIER == 1 %}
    ...
{% elseif item.TYPE_DOSSIER == 5 and item.DATEREP_DOSSIER is not empty %}
    ...
{% elseif item.TYPE_DOSSIER == 5 and item.DATEREP_DOSSIER is empty %}
    ...
{% elseif item.ID_AVIS == 1 %}
    <span ...>Favorable {% if item.ID_DOSSIERNATURE in [19, 21, 23, 24, 47, 26, 28, 29, 48] %} à l'exploitation{% endif %}</span>
{% elseif item.ID_AVIS == 2 %}
    <span ...>Défavorable {% if item.ID_DOSSIERNATURE in [19, 21, 23, 24, 47, 26, 28, 29, 48] %} à l'exploitation{% endif %}</span>
{% else %}
    <span ...>Avis indisponible</span>
{% endif %}
```

**Solution :** Créer une extension Twig `DossierStatusExtension` :
```php
// src/Twig/DossierStatusExtension.php
public function getFilters(): array
{
    return [
        new TwigFilter('dossier_status_badge', [$this, 'renderStatusBadge'], ['is_safe' => ['html']]),
    ];
}

/**
 * @param array<string, mixed> $item
 */
public function renderStatusBadge(array $item): string
{
    // Logique extraite du template
}
```

```twig
{# APRÈS #}
{{ item|dossier_status_badge }}
```

> **✅ Validé**

---

### REF-TWIG-04 — JavaScript inline massif dans les templates

| | |
|---|---|
| **Priorité** | 🟢 Basse |
| **Complexité** | Complexe |
| **Impact montée version** | CSP, maintenabilité, Webpack Encore |
| **Worktree** | `refactor/twig-extract-js` |

**Problème :** Plusieurs templates contiennent des centaines de lignes de JavaScript inline dans des balises `<script>`.

**Templates les plus touchés :**
- `templates/calendrier_des_commissions/gestionodj.html.twig` — 400+ lignes de JS (calendrier)
- `templates/search/etablissement.html.twig` — JS de recherche inline
- `templates/components/_formulaire_personnalise_edit.html.twig` — JS de formulaire dynamique
- `templates/components/_contacts_panel.html.twig` — JS de panneau contacts

**Impact :** Bloque l'activation d'une Content Security Policy (CSP), empêche la minification et le cache navigateur.

**Solution :** Extraire vers des fichiers JS dédiés dans `assets/` et les charger via `{{ encore_entry_script_tags() }}` ou `<script src="{{ asset(...) }}">`.

> **✅ Validé** — Priorité basse, complexité élevée.

## 7. Event Subscribers

### REF-EVT-01 — `isMasterRequest()` déprécié (2 occurrences)

| | |
|---|---|
| **Priorité** | 🟡 Moyenne |
| **Complexité** | Simple |
| **Impact montée version** | Déprécié Symfony 5.3, supprimé Symfony 7 |
| **Worktree** | `refactor/subscriber-isMainRequest` |

**Problème :** Deux event subscribers utilisent `isMasterRequest()` qui est déprécié depuis Symfony 5.3 au profit de `isMainRequest()`.

**Fichiers :**
- `src/EventSubscriber/LastActionSubscriber.php` — ligne 49
- `src/EventSubscriber/SymfonyRouteFoundSubscriber.php` — ligne 30

```php
// AVANT
if (!$event->isMasterRequest()) { return; }

// APRÈS (Symfony 5.3+)
if (!$event->isMainRequest()) { return; }
```

> **Note :** Sur Symfony 4.4, `isMainRequest()` n'existe pas encore. Ce changement doit être fait lors de la montée vers Symfony 5.3+. En attendant, documenter comme prêt à changer.

> **✅ Documentation uniquement** — Aucune action à mener sur SF 4.4. Les deux occurrences sont correctes en l'état. Ajouter un commentaire `// @todo SF 5.3: remplacer par isMainRequest()` dans les deux fichiers pour guider la prochaine montée de version.

---

## 8. Configuration et DI

### REF-CFG-01 — Configuration Guard dans security.php

| | |
|---|---|
| **Priorité** | 🔴 Haute |
| **Complexité** | Modérée |
| **Impact montée version** | Bloquant Symfony 6 |
| **Worktree** | `refactor/authenticator-migration` (même tâche que REF-SEC-01) |

**Problème :** La configuration du firewall utilise la clé `guard` qui est supprimée en Symfony 6.

**Fichier :** `config/packages/security.php`

```php
// AVANT
'guard' => [
    'authenticators' => ['App\Security\LegacySessionAuthenticator'],
    'entry_point' => 'App\Security\LegacySessionAuthenticator',
]

// APRÈS (Symfony 5.1+)
'custom_authenticators' => ['App\Security\LegacySessionAuthenticator'],
'entry_point' => 'App\Security\LegacySessionAuthenticator',
```

> **❌ N/A — SF 4.4** — La clé `custom_authenticators` n'existe pas en SF 4.4. La migration vers le nouveau système d'authentification est indissociable de la montée en SF 5.1+. Ce point est traité conjointement avec REF-SEC-01 lors de la montée de version, pas avant.

---

## 9. Roadmap worktrees suggérée

Les tâches sont ordonnées par priorité puis par dépendances. Chaque worktree est indépendant et peut être mené en parallèle des autres, sauf mention contraire.

> **Contexte solo :** La migration Zend → Symfony reste la priorité principale. Les worktrees de refactor peuvent être ouverts en parallèle mais chaque tâche implique une relecture et une passe de tests avant merge. Privilégier les tâches à faible risque de régression (Simple/Modérée) pour éviter de bloquer la branche principale.

### Phase 1 — Préparation montée de version (actionnables sur SF 4.4)

| # | Worktree | Refs | Jours (hors relecture) | Prérequis |
|---|----------|------|------------------------|-----------|
| 1 | `refactor/authenticator-migration` | REF-SEC-01 (prep), REF-SEC-02 | 2-3 | Aucun — extraction logique credentials uniquement, pas de migration Guard |
| 2 | `refactor/controller-return-types` | REF-CTL-02 | — | ✅ Déjà fait — worktree non nécessaire |
| 3 | `refactor/subscriber-authchecker` | REF-SEC-03 | 0.5 | Aucun |
| 4 | `refactor/subscriber-isMainRequest` | REF-EVT-01 (todo comments) | 0.5 | Aucun — ajout de commentaires @todo uniquement |

> **⚠️ REF-CFG-01 exclu de cette phase** — La migration de la config `guard` est N/A sur SF 4.4 (voir section 8).

### Phase 2 — Haute priorité structurelle

| # | Worktree | Refs | Jours (hors relecture) | Prérequis |
|---|----------|------|------------------------|-----------|
| 5 | `refactor/calendrier-controller-split` | REF-CTL-01 | 5-7 | Aucun |
| 6 | `refactor/search-service-split` | REF-SVC-01 | 3-5 | Vérifier `develop` d'abord (exports potentiellement déjà extraits) |
| 7 | `refactor/repositories-dossier-qb` | REF-REP-01 (DossierRepository) | 5-7 | Aucun |

### Phase 3 — Moyenne priorité

| # | Worktree | Refs | Jours (hors relecture) | Prérequis |
|---|----------|------|------------------------|-----------|
| 8 | `refactor/twig-asset-paths` | REF-TWIG-01, REF-TWIG-02 | 1-2 | Aucun — risque faible |
| 9 | `refactor/findall-autocomplete` | REF-REP-02 | 2-3 | Aucun — autocomplete AJAX pour Commune, tolérer le reste |
| 10 | `refactor/form-datatransformers` | REF-FORM-01 | 2-3 | Aucun |
| 11 | ~~`refactor/etablissement-service-split`~~ ✅ | REF-SVC-02 | — | Terminé |
| 12 | `refactor/services-no-request` | REF-SVC-03 | 1-2 | Aucun — CalendarService en priorité, RessourcesSpecialisees à évaluer |
| 13 | `refactor/twig-business-logic` | REF-TWIG-03 | 2-3 | Aucun |
| 14 | `refactor/paramconverter` | REF-CTL-03 | 2-3 | Aucun — exclure ContactController (pattern intentionnel) |
| 15 | `refactor/repository-dtos` | REF-REP-03 | 3-4 | Phase 2 #7 |

### Phase 4 — Basse priorité

| # | Worktree | Refs | Jours (hors relecture) | Prérequis |
|---|----------|------|------------------------|-----------|
| 16 | `refactor/json-responses` | REF-CTL-04 | 0.5 | Aucun |
| 17 | `refactor/static-to-services` | REF-SVC-04 | 1-2 | Aucun |
| 18 | `refactor/form-constraints` | REF-FORM-02 | 2-3 | Aucun — contraintes certaines uniquement, @todo pour le reste |
| 19 | `refactor/dashboard-calendar-split` | REF-SVC-05 | 2-3 | Aucun |
| 20 | `refactor/twig-extract-js` | REF-TWIG-04 | 5-7 | Aucun |

### Recommandations pour le contexte solo

En tant que développeur unique avec la migration en cours, les worktrees les plus adaptés à paralléliser sont ceux à **risque faible et durée courte** :

| Candidats prioritaires pour worktree solo | Justification |
|-------------------------------------------|---------------|
| `refactor/controller-return-types` (#2) | Automatisable, non fonctionnel, testable rapidement |
| `refactor/subscriber-authchecker` (#3) | 0.5j, changement purement structurel, testable |
| `refactor/twig-asset-paths` (#8) | Simple, visuel, facile à régresser test |
| `refactor/json-responses` (#16) | 0.5j, risque quasi nul |

Les tâches complexes (REF-CTL-01, REF-REP-01, REF-SVC-01) sont à réserver à des périodes de calme sur la migration principale.

---

## 10. Tableau de synthèse

> **Légende Réalisé :** ✅ Fait · ⚠️ Partiel · 🔴 À faire · ❌ N/A  
> **Légende Statut :** indique si l'approche/solution est validée, pas si elle est implémentée.

| Ref | Domaine | Problème | Priorité | Complexité | Statut | Réalisé | Mesure actuelle | Worktree |
|-----|---------|----------|----------|------------|--------|---------|-----------------|----------|
| REF-SEC-01 | Sécurité | Guard Authenticator déprécié | 🔴 Haute | Modérée | ❌ N/A SF 4.4 — migration Guard impossible avant SF 5.1+ et migration complète | ❌ N/A | — | `refactor/authenticator-migration` |
| REF-SEC-02 | Sécurité | `$_SESSION` directe | 🟡 Moyenne | Simple | ✅ Validé | ✅ Fait | `ZendSessionBridge` encapsule `$_SESSION['Zend_Auth']`; `$_SESSION['phpCAS']` = lib CAS, hors périmètre | `refactor/authenticator-migration` |
| REF-SEC-03 | Sécurité | Appel direct au Voter | 🟡 Moyenne | Simple | ✅ Validé (via PrivilegeVoter) | ✅ Fait | `isGranted()` via AuthorizationChecker | `refactor/subscriber-authchecker` |
| REF-CTL-01 | Controller | Controller 1494 lignes | 🔴 Haute | Complexe | ✅ Validé | 🔴 À faire | 1646 lignes (+152 depuis rapport) | `refactor/calendrier-controller-split` |
| REF-CTL-02 | Controller | 72 méthodes sans return type | 🔴 Haute | Simple | ✅ Terminé (code déjà correct) | ✅ Fait | 0 méthode sans return type | — |
| REF-CTL-03 | Controller | 41 find() manuels | 🟡 Moyenne | Modérée | ⚠️ À analyser au cas par cas | 🔴 À faire | ~49 find() manuels | `refactor/paramconverter` |
| REF-CTL-04 | Controller | `json_encode()` manuel | 🟢 Basse | Simple | ✅ Validé | ✅ Fait | 3 restants (sérialisation interne, non-réponse) | `refactor/json-responses` |
| REF-SVC-01 | Service | Search monolithique SQL brut | 🔴 Haute | Complexe | ⚠️ Vérifier sur `develop` | 🔴 À faire | `Search.php` = 1386 lignes | `refactor/search-service-split` |
| REF-SVC-02 | Service | Etablissement 795 lignes, SRP | 🟡 Moyenne | Modérée | ✅ Validé | ✅ Fait | 3 services : 490+250+232 lignes | `refactor/etablissement-service-split` |
| REF-SVC-03 | Service | 5 services avec Request | 🟡 Moyenne | Simple | 💬 À étudier (factory DTO = pattern valide) | 🔴 À faire | Services injectant Request présents | `refactor/services-no-request` |
| REF-SVC-04 | Service | Classes statiques | 🟢 Basse | Simple | ✅ Validé (quand testabilité requise) | 🔴 À faire | ~39 méthodes statiques | `refactor/static-to-services` |
| REF-SVC-05 | Service | Dashboard/Calendar SRP | 🟢 Basse | Modérée | ✅ Validé | 🔴 À faire | Non analysé | `refactor/dashboard-calendar-split` |
| REF-REP-01 | Repository | 57 `getConnection()` SQL brut | 🔴 Haute | Complexe | ✅ Validé | ⚠️ Partiel | 29 `getConnection()` (était 57, −49 %) | `refactor/repositories-querybuilder` |
| REF-REP-02 | Repository | 29 `findAll()` sans pagination | 🟡 Moyenne | Simple | ✅ Autocomplete AJAX (pas pagination) | 🔴 À faire | 32 `findAll()` dans controllers/services | `refactor/findall-autocomplete` |
| REF-REP-03 | Repository | Retours `array<mixed>` | 🟡 Moyenne | Modérée | ✅ Validé | 🔴 À faire | 108 fonctions retournant `array` | `refactor/repository-dtos` |
| REF-FORM-01 | Formulaire | DataTransformers manquants | 🟡 Moyenne | Modérée | ✅ Validé | ⚠️ Partiel | 2 DataTransformers créés (Boolean, DateCommission) | `refactor/form-datatransformers` |
| REF-FORM-02 | Formulaire | 64 forms sans contraintes | 🟢 Basse | Simple | ✅ Contraintes certaines + @todo | 🔴 À faire | 55 formulaires sans contraintes | `refactor/form-constraints` |
| REF-TWIG-01 | Template | Assets hardcodés | 🟡 Moyenne | Simple | ✅ Validé | 🔴 À faire | Non re-mesuré | `refactor/twig-asset-paths` |
| REF-TWIG-02 | Template | Liens hardcodés | 🟡 Moyenne | Simple | ✅ Routes migrées uniquement | 🔴 À faire | Non re-mesuré | `refactor/twig-asset-paths` |
| REF-TWIG-03 | Template | Logique métier dans Twig | 🟡 Moyenne | Modérée | ✅ Validé | 🔴 À faire | Non re-mesuré | `refactor/twig-business-logic` |
| REF-TWIG-04 | Template | JavaScript inline massif | 🟢 Basse | Complexe | ✅ Validé | 🔴 À faire | Non re-mesuré | `refactor/twig-extract-js` |
| REF-EVT-01 | Subscriber | `isMasterRequest()` déprécié | 🟡 Moyenne | Simple | ✅ Documentation + @todo SF 5.3 | ✅ Fait | 2 occurrences avec `@todo` (SF 4.4 compatible) | `refactor/subscriber-isMainRequest` |
| REF-CFG-01 | Config | Guard firewall config | 🔴 Haute | Modérée | ❌ N/A SF 4.4 — traiter lors de la montée SF 5.1+ | ❌ N/A | — | — |

---

## Annexe A — Métriques des fichiers les plus volumineux

> Dernière mesure : 2026-05-07

| Fichier | Lignes | `getConnection()` | Méthodes publiques |
|---------|--------|--------------------|--------------------|
| `src/Controller/Commission/CalendrierDesCommissionsController.php` | 1646 | 5 | 12 |
| `src/Service/Search.php` | 1386 | 9 | 6 |
| `src/Repository/DossierRepository.php` | 1491 | 27 | 40+ |
| `src/Service/Etablissement.php` | 490 | 0 | 7 |
| `src/Service/EtablissementHistoriqueService.php` | 250 | 0 | 2 |
| `src/Service/EtablissementPieceJointeService.php` | 232 | 0 | 7 |
| `src/Controller/DossierController.php` | 776 | 0 | 10 |
| `src/Service/Dashboard/DashboardService.php` | 635 | 0 | 10+ |
| `src/Controller/SearchController.php` | 620 | 0 | 6 |
| `src/Controller/EtablissementController.php` | 629 | 0 | 8 |
| `src/Repository/EtablissementRepository.php` | 618 | 8 | 20+ |
| `src/Service/Commission/CalendarService.php` | 532 | 0 | 15 |

---

## Annexe B — Hors périmètre (rappel)

Les éléments suivants sont **exclus** de ce rapport car déjà couverts ailleurs :

- **RAPPORT_MODERNISATION.md** : PHPExcel → PhpSpreadsheet, Chosen → TomSelect, FancyBox, FullCalendar, `$_SERVER['DOCUMENT_ROOT']`, twig-cs-fixer, Rector, dépendances frontend.
- **Performances** : N+1 queries, index manquants, cache Doctrine (couvert par audit performance).
- **Typed properties PHP 8+** : Hors scope PHP 7.1.
- **Nouvelles fonctionnalités** : Pas de refactoring fonctionnel.
