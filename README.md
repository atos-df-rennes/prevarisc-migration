![Static Badge](https://img.shields.io/badge/status-rc-orange)
![Static Badge](https://img.shields.io/badge/php-8.5-blue)
![Static Badge](https://img.shields.io/badge/symfony-7.4-green)
![Static Badge](https://img.shields.io/badge/license-CeCILL--B-yellow)

# Prevarisc

Ceci est le dépôt de la nouvelle version technique de [Prevarisc](https://github.com/atos-df-rennes/prevarisc).

## Table des matières

- [Guide](#guide)
- [Technologies utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Exploitation](#exploitation)
- [Licence](#licence)

## Guide

Consultez la [documentation dédiée](docs/guide.md).

## Technologies utilisées

- **PHP** : Utilisé en version 8.5+ avec le framework [Symfony 7.4 LTS](https://symfony.com/doc/7.4/index.html)
- **MySQL**: Utilisé en version 5.6+ pour les migrations (version recommandée : 8.0+)
- **JavaScript** : Framework JavaScript moderne pour l'interface utilisateur
- **Bootstrap** : Utilisé en [version 5](https://getbootstrap.com/docs/5.3/)
- **LibreOffice** : Utilisé en version 7.x pour l'édition des documents (Les dernières versions ne sont actuellement pas compatibles)

## Installation

Prevarisc 3.0 est une refonte complète utilisant Symfony 7.4 LTS et PHP 8.5. Pour l'installer, suivez les étapes ci-dessous :

1. Clonez le dépôt :
```sh
$ git clone https://github.com/atos-df-rennes/prevarisc.git
$ cd prevarisc
$ git checkout release/3.0
```
> [!NOTE]
> Prevarisc 3.0 utilise la branche `release/3.0`. Les versions précédentes (2.8, 2.10) restent sur leurs branches respectives.

2. Copiez le fichier de variables d'environnement d'exemple et renseignez les valeurs suivant votre configuration :
```sh
$ cd prevarisc-migration
$ cp .env.example .env
```
```
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=<chaine_de_caractere>
(cf. pour générer un secret : http://nux.net/secret)
DATABASE_URL="mysql://<user>:<password>@<host>:<port>/<database_name>?serverVersion=5.6&charset=utf8mb4"
(e.g. mysql://root:root@localhost:3306/PRV_prevarisc_v2?serverVersion=5.6&charset=utf8mb4)
PREVARISC_PATH=/chemin/absolu/vers/Prevarisc
(Optionnel, si valeur différente de l'exemple : e.g. /var/www/html/prevarisc)
```

> [!TIP]
> Si l'un des paramètres de la variable DATABASE_URL contient des caractères spéciaux, ils doivent être encodés : + => %2B, @ => %40, $ => %24, # => %23, / => %2F, : => %3A, * => %2A, ! => %21

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
$ php bin/console doctrine:migrations:migrate
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