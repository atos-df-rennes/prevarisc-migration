# Rapport d'écarts — MOD-06 RECHERCHE

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-06-RECHERCHE.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 32 (RG-RCH-001 à RG-RCH-040, numérotation discontinue) |
| Écarts spec ↔ Symfony | 4 |
| Écarts spec ↔ Legacy | 1 |
| Écarts Symfony ↔ Legacy | 3 |
| Niveau de confiance | ⚠️ Bon sur les filtres, exports et résultats établissements ; quelques comportements dossier restent partiellement portés |

---

## Écarts détaillés

### Écart 1 — Le cache de recherche legacy n'est pas reproduit en Symfony

| | Détail |
|---|--------|
| **Règles** | RG-RCH-004 |
| **Spec** | Deux recherches identiques (mêmes critères, même page) doivent retourner les résultats du cache sans relancer la requête. |
| **Symfony** | `EtablissementSearchService` et `DossierSearchService` exécutent directement les requêtes SQL à chaque appel (`executeQuery()` sur le COUNT puis sur la requête paginée), sans identifiant de recherche ni couche de cache applicatif. |
| **Legacy** | `Service_Search::etablissements()` et `Service_Search::dossiers()` calculent un `search_id` basé sur `md5(serialize(func_get_args()))`, lisent `cacheSearch`, puis sauvegardent les résultats sérialisés. |
| **Impact** | **Moyen** — régression de performance sur les recherches répétées et non-conformité directe à la règle commune de mise en cache. |
| **Fichiers concernés** | `prevarisc/application/services/Search.php:38-44, 234-240, 515-521, 758`, `prevarisc-migration/src/Service/EtablissementSearchService.php:321-329`, `prevarisc-migration/src/Service/DossierSearchService.php:368-380` |

---

### Écart 2 — Les filtres cachés « date de commission » et « date de visite » des dossiers ne sont plus câblés dans le contrôleur Symfony

| | Détail |
|---|--------|
| **Règles** | RG-RCH-036, §4.1.2, §7.8 |
| **Spec** | Les plages de dates « passage en commission » et « visite » sont supportées par le moteur de recherche, bien qu'elles ne soient pas visibles dans l'interface. |
| **Symfony** | `DossierSearchService` supporte toujours `dateCommissionStart/End` et `dateVisiteStart/End`, mais `SearchController::dossier()` ne lit ni ne transmet ces paramètres, ni pour la recherche, ni pour l'export. Une URL manuelle avec ces critères est donc ignorée. |
| **Legacy** | Le contrôleur Zend renseigne bien `dateCommissionStart/End` et `dateVisiteStart/End` dans `$criteresRecherche` pour la recherche et l'export. |
| **Impact** | **Moyen** — perte d'une capacité existante en legacy et explicitement décrite par la spec, même si elle n'était pas exposée dans le formulaire. |
| **Fichiers concernés** | `prevarisc/application/controllers/SearchController.php:354-357, 528-531`, `prevarisc-migration/src/Controller/SearchController.php:408-420, 478-497`, `prevarisc-migration/src/Service/DossierSearchService.php:718-731` |

---

### Écart 3 — Le dépliage hiérarchique des dossiers liés n'est pas migré côté Symfony

| | Détail |
|---|--------|
| **Règles** | §4.2.3, §5.3 |
| **Spec** | Les dossiers ayant des dossiers liés doivent pouvoir être dépliés dynamiquement au clic pour afficher leur arborescence. |
| **Symfony** | Le contrôleur Symfony n'expose qu'une route AJAX pour les établissements (`search_ajax_etablissement`). La page `search/dossier.html.twig` affiche un tableau plat et son JavaScript ne gère que le chargement des voies, numéros, datepickers et pièces jointes. Aucun endpoint ni handler ne charge les dossiers enfants. |
| **Legacy** | `displayAjaxSearchDossierAction()` renvoie les dossiers liés via `search/results/dossier.phtml`, et le template `search/dossier.phtml` poste sur `/search/display-ajax-search-dossier` lorsqu'une ligne `slide` est cliquée. |
| **Impact** | **Moyen** — perte d'un comportement de navigation décrit par la spec et disponible dans le legacy pour explorer les dossiers liés depuis les résultats. |
| **Fichiers concernés** | `prevarisc/application/controllers/SearchController.php:615-630`, `prevarisc/application/views/scripts/search/dossier.phtml:242-255`, `prevarisc-migration/src/Controller/SearchController.php:565-590`, `prevarisc-migration/templates/search/dossier.html.twig:222-343` |

---

### Écart 4 — Les informations d'établissement liées à un dossier restent « courantes » au lieu d'être figées à la date pertinente

| | Détail |
|---|--------|
| **Règles** | RG-RCH-034 |
| **Spec** | Les informations de l'établissement lié à un dossier doivent être celles valables à la date la plus pertinente parmi date d'insertion, date de visite et date de commission ; à défaut, les plus anciennes. |
| **Symfony** | La recherche dossier joint `etablissementinformationsactuel` pour l'affichage et prend la dernière version (`MAX(DATE_ETABLISSEMENTINFORMATIONS)`) pour l'export. Aucun mécanisme ne reconstitue l'état historisé à la date du dossier. |
| **Legacy** | Même constat : la recherche utilise aussi `etablissementinformationsactuel`, et l'export prend la dernière fiche `etablissementinformations`. |
| **Impact** | **Faible à moyen** — la recherche peut afficher l'état actuel d'un établissement au lieu de l'état historique correspondant au dossier consulté. Il s'agit d'un écart de spec partagé par les deux implémentations. |
| **Fichiers concernés** | `prevarisc/application/services/Search.php:551-559, 815-822`, `prevarisc-migration/src/Service/DossierSearchService.php:105-118, 531-540` |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-RCH-001 / 002 / 003 / 005 | Exclusion des supprimés, pagination 50, compteur total, plafonds 1000/100 | ✅ Aligné (`where ... DATESUPPRESSION IS NULL`, `SearchPagination`, constantes `MAX_LIMIT_PAGES_*`) |
| RG-RCH-006 / 007 / 008 | Export limité à 10 000, droits séparés établissements/dossiers, bouton masqué après modification | ✅ Aligné (`SearchController`, templates et JS de recherche) |
| RG-RCH-010 à 019 / 022 | Recherche établissements : présélection groupement, détection `#` / identifiant, filtres conditionnels, zone géographique, dernière fiche historisée, tri conditionnel, avis donnant avis, semi-transparence | ✅ Aligné (`SearchController`, `EtablissementSearchService`, `search-etablissement.js`, `_etablissement.html.twig`) |
| RG-RCH-020 / 021 | Export établissements : calcul prochaine visite périodique et premier avis défavorable consécutif | ✅ Aligné (`EtablissementSearchService::extractionEtablissements()`, `SearchExport::exportEtablissements()`) |
| RG-RCH-030 / 031 / 032 / 033 | Recherche dossiers : présélection groupement, recherche par `#`, numéro d'urbanisme et identifiant Plat'AU | ✅ Aligné (`SearchController::dossier()`, `DossierSearchService::dossiers()`) |
| RG-RCH-035 / 036 / 037 / 038 / 039 / 040 | Provenance Plat'AU conditionnelle, validation des dates visibles, tri conditionnel, export Plat'AU conditionnel, temps restant coloré, troncature à 70 caractères | ✅ Aligné (hors dates cachées de l'écart n°2) |
| §3.2.2 / §5.1 | Hiérarchie et autocomplétion de la recherche établissements | ✅ Aligné (route AJAX établissements + modale commune/voie/numéro) |
| §3.3 / §4.3 / §7.6 | Exports ODS et mode dégradé sans auto-dimensionnement des colonnes | ✅ Aligné (`SearchExport`) |
| §7.2 / §7.3 | Écrans « courriers » et « utilisateurs » non actifs | ✅ Cohérent avec la réserve de la spec : non migrés côté Symfony ; le legacy conserve encore des restes techniques/non utilisés |

---

## Recommandations

1. **Priorité haute** : réintroduire un cache de recherche Symfony (clé = critères + page) pour couvrir RG-RCH-004 et retrouver le comportement/performance du legacy.
2. **Priorité haute** : recâbler dans `SearchController::dossier()` les paramètres `date-commission-*` et `date-visite-*` pour la recherche et l'export.
3. **Priorité moyenne** : migrer l'endpoint AJAX et le JavaScript de dépliage des dossiers liés afin de restaurer la navigation hiérarchique des résultats.
4. **Priorité moyenne** : clarifier avec le métier RG-RCH-034, puis soit documenter l'écart historique spec ↔ implémentations, soit implémenter une vraie résolution des informations établissement « à date dossier ».
