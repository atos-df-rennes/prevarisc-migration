# Rapport d'écarts — MOD-01 ÉTABLISSEMENTS

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-01-ETABLISSEMENTS.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 61 (RG-ETB-001 à RG-ETB-061) |
| Écarts spec ↔ Symfony | 3 (3 traités ✅) |
| Écarts spec ↔ Legacy | 1 |
| Écarts Symfony ↔ Legacy | 2 (2 comblés ✅) |
| Impact critique | 0 |

---

## Écarts détaillés

### Écart 1 — Suppression d'un établissement ✅ Migré

| | Détail |
|---|--------|
| **Règles** | RG-ETB-018, RG-ETB-019 |
| **Spec** | Soft-delete de l'établissement + suppression en cascade de tous les dossiers liés |
| **Symfony (avant)** | Pas d'action `delete` — seul `restore()` existait dans `ElementsSupprimesController`. La suppression restait gérée par le legacy via Strangler Pattern. |
| **Legacy** | `deleteAction()` dans `EtablissementController` appelle `$service_dossier->deleteByEtab()` (soft-delete cascade dossiers) puis `$this->serviceEtablissement->delete()`. |
| **Solution** | Ajout de `Dossier::deleteByEtablissement()` (DBAL bulk update), `Etablissement::delete()`, et de l'action `deleteEtablissement` dans `EtablissementController`. Template `informations.html.twig` mis à jour pour utiliser un formulaire POST vers la nouvelle route Symfony avec protection CSRF. |
| **Fichiers modifiés** | `prevarisc-migration/src/Service/Dossier.php`, `prevarisc-migration/src/Service/Etablissement.php`, `prevarisc-migration/src/Controller/Etablissement/EtablissementController.php`, `prevarisc-migration/templates/etablissement/informations.html.twig` |

---

### Écart 2 — Identifiant Winprev jamais auto-calculé ✅ Spécification corrigée

| | Détail |
|---|--------|
| **Règles** | RG-ETB-002, RG-ETB-003 |
| **Spec (avant)** | Formule automatique `[genre][INSEE 6 chiffres][rang 5 chiffres]-[rang cellule 3 chiffres]-[commission]` |
| **Legacy** | Pas d'auto-génération — stocke la valeur fournie par l'utilisateur ou fallback sur `ID_ETABLISSEMENT`. |
| **Symfony** | Le champ `NUMEROID_ETABLISSEMENT` est lu et affiché (rapports ODT, recherche) mais jamais auto-calculé. |
| **Décision** | Écart de spécification historique. La spec a été mise à jour pour refléter le comportement réel de l'application : l'identifiant Winprev est saisi manuellement ou hérité d'un import historique, et n'est pas généré automatiquement. Aucune modification de code requise. |
| **Fichiers modifiés** | `prevarisc-migration/docs/specs/MOD-01-ETABLISSEMENTS.md` (RG-ETB-002, RG-ETB-003) |

---

### Écart 3 — Propagation de la périodicité/catégorie aux cellules enfants ✅ Spécification corrigée

| | Détail |
|---|--------|
| **Règles** | RG-ETB-028, RG-ETB-061 |
| **Spec (avant)** | Propagation automatique de la catégorie et de la périodicité aux cellules enfants lors de la modification d'un ERP/IGH parent |
| **Legacy** | La fonction `recalcEnfants()` dans `Model_DbTable_Etablissement` prévoyait cette propagation, mais elle n'est **jamais appelée** dans le code legacy. |
| **Symfony (avant)** | La catégorie était propagée dans `EtablissementPersistenceService::persistEtablissementsLies()` et `persistPere()`, mais **pas la périodicité**. Ce code reproduisait un comportement absent du legacy. |
| **Décision** | Suppression des blocs de propagation (catégorie ET périodicité) dans `EtablissementPersistenceService` pour aligner le comportement Symfony sur le legacy réel. La spec a été mise à jour pour documenter que `recalcEnfants()` existait mais n'a jamais été utilisée. Aucune régression fonctionnelle : ni le legacy ni Symfony ne propagent ces valeurs. |
| **Fichiers modifiés** | `prevarisc-migration/src/Service/EtablissementPersistenceService.php`, `prevarisc-migration/src/Service/Etablissement.php`, `prevarisc-migration/docs/specs/MOD-01-ETABLISSEMENTS.md` (RG-ETB-028, RG-ETB-061) |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-ETB-004 à 017 | Création / Modification (formulaire, genres, validations) | ✅ Aligné |
| RG-ETB-020/021 | Restauration (retrait soft-delete) | ✅ Aligné |
| RG-ETB-022 à 027 | Hiérarchie site/ERP/cellule (validation père/fils) | ✅ Aligné |
| RG-ETB-028, RG-ETB-061 | Propagation catégorie/périodicité parent → cellules lors de modification | ✅ Aligné (ni legacy ni Symfony ne propagent : `recalcEnfants()` définie mais jamais appelée dans le legacy) |
| RG-ETB-030/031 | Adresse site = enfant max effectif ; cellule = parent racine | ✅ Aligné (`EtablissementAdresseRepository::get()`) |
| RG-ETB-032 à 038 | Sous-onglets (plans, activités, descriptifs) | ✅ Aligné |
| RG-ETB-039 à 041 | Historisation (nouvelle fiche à chaque modification) | ✅ Aligné |
| RG-ETB-042/043 | Alertes (détection changement statut/classement) | ✅ Aligné (`checkAlerte()`) |
| RG-ETB-044 à 046 | Avis d'exploitation (dossierDonnantAvis vs avisDifféré) | ✅ Aligné (logique portée dans controller + `DossierRepository::findLastDossierAvisDiffere()`) |
| RG-ETB-047 à 060 | Périodicité/Commission en lecture (site=min enfants, cellule=parent) | ✅ Aligné (`getDisplayedPeriodicity()`) |

---

## Recommandations

Tous les écarts ont été traités. Aucune action complémentaire requise sur ce module.
