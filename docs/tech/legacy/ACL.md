# Documentation du plugin ACL

Ce document explique le fonctionnement du fichier `ACL.php` qui implémente le plugin de gestion des contrôles d'accès (ACL) pour l'application Prevarisc basée sur Zend Framework.

## Vue d'ensemble

Le plugin ACL gère les autorisations des utilisateurs en fonction de leur rôle, des ressources et des privilèges. Il utilise le cache pour optimiser la génération des ACL et adapte dynamiquement les ressources selon le contexte utilisateur.

## Principales fonctionnalités

### 1. Gestion des ACL
La méthode statique `getAcl()` crée et récupère les ACL. Elle utilise le cache pour éviter de recalculer les ACL à chaque requête :

```php
if (($acl = unserialize($cache->load('acl'))) === false) {
    $acl = new Zend_Acl();
    // Ajout des rôles, ressources et privilèges
}
```

Les rôles, ressources et privilèges sont extraits de la base de données et ajoutés à l'ACL :

```php
if (!$acl->hasRole($role->LIBELLE_GROUPE)) {
    $acl->addRole($role->LIBELLE_GROUPE);
}
```

Les ressources et privilèges sont associés aux rôles :

```php
if (!$acl->has($resource['name'])) {
    $acl->add(new Zend_Acl_Resource($resource['name']));
}
$acl->allow($role->LIBELLE_GROUPE, $resource['name'], $privilege['name']);
```

### 2. Pré-dispatch des requêtes
La méthode `preDispatch()` vérifie l'authentification et l'autorisation avant chaque action du contrôleur. Elle gère l'authentification CAS si activée :

```php
if (1 == getenv('PREVARISC_CAS_ENABLED')) {
    phpCAS::client(...);
    phpCAS::forceAuthentication();
}
```

Si l'utilisateur n'est pas connecté, il est redirigé :

```php
if (!Zend_Auth::getInstance()->hasIdentity()) {
    $redirector->gotoSimple('login', 'session', 'default', $redirect);
}
```

### 3. Gestion des ressources dynamiques
Pour certains contrôleurs, des ressources dynamiques sont générées selon les données utilisateur :

```php
if ('etablissement' === explode('_', $resource['name'])[0]) {
    $resource_exploded = explode('_', $resource['name']);
    // Adaptation des ressources dynamiques
}
```

### 4. Contrôle des autorisations
L'accès est vérifié pour chaque ressource et privilège :

```php
if ($acl->has($resource) && $acl->isAllowed($role, $resource, $privilege)) {
    $access_granted = true;
}
```

En cas de refus, l'utilisateur est redirigé vers une page d'erreur.

### 5. Conteneur de ressources
La classe `ResourceContainer` gère les ressources dynamiques et optimise leur développement à la demande :

```php
public function develop_resources(&$list_resources_finale): array {
    // Développement des ressources dynamiques
}
```

Cette classe implémente l'interface `Iterator` pour faciliter l'itération sur les ressources.

## Conclusion

Le plugin ACL assure la sécurité et la gestion fine des autorisations dans Prevarisc, en adaptant dynamiquement les ressources et privilèges selon le contexte utilisateur et en intégrant des mécanismes d'authentification et de gestion des erreurs.

