![Static Badge](https://img.shields.io/badge/status-ready-dark--green)
![Static Badge](https://img.shields.io/badge/php-7.1-blue)
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

- **PHP** : Utilisé en version 7.1 avec le framework [Symfony 4](https://symfony.com/doc/4.x/index.html)
- **MySQL**: Utilisé en version 5.6 pour les migrations
- **JavaScript** : Utilisé avec [jQuery](https://jquery.com/)
- **Bootstrap** : Utilisé en [version 3](https://getbootstrap.com/docs/3.4/)
- **LibreOffice** : Utilisé en version 7.x pour l'édition des documents (Les dernières versions ne sont actuellement pas compatibles)

## Installation

Cette version est destinée à co-exister avec la version actuelle de Prevarisc. Pour l'installer, suivez les étapes ci-dessous :

1. Clonez le dépôt à côté du dépôt existant :
```sh
$ git clone https://github.com/atos-df-rennes/prevarisc-migration.git
```
> [!NOTE]
> L'architecture attendue est la suivante :\
/home/prv/prevarisc\
/home/prv/prevarisc-migration

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

3. Installez les dépendances :
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

Vous devez ensuite modifier le Virtualhost pour définir ce dossier comme étant le principale avec le dossier *prevarisc/* celui de secours.\
Pour cela, configurer le VirtualHost comme suit :
```
<VirtualHost *:80>
    ServerName prevarisc.sdisxx.fr
    DocumentRoot /var/www/html/prevarisc-migration/public
    SetEnv [CLE DE CONFIGURATION] [VALEUR]
    <Directory /var/www/html/prevarisc-migration/public>
        DirectoryIndex index.php
        AllowOverride all
        Order allow,deny
        Allow from all

        <IfModule rewrite_module>
            Options +FollowSymlinks
            RewriteEngine On

            RewriteCond %{REQUEST_URI}::$1 ^(/.+)/(.*)::\2$
            RewriteRule ^(.*) - [E=BASE:%1]

            RewriteCond %{ENV:REDIRECT_STATUS} ^$
            RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]

            RewriteRule ^index\.php - [L]

            RewriteCond %{REQUEST_FILENAME} -f
            RewriteCond %{REQUEST_FILENAME} !^.+\.php$
            RewriteRule ^ - [L]

            RewriteRule ^ %{ENV:BASE}/index.php [L]
	    </IfModule>
    </Directory>
</VirtualHost>
```

## Exploitation

Consultez la [documentation dédiée](docs/exploitation.md).

## Licence

Ce projet est sous license [CeCILL-B](http://www.cecill.info/licences/Licence_CeCILL-B_V1-fr.html). Consultez le fichier [LICENSE](LICENSE.md) pour plus de détails.