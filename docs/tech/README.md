# Documentation Technique - Prevarisc Migration

Ce répertoire contient l'ensemble de la documentation technique du projet de migration de Prevarisc (Zend v1 → Symfony v4).

## 📁 Organisation

### `/dossier`
Documentation relative aux fonctionnalités de gestion des dossiers :
- **AVANCEMENT_EDITION_DOSSIER.md** : État d'avancement de la migration de l'édition des dossiers
- **MAPPING_CHAMPS_DOSSIER.md** : Mapping des champs entre legacy et Symfony
- **PLAN_MIGRATION_EDITION_DOSSIER.md** : Plan détaillé de la migration de l'édition
- **migration-affichage-dossiers.md** : Documentation de la migration de l'affichage des dossiers

### `/tests`
Documentation sur les stratégies de tests et de validation :
- **SYSTEME_TESTS_COMPARAISON.md** : Système de tests de comparaison avec le legacy
- **TESTS_FONCTIONNELS_AFFICHAGE.md** : Tests fonctionnels automatisés pour l'affichage des dossiers

### `/migration`
Documentation générale sur la migration :
- **MAPPING_LEGACY_REFERENCE.md** : Référence de mapping entre l'ancien et le nouveau système

### `/legacy`
Documentation sur le fonctionnement du système legacy (Zend v1) :
- **ACL.md** : Documentation du système de droits et d'ACL du legacy

### `/suggestions`
Propositions d'améliorations et recommandations :
- **SUGGESTIONS_AMELIORATION_AFFICHAGE_DOSSIER.md** : Suggestions pour l'affichage des dossiers
- **SUGGESTIONS_AMELIORATION_EDITION_DOSSIER.md** : Suggestions pour l'édition des dossiers

## 📄 Fichiers à la racine

- **REPRENDRE_ICI.md** : Point de reprise du développement (TODO actuel)
- **datastore_configuration.md** : Configuration du datastore
- **SESSION_2025-12-11.md** : Synthèse de la session du 11 décembre 2025 (organisation + tests)

## 🔍 Conventions

- Les fichiers en MAJUSCULES sont généralement des documents de suivi ou de planification
- Les fichiers en minuscules sont des documentations techniques détaillées
- Chaque sous-dossier peut contenir son propre README.md si nécessaire
