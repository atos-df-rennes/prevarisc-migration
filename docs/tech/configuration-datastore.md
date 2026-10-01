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
