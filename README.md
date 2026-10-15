![Static Badge](https://img.shields.io/badge/status-rc.2-orange)
![Static Badge](https://img.shields.io/badge/php-8.5-blue)
![Static Badge](https://img.shields.io/badge/symfony-7.4-green)
![Static Badge](https://img.shields.io/badge/license-CeCILL--B-yellow)

# Prevarisc

Application web open-source gérant l'ensemble de l'activité du service prévention au sein d'un Service Départemental d'Incendie et de Secours (SDIS).

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
- **MariaDB**: Utilisé en version 11.8+ pour les migrations
- **JavaScript** : Framework JavaScript moderne pour l'interface utilisateur
- **Bootstrap** : Utilisé en [version 3](https://getbootstrap.com/docs/3.4/)
- **LibreOffice** : Utilisé pour créer et éditer les documents ; les modèles ODT LibreOffice 7 et 25 sont pris en charge

## Installation

1. Clonez le dépôt :
```sh
$ git clone https://github.com/atos-df-rennes/prevarisc-migration.git prevarisc
$ cd prevarisc
$ git checkout release/3.0
```

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