# Reprendre ici — État de la migration

> Ce fichier est mis à jour après chaque phase de migration. Il décrit l'état courant et le prochain point de départ pour reprendre les travaux.

---

## ✅ Phases terminées

### Phase 1 — Affichage read-only des formulaires personnalisés
**Branche :** `migration/affichage-formulaires-persos` (mergée)  
**Périmètre :** Affichage des 4 onglets de formulaires personnalisés en lecture seule.

### Phase 2 — Édition des formulaires personnalisés
**Commit :** `feat(formulaires): édition formulaires personnalisés - Phase 2`  
**Périmètre :** Formulaires d'édition POST pour les 4 onglets :

| Onglet | Entité | Route |
|--------|--------|-------|
| Descriptif de l'établissement | Etablissement | `etablissement_descriptif_modifier` |
| Effectifs et dégagements établissement | Etablissement | `etablissement_effectifs_degagements_edit` |
| Vérifications techniques du dossier | Dossier | `dossier_verifications_techniques_edit` |
| Effectifs et dégagements du dossier | Dossier | `dossier_effectifs_degagements_edit` |

**Fichiers clés :**
- `src/Service/DescriptifSaver.php` — service central de sauvegarde POST → Doctrine
- `templates/components/_formulaire_personnalise_edit.html.twig` — macros Twig d'édition
- `tests/Service/DescriptifSaverTest.php` — tests unitaires (407 tests, 0 erreur)

**Améliorations vs legacy :**
- Tokens CSRF sur chaque formulaire
- Contrôle d'accès `@Security` aligné sur les vues lecture
- Doctrine EntityManager (pas de SQL direct)
- Twig auto-escaping (pas de `htmlspecialchars()` manuel)

**Suggestions maintenabilité (non implémentées) :**  
→ Voir `docs/tech/suggestions/SUGGESTIONS_MAINTENABILITE_FORMULAIRES.md`

### Phase 3A — DTOs pour les formulaires personnalisés (Symfony Forms)
**Commit :** `feat(descriptif): ajout des DTOs pour les formulaires personnalisés`  
**Périmètre :** Création des 4 DTOs du pipeline DTO → FormType → Service :

| Fichier | Rôle |
|---------|------|
| `src/Dto/Descriptif/DescriptifFormData.php` | DTO racine — rubriques indexées par rubriqueId |
| `src/Dto/Descriptif/RubriqueFormData.php` | DTO rubrique — display + champs indexés par champId |
| `src/Dto/Descriptif/ChampValueData.php` | DTO champ — valeur typée ou conteneur tableau/groupe |
| `src/Dto/Descriptif/TableauRowData.php` | DTO ligne — cellules + valeurIds pour suivi update/create |

**Plan complet :** `docs/tech/dossier/PLAN_MIGRATION_FORMULAIRES_SYMFONY_FORMS.md`

---

## 🎯 Prochaine phase : Phase B — FormTypes

**Branche :** `migration/edition-formulaire-perso` (en cours)  
**Plan :** `docs/tech/dossier/PLAN_MIGRATION_FORMULAIRES_SYMFONY_FORMS.md`

### Phase B1 — Types feuille + DataTransformer

| Fichier à créer | Description |
|-----------------|-------------|
| `src/Form/DataTransformer/DateToStringTransformer.php` | Transformateur d/m/Y ↔ DateTime |
| `src/Form/Type/Descriptif/ChampTextType.php` | Wrapper TextType + form-control |
| `src/Form/Type/Descriptif/ChampTextareaType.php` | Wrapper TextareaType |
| `src/Form/Type/Descriptif/ChampSelectType.php` | ChoiceType dynamique |
| `src/Form/Type/Descriptif/ChampNumberType.php` | Wrapper IntegerType |
| `src/Form/Type/Descriptif/ChampCheckboxType.php` | Wrapper CheckboxType |
| `src/Form/Type/Descriptif/ChampDateType.php` | TextType + DateToStringTransformer |

### Phase B2 — Types composites

| Fichier à créer | Description |
|-----------------|-------------|
| `src/Form/Type/Descriptif/TableauRowType.php` | Type pour une ligne de tableau |
| `src/Form/Type/Descriptif/ChampValueType.php` | Dispatcheur dynamique (PRE_SET_DATA) |

---

## 📌 Commandes de validation

```bash
castor symfony:analyse   # PHPStan niveau 10 — 6 erreurs pré-existantes attendues
castor symfony:cs        # CS/Rector/Twig — 0 erreur attendue
castor symfony:test      # PHPUnit — 420 tests attendus
```
