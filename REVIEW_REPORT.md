# Code Review Report — Migration gestion des pièces jointes (établissement)

**Branche :** `migration/gestion-pieces-jointes-etablissement`
**Commit initial :** `060b23d`
**Correctifs appliqués :** `fix(etablissement): corrections post-revue gestion PJ`
**Date :** 2026-04-23
**Relecteur :** Copilot (Opus)

---

## Fichiers analysés

- `src/Controller/EtablissementController.php` (actions `editPiecesJointes`, `addPieceJointeForm`, `addPieceJointe`, `deletePieceJointe`)
- `src/Service/Etablissement.php` (méthodes `addPJ`, `deletePJ`)
- `templates/etablissement/edit_pieces_jointes.html.twig`
- `templates/etablissement/add_piece_jointe.html.twig`
- `templates/etablissement/pieces_jointes.html.twig`

---

## Points positifs

- Port direct fidèle : logique de blacklist extension, placement image, miniature 450px correctement portés
- PHP 7.1 respecté : DocBlocks `@var`, `@param`, `@return` présents, pas d'union types ni d'attributs PHP 8
- Bootstrap 3 : zéro résidu BS2 (`icon-*` → `glyphicon glyphicon-*`, `unstyled` → `list-unstyled`, `muted` → `text-muted`)
- `UploadedFile` Symfony utilisé correctement à la place de `$_FILES`
- Injection par constructeur propre pour `PieceJointeRepository` et `Image`
- Routes Symfony remplacent tous les chemins hardcodés dans `pieces_jointes.html.twig`

---

## Problèmes détectés et corrigés

| Gravité | Fichier | Description | Statut |
|---------|---------|-------------|--------|
| 🔴 Critique | `EtablissementController.php:436` | Suppression via GET sans CSRF — exploitable par CSRF/prefetch | ✅ Corrigé : POST + `isCsrfTokenValid` + formulaire avec `_token` |
| 🔴 Critique | `edit_pieces_jointes.html.twig:101-107` | Dropzone success handler : `responseText === ""` jamais `undefined`, `#fiche_etablissement` absent du layout Symfony → la liste ne se rafraîchit jamais | ✅ Corrigé : handler simplifié en `document.location.reload()` |

---

## Suggestions d'amélioration

- **Confirmation suppression** : un `confirm()` JS a été ajouté sur le bouton de suppression (cohérence UX avec le reste de l'application)
- **`deletePJ` null guard** : si `EtablissementPieceJointe` introuvable en DB, les fichiers physiques sont supprimés mais l'entité `PieceJointe` est retirée seule — comportement identique au legacy (la vérification `if (null != $DBitem)` était toujours vraie dans Zend)
- **Cache** : le `$cache->remove(...)` legacy n'a pas d'équivalent Symfony direct ; l'invalidation de cache HTTP/Doctrine n'est pas nécessaire dans l'état actuel de la migration

---

## Métriques

- Nombre de fichiers analysés : 5
- Problèmes critiques détectés : 2
- Problèmes critiques corrigés : 2
- Suggestions mineures : 3
