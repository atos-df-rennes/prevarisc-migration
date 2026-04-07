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

---

## 🎯 Prochaine phase à planifier

Les formulaires personnalisés en lecture et édition sont maintenant complètement migrés.

**Sujets restants à identifier :**
- Vérifier s'il reste des URLs Zend legacy dans d'autres templates
- Identifier la prochaine fonctionnalité à migrer (consulter le backlog)

---

## 📌 Commandes de validation

```bash
castor symfony:analyse   # PHPStan niveau 10 — 6 erreurs pré-existantes attendues
castor symfony:cs        # CS/Rector/Twig — 0 erreur attendue
castor symfony:test      # PHPUnit — 407 tests attendus
```
