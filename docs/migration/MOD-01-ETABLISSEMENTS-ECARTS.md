# Rapport d'écarts — MOD-01 ÉTABLISSEMENTS

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-01-ETABLISSEMENTS.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 61 (RG-ETB-001 à RG-ETB-061) |
| Écarts spec ↔ Symfony | 3 |
| Écarts spec ↔ Legacy | 1 |
| Écarts Symfony ↔ Legacy | 2 |
| Impact critique | 1 (propagation périodicité) |

---

## Écarts détaillés

### Écart 1 — Suppression d'un établissement non migrée

| | Détail |
|---|--------|
| **Règles** | RG-ETB-018, RG-ETB-019 |
| **Spec** | Soft-delete de l'établissement + suppression en cascade de tous les dossiers liés |
| **Symfony** | Pas d'action `delete` — seul `restore()` existe dans `ElementsSupprimesController`. La suppression reste gérée par le legacy via Strangler Pattern. |
| **Legacy** | `deleteAction()` dans `EtablissementController` appelle `$service_dossier->deleteByEtab()` (soft-delete cascade dossiers) puis `$this->serviceEtablissement->delete()`. |
| **Impact** | Faible — la suppression fonctionne via le fallback Zend. À migrer ultérieurement. |
| **Fichiers concernés** | `prevarisc/application/controllers/EtablissementController.php:568`, `prevarisc-migration/src/Controller/ElementsSupprimesController.php` |

---

### Écart 2 — Identifiant Winprev jamais auto-calculé

| | Détail |
|---|--------|
| **Règles** | RG-ETB-002, RG-ETB-003 |
| **Spec** | Formule automatique `[genre][INSEE 6 chiffres][rang 5 chiffres]-[rang cellule 3 chiffres]-[commission]` |
| **Symfony** | Le champ `NUMEROID_ETABLISSEMENT` est lu et affiché (rapports ODT, recherche) mais jamais auto-calculé à la création. |
| **Legacy** | Pas d'auto-génération non plus — stocke la valeur fournie par l'utilisateur ou fallback sur `ID_ETABLISSEMENT`. |
| **Impact** | Écart de spécification vs implémentation des DEUX côtés. La spec décrit un comportement jamais implémenté ; l'identifiant est vraisemblablement saisi manuellement ou hérité d'un import historique. |
| **Fichiers concernés** | `prevarisc/application/services/Etablissement.php:1049`, `prevarisc-migration/src/Entity/Etablissement.php:72` |

---

### Écart 3 — Propagation de la périodicité aux cellules enfants (écriture)

| | Détail |
|---|--------|
| **Règles** | RG-ETB-028, RG-ETB-061 |
| **Spec** | Quand la périodicité d'un ERP/IGH parent change, elle doit se propager aux cellules enfants |
| **Symfony** | La **catégorie** est bien propagée aux cellules (`EtablissementPersistenceService::persistFils()` et `persistPere()`), mais **PAS la périodicité**. |
| **Legacy** | `recalcEnfants()` propage catégorie ET périodicité à toutes les cellules du parent lors de la sauvegarde. |
| **Impact** | **Moyen à élevé** — Un utilisateur qui modifie la périodicité d'un ERP parent dans Symfony ne verra pas les cellules enfants mises à jour, contrairement au legacy. Peut entraîner des incohérences dans le calcul des relances de visite. |
| **Fichiers concernés** | `prevarisc/application/services/Etablissement.php` (méthode `recalcEnfants`), `prevarisc-migration/src/Service/EtablissementPersistenceService.php:280-284` |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-ETB-004 à 017 | Création / Modification (formulaire, genres, validations) | ✅ Aligné |
| RG-ETB-020/021 | Restauration (retrait soft-delete) | ✅ Aligné |
| RG-ETB-022 à 027 | Hiérarchie site/ERP/cellule (validation père/fils) | ✅ Aligné |
| RG-ETB-028 (catégorie) | Propagation catégorie parent → cellules | ✅ Aligné |
| RG-ETB-030/031 | Adresse site = enfant max effectif ; cellule = parent racine | ✅ Aligné (`EtablissementAdresseRepository::get()`) |
| RG-ETB-032 à 038 | Sous-onglets (plans, activités, descriptifs) | ✅ Aligné |
| RG-ETB-039 à 041 | Historisation (nouvelle fiche à chaque modification) | ✅ Aligné |
| RG-ETB-042/043 | Alertes (détection changement statut/classement) | ✅ Aligné (`checkAlerte()`) |
| RG-ETB-044 à 046 | Avis d'exploitation (dossierDonnantAvis vs avisDifféré) | ✅ Aligné (logique portée dans controller + `DossierRepository::findLastDossierAvisDiffere()`) |
| RG-ETB-047 à 060 | Périodicité/Commission en lecture (site=min enfants, cellule=parent) | ✅ Aligné (`getDisplayedPeriodicity()`) |

---

## Recommandations

1. **Priorité haute** : Implémenter la propagation de la périodicité aux cellules dans `EtablissementPersistenceService` (équivalent de `recalcEnfants()`) pour combler l'écart n°3.
2. **Priorité basse** : La suppression d'établissement (écart n°1) peut rester dans le legacy tant que la migration complète n'est pas planifiée.
3. **Information** : L'écart n°2 (Winprev ID) est un écart de spec historique — à clarifier avec le métier si une auto-génération est réellement attendue.
