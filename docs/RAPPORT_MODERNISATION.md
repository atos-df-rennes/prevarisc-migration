# Rapport de modernisation — prevarisc-migration

> Branche analysée : `chore/modernisation` → `chore/tom-select` (depuis `develop`)
> Date initiale : 2026-04-16 | Dernière mise à jour : 2026-04-20
> Périmètre : 449 fichiers PHP (`src/`), 101 fichiers de tests, 185 templates Twig

## Suivi d'avancement

| # | Action | Statut | Commit |
|---|--------|--------|--------|
| QW-1 | Ajouter `: array` sur `getSubscribedEvents()` | ✅ Fait | `9a926c6` |
| QW-2 | Renommer `dossierDonnantAvisId` → `dossier_donnant_avis_id` | ✅ Fait | `9a926c6` |
| QW-3 | `%kernel.project_dir%` à la place de `$_SERVER['DOCUMENT_ROOT']` | ✅ Fait | `9a926c6` |
| QW-4 | Chosen 1.1.0 → **1.8.7** | ✅ Fait | `a251c4a` |
| QW-5 | TomSelect 2.0 → **2.5.2** | ✅ Fait | `a251c4a` |
| QW-6 | SortableJS 1.15.0 → **1.15.7** | ✅ Fait | `a251c4a` |
| QW-7 | **Suppression de TableSorter** (bibliothèque inutilisée) | ✅ Fait | `cedcc69` (#172) |
| BUG-1 | Fix tooltip Tipsy orphelin sur bouton × TomSelect (`main-bs3.js`) | ✅ Fait | `fde17ae` |
| MT-1 | **PHPExcel → PhpSpreadsheet** (`SearchController`) | ✅ Fait | `db4e5e4` (#169) |
| MT-2 | Injecter `CacheInterface` dans `CouchesCarto` | ✅ Fait | `c76acdb` (#170) |
| MT-3 | **Multiselect + Chosen → TomSelect** (5 templates, layout Bootstrap) | ✅ Fait | `ca636a6`, `d2b14e1`, `4fb4438` |
| MT-4 | FancyBox 1.3.4 → 3.5.x | ⏳ À faire | — |
| MT-5 | FullCalendar 1.6.4 → 5.x | ⏳ À faire | — |

> **Worktrees clos :** `chore/modernisation` (QW-1→QW-6, BUG-1), `chore/export-phpspreadsheet` (MT-1), `chore/inject-couchecarto` (MT-2), `chore/table-sorter` (QW-7), `chore/tom-select` (MT-3).
> **Prochaine session :** MT-4 (FancyBox 1.3.4 → 3.5.x).

---

## Résumé exécutif

L'analyse du code déjà migré identifie **4 catégories d'opportunités**, avec des résultats d'outils **réels** (Rector et twig-cs-fixer exécutés via `prevarisc-infra-platau-1`, PHP 8.1.34) :

| Catégorie | Nb d'opportunités | Priorité globale |
|-----------|------------------|-----------------|
| Dépendances obsolètes/EOL | 3 | 🔴 Urgent (phpexcel) / 🟡 Moyen terme |
| Code PHP non-Symfony | 5 | 🟡 Moyen terme |
| Qualité code PHP | 3 | 🟢 Quick win |
| Twig (twig-cs-fixer) | 1 erreur | 🟢 Quick win |
| Frontend — quick wins BS3 | 4 | 🟢 Quick win immédiat |
| Frontend — moyen terme BS3 | 4 | 🟡 Moyen terme |
| Frontend — long terme | 2 | 🔵 Post-migration complète |

**Note outils** : Rector et twig-cs-fixer dans `tools/` requièrent PHP ≥ 8.1. Ils sont exécutables via le container `prevarisc-infra-platau-1` (PHP 8.1.34) qui monte déjà `prevarisc-migration/`. Les résultats Rector et twig-cs-fixer ci-dessous ont été obtenus via ce container.

---

## Matrice de priorités

| Priorité | Catégorie | Description | Effort | Impact | Risque |
|----------|-----------|-------------|--------|--------|--------|
| ~~🔴 Urgent~~ ✅ | Dépendances | ~~Remplacer `phpoffice/phpexcel` (EOL 2017) par `phpspreadsheet`~~ **Fait** (#169) | M | H | 🟡 |
| 🟡 Moyen | Dépendances | Monter `phpunit/phpunit` de 7.5 → 8.x (conditionné à PHP ≥ 7.2) | S | M | 🟢 |
| 🟡 Moyen | Dépendances | Planifier la suppression de `sensio/framework-extra-bundle` | S | M | 🟢 |
| 🟡 Moyen | Code PHP | Découper `SearchController::etablissement()` (344 lignes) et `SearchController::dossier()` (335 lignes) | L | H | 🟡 |
| ~~🟡 Moyen~~ ✅ | Code PHP | ~~Remplacer `$_SERVER['DOCUMENT_ROOT']` par le paramètre Symfony kernel~~ **Fait** (`9a926c6`) | S | L | 🟢 |
| ~~🟡 Moyen~~ ✅ | Code PHP | ~~Injecter `CacheInterface` dans `CouchesCarto` au lieu de `new FilesystemAdapter()`~~ **Fait** (#170) | S | L | 🟢 |
| ~~🟢 Quick win~~ ✅ | Code PHP | ~~Ajouter `: array` sur 2 méthodes `getSubscribedEvents()` sans return type~~ **Fait** (`9a926c6`) | XS | L | 🟢 |
| ~~🟢 Quick win~~ ✅ | Twig | ~~Renommer `dossierDonnantAvisId` → `dossier_donnant_avis_id` (twig-cs-fixer)~~ **Fait** (`9a926c6`) | XS | L | 🟢 |
| ~~🟢 Quick win~~ ✅ | Frontend | ~~Mettre à jour Chosen 1.1.0 → 1.8.7 (4 templates, compatible BS3)~~ **Fait** (`a251c4a`) | XS | L | 🟢 |
| ~~🟢 Quick win~~ ✅ | Frontend | ~~Mettre à jour TomSelect 2.0 → 2.5.2~~ **Fait** (`a251c4a`) | XS | L | 🟢 |
| ~~🟢 Quick win~~ ✅ | Frontend | ~~Mettre à jour SortableJS 1.15.0 → 1.15.7~~ **Fait** (`a251c4a`) | XS | L | 🟢 |
| ~~🟢 Quick win~~ ✅ | Frontend | ~~Mettre à jour TableSorter 2.0.5b → 2.31.3~~ **Supprimé (inutilisé)** (`cedcc69` #172) | S | M | 🟢 |
| ~~🟡 Moyen~~ ✅ | Frontend | ~~Remplacer jQuery Multiselect (plus maintenu) par TomSelect (déjà présent)~~ **Fait** + Chosen supprimé (`ca636a6`, `d2b14e1`, `4fb4438`) | M | M | 🟡 |
| 🟡 Moyen | Frontend | Remplacer FancyBox 1.3.4 (2010, EOL) par Fancybox 3.x (BS3 compatible) | M | M | 🟡 |
| 🟡 Moyen | Frontend | Remplacer FullCalendar 1.6.4 (2012, EOL) par FullCalendar 5.x | L | M | 🟡 |
| 🔵 Long terme | Frontend | Mettre à jour jQuery 1.10.2 → 3.x et les plugins associés | XL | H | 🔴 |
| 🔵 Long terme | Frontend | Migrer Bootstrap 3 → Bootstrap 5 + Webpack Encore | XL | H | 🔴 |
| 🔵 Long terme | Code PHP | Migrer les annotations `@Route` vers les attributs PHP 8 natifs | L | M | 🟡 |
| 🔵 Long terme | Code PHP | Rector : transformer automatiquement vers PHP 8.x (0 changements en PHP 7.1) | M | H | 🟡 |

---

## 1. Dépendances

### 1.1 Backend (Composer)

#### `phpoffice/phpexcel` v1.8.2 — 🔴 URGENT

**Statut** : EOL depuis décembre 2017. Dernière release : `1.8.2` en 2017. Le projet GitHub est archivé.

**Usages** : 32 occurrences dans `src/Controller/SearchController.php` uniquement (2 blocs d'export — `etablissement()` l. 192 et `dossier()` l. 566). Classes utilisées :
- `\PHPExcel`, `\PHPExcel_Style_Border`, `\PHPExcel_Style_Alignment`
- `\PHPExcel_Style_NumberFormat`, `\PHPExcel_Shared_Date`, `\PHPExcel_IOFactory`

**Remplacement recommandé** : `phpoffice/phpspreadsheet` (successeur officiel, actif, compatible PHP 7.1+).

**Migration** : L'API est similaire mais les noms de classes changent (`PHPExcel` → `Spreadsheet`, `PHPExcel_IOFactory` → `IOFactory`, etc.). Environ 2–3 heures de travail car le code d'export est concentré dans un seul fichier.

```bash
composer remove phpoffice/phpexcel
composer require phpoffice/phpspreadsheet
```

> ⚠️ Note : la méthode `etablissement()` fait 344 lignes et contient le code d'export inline (pas extrait en service). La migration PHPExcel est l'occasion d'extraire ce code dans un service `ExportSearchService`.

---

#### `phpunit/phpunit` ^7.5 — 🟡 Moyen terme

**Statut** : PHPUnit 7.5.20 est EOL depuis 2020. PHPUnit 8.x supporte PHP ≥ 7.2, PHPUnit 9.x supporte PHP ≥ 7.3. Avec PHP 7.1, la version maximale est PHPUnit 7.x (PHPUnit 8 exige PHP 7.2+).

**Conséquence** : On ne peut pas monter PHPUnit sans d'abord monter PHP. La montée vers PHPUnit 9 ou 10 est donc conditionnée à la montée de PHP 8.x.

**Action** : Planifier la mise à jour simultanée PHP 8.1 + PHPUnit 10 lors de la montée de version.

**Patterns PHPUnit 7 à corriger maintenant** (sans changer la version) :
- Aucun `@expectedException` détecté ✅
- Toutes les méthodes `setUp()` ont bien `: void` ✅
- Les `assertSame()` sont bien utilisés ✅

---

#### `sensio/framework-extra-bundle` ^5.1 — 🟡 Planifier

**Statut** : Déprécié depuis Symfony 5 ; la fonctionnalité est intégrée nativement depuis Symfony 6. On est en Symfony 4.4, donc aucune urgence fonctionnelle.

**Usages détectés** :
- `@Route` annotations sur la totalité des controllers (50 fichiers concernés)
- `@ParamConverter` potentiellement utilisé (à confirmer)

**Action recommandée** : Ne pas migrer maintenant. Lors du passage à PHP 8.x + Symfony 6, remplacer les annotations `@Route` par les attributs PHP natifs `#[Route(...)]` via Rector (règle `SensioRouteAnnotationToSymfonyAttributeRector`).

---

#### `phpdocumentor/reflection-docblock` ^4.3 — 🟢 À auditer

**Statut** : Aucune utilisation directe trouvée dans `src/`. C'est une dépendance transitive (utilisée par Doctrine/Serializer pour l'introspection des DocBlocks). Elle peut être conservée telle quelle.

---

#### `sebastian/git` ^2.1.4 — 🟢 Usage limité

**Statut** : Utilisé dans `src/VersionType/GithubType.php` uniquement. Dépendance légère pour récupérer le SHA du commit courant. Pas obsolète, mais dépendance inutile en production si l'info vient d'une variable d'environnement.

---

### 1.2 Frontend

Les librairies sont chargées en tant que fichiers statiques depuis `public/js/` et `public/css/` (pas de CDN externe — ✅ bonne pratique). Les versions ont été vérifiées directement dans les fichiers source.

#### État global des librairies (cible : Bootstrap 3)

| Librairie | Version actuelle | Statut | Action | Horizon |
|-----------|-----------------|--------|--------|---------|
| jQuery | **1.10.2** (2013) | 🔴 EOL | Monter vers 3.7.x (cassant, débloqué par la fin de migration) | 🔵 Long terme |
| jQuery Migrate | **1.2.1** | 🔴 Obsolète | Supprimer après montée jQuery | 🔵 Long terme |
| jQuery UI | **1.10.3** | 🔴 EOL | Identifier usages exacts et remplacer progressivement | 🔵 Long terme |
| Bootstrap | **3.4.1** | ✅ Dernière v3 | Conserver — cible actuelle | — |
| **Chosen** | **1.1.0** (2013) | 🟡 Peu maintenu | ✅ Monter vers **1.8.7** (dernière stable, BS3 compatible) | 🟢 Quick win |
| **TomSelect** | **2.0** | ✅ Actif | ✅ Monter vers **2.4.x** (patch/minor, compatible) | 🟢 Quick win |
| **SortableJS** | **1.15.0** | ✅ Actif | ✅ Monter vers **1.15.6** (patch) | 🟢 Quick win |
| **TableSorter** | **2.0.5b** (2009) | 🟡 Maintenance réduite | Monter vers **2.31.x** (aucune dépendance BS) | 🟢 Quick win |
| **FancyBox** | **1.3.4** (2010) | 🔴 EOL | Remplacer par Fancybox **3.5.x** (BS3 compatible) | 🟡 Moyen terme |
| **FullCalendar** | **1.6.4** (2012) | 🔴 EOL | Remplacer par FullCalendar **5.x** (BS3 compatible via thème) | 🟡 Moyen terme |
| jQuery Multiselect | Ancienne | 🔴 Abandonné | Remplacer par TomSelect (déjà chargé, 10 templates vs 4) | 🟡 Moyen terme |
| jQuery Tipsy | Ancienne (2012) | 🔴 EOL | Remplacer par tooltips Bootstrap 3 natifs | 🟡 Moyen terme |
| TinyMCE | Récente | ✅ Actif | — | — |
| Dropzone | Récente | ✅ Actif | — | — |
| FOS JS Routing | 2.8 | ✅ Actif | — | — |

---

#### Quick wins frontend (réalisables maintenant, Bootstrap 3 inchangé)

**Chosen 1.1.0 → 1.8.7**

Chosen est utilisé dans **4 templates** (`_header.html.twig`, et 3 autres). TomSelect est déjà présent dans **10 templates** : la cohabitation est acceptée le temps de la migration. La mise à jour 1.1.0 → 1.8.7 est rétrocompatible et apporte des correctifs de sécurité et de compatibilité navigateur.

```bash
# Télécharger chosen.jquery.min.js 1.8.7 depuis le repo GitHub/chosen
# Remplacer public/js/chosen.jquery.min.js et public/css/chosen.min.css
```

**TomSelect 2.0 → 2.4.x**

Mise à jour mineure/patch sans changement d'API. Apporte des correctifs et améliorations de performance.

**SortableJS 1.15.0 → 1.15.6**

Utilisé pour le drag-and-drop des rubriques dans les formulaires descriptifs. Patch release.

**TableSorter 2.0.5b → 2.31.x**

Bibliothèque pure (sans dépendance jQuery UI ou Bootstrap), mise à jour directement substituable. Apporte le support des colonnes avec `colspan`, un meilleur tri des dates françaises, et des correctifs majeurs.

---

#### Moyen terme frontend (BS3 compatible, effort modéré)

**FancyBox 1.3.4 → 3.5.x**

FancyBox 1.3.4 date de novembre 2010 et n'est plus maintenu. FancyBox 3.5.x est compatible Bootstrap 3 et ne dépend plus de jQuery UI. L'API a changé mais les appels sont localisables via `grep -r "fancybox\|$.fancybox" templates/ src/`.

**FullCalendar 1.6.4 → 5.x**

FullCalendar 1.6.4 date de 2012, l'API a été complètement réécrite depuis la v3. La v5 est compatible avec Bootstrap 3 via le thème `bootstrap` optionnel. Nécessite une réécriture des appels JS (`$('#calendrier').fullCalendar({})` → `new Calendar(el, {})`). À faire si le calendrier est une fonctionnalité active.

**Multiselect → TomSelect**

jQuery Multiselect est abandonné. TomSelect est déjà chargé dans la base. La migration est template par template (4 restants avec `chosen`, le reste utilisant déjà TomSelect).

---

## 2. Code PHP

### 2.1 Rector — Transformations automatisables

> ✅ **Rector exécuté avec succès** via `prevarisc-infra-platau-1` (PHP 8.1.34), qui monte déjà `prevarisc-migration/`.
>
> ```bash
> docker exec prevarisc-infra-platau-1 sh -c \
>   "cd /var/www/html/prevarisc-migration && tools/vendor/bin/rector --dry-run --no-progress-bar"
> ```
>
> **Résultat : `[OK] Rector is done!` — 0 fichier modifié.**

Le code est **entièrement conforme** à la configuration Rector actuelle (PHP 7.1, type coverage 50, dead code 50, code quality 50, coding style, privatization, earlyReturn, Symfony + Doctrine + Twig). Aucune transformation automatique à appliquer pour la cible PHP 7.1.

**Conséquence** : Rector sera le levier principal lors de la montée vers PHP 8.1. À ce moment :
1. Mettre à jour `tools/` : `composer update` dans `tools/`
2. Monter la cible dans `rector.php` : `withPhpVersion(PhpVersion::PHP_81)`
3. Augmenter les niveaux : `withTypeCoverageLevel(100)`, `withDeadCodeLevel(100)`, etc.
4. Lancer : `castor symfony:cs` (appelle Rector en mode apply)

---

### 2.2 Patterns non-Symfony

#### `$_SERVER['DOCUMENT_ROOT']` dans un controller — 🟡

**Fichier** : `src/Controller/CouchesCartographiquesController.php:48`

```php
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
```

**Problème** : Accès direct au superglobal PHP au lieu du service Symfony.

**Solution** : Remplacer par le paramètre kernel `%kernel.project_dir%` :
```php
// À injecter via le constructeur ou le container
$documentRoot = $this->getParameter('kernel.project_dir') . '/public';
```

---

#### `$_SESSION` dans LegacySessionAuthenticator — 🟢 Acceptable

**Fichier** : `src/Security/LegacySessionAuthenticator.php:52-59`

L'accès direct à `$_SESSION['Zend_Auth']` est intentionnel : il s'agit d'une couche de compatibilité avec l'authentification Zend legacy. À supprimer quand la migration auth sera complète.

---

#### Instanciation directe du cache dans `CouchesCarto` — 🟡

**Fichier** : `src/Service/CouchesCarto.php:204`

```php
$cache = new FilesystemAdapter('app.cache');
```

Le service utilise le composant Cache de Symfony mais l'instancie directement au lieu d'utiliser l'injection de dépendances. Le constructeur n'accepte que `HttpClientInterface` et `LoggerInterface`.

**Solution** : Injecter `CacheInterface` (ou `FilesystemAdapter` tagué) via le constructeur :
```php
use Symfony\Contracts\Cache\CacheInterface;

public function __construct(
    HttpClientInterface $client,
    LoggerInterface $logger,
    CacheInterface $cache
) { ... }
```
Puis déclarer dans `config/services.yaml` si nécessaire. Gain : testabilité (mock du cache en tests) + cohérence avec l'injection Symfony.

---

#### Instanciations directes dans les controllers — 🟢 Acceptable (entités)

76 instanciations `new` détectées dans les controllers. Analyse :
- **Entités Doctrine** (`new Groupement()`, `new Champ()`, `new Periodicite()`, etc.) : normal, pas d'injection de dépendances nécessaire
- **DTOs** (`new FormDocument()`, `new PrescriptionTexteListe()`) : acceptable dans les controllers
- **`new Email()`** dans `AlerteController` : la logique d'envoi d'email est en controller — candidat à extraction en service si le besoin évolue

---

#### Méthodes de controller très longues — 🟡

| Méthode | Fichier | Longueur | Problème |
|---------|---------|----------|----------|
| `etablissement()` | `SearchController.php` | **344 lignes** | Export Excel inline, logique de recherche, rendu |
| `dossier()` | `SearchController.php` | **335 lignes** | Même structure |
| `edit()` | `DossierController.php` | 199 lignes | Gestion du formulaire |
| `new()` | `DossierController.php` | 140 lignes | Création dossier |
| `envoyer()` | `AlerteController.php` | 91 lignes | Envoi mail inline |

**Recommandation** : Extraire la logique d'export Excel de `SearchController` dans un service `ExportXlsSearchService` lors de la migration PHPExcel → PHPSpreadsheet. Les autres méthodes peuvent rester telles quelles (port direct).

---

### 2.3 Qualité de code

#### Return types manquants sur `getSubscribedEvents()` — 🟢 Quick win

2 EventSubscribers n'ont pas de return type sur `getSubscribedEvents()` :

```
src/EventSubscriber/DossierLateralNavigationSubscriber.php:85
src/EventSubscriber/EtablissementLateralNavigationSubscriber.php:111
```

**Fix trivial** :
```php
// Avant
public static function getSubscribedEvents()

// Après
public static function getSubscribedEvents(): array
```

---

#### DocBlocks `@var array` non typés — 🟢 Déjà bon état

Seulement **5 occurrences** de `@var array` / `@return array` / `@param array ` sans type générique dans tout `src/`. La base de code est globalement bien typée.

---

#### Méthodes statiques — 🟢 Usage justifié

27 méthodes statiques identifiées dans `src/`. Analyse :
- `getSubscribedEvents()` : imposé par `EventSubscriberInterface` ✅
- `fromEntity()` / `fromRequest()` sur les DTOs et Value Objects : pattern named constructor, approprié ✅
- `ChampNamer::getFullFusionName()` / `ChampCreator::make()` : fonctions utilitaires pures, statiques acceptables ✅
- `Informations::dbUrl()` etc. : utilitaires de configuration, statiques acceptables ✅

Aucune méthode statique problématique détectée.

---

## 3. Templates Twig

### 3.1 twig-cs-fixer — Résultat réel

> ✅ **twig-cs-fixer exécuté avec succès** via `prevarisc-infra-platau-1` (PHP 8.1.34) :
>
> ```bash
> docker exec prevarisc-infra-platau-1 sh -c \
>   "cd /var/www/html/prevarisc-migration && tools/vendor/bin/twig-cs-fixer lint templates/ --no-cache"
> ```

**Résultat : 1 erreur sur 199 fichiers** :

```
KO templates/etablissement/dossiers.html.twig
  line 14 : The var name must use snake_case; expected dossier_donnant_avis_id.
            {% set dossierDonnantAvisId = etablissement.dossierDonnantAvis ? ... %}
```

**Fix** (quick win) : Renommer la variable en snake_case dans `etablissement/dossiers.html.twig:14` :
```twig
{# Avant #}
{% set dossierDonnantAvisId = etablissement.dossierDonnantAvis ? etablissement.dossierDonnantAvis.id : null %}

{# Après #}
{% set dossier_donnant_avis_id = etablissement.dossierDonnantAvis ? etablissement.dossierDonnantAvis.id : null %}
```
Puis mettre à jour toutes les occurrences de `dossierDonnantAvisId` dans ce même template.

**Commande pour corriger automatiquement** :
```bash
docker exec prevarisc-infra-platau-1 sh -c \
  "cd /var/www/html/prevarisc-migration && tools/vendor/bin/twig-cs-fixer fix templates/etablissement/dossiers.html.twig"
```

### 3.2 Patterns à améliorer

#### URLs absolues dans les templates — 🟢 Note

Les occurrences de liens `href='/'` et `href='/etablissement/...'` dans les templates correspondent à des **routes non encore migrées** dans le code Symfony. C'est intentionnel dans le cadre du port direct : ces URLs seront remplacées par `path()` au fur et à mesure que les routes correspondantes seront migrées.

---

#### Templates volumineuses avec logique conditionnelle dense — 🟡

Templates avec le plus de balises de contrôle `{%...%}` :

| Template | Balises `{%` | Lignes |
|----------|-------------|--------|
| `dossier/index.html.twig` | 111 | 505 |
| `etablissement/_informations/_carto.html.twig` | 80 | 347 |
| `etablissement/_informations/_etablissement_lies.html.twig` | 79 | — |
| `layouts/dossier.html.twig` | 72 | 240 |
| `etablissement/_informations/_general.html.twig` | 72 | 314 |

Ces templates reflètent la complexité métier du legacy — pas de refactoring recommandé sans validation, mais des macros Twig pourraient être extraites pour les blocs répétitifs.

---

#### Commentaires `@todo: breadcrumb Bootstrap` — 🟢 Backlog

8 templates contiennent `{# @todo: utiliser le breadcrumb Bootstrap #}` (prescriptions_articles, prescriptions_textes, prescriptions_rappels). À intégrer dans le backlog comme une tâche de finition.

---

#### Webpack Encore non utilisé — 🟡 Long terme

`base.html.twig` contient les commentaires :
```twig
{# Run `composer require symfony/webpack-encore-bundle` ... #}
{# {{ encore_entry_link_tags('app') }} #}
```

Les assets sont chargés manuellement depuis `public/`. L'intégration de Webpack Encore permettrait le versioning automatique, le bundling et le tree-shaking. À planifier en même temps que la migration des librairies frontend.

---

## 4. Tests

### 4.1 État de la couverture

**Total : 101 fichiers de tests, 351 méthodes de test**

| Namespace | Fichiers de test | Couverture |
|-----------|-----------------|------------|
| `Service/` | 24 | ✅ Bonne |
| `Security/` | 19 (Functional/DossierEditNature*) | ✅ Très bonne (1 par nature dossier) |
| `Functional/` | 45 | ✅ Bonne |
| `Validator/` | 2 | ✅ OK |
| `Repository/` | 2 | 🟡 Partiel |
| `Form/` | 1 | 🔴 Faible (1 seul FormType testé) |
| `Dto/` | 1 | 🔴 Faible |
| `Enum/` | 2 | 🟡 Partiel |
| `Entity/` | 1 | 🔴 Faible |
| `Twig/` | 2 | 🟡 Partiel |
| `Utils/` | 1 | 🟡 Partiel |
| **Controllers/** | **0** | 🔴 Aucun test unitaire controller |

**Zones non couvertes** :
- Aucun test sur les controllers (50 fichiers) — couvert partiellement par les tests fonctionnels Dossier
- Peu de tests sur les FormTypes (1/~30)
- Peu de tests sur les Entities
- Pas de test sur les Builder, DependencyInjection, EventSubscriber

### 4.2 Qualité des tests existants

**Points positifs** :
- `setUp(): void` correctement typé dans tous les fichiers ✅
- Pas de `@expectedException` obsolète ✅
- Utilisation de `assertSame()` ✅
- `@dataProvider` utilisé ✅

**Points à améliorer** :
- Les tests `Validator/DossierObjetValidatorTest.php` utilisent des setters sur les entités pour construire les fixtures — acceptable mais des factories seraient plus maintenables
- `phpunit/phpunit ^7.5` est EOL — bloquerait l'utilisation de `#[DataProvider]` (attributs PHP 8) et certaines assertions récentes

### 4.3 Opportunités

- **FormTypes** : 30+ FormTypes sans tests. Priorité : tester `DossierType` (561 lignes, le plus complexe)
- **Repositories custom** : `DossierRepository` (664 lignes) et `EtablissementInformations`-related repositories n'ont pas de tests pour leurs requêtes complexes

---

## 5. Plan d'action recommandé

### ✅ Quick wins réalisés (worktree `chore/modernisation`)

| # | Action | Commit |
|---|--------|--------|
| 1 | `: array` sur `getSubscribedEvents()` + `%kernel.project_dir%` + Twig snake_case | `9a926c6` |
| 2 | Chosen 1.1.0 → 1.8.7, TomSelect 2.0 → 2.5.2, SortableJS 1.15.0 → 1.15.7 | `a251c4a` |
| 3 | Fix tooltip Tipsy orphelin sur bouton × TomSelect (exclusion `.ts-wrapper a` dans `bindTitlePopup`) | `fde17ae` |

### ⏳ Prochain worktree — Priorité : PHPExcel → PhpSpreadsheet

**Objectif** : remplacer `phpoffice/phpexcel` (EOL 2017) par `phpoffice/phpspreadsheet`.

**Périmètre** : un seul fichier à migrer — `src/Controller/SearchController.php` (méthodes `etablissement()` et `dossier()`).

**Mapping API PHPExcel → PhpSpreadsheet** :

| PHPExcel (ancien) | PhpSpreadsheet (nouveau) |
|-------------------|--------------------------|
| `new \PHPExcel()` | `new \PhpOffice\PhpSpreadsheet\Spreadsheet()` |
| `\PHPExcel_IOFactory::createWriter($obj, 'Excel5')` | `\PhpOffice\PhpSpreadsheet\IOFactory::createWriter($obj, 'Xls')` |
| `\PHPExcel_Style_Border::BORDER_THIN` | `\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN` |
| `\PHPExcel_Style_Alignment::HORIZONTAL_CENTER` | `\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER` |
| `\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY` | `\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY` |
| `\PHPExcel_Shared_Date::FormattedPHPToExcel(y, m, d)` | `\PhpOffice\PhpSpreadsheet\Shared\Date::formattedPHPToExcel(y, m, d)` *(lowercase f)* |
| `setCellValueByColumnAndRow($col, $row, $val)` — col 0-based | `setCellValueByColumnAndRow($col, $row, $val)` — col **1-based** ⚠️ |

> ⚠️ **Point de vigilance** : PhpSpreadsheet utilise des colonnes **1-based** (colonne A = 1) là où PHPExcel utilisait du **0-based** (colonne A = 0). Incrémenter tous les index de colonne de +1.

**Commandes** :
```bash
composer remove phpoffice/phpexcel
composer require phpoffice/phpspreadsheet
```

**Décision d'extraction** : le rapport initial suggérait d'extraire le code d'export en service. En cohérence avec la **stratégie port direct**, ne pas extraire — migrer l'API inline dans `SearchController`. L'extraction pourra être faite dans un second temps si nécessaire.

### Moyen terme (suite)

| # | Action | Effort | Prérequis |
|---|--------|--------|-----------|
| 1 | ~~**Migrer PHPExcel → PHPSpreadsheet**~~ | ~~M~~ | ✅ Prochain worktree |
| 2 | Injecter `CacheInterface` dans `CouchesCarto` (supprimer `new FilesystemAdapter()`) | S | Aucun |
| 3 | Remplacer jQuery Multiselect par TomSelect (4 templates restants avec Chosen) | M | Aucun |
| 4 | Remplacer FancyBox 1.3.4 par FancyBox 3.5.x | M | Aucun |
| 5 | Remplacer FullCalendar 1.6.4 par FullCalendar 5.x | L | Identifier usage actif |
| 6 | Mettre à jour TableSorter 2.0.5b → 2.31.x | S | Aucun |
| 7 | Ajouter tests sur `DossierType` FormType | M | Aucun |
| 8 | Ajouter tests sur `DossierRepository` (requêtes complexes) | M | Aucun |
| 9 | Traiter les 8 `@todo breadcrumb Bootstrap` dans les templates prescriptions | S | Aucun |

### Long terme (après montée de version PHP 8.1+)

Ces actions sont rendues possibles ou simplifiées par PHP 8.1 :

| # | Action | Déclencheur |
|---|--------|-------------|
| 1 | Exécuter Rector dry-run avec cible PHP 8.1 et appliquer les transformations | Après montée PHP |
| 2 | Monter PHPUnit 7.5 → PHPUnit 10 | PHP ≥ 8.1 requis |
| 3 | Migrer `@Route` annotations → attributs PHP 8 (`#[Route]`) via Rector | Après PHP 8.1 + Symfony 6 |
| 4 | Supprimer `sensio/framework-extra-bundle` | Après migration attributs |
| 5 | Migrer jQuery 1.10.2 → 3.x + supprimer jQuery Migrate | Après fin de migration Zend |
| 6 | Migrer Bootstrap 3 → Bootstrap 5 | Chantier frontend dédié (post-migration complète) |
| 7 | Intégrer Webpack Encore pour les assets | Après migration frontend |

---

## Annexe : Commandes utiles

```bash
# Vérification PHPStan (container app, PHP 7.1)
castor symfony:analyse

# Code Style PHP (container app, PHP 7.1)
castor symfony:cs

# Tests (container app, PHP 7.1)
castor symfony:test
castor symfony:test --group functional
castor symfony:test --exclude-group functional

# Rector dry-run via le container platau (PHP 8.1) — sur prevarisc-migration/
docker exec prevarisc-infra-platau-1 sh -c \
  "cd /var/www/html/prevarisc-migration && tools/vendor/bin/rector --dry-run --no-progress-bar"

# twig-cs-fixer via le container platau (PHP 8.1)
docker exec prevarisc-infra-platau-1 sh -c \
  "cd /var/www/html/prevarisc-migration && tools/vendor/bin/twig-cs-fixer lint templates/ --no-cache"

# Corriger automatiquement l'erreur twig-cs-fixer
docker exec prevarisc-infra-platau-1 sh -c \
  "cd /var/www/html/prevarisc-migration && tools/vendor/bin/twig-cs-fixer fix templates/etablissement/dossiers.html.twig"

# Grep utiles pour suivre la migration PHPExcel
grep -rn "PHPExcel" src/ --include="*.php"

# Vérifier les usages de Chosen restants
grep -rl "chosen\|js-chosen" templates/
```
