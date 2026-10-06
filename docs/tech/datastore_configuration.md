# Configuration du DataStore

## Description

L'application utilise un système de DataStore pour gérer le stockage des fichiers (pièces jointes, documents, etc.). Deux implémentations sont disponibles :

1. **SimpleFileDataStore** : Stockage simple sans hash (par défaut)
2. **HashedFileDataStore** : Stockage avec structure hashée (recommandé pour production)

> **💡 Note importante** : Le système utilise une résolution **runtime** du DataStore, permettant un changement de configuration sans recompilation du conteneur Symfony. Seul un redémarrage d'Apache est nécessaire.

## Configuration

Le choix du DataStore se fait via la variable d'environnement `PREVARISC_DATASTORE_ADAPTER`.

### Valeurs possibles :

- `Plugin_SimpleFileDataStore` (défaut) : Utilise SimpleFileDataStore
- `Plugin_HashedFileDataStore` : Utilise HashedFileDataStore

### Configuration dans Apache

La variable d'environnement est définie dans le fichier `apache/httpd-prevarisc-config.conf` :

```apache
SetEnv PREVARISC_DATASTORE_ADAPTER "Plugin_HashedFileDataStore"
# ou
SetEnv PREVARISC_DATASTORE_ADAPTER "Plugin_SimpleFileDataStore"
```

**Important :** Après modification de cette variable, le conteneur doit être redémarré pour que Apache prenne en compte le changement :

```bash
docker compose -f compose.dev.yaml restart app
```

## Implémentation technique

### Personnaliser les noms de téléchargement

La variable facultative `PREVARISC_DATASTORE_FILE_FORMAT` permet de personnaliser le nom des pièces jointes au téléchargement, sans modifier le source. Elle est prise en compte par les deux adaptateurs. Si elle est absente, la configuration Symfony fournit le format par défaut `%NOM_PIECEJOINTE%%EXTENSION_PIECEJOINTE%`, injecté dans les adaptateurs.

Une variable explicitement vide (ou contenant uniquement des espaces) ne déclenche pas le défaut Symfony : elle est rejetée avec une erreur de configuration. Pour retrouver le format standard, supprimer la directive `SetEnv PREVARISC_DATASTORE_FILE_FORMAT` ou la définition de cette variable dans l'environnement ; ne pas la remplacer par une chaîne vide.

Exemple de configuration Apache reprenant un format personnalisé :

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

Le libellé affiché et les chemins physiques ne changent pas. Les pièces Plat'AU conservent leur chemin physique dédié, mais utilisent aussi le format configuré pour leur nom de téléchargement. Les téléchargements unitaires et les entrées ZIP de dossiers sont assainis par le contrôleur HTTP. Si plusieurs pièces d'un dossier produisent le même nom configuré, une entrée ZIP peut en remplacer une autre ; inclure `%ID_PIECEJOINTE%` dans le format permet de les distinguer. Redémarrer Apache après modification de sa configuration, comme pour le choix de l'adaptateur.

Le système utilise un **pattern Factory avec Proxy runtime** pour résoudre le DataStore à l'exécution, permettant ainsi un changement dynamique sans recompilation du conteneur.

### Architecture

1. **`DataStoreFactory`** : Crée l'instance appropriée du DataStore en lisant la variable d'environnement `PREVARISC_DATASTORE_ADAPTER`
2. **`RuntimeDataStore`** : Proxy/Wrapper qui implémente l'interface `DataStore` et délègue les appels à l'instance créée par la factory
3. **`DataStoreCompilerPass`** : Configure l'alias entre l'interface `DataStore` et le `RuntimeDataStore`

Le CompilerPass est enregistré dans la méthode `build()` du Kernel, conformément aux bonnes pratiques Symfony.

### Avantages de cette approche

✅ **Pas de recompilation nécessaire** : Le changement de variable d'environnement est pris en compte au prochain redémarrage d'Apache
✅ **Production-ready** : Fonctionne de manière robuste en environnement de production 
✅ **Pas de manipulation complexe** : Aucun script spécifique ou suppression manuelle de cache requis

### Procédure de changement en production

Après modification de la variable dans `apache/httpd-prevarisc-config.conf` :

```bash
# 1. Redémarrer Apache (pour charger la nouvelle variable)
service apache2 restart
```

C'est tout ! Le nouveau DataStore sera utilisé immédiatement.

### Fichiers concernés :

- `src/DependencyInjection/DataStoreCompilerPass.php` : Configuration de l'alias vers RuntimeDataStore
- `src/Service/DataStore/DataStoreFactory.php` : Logique de sélection du DataStore à l'exécution
- `src/Service/DataStore/RuntimeDataStore.php` : Proxy qui délègue aux implémentations concrètes
- `src/Kernel.php` : Enregistrement du CompilerPass dans la méthode `build()`
- `src/Service/DataStore/DataStore.php` : Interface
- `src/Service/DataStore/SimpleFileDataStore.php` : Implémentation simple
- `src/Service/DataStore/HashedFileDataStore.php` : Implémentation hashée

## Différences entre les deux implémentations

### SimpleFileDataStore
- Structure : `/uploads/{type}/{id}{extension}`
- Avantages : Simple, facile à naviguer
- Inconvénients : Peut poser problème avec un grand nombre de fichiers

### HashedFileDataStore
- Structure : `/uploads/{type}/{hash[0-2]}/{hash[2-4]}/{linkedObjectId}/{id}{extension}`
- Avantages : Meilleure distribution des fichiers, performances améliorées
- Inconvénients : Structure plus complexe
- Types non hashés : document, courrier, avatar (utilise la structure simple)
