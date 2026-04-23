# Code Review Report — PR #191

**Branche :** `refactor/repositories-querybuilder`
**Commit :** `12ea64f`
**Date :** 2026-04-23
**Reviewers :** 🐙 GitHub Copilot · 👤 @devottered · 🤖 Copilot CLI (Claude Opus 4.6)

---

## Fichiers analysés

- `src/Repository/DossierRepository.php` (+306 / -183 lignes)

---

## Résumé exécutif

✅ **Aucun problème bloquant détecté.** La migration des 24 appels `getConnection()` vers QueryBuilder/DQL/NativeQuery est correcte et fidèle au comportement legacy.

---

## Points positifs

- **Cohérence fonctionnelle** : Toutes les conversions DateTime (`DATEDIFF(CURDATE(), col) >= :days` → `col <= today - days`) sont mathématiquement équivalentes, vérifiées aux valeurs limites.
- **`buildNativeQueryRsm()` robuste** : L'introspection des métadonnées Doctrine garantit que `d.*` est mappé sans perte — colonnes scalaires + FK ManyToOne/OneToOne — sans avoir à lister manuellement ~30 colonnes.
- **`findDossiersLies()` correctement splitté** : Les deux QueryBuilder (owning `dossiersLies` / inverse `dossiersAyantLie`) reproduisent exactement le `WHERE ID_DOSSIER1 = :id OR ID_DOSSIER2 = :id` original. Les clés `ID_DOSSIER1`/`ID_DOSSIER2` retournées sont compatibles avec tous les appelants.
- **Sécurité SQL préservée** : Le pattern `implode(',', array_map('intval', $idsCommission))` est sûr — `intval()` garantit une sortie entière uniquement.
- **`getTypeDossier()`** : `IDENTITY(d.type)` extrait correctement la valeur FK. Le retour `false` pour non-trouvé est conservé, compatible avec tous les appelants (`false !== $tabType`).
- **13 méthodes COUNT** : Toutes préservent fidèlement le `COUNT(DISTINCT d.id)`, les JOINs nécessaires aux conditions WHERE, et les filtres de suppression logique.
- **RSM `buildDashboardResultSetMapping()`** : Vérifié que les colonnes de `d.*`, `avis.*` (2 cols), `dossieraffectation.*` (5 cols), `datecommission.*` (9 cols) et `dossierlie.*` (2 cols) sont toutes couvertes. Aucune perte silencieuse de données vs. l'ancien `fetchAllAssociative`.

---

## Problèmes détectés

| Gravité | Source | Localisation | Description |
|---------|--------|-------------|-------------|
| 🔴 Critique | 🐙 Copilot | `countDossierDateCommissionEchu()` L.1452 | `COUNT(d.id)` non DISTINCT peut **surcompter** si un dossier a plusieurs natures, liaisons ou affectations (JOINs multiples). La liste correspondante (`findDossierDateCommissionEchu`) utilise `GROUP BY d.ID_DOSSIER`, le count doit être `COUNT(DISTINCT d.id)` pour rester cohérent. **Correction proposée par Copilot :** `->select('COUNT(DISTINCT d.id)')` |
| 🟡 Mineur | 🐙 Copilot | `countDossierDateCommissionEchu()` L.1447 | Calcul des seuils temporels côté PHP (`new DateTime('today')`) plutôt qu'en SQL (`DATEDIFF(CURDATE(), ...)`). En cas de désynchronisation de timezone entre l'appli PHP et la DB, les résultats peuvent diverger autour des changements de jour. Envisager d'exprimer le filtre via `CURRENT_DATE()` en DQL, ou s'assurer explicitement que les timezones sont alignés. |
| 🟡 Mineur | 👤 @devottered | `buildSearchDossierBaseSql()` L.647 | Suppression du paramètre `$getCount` : les méthodes de comptage dashboard utilisent désormais des QueryBuilder dédiés (lean), ce qui est correct. **Mais la question reste ouverte** : le dashboard déclenche-t-il bien des requêtes COUNT légères à l'ouverture de page, sans charger toutes les données ? À vérifier au niveau du contrôleur/service appelant. |
| 🟡 Mineur | 👤 @devottered | DocBlocks globaux | Les méthodes retournant `array<int, array<string, mixed>>` n'ont pas évolué vers des objets Doctrine — le but affiché dans les commentaires de la PR parlait de "passer vers des tableaux d'objets". Les DocBlocks sont cohérents avec l'implémentation actuelle (scalaires), mais l'intention fonctionnelle finale devrait être clarifiée. |
| 💡 Préexistant | 🤖 CLI | `findDossierDateCommissionEchu` vs son `count` | Asymétrie sur le filtre `dateSuppression` — préexistant, hors scope de cette PR. |
| 💡 Préexistant | 🤖 CLI | `findLeveePresc`, `findAbsenceQuorum`, `findNpsp` | Motif N+1 en boucle — préexistant, hors scope de cette PR. |

---

## Corrections requises avant merge

1. **`countDossierDateCommissionEchu()` — `COUNT(DISTINCT d.id)`** (🔴 Critique)
   ```php
   ->select('COUNT(DISTINCT d.id)')
   ```

2. **Timezone dashboard** (🟡 selon config serveur) — vérifier que `date_default_timezone_set` PHP == timezone MySQL, ou remplacer par un filtre DQL avec `CURRENT_DATE()`.

3. **Clarifier l'intention des DocBlocks** (🟡 discussion) — préciser si les `array<int, array<string, mixed>>` sont le format final ou un état intermédiaire vers des objets typés.

---

## Métriques

| Métrique | Valeur |
|---------|--------|
| Fichiers analysés | 1 |
| Problèmes critiques | 1 |
| Problèmes mineurs | 2 |
| Points préexistants signalés | 2 |

---

## Corrections apportées (commit `f73c7e7`)

| # | Gravité | Source | Correction |
|---|---------|--------|-----------|
| 1 | 🔴 Critique | 🐙 Copilot | `COUNT(d.id)` → `COUNT(DISTINCT d.id)` dans `countDossierDateCommissionEchu()` |
| 2 | 🟡 Mineur | 🐙 Copilot | Suppression des JOINs `n.type` et `dossiersAyantLie` inutiles (non référencés en WHERE) qui amplifiaient le surcomptage |
| 3 | 🟡 Mineur | 🐙 Copilot | `new \DateTime` → `new \DateTimeImmutable` + commentaire explicite sur l'alignement timezone PHP/MySQL |
| 4 | 🟡 Mineur | 👤 @devottered | DocBlock sur `buildSearchDossierBaseSql()` : clarification que les `find*` chargent les données, les `count*` utilisent des QueryBuilder légers dédiés |
| 5 | 🟡 Mineur | 👤 @devottered | DocBlock sur `buildNativeQueryRsm()` : le mode scalaire `array<string, mixed>` est intentionnel pour la compatibilité avec les templates Twig existants — une migration vers des objets typés nécessiterait d'adapter tous les templates |

---

## Verdict final

✅ **Approuvé — prêt à merger** (après corrections commit `f73c7e7`).
