# Reprendre ici — État de la migration

> Ce fichier est mis à jour après chaque phase de migration. Il décrit l'état courant et le prochain point de départ pour reprendre les travaux.

---

## ✅ Phases terminées

### Phase 1 — Affichage read-only des formulaires personnalisés
**Branche :** `migration/affichage-formulaires-persos` (mergée)  
**Périmètre :** Affichage des 4 onglets de formulaires personnalisés en lecture seule.

### Phase 2 — Édition des formulaires personnalisés (POST brut, DescriptifSaver)
**Commit :** `feat(formulaires): édition formulaires personnalisés - Phase 2`  
**Note :** Ce service a été remplacé en Phase 3B — voir ci-dessous.

### Phase 3 — Migration complète vers Symfony Forms + DTOs
**Branche :** `migration/edition-formulaire-perso`  
**Commit :** `feat(descriptif): migration formulaires personnalisés vers Symfony Forms`  
**Périmètre :** Remplacement de `DescriptifSaver` (parsing POST brut) par un pipeline Symfony Forms complet.

#### Pipeline mis en place

| Étape | Fichiers clés |
|-------|---------------|
| **DTOs** | `src/Dto/Descriptif/DescriptifFormData.php`, `RubriqueFormData.php`, `ChampValueData.php`, `TableauRowData.php` |
| **FormTypes feuille** | `src/Form/Type/Descriptif/ChampText/Textarea/Select/Number/Checkbox/DateType.php` |
| **DataTransformer** | `src/Form/DataTransformer/DateToStringTransformer.php` |
| **FormTypes composites** | `src/Form/Type/Descriptif/ChampValueType.php` (PRE_SET_DATA), `TableauRowType.php` |
| **Hydrator** | `src/Service/Descriptif/DescriptifFormHydrator.php` — Valeur entities → DTOs |
| **Builder** | `src/Service/Descriptif/DescriptifFormBuilder.php` — DTOs + rubriques → FormInterface |
| **Persister** | `src/Service/Descriptif/DescriptifFormPersister.php` — DTOs → Valeur entities (idx normalisé) |
| **Templates** | `templates/components/_formulaire_personnalise_edit.html.twig` (réécrit), `templates/form/descriptif_theme.html.twig` |
| **JS** | `public/js/formulaire/descriptif/descriptif-collection.js` (add/delete + `reindexTableauRows` pour drag&drop futur), `edit.js` mis à jour |
| **Tests** | `tests/Service/Descriptif/DescriptifFormHydratorTest.php` (13 tests), `DescriptifFormPersisterTest.php` (6 tests) |

#### Fichiers supprimés (nettoyage)
- `src/Service/DescriptifSaver.php` (remplacé par le pipeline ci-dessus)
- `tests/Service/DescriptifSaverTest.php`
- `public/js/formulaire/tableau/gestionTableau.js`

#### Compatibilité drag & drop
Le `DescriptifFormPersister` normalise l'`idx` de toutes les lignes à chaque sauvegarde (position 1-based). `reindexTableauRows($tbody)` dans `descriptif-collection.js` est prêt pour le futur drag & drop JS.

**Plan complet :** `docs/tech/dossier/PLAN_MIGRATION_FORMULAIRES_SYMFONY_FORMS.md`

---

## 🎯 Prochaine phase : à définir

**Branche courante :** `migration/edition-formulaire-perso` (prête à merger)

Suggestions :
- Tester manuellement les 4 onglets d'édition en environnement DEV
- Implémenter le drag & drop JS (appel à `window.reindexTableauRows` déjà disponible)
- Continuer la migration des autres fonctionnalités (voir backlog)

---

## 📌 Commandes de validation

```bash
castor symfony:analyse   # PHPStan niveau 10 — 9 erreurs pré-existantes attendues
castor symfony:cs        # CS/Rector/Twig — 0 erreur attendue
castor symfony:test      # PHPUnit — 438 tests attendus
```
