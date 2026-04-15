# Code Review Report — Recherche (établissements + dossiers)

## Fichiers analysés
- `src/Controller/SearchController.php`
- `src/Service/Search.php`
- `src/Repository/GenreRepository.php`
- `src/Repository/EtablissementStatutRepository.php`
- `src/Repository/EtablissementCategorieRepository.php`
- `src/Repository/IghClasseRepository.php`
- `src/Repository/FamilleRepository.php`
- `src/Repository/CommissionRepository.php`
- `src/Repository/EtablissementActiviteRepository.php`
- `src/Repository/CommuneRepository.php`
- `src/Repository/AdresseRueRepository.php`
- `src/Repository/EtablissementAdresseRepository.php`
- `templates/search/etablissement.html.twig`
- `templates/search/dossier.html.twig`
- `templates/search/results/_etablissement.html.twig`
- `templates/search/results/_dossier_tab.html.twig`
- `templates/search/_pagination.html.twig`

## Points positifs
- ✅ **Sécurité SQL** : Toutes les valeurs utilisateur passent par `safeQuote()` (wrapper `$this->connection->quote()`), pas d'injection possible dans les requêtes DBAL brutes
- ✅ **Contrôle d'accès** : `denyAccessUnlessGranted` présent sur les 3 routes exposées (`etablissement`, `dossier`, `ajaxEtablissement`)
- ✅ **Templates Twig** : Échappement automatique actif, les `|raw` sont limités à du HTML statique sans données utilisateur
- ✅ **Requêtes COUNT optimisées** : Les flags `$needsAdresseCommune` et `$needsDocUrba` allègent correctement les COUNT sans altérer les résultats
- ✅ **Aliases repositories** : `ID_GENRE`, `LIBELLE_GENRE`, etc. correspondent exactement aux accès dans les templates — aucune régression
- ✅ **Validation des paramètres GET** : `getStringQueryParam()` / `getArrayQueryParam()` filtrent les entrées avant usage
- ✅ **Requêtes batch sécurisées** : Préventionnistes et pièces jointes utilisent `PARAM_STR_ARRAY` (préparée)
- ✅ **Pré-sélection groupements** : Comportement iso-fonctionnel (utilisateur courant pré-sélectionné au premier chargement)

## Problèmes détectés

| Gravité | Fichier | Description |
|---------|---------|-------------|
| 🟡 Mineur | `results/_etablissement.html.twig` | Le legacy vérifie `file_exists()` avant d'afficher l'image du type (`icone-type-*.png`) et affiche le libellé texte en fallback. Le code migré rend toujours la balise `<img>` — image cassée si le fichier est absent. |

## Suggestions
- Aucune suggestion critique. La migration respecte le principe de port direct.

## Métriques
- Fichiers analysés : 17
- Problèmes critiques : 0
- Problèmes mineurs : 1
- Suggestions : 0
