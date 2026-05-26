# Rapport d'écarts — MOD-02 DOSSIERS

> **Date** : 2026-05-22
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

> **Note Strangler** : la suppression d'un dossier reste volontairement branchée sur le legacy côté UI (`templates/dossier/index.html.twig` → `/dossier/delete/...`) ; ce point n'est pas compté comme écart de migration tant que ce chaînage est assumé.

---

## Écarts détaillés

### Écart 1 — Multi-natures non prises en charge

| | Détail |
|---|---|
| **Règles** | RG-DOS-003 |
| **Spec** | Les champs affichés doivent être l'union des champs de chaque nature lorsqu'un dossier porte plusieurs natures. |
| **Symfony** | Le modèle et le formulaire imposent une seule nature active : `Dossier::getNature()` retourne uniquement le premier élément, `setNature()` réinitialise la collection, et `DossierType` expose un `EntityType` simple, non multiple. |
| **Legacy** | Le contrôleur n'enregistre qu'un seul `selectNature` et les accès à la nature active utilisent `fetchRow()` / `getDossierNaturesId()` ; le comportement multi-natures n'est pas exploité. |
| **Impact** | **Moyen** — la règle métier décrite dans la spec n'est pas couverte ni dans le legacy ni dans Symfony ; toute évolution s'appuyant sur l'union de plusieurs natures nécessitera une refonte fonctionnelle et technique. |
| **Fichiers concernés** | `prevarisc-migration/src/Entity/Dossier.php`, `prevarisc-migration/src/Form/Dossier/DossierType.php`, `prevarisc/application/controllers/DossierController.php`, `prevarisc/application/models/DbTable/DossierNature.php` |

### Écart 2 — L'avis différé ne masque pas réellement le formulaire d'avis

| | Détail |
|---|---|
| **Règles** | RG-DOS-015 |
| **Spec** | L'avis du préventionniste et la date de commission/visite doivent être masqués dans le formulaire en cas d'avis différé. |
| **Symfony** | Le module JS `avis.js` ne traite que `incomplet`, `horsDelai`, `absenceDeQuorum` et `nePeutSePrononcer` ; `avisDiffere` n'entre pas dans `applyHideAvisLogicOnLoad()`. Le champ `avisDiffere` existe bien dans le formulaire, mais sans logique de masquage associée. |
| **Legacy** | La consultation calcule bien `$afficheAvis` avec `DIFFEREAVIS_DOSSIER`, mais la logique JS d'édition (`afficherCacherAvis()` / `afficheChamps()`) n'applique pas ce cas ; la case `differeavis` n'est pas branchée sur la classe `hideavis`. |
| **Impact** | **Moyen** — l'écran d'édition peut laisser visibles des champs qui devraient être neutralisés par la règle métier, avec risque de saisie incohérente. |
| **Fichiers concernés** | `prevarisc-migration/public/js/dossier/modules/avis.js`, `prevarisc-migration/src/Form/Dossier/DossierType.php`, `prevarisc/application/controllers/DossierController.php`, `prevarisc/application/views/scripts/dossier/index.phtml` |

### Écart 3 — Le verrouillage n'interdit pas toutes les modifications côté serveur

| | Détail |
|---|---|
| **Règles** | RG-DOS-021 |
| **Spec** | Quand un dossier est verrouillé, toute modification doit être interdite : formulaire principal, documents consultés, textes applicables, etc. |
| **Symfony** | Les prescriptions, contacts, liaisons, pièces jointes et effectifs ont des gardes explicites, mais `DossierController::edit()`, `editDescriptif()`, `editTextesApplicables()` et le CRUD des documents consultés n'ont pas de contrôle `hasVerrou()` ; l'UI masque les boutons, sans blocage serveur homogène. |
| **Legacy** | Même logique partielle : l'interface masque les actions quand `VERROU_DOSSIER = 1`, mais la sauvegarde principale recharge le dossier et persiste les champs sans contrôle serveur explicite sur le verrou. |
| **Impact** | **Élevé** — un accès direct aux routes de modification ou un POST forgé peut contourner le verrouillage sur certaines zones du module. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Dossier/DossierController.php`, `prevarisc-migration/src/Controller/Dossier/DossierDocumentsConsultesController.php`, `prevarisc-migration/templates/dossier/index.html.twig`, `prevarisc-migration/templates/dossier/descriptif/descriptif.html.twig`, `prevarisc/application/controllers/DossierController.php`, `prevarisc/application/views/scripts/dossier/index.phtml`, `prevarisc/application/views/scripts/dossier/descriptif.phtml` |

### Écart 4 — Le champ CNE n'est plus exposé dans Symfony

| | Détail |
|---|---|
| **Règles** | RG-DOS-041 |
| **Spec** | Les booléens métier, dont `CNE`, doivent être explicitement positionnés à 0/1 à chaque sauvegarde. |
| **Symfony** | L'entité conserve `CNE_DOSSIER` (`celluleNonExploitee`), mais aucun champ Symfony dédié n'est déclaré dans `DossierType`, et la configuration d'affichage dynamique ne prévoit pas `CNE`. Le booléen existe en base sans être piloté depuis l'interface migrée. |
| **Legacy** | Le formulaire legacy affiche bien `CNE_DOSSIER` et le contrôleur le force explicitement à 0 ou 1 à chaque sauvegarde. |
| **Impact** | **Moyen** — régression fonctionnelle sur les natures qui utilisent ce drapeau, avec impossibilité de créer ou corriger la valeur depuis Symfony. |
| **Fichiers concernés** | `prevarisc-migration/src/Entity/Dossier.php`, `prevarisc-migration/src/Form/Dossier/DossierType.php`, `prevarisc-migration/src/Service/DossierFields.php`, `prevarisc-migration/public/js/dossier/modules/type-nature.js`, `prevarisc/application/controllers/DossierController.php`, `prevarisc/application/views/scripts/dossier/index.phtml` |

### Écart 5 — L'accès à un dossier Plat'AU désactivé n'est pas bloqué en Symfony

| | Détail |
|---|---|
| **Règles** | RG-DOS-044 |
| **Spec** | Si l'intégration Plat'AU est désactivée, l'accès à un dossier lié à Plat'AU doit générer une erreur. |
| **Symfony** | Le contrôleur dossier affiche les données Plat'AU dès qu'une consultation est liée, via `getPlatauDataForView()`, sans contrôle sur le flag global de désactivation. |
| **Legacy** | Le contrôleur legacy vérifie `PREVARISC_DEACTIVATE_PLATAU` et lève une exception si un dossier lié Plat'AU est consulté alors que l'intégration est désactivée. |
| **Impact** | **Moyen** — divergence de comportement selon le front utilisé, avec exposition d'un dossier Plat'AU alors que l'instance est censée couper cette intégration. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Dossier/DossierController.php`, `prevarisc-migration/src/Service/PlatauConsultation.php`, `prevarisc/application/controllers/DossierController.php` |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|---|---|---|
| RG-DOS-001, RG-DOS-002 | Type / nature obligatoires et natures ordonnées | ✅ `DossierType` impose type+nature et `DossierNatureListeRepository::findByTypeOrderedByOrdre()` aligne le filtrage/tri des natures. |
| RG-DOS-004, RG-DOS-005, RG-DOS-006 | Création depuis un établissement | ✅ Le créateur est renseigné, l'établissement d'origine est lié, et les contacts métier sont copiés à la création. |
| RG-DOS-009, RG-DOS-013, RG-DOS-014, RG-DOS-036 | Initialisation métier à la création | ✅ Symfony reprend prescriptions par défaut, textes applicables, documents de dernière VP et crée les 5 attestations de levée comme le legacy. |
| RG-DOS-011, RG-DOS-012 | Reprise de prescriptions / prescriptions levées | ✅ Les prescriptions reprises gardent `dossierReprise`, les levées et justificatifs sont affichés, et les prescriptions levées sont filtrées pour les documents générés. |
| RG-DOS-016, RG-DOS-017, RG-DOS-018, RG-DOS-019, RG-DOS-034, RG-DOS-035 | Dossier donnant avis et groupe de visite | ✅ L'affectation / désaffectation du dossier donnant avis, la répercussion aux établissements enfants et l'alerte VAO sur établissement en statut projet sont portées. |
| RG-DOS-022, RG-DOS-023 | Verrouillage : identité et droit par nature | ✅ Le verrou enregistre l'utilisateur et le droit `verrouillage_dossier` est bien résolu via les ressources spécialisées par nature. |
| RG-DOS-024, RG-DOS-025, RG-DOS-028, RG-DOS-046 | Suppression logique, rétablissement, cache | ✅ Le modèle Symfony conserve la suppression logique (`DATESUPPRESSION_DOSSIER`), filtre les dossiers supprimés, propose un rétablissement via `ElementsSupprimesController` et continue d'invalider le cache via les routes legacy de transition. |
| RG-DOS-029, RG-DOS-030, RG-DOS-031, RG-DOS-032 | Affectation commission / visite | ✅ `DossierAffectationService` gère la synchronisation des dates, la suppression des affectations et la remise à zéro des horaires/ordre lors d'un changement de date. |
| RG-DOS-037 | Numéros d'urbanisme et documents consultés | ✅ Les numéros d'urbanisme restent saisis en collection dédiée, affichés en consultation et exploités dans la recherche / génération documentaire. |
| RG-DOS-042 | Transfert / suppression des documents consultés | ✅ `DocumentConsulteTransferService` reproduit la compatibilité legacy entre types/natures et purge ou transfère les documents selon le changement de nature. |
| RG-DOS-043, RG-DOS-045, RG-DOS-047 | Affichage Plat'AU, export PJ, notifications | ✅ Le dossier Symfony affiche les statuts Plat'AU, marque les PJ à exporter et déclenche les flashs de notification lors d'un changement d'avis propagé. |

---

## Recommandations

1. Corriger en priorité le verrouillage serveur (RG-DOS-021) sur les routes de modification encore ouvertes en accès direct.
2. Réintroduire le cas `avisDiffere` dans la logique de masquage du formulaire pour réaligner RG-DOS-015 avec le legacy attendu par la spec.
3. Ajouter le champ `CNE` à l'UI Symfony (formulaire + configuration dynamique + persistance) si la fonctionnalité reste active côté métier.
4. Ajouter un garde explicite sur la désactivation Plat'AU dans le module Dossier Symfony pour réaligner RG-DOS-044.
5. Documenter dans la spec ou le manifest que la suppression reste en strangler via le legacy, afin d'éviter de la signaler à tort comme non migrée lors des prochains audits.
