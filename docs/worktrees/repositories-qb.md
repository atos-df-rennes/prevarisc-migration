# Suivi worktree — `refactor/repositories-querybuilder`

> **Référence audit :** `RAPPORT_SYMFONY.md` — item REF-REP-01
> **Périmètre :** Migration SQL brut → QueryBuilder/DQL/NativeQuery dans les repositories
> **Branche :** `refactor/repositories-querybuilder`
> **Cible :** `release/2.8`

---

## Objectif

Remplacer les 57 occurrences de `getConnection()` SQL brut (REF-REP-01) réparties dans
les repositories Doctrine, en commençant par `DossierRepository` (27 occurrences).

---

## Suivi d'avancement

| # | Action | Statut | Commit | PR |
|---|--------|--------|--------|----|
| RQB-1 | **Migration SQL brut → QueryBuilder/NativeQuery dans `DossierRepository`** : 24 appels `getConnection()` supprimés, 13 méthodes COUNT migrées vers QueryBuilder lean, 12 méthodes data migrées vers `createNativeQuery()` + `ResultSetMapping`, helpers `buildNativeQueryRsm()` / `buildDashboardResultSetMapping()` | ✅ Fait | `12ea64f` | #191 |
| RQB-2 | **Fix post-review PR #191** : `COUNT(DISTINCT d.id)` dans `countDossierDateCommissionEchu`, suppression JOINs inutiles (`n.type`, `dossiersAyantLie`), `DateTime` → `DateTimeImmutable` + commentaire timezone, DocBlocks clarifiés (`find*` vs `count*`, mode scalaire intentionnel) | ✅ Fait | `f73c7e7` | #191 |

---

## Reste à faire

- `EtablissementRepository` : 8 appels `getConnection()` restants (voir REF-REP-01, phase 2)
- Autres repositories secondaires identifiés dans le RAPPORT_SYMFONY

---

> 🗑️ Ce fichier est supprimé à la clôture du worktree (merge de la PR).
