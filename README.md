![Static Badge](https://img.shields.io/badge/status-not--ready-red)
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
- **JavaScript** : Utilisé avec [jQuery](https://jquery.com/)
- **Bootstrap** : Utilisé en [version 3](https://getbootstrap.com/docs/3.4/)

## Installation

Cette version est destinée à co-exister avec la version actuelle de Prevarisc. Pour l'installer, suivez les étapes ci-dessous :

1. Clonez le dépôt à côté de du dépôt existant :
```sh
$ git clone https://github.com/atos-df-rennes/prevarisc-migration.git
```
> [!NOTE]
> L'architecture attendue est la suivante :\
/home/prv/prevarisc\
/home/prv/prevarisc-migration

2. Accédez au répertoire du projet :
```sh
$ cd prevarisc-migration
```
3. Installez les dépendances :
```sh
$ composer install --prefer-source --no-dev --optimize-autoloader
```
4. Renseignez les variables d'environnement nécessaires :
```
SetEnv APP_ENV prod
```
```
SetEnv APP_SECRET <chaine_de_caractere>

(cf. pour générer un secret : http://nux.net/secret)
```
```
SetEnv DATABASE_URL mysql://<user>:<password>@<host>:<port>/<database_name>?serverVersion=5.6&charset=utf8mb4

(e.g. mysql://root:root@localhost:3306/PRV_prevarisc_v2?serverVersion=5.6&charset=utf8mb4)
```
(Optionnel, si valeur différente de l'exemple)
```
SetEnv PREVARISC_PATH /chemin/absolu/vers/Prevarisc

(e.g. /home/prv/current/prevarisc)
```
5. Jouez les migrations pour avoir un schéma de base de données à jour :
```sh
$ php bin/console doctrine:migrations:migrate
```
6. Appliquez les permissions nécessaires :
```sh
$ chown –R www-data:www-data *
$ chmod –R 555 *
$ chmod –R 755 public/
```

Vous devez ensuite modifier le Virtualhost pour définir ce dossier comme étant le principale avec le dossier *prevarisc/* celui de secours.\
Pour cela, configurer le VirtualHost comme suit :\
```
<VirtualHost *:80>
    ServerName prevarisc.sdisxx.fr
    DocumentRoot /var/www/prevarisc-migration/public
    SetEnv [CLE DE CONFIGURATION] [VALEUR]
    <Directory /var/www/prevarisc-migration/public>
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