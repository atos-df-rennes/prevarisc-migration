# Reprendre ici — État de la migration

> Ce fichier est mis à jour après chaque phase de migration. Il décrit l'état courant et le prochain point de départ pour reprendre les travaux.

---

## ✅ Phases terminées

### Phase 1 — Affichage read-only des formulaires personnalisés
**Branche :** `migration/affichage-formulaires-persos` (mergée)  
**Périmètre :** Affichage des 4 onglets de formulaires personnalisés en lecture seule.

### Phase 2 — Édition des formulaires personnalisés (POST brut, DescriptifSaver)
**Commit :** `feat(formulaires): édition formulaires personnalisés - Phase 2`  
**Note :** Ce service a été remplacé en Phase 3 — voir ci-dessous.

### Phase 3 — Migration complète vers Symfony Forms + DTOs + drag & drop
**Branche :** `migration/edition-formulaire-perso` (prête à merger)  
**Périmètre :** Remplacement de `DescriptifSaver` par un pipeline Symfony Forms complet avec drag & drop des lignes de tableau.

**Documentation complète :** `docs/tech/dossier/FORMULAIRES_PERSONNALISES.md`

#### Fichiers supprimés (nettoyage)
- `src/Service/DescriptifSaver.php` (remplacé par le pipeline)
- `tests/Service/DescriptifSaverTest.php`
- `public/js/formulaire/tableau/gestionTableau.js`

---

## 🎯 Prochaine phase : à définir

**Branche courante :** `migration/edition-formulaire-perso` (prête à merger)

Suggestions :
- Continuer la migration des autres fonctionnalités (voir backlog)

---

## 📌 Commandes de validation

```bash
castor symfony:analyse   # PHPStan niveau 10 — 6 erreurs pré-existantes attendues
castor symfony:cs        # CS/Rector/Twig — 0 erreur attendue
castor symfony:test      # PHPUnit — 425 tests attendus
```
