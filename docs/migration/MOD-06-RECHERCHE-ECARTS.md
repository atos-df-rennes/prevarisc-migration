# Rapport d'écarts — MOD-06 RECHERCHE

> **Date** : 2026-05-22 — **Mis à jour** : 2026-05-23  
> **Spec source** : `docs/specs/MOD-06-RECHERCHE.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 32 (RG-RCH-001 à RG-RCH-040, numérotation discontinue) |
| Écarts identifiés | 4 |
| Écarts résolus | 3 (Écarts 1, 2, 3) |
| Écarts documentés | 1 (Écart 4) |
| Niveau de confiance | ✅ Bon — filtres, exports, résultats établissements et dossiers alignés ; écarts corrigés ou documentés |

---

## Écarts détaillés

### Écart 1 — Cache de recherche ✅ Résolu

| | Détail |
|---|--------|
| **Règles** | RG-RCH-004 |
| **Spec** | Deux recherches identiques (mêmes critères, même page) doivent retourner les résultats du cache sans relancer la requête. |
| **Legacy** | `Service_Search::etablissements()` et `Service_Search::dossiers()` calculent un `search_id` basé sur `md5(serialize(func_get_args()))`, lisent `cacheSearch`, puis sauvegardent les résultats sérialisés. L'invalidation passait par l'action `clearSearchCacheAndRedirectAction` du `LegacyController`. |
| **Résolution** | Un pool `cache.search` dédié a été configuré avec `tags: true` (adaptateur `TagAwareCacheInterface`). La clé de cache suit le même mécanisme md5 que le legacy (`'search_etablissements_'.md5(serialize(func_get_args()))` / `'search_dossiers_...'`). Chaque entrée reçoit le tag `search`. L'invalidation se fait désormais directement via Symfony (`invalidateTags(['search'])`) sans passer par le legacy. Les trois points d'invalidation (`TableauDesPeriodicitesController`, `ElementsSupprimesController`, `DossierSauvegardeService`) ont été mis à jour. |
| **Fichiers modifiés** | `config/packages/cache.php`, `config/services.php`, `src/Service/EtablissementSearchService.php`, `src/Service/DossierSearchService.php`, `src/Controller/TableauDesPeriodicitesController.php`, `src/Controller/ElementsSupprimesController.php`, `src/Service/Dossier/DossierSauvegardeService.php` |

---

### Écart 2 — Filtres « date de commission » et « date de visite » ✅ Résolu (code mort supprimé)

| | Détail |
|---|--------|
| **Règles** | RG-RCH-036, §4.1.2, §7.8 |
| **Constat initial** | La spec décrivait ces deux filtres comme « supportés en traitement mais non affichés dans l'interface ». Le legacy renseigne bien les valeurs dans `$criteresRecherche`, mais ces valeurs ne sont jamais présentes dans le formulaire côté template. Elles ne sont donc jamais transmises au contrôleur, et l'export n'est jamais lancé directement via URL avec ces paramètres. |
| **Résolution** | Le code de traitement de ces critères dans `DossierSearchService::dossiers()` était du code mort : il ne pouvait jamais être atteint. Il a été supprimé. La spec (§4.1.2 et §7.8) a été mise à jour pour refléter la réalité : ces filtres n'ont jamais été exposés et n'existent plus dans le service. Aucune modification du dépôt legacy requise. |
| **Fichiers modifiés** | `src/Service/DossierSearchService.php` (suppression des 4 blocs WHERE `dateCommission*` / `dateVisite*`), `docs/specs/MOD-06-RECHERCHE.md` (§4.1.2 et §7.8 mis à jour) |

---

### Écart 3 — Dépliage hiérarchique des dossiers liés ✅ Résolu (non porté par design)

| | Détail |
|---|--------|
| **Règles** | §4.2.3, §5.3 |
| **Constat initial** | La spec décrivait un dépliage dynamique au clic pour afficher les dossiers liés (enfants). Le legacy dispose d'un endpoint `displayAjaxSearchDossierAction()` et d'une classe `slide` dans son template. |
| **Résolution** | La ligne `slide` est mal positionnée dans le template legacy (`search/dossier.phtml`), ce qui rend le dépliage inopérant en production. La fonctionnalité ne fonctionne donc pas dans le legacy, et il serait incorrect de la porter en Symfony. Le code superflu (classe `slide`, `icone_enfants`, spinner de chargement, sous-requête `NB_DOSS_LIES`) a été supprimé. La spec (§4.2.3 et §5.3) a été mise à jour pour documenter cet état. Aucune modification du dépôt legacy requise. |
| **Fichiers modifiés** | `src/Service/DossierSearchService.php` (suppression sous-requête `NB_DOSS_LIES`), `src/Dto/Repository/DossierSearchResultDto.php` (suppression propriété `NB_DOSS_LIES`), `templates/search/results/_dossier.html.twig` (suppression classe `slide`, `icone_enfants`, spinner), `docs/specs/MOD-06-RECHERCHE.md` (§4.2.3 et §5.3 mis à jour) |

---

### Écart 4 — Informations d'établissement à la date pertinente 📋 Documenté

| | Détail |
|---|--------|
| **Règles** | RG-RCH-034 |
| **Spec** | Les informations de l'établissement lié à un dossier doivent être celles valables à la date la plus pertinente parmi date d'insertion, date de visite et date de commission ; à défaut, les plus anciennes. |
| **Symfony** | La recherche dossier joint `etablissementinformationsactuel` pour l'affichage et prend la dernière version (`MAX(DATE_ETABLISSEMENTINFORMATIONS)`) pour l'export. |
| **Legacy** | Même constat : la recherche utilise aussi `etablissementinformationsactuel`, et l'export prend la dernière fiche `etablissementinformations`. |
| **Résolution** | Les deux implémentations partagent le même écart vis-à-vis de la spec. Le legacy fait autorité. L'écart est documenté dans RG-RCH-034 (spec mise à jour) et ne sera pas corrigé sans décision métier explicite. |
| **Fichiers modifiés** | `docs/specs/MOD-06-RECHERCHE.md` (RG-RCH-034 annoté) |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-RCH-001 / 002 / 003 / 005 | Exclusion des supprimés, pagination 50, compteur total, plafonds 1000/100 | ✅ Aligné |
| RG-RCH-004 | Cache de recherche (clé md5 + tag + invalidation) | ✅ Résolu — voir Écart 1 |
| RG-RCH-006 / 007 / 008 | Export limité à 10 000, droits séparés, bouton masqué après modification | ✅ Aligné |
| RG-RCH-010 à 019 / 022 | Recherche établissements : présélection groupement, détection `#` / identifiant, filtres conditionnels, zone géographique, dernière fiche historisée, tri conditionnel, avis donnant avis, semi-transparence | ✅ Aligné |
| RG-RCH-020 / 021 | Export établissements : calcul prochaine visite périodique et premier avis défavorable consécutif | ✅ Aligné |
| RG-RCH-030 / 031 / 032 / 033 | Recherche dossiers : présélection groupement, recherche par `#`, numéro d'urbanisme et identifiant Plat'AU | ✅ Aligné |
| RG-RCH-035 / 036 / 037 / 038 / 039 / 040 | Provenance Plat'AU conditionnelle, validation des dates, tri conditionnel, export Plat'AU conditionnel, temps restant coloré, troncature à 70 caractères | ✅ Aligné |
| §3.2.2 / §5.1 | Hiérarchie et autocomplétion de la recherche établissements | ✅ Aligné |
| §3.3 / §4.3 / §7.6 | Exports ODS et mode dégradé | ✅ Aligné |
| §7.2 / §7.3 | Écrans « courriers » et « utilisateurs » non actifs | ✅ Cohérent avec la spec |

---

## Recommandations

Tous les écarts identifiés ont été traités :

- ✅ **Écart 1** résolu — cache `TagAwareCacheInterface` avec pool dédié et invalidation directe Symfony.
- ✅ **Écart 2** résolu — code mort supprimé, spec mise à jour.
- ✅ **Écart 3** résolu — code superflu supprimé, comportement non porté par design (feature inopérante en legacy), spec mise à jour.
- 📋 **Écart 4** documenté — écart spec partagé par les deux implémentations, le legacy fait autorité. Une évolution nécessiterait une décision métier.
