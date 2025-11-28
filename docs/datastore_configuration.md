# Configuration du DataStore

## Description

L'application utilise un système de DataStore pour gérer le stockage des fichiers (pièces jointes, documents, etc.). Deux implémentations sont disponibles :

1. **SimpleFileDataStore** : Stockage simple sans hash (par défaut)
2. **HashedFileDataStore** : Stockage avec structure hashée (recommandé pour production)

> **💡 Note importante** : Le système utilise une résolution **runtime** du DataStore, permettant un changement de configuration sans recompilation du conteneur Symfony. Seul un redémarrage d'Apache et optionnellement un `cache:clear` sont nécessaires.

## Configuration

Le choix du DataStore se fait via la variable d'environnement `PREVARISC_DATASTORE_ADAPTER`.

### Valeurs possibles :

- `Plugin_SimpleFileDataStore` (défaut) : Utilise SimpleFileDataStore
- `Plugin_HashedFileDataStore` : Utilise HashedFileDataStore

### Configuration dans Apache

La variable d'environnement est définie dans le fichier `apache/httpd-prevarisc-config.conf` (ligne 87) :

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

Le système utilise un **pattern Factory avec Proxy runtime** pour résoudre le DataStore à l'exécution, permettant ainsi un changement dynamique sans recompilation du conteneur.

### Architecture

1. **`DataStoreFactory`** : Crée l'instance appropriée du DataStore en lisant la variable d'environnement `PREVARISC_DATASTORE_ADAPTER`
2. **`RuntimeDataStore`** : Proxy/Wrapper qui implémente l'interface `DataStore` et délègue les appels à l'instance créée par la factory
3. **`DataStoreCompilerPass`** : Configure l'alias entre l'interface `DataStore` et le `RuntimeDataStore`

Le CompilerPass est enregistré dans la méthode `build()` du Kernel, conformément aux bonnes pratiques Symfony.

### Avantages de cette approche

✅ **Pas de recompilation nécessaire** : Le changement de variable d'environnement est pris en compte au prochain redémarrage d'Apache  
✅ **Production-ready** : Fonctionne de manière robuste en environnement de production  
✅ **Simplicité opérationnelle** : Seul un simple `php bin/console cache:clear` est nécessaire après redémarrage Apache  
✅ **Pas de manipulation complexe** : Aucun script spécifique ou suppression manuelle de cache requis

### Procédure de changement en production

Après modification de la variable dans `apache/httpd-prevarisc-config.conf` :

```bash
# 1. Redémarrer Apache (pour charger la nouvelle variable)
service apache2 restart

# 2. Vider le cache Symfony (optionnel mais recommandé)
php bin/console cache:clear
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
