# Configuration du stockage des fichiers

Le service `App\Service\DataStore\DataStore` fournit l'accès aux fichiers associés aux pièces jointes. Le choix de l'implémentation est configuré par `PREVARISC_DATASTORE_ADAPTER` dans `config/prevarisc.php`.

## Sélection de l'implémentation

| Valeur | Implémentation |
|---|---|
| `Plugin_SimpleFileDataStore` (défaut) | `SimpleFileDataStore` |
| `Plugin_HashedFileDataStore` | `HashedFileDataStore` |
| Toute autre valeur | `SimpleFileDataStore` (fallback de `DataStoreFactory`) |

Le `DataStoreCompilerPass`, enregistré par `Kernel::build()`, redirige l'interface vers `RuntimeDataStore`. Le proxy crée l'implémentation choisie à sa première utilisation et délègue ensuite les appels à cette instance.

La variable doit être fournie à l'environnement PHP de l'application par le mécanisme de déploiement utilisé. Après un changement, redémarrer le processus applicatif concerné pour qu'il recharge son environnement. Il n'y a pas de migration automatique des fichiers entre les deux organisations : vérifier les chemins utilisés et prévoir toute conversion séparément.

## Organisation des fichiers

- `SimpleFileDataStore` construit les chemins sans distribution par hash.
- `HashedFileDataStore` calcule un hash MD5 à partir de l'identifiant de la pièce jointe et distribue les types hashés dans des sous-répertoires.
- `DataStoreObjectTypes::NON_HASHED_TYPES` définit les types conservés sans hash par l'implémentation hashée.
- `PREVARISC_DATA_PATH` et `PREVARISC_REAL_DATA_PATH` configurent respectivement le chemin d'URL et le chemin physique via `config/prevarisc.php`.

Voir `src/Service/DataStore/` et `src/Constant/DataStoreObjectTypes.php` pour les détails et les chemins exacts.

## Personnaliser les noms de téléchargement

La variable facultative `PREVARISC_DATASTORE_FILE_FORMAT` permet de personnaliser le nom des pièces jointes au téléchargement. Elle est prise en compte par les deux adaptateurs. Si elle est absente, la configuration Symfony fournit le format par défaut `%NOM_PIECEJOINTE%%EXTENSION_PIECEJOINTE%`, injecté dans les adaptateurs.

Une variable explicitement vide (ou contenant uniquement des espaces) est rejetée avec une erreur de configuration. Pour retrouver le format standard, supprimer la définition de cette variable dans l'environnement ; ne pas la remplacer par une chaîne vide.

Exemple de configuration Apache :

```apache
SetEnv PREVARISC_DATASTORE_FILE_FORMAT "%NUMEROID_ETABLISSEMENT%_%DATE_PIECEJOINTE%_%NOM_PIECEJOINTE%_%ID_PIECEJOINTE%%EXTENSION_PIECEJOINTE%"
```

Pour une séance `42`, un ODJ `123` généré le 5 octobre 2026 sera téléchargé sous `42_2026-10-05_Ordre du jour_123.odt`. Le même format s'applique aux autres pièces jointes de séance, notamment convocations, PV et comptes-rendus, ainsi qu'aux pièces de dossier (y compris Plat'AU) et aux entrées de leurs archives ZIP.

Pour une pièce jointe de dossier, `%NUMEROID_ETABLISSEMENT%` utilise les numéros des établissements liés au dossier. Si la description de la pièce contient le numéro d'un établissement lié, ce numéro est retenu ; sinon, les numéros disponibles sont assemblés avec `_`. Un établissement sans numéro est représenté par l'identifiant du dossier. Si le dossier n'a aucun établissement lié, son identifiant est utilisé.

| Jeton | Valeur |
|---|---|
| `%NOM_PIECEJOINTE%` | Libellé enregistré, sans extension |
| `%EXTENSION_PIECEJOINTE%` | Extension, point compris |
| `%ID_PIECEJOINTE%` | Identifiant de la pièce jointe |
| `%DATE_PIECEJOINTE%` | Date d'ajout/génération au format `Y-m-d`, vide si absente ; ce n'est pas la date de la séance |
| `%DESCRIPTION_PIECEJOINTE%` | Description |
| `%ID_OBJECT%` | Identifiant de l'objet auquel la pièce jointe est rattachée |
| `%NUMEROID_ETABLISSEMENT%` | Pour un établissement : son numéro, ou son identifiant si le numéro est absent. Pour un dossier : numéro(s) des établissements liés selon la règle ci-dessus, ou identifiant du dossier s'il n'y en a aucun. Pour une commission : identifiant de la date de commission (séance), pas de la commission |
| `%TYPE_OBJET%` | Type de rattachement, par exemple `dateCommission` |
| `%CODE_TYPE_OBJET%` | Trois premières lettres du type, en majuscules |
| `%SHORT_CODE_TYPE_OBJET%` | Première lettre du type, en majuscule |

Le libellé affiché et les chemins physiques ne changent pas. Les pièces Plat'AU conservent leur chemin physique dédié, mais utilisent également le format configuré pour leur nom de téléchargement. Les téléchargements unitaires et les entrées ZIP de dossiers sont assainis par le contrôleur HTTP. Si plusieurs pièces d'un dossier produisent le même nom configuré, une entrée ZIP peut en remplacer une autre ; inclure `%ID_PIECEJOINTE%` dans le format permet de les distinguer. Après modification, redémarrer le processus applicatif concerné pour qu'il recharge son environnement, comme pour le choix de l'adaptateur.
