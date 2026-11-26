![Version](https://img.shields.io/badge/version-3.0.2-orange)
![Static Badge](https://img.shields.io/badge/php-8.5-blue)
![Static Badge](https://img.shields.io/badge/symfony-7.4-green)
![Static Badge](https://img.shields.io/badge/license-CeCILL--B-yellow)

# Prevarisc

Application web open-source gérant l'ensemble de l'activité du service prévention au sein d'un Service Départemental d'Incendie et de Secours (SDIS).

## Table des matières

- [Guide](#guide)
- [Technologies utilisées](#technologies-utilisées)
- [Versions](#versions)
- [Installation](#installation)
- [Exploitation](#exploitation)
- [Licence](#licence)

## Guide

Consultez la [documentation dédiée](docs/guide.md).

Pour migrer une installation **2.5, 2.8 ou 2.10 vers 3.0**, consultez le
[guide de migration](MIGRATION_3.0.md).

## Technologies utilisées

- **PHP** : Utilisé en version 8.5+ avec le framework [Symfony 7.4 LTS](https://symfony.com/doc/7.4/index.html)
- **MariaDB**: Utilisé en version 11.8+ pour les migrations
- **JavaScript** : Framework JavaScript moderne pour l'interface utilisateur
- **Bootstrap** : Utilisé en [version 3](https://getbootstrap.com/docs/3.4/)
- **LibreOffice** : Utilisé pour créer et éditer les documents ; les modèles ODT LibreOffice 7 et 25 sont pris en charge

## Versions

Prevarisc suit le [versionnage sémantique (SemVer)](https://semver.org/lang/fr/) au format `MAJEUR.MINEUR.CORRECTIF` :

- **MAJEUR** : peut introduire des changements incompatibles et nécessite de suivre le guide de migration correspondant, le cas échéant.
- **MINEUR** : apporte de nouvelles fonctionnalités rétrocompatibles. La mise à jour peut nécessiter des migrations de base de données ou de nouvelles variables d'environnement ; consultez le changelog et les instructions de mise à jour.
- **CORRECTIF** : apporte des corrections rétrocompatibles.

Pour une migration majeure depuis Prevarisc 2.x, suivez le
[guide de migration vers 3.0](MIGRATION_3.0.md) avant de déployer.

| Type de version | Exemple | Utilisation |
| --- | --- | --- |
| Préversion (`-rc.N`) | `3.0.0-rc.4` | Release candidate destinée à l'environnement de test/recette. Elle peut encore contenir des anomalies et être suivie d'autres RC. |
| Stable (sans suffixe) | `3.0.0` | Version destinée à la production, après une mise en test préalable. |

> [!WARNING]
> Ne déployez jamais une version `-rc.N` en production : il s'agit d'une préversion destinée à la recette.

Les branches `develop` (intégration) et `release/x.y` (préparation des versions `x.y`) ne sont pas des versions à déployer. `main` correspond à la dernière version stable publiée. Pour installer ou mettre à jour Prevarisc, choisissez un tag `vX.Y.Z` stable ; utilisez un tag `vX.Y.Z-rc.N` uniquement pour la recette. Consultez les [tags publiés](https://github.com/atos-df-rennes/prevarisc-migration/tags), le [changelog 3.x](CHANGELOG-3.x.md) et le [changelog 2.x](CHANGELOG-2.x.md).

## Installation

> [!IMPORTANT]
> Pour migrer une installation existante 2.5, 2.8 ou 2.10, ne suivez pas
> directement une procédure d'installation neuve : consultez le
> [guide de migration vers 3.0](MIGRATION_3.0.md).

1. Clonez le dépôt :
```sh
$ git clone https://github.com/atos-df-rennes/prevarisc-migration.git prevarisc
$ cd prevarisc
$ git fetch --tags origin
$ git checkout vX.Y.Z
```
Pour installer une préversion en environnement de test/recette, remplacez `vX.Y.Z` par le tag `vX.Y.Z-rc.N` souhaité. Consultez la section [Versions](#versions) avant de choisir un tag.

2. Copiez le fichier de variables d'environnement d'exemple et renseignez les valeurs suivant votre configuration :
```sh
$ cp .env.example .env
```
```
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=<chaine_de_caractere>
(pour générer un secret : php -r "echo bin2hex(random_bytes(16));")
DATABASE_URL="mysql://<user>:<password>@<host>:<port>/<database_name>?serverVersion=11.8.8-MariaDB&charset=utf8mb4"
(e.g. mysql://root:root@localhost:3306/PRV_prevarisc_v2?serverVersion=11.8.8-MariaDB&charset=utf8mb4)
```

Consultez le [catalogue complet des variables d'environnement](docs/documentation_installation.md#configuration-de-lapplication-30) pour les types, les valeurs par défaut et les réglages optionnels (LDAP, CAS, courriels, Plat'AU, stockage, etc.).

3. Installez les dépendances (Composer 2.x requis) :
```sh
$ composer install --no-dev --classmap-authoritative
```

4. Optimisez la lecture des configurations :
```sh
$ composer dump-env prod
```

> [!IMPORTANT]
> En cas de modification des variables d'environnement, cette commande est à rejouer à la suite pour que la nouvelle configuration soit prise en comtpe.

5. Jouez les migrations pour avoir un schéma de base de données à jour :
```sh
$ php bin/console doctrine:migrations:migrate --no-interaction
```

> [!IMPORTANT]
> Pour exécuter les commandes de migration, l'utilisateur de base de données utilisé doit disposer des droits suivants : CREATE, DROP, ALTER, INDEX, CREATE VIEW, SELECT, INSERT, UPDATE, DELETE.

6. Appliquez les permissions nécessaires :
```sh
$ chown –R www-data:www-data *
$ find . -type d -exec chmod 0755 {} \;
$ find . -type f -exec chmod 0644 {} \;
$ chmod -R 755 vendor/bin
```

7. Configurez votre serveur web (Apache 2.4+ ou Nginx recommandé) :

**Apache VirtualHost** :
```apache
<VirtualHost *:80>
    ServerName prevarisc.local
    DocumentRoot /var/www/html/prevarisc/public
    <Directory /var/www/html/prevarisc/public>
        DirectoryIndex index.php
        AllowOverride all
        Order allow,deny
        Allow from all
        <IfModule rewrite_module>
            Options +FollowSymlinks
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !^.+\.php$
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [L]
        </IfModule>
    </Directory>
</VirtualHost>
```

**Nginx** :
```nginx
server {
    listen 80;
    server_name prevarisc.local;
    root /var/www/html/prevarisc/public;
    location / {
        try_files $uri /index.php$is_args$args;
    }
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

## Exploitation

Consultez la [documentation dédiée](docs/exploitation.md).

## Licence

Ce projet est sous license [CeCILL-B](http://www.cecill.info/licences/Licence_CeCILL-B_V1-fr.html). Consultez le fichier [LICENSE](LICENSE.md) pour plus de détails.