# Rapport d'écarts — Ajout d'un établissement

**Feature :** Ajout d'un établissement  
**Tâche de référence :** T0–T14  
**Date :** 2026-05-13  
**Legacy :** `EtablissementController::indexAction()` + `Service_Etablissement::save()` + `Api_Service_Etablissement::getDefaultValues()`  
**Symfony :** `EtablissementController::add()` + `EtablissementService::save()` + `Api/EtablissementController::defaultsValues()`

---

## 1. Calcul de la commission suggérée

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | `Model_DbTable_Commission::getCommission($numinsee, $categorie, $type, $localSommeil)` — requête SQL complexe avec jointures sur commune, type, catégorie et local à sommeil | Lookup de la commission de l'établissement père via `EtablissementRepository::find()` |
| **Résultat** | Commission précisément déterminée selon la configuration territoriale et le type ERP | Commission héritée du père (approximation) ; `null` si pas de père |
| **Impact** | Moyen — la commission suggérée peut différer en mode ajout sans père | L'utilisateur peut corriger manuellement dans le formulaire |
| **Décision** | Écart accepté : `Model_DbTable_Commission` n'a pas d'équivalent Symfony. Un refactoring ultérieur pourra implémenter la règle métier complète via un `CommissionResolverService`. |

---

## 2. Calcul des préventionnistes suggérés

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | `Model_DbTable_Preventionniste::getPrev($numinsee, $idPere)` — recherche les préventionnistes par commune INSEE et établissement père | Retourne `[]` (tableau vide) |
| **Résultat** | Liste des préventionnistes suggérés | Aucune suggestion |
| **Impact** | Faible — l'utilisateur doit saisir manuellement les préventionnistes lors de la création |
| **Décision** | Écart accepté : `Model_DbTable_Preventionniste::getPrev()` n'a pas d'équivalent Symfony. La fonctionnalité de suggestion pourra être implémentée via `UtilisateurRepository` lorsque la règle métier sera spécifiée. |

---

## 3. Types de local à sommeil (configuration)

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | Constante `PREVARISC_LOCAL_SOMMEIL_TYPES` ou valeur par défaut codée en dur | Paramètre `app.local_sommeil_types` (env : `PREVARISC_LOCAL_SOMMEIL_TYPES`) |
| **Format** | Identifiants de types séparés par `;` | Même format (chaîne `;`-délimitée) |
| **Résultat** | Identique si la variable d'environnement est configurée | Identique |
| **Impact** | Aucun si `PREVARISC_LOCAL_SOMMEIL_TYPES` est défini en production |
| **Décision** | Non-breaking. La valeur par défaut (`7;11`) correspond aux types legacy habituels. |

---

## 4. Validation NUMEROID

| | Legacy | Symfony |
|---|---|---|
| **Comportement** | `NUMEROID` = valeur fournie par l'utilisateur, ou `ID` auto-increment si absent | Idem — double flush : persist→flush pour obtenir l'ID, puis setNumero(ID) si absent |
| **Différence** | Aucune différence fonctionnelle | Identique |
| **Impact** | Aucun |

---

## 5. Propagation catégorie aux cellules (fils)

| | Legacy | Symfony |
|---|---|---|
| **Comportement** | Propagation de la catégorie ERP/IGH vers les cellules enfants lors de la création | Identique (voir `EtablissementService::save()` lignes ~895–915) |
| **Impact** | Aucun |

---

## 6. Bootstrap 2 → Bootstrap 3 (templates)

| | Legacy | Symfony |
|---|---|---|
| **Grid** | `row-fluid` / `span6` / `span4` | `row` / `col-md-6` / `col-md-4` |
| **Modales** | `modal hide fade` | `modal fade` |
| **Boutons** | `btn btn-success`, `btn btn-primary` (identiques) | Identiques |
| **Tables** | `table-bordered table-condensed` | Identiques |
| **Formulaires** | `help-block` | Identique |
| **Impact** | Cosmétique uniquement — rendu Bootstrap 3 légèrement différent (gutter, mobile-first) |

---

## 7. JavaScript — show/hide des sections par genre

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | jQuery `.show()`/`.hide()` sur les sections selon `$('#ID_GENRE').val()` via `etablissement/edit/js.phtml` | JS inline dans `_form.html.twig` — même logique switch sur `ID_GENRE` |
| **Différence** | Le JS legacy est un fichier `.phtml` injecté avec variables Zend_View. En Symfony, le JS est inline dans le template. | Comportement fonctionnel identique. |
| **Impact** | Aucun fonctionnel. Le JS inline devrait être externalisé lors d'une refactorisation front. |

---

## 8. Géolocalisation — fournisseurs

| | Legacy | Symfony |
|---|---|---|
| **IGN** | SDK Géoportail 2D via `GpSDK2D.js` | Idem — fichier déjà présent dans `public/js/geoportail/sdk-ol/` |
| **Geoconcept** | URL configurable via `app.geoconcept_url` | Idem |
| **Nominatim** | Absent en legacy | Ajouté comme fournisseur de repli OSM |
| **Impact** | Enrichissement (Nominatim en plus) — comportement identique pour IGN/Geoconcept. |

---

## 9. Gestion des erreurs de transaction

| | Legacy | Symfony |
|---|---|---|
| **Transaction** | `Zend_Db_Adapter::beginTransaction()` / `commit()` / `rollBack()` | `Connection::beginTransaction()` (DBAL niveau connexion, pas ORM) |
| **Niveau** | Application Zend | DBAL (évite les soucis de nested transactions Doctrine ORM) |
| **Rollback** | Oui | Oui — dans le bloc `catch` de `save()` |
| **Impact** | Aucun fonctionnel. Le comportement de rollback est équivalent. |

---

## 10. Voter CREATE — héritage

| | Legacy | Symfony |
|---|---|---|
| **ACL** | `Zend_Acl` avec ressource `ETABLISSEMENT` et privilège `ADD` | Voter Symfony `EtablissementCreationVoter` avec attribut `create_ets` |
| **Condition** | Rôle utilisateur vérifié via `Zend_Acl` | Utilisateur authentifié avec un groupe non-null |
| **Impact** | Logique équivalente. La vérification de groupe garantit que seuls les utilisateurs actifs peuvent créer. |

---

## Résumé

| # | Écart | Criticité | Décision |
|---|---|---|---|
| 1 | Commission suggérée simplifiée | Moyen | Accepté — refactoring futur `CommissionResolverService` |
| 2 | Préventionnistes suggérés absents | Faible | Accepté — saisie manuelle possible |
| 3 | Types local à sommeil via env var | Aucun | Non-breaking |
| 4 | NUMEROID double-flush | Aucun | Comportement identique |
| 5 | Propagation catégorie cellules | Aucun | Comportement identique |
| 6 | BS2 → BS3 | Cosmétique | Accepté |
| 7 | JS inline vs fichier | Technique | Accepté |
| 8 | Nominatim ajouté | Enrichissement | Accepté |
| 9 | DBAL vs ORM transaction | Aucun | Comportement identique |
| 10 | Voter CREATE | Aucun | Logique équivalente |
