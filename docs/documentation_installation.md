# Documentation d'installation

> [!IMPORTANT]
> Le [catalogue des variables d'environnement](#configuration-de-lapplication-30) ci-dessous décrit **Prevarisc 3.0+** (Symfony 7.4).
> Pour installer cette version, consultez le [README.md](../README.md).
> Pour migrer une installation existante **2.5, 2.8 ou 2.10 vers 3.0**, suivez le [guide de migration dédié](../MIGRATION_3.0.md).
> Les procédures Debian, Apache, base de données et cache ci-dessous restent disponibles comme référence historique **2.x** ; elles ne constituent pas une procédure d'installation 3.x.

## Table des matières

1. [Installation d'un serveur web Debian](#installation-dun-serveur-web-debian)
    1. [Installation d'un serveur Debian](#installation-dun-serveur-debian)
    2. [Installation d'un serveur Debian sur Oracle VM VirtualBox](#installation-dun-serveur-debian-sur-oracle-vm-virtualbox)
2. [Installation du serveur web](#installation-du-serveur-web)
3. [Configuration de l'application 3.0+](#configuration-de-lapplication-30)
4. [Installation de Prevarisc](#installation-de-prevarisc)
5. [Installation de la base de données](#installation-de-la-base-de-données)
6. [Connexion d’un client Firefox](#connexion-dun-client-firefox)
7. [Installation d'un cache mysql (optionnel)](#installation-dun-cache-mysql-optionnel)

- [Mise à jour de la base de données de Prevarisc](#mise-à-jour-de-la-base-de-données-de-prevarisc)

## Installation d'un serveur web Debian

Deux options s'offrent à vous :
* soit une installation définitive sur un [serveur dédié](#installation-d-un-serveur-debian)
* soit une installation de test sur un [poste de travail](#installation-d-un-serveur-debian-sur-oracle-vm-virtualbox) (ordinateur de bureau ou portable)

### Installation d'un serveur Debian

* Télécharger la version stable de Debian (classiquement version i386 avec interface xfce)
* Installer le serveur de préférence en anglais

### Installation d'un serveur Debian sur Oracle VM VirtualBox

* Télécharger Oracle VM VirtualBox et son pack d'extensions (https://www.virtualbox.org/wiki/Downloads)
* Télécharger la version stable de Debian (classiquement version i386 avec interface xfce)
* Installer Oracle VM VirtualBox et exécuter le programme
* Aller dans le menu Fichiers->Paramètres puis Extension et ajouter l’Extension Pack
* Dans Oracle VM VirtualBox, cliquer sur Nouvelle, saisir un nom de machine (ex : Debian) et cliquer plusieurs fois sur "Suivant". Votre machine apparait "Eteinte" dans la partie gauche.
* Effectuer un clic droit sur Debian, choisir Démarrer, cliquer sur le dossier avec une flèche verte, sélectionner le fichier debian-X.X.X-i386-xfce-CD-1.iso et cliquer sur "Démarrer"

## Installation du serveur web

- Ouvrir une invite de commande et exécuter la commande `su` puis entrer le mot de passe administrateur
- Installer les paquets nécessaires :
```sh
apt-get install apache2 mysql-server php7 php7-gd php7-ldap php7-mysql php-apc mysql-workbench curl git p7zip-full
```
- A la demande, faire un clic droit sur le CD en bas pour remonter l’image debian-X.X.X-i386-xfce-CD-1.iso et appuyer sur "Enter"
- Saisir le mot de passe "root" pour MySQL
- Activer les modules nécessaires :
```sh
a2enmod rewrite
a2enmod expires
a2enmod include
```

Votre serveur web est prêt à accueillir Prevarisc. Il faut maintenant configurer un VirtualHost afin de pouvoir d'une part y accéder via une URL bien définie et d'autre part pour spécifier les valeurs de configuration.

* Ouvrir le fichier de configuration Apache : `nano /etc/apache2/apache2.conf` et insérer à la fin le code suivant :
```
<VirtualHost *:80>
    ServerName prevarisc.sdisxx.fr
    DocumentRoot /var/www/prevarisc/public
    SetEnv [CLE DE CONFIGURATION] [VALEUR]
    <Directory /var/www/prevarisc/public>
        DirectoryIndex index.php
        AllowOverride all
        Order allow,deny
        Allow from all
    </Directory>
</VirtualHost>
```

Où **xx** est le numéro de département.

* Redémarrer Apache après modification du VirtualHost : `systemctl restart apache2`

## Configuration de l'application 3.0+

Ce catalogue couvre les variables référencées dans [`config/prevarisc.php`](../config/prevarisc.php),
[`config/platau.php`](../config/platau.php) et `config/packages/`, ainsi que les
variables complémentaires lues au démarrage ou directement par les services.
`config/reference.php` est une référence générée des options des bundles, pas une
déclaration de configuration de l'application.

Les **défauts ci-dessous sont ceux du code lorsque la variable est absente**.
Une valeur fournie par l'environnement ou un fichier `.env*` les remplace, même
si elle est vide. En particulier, `.env.example` peut proposer des valeurs
d'installation différentes de ces défauts (par exemple le port SMTP `25`).
`""` signifie une chaîne vide ; « non défini » signifie qu'aucun défaut n'est
déclaré dans `config/`.

Pour les variables booléennes, utiliser `0` (désactivé) ou `1` (activé).
Les entiers sont en base 10 ; les décimaux utilisent un point.
Dans un fichier `.env.local`, mettre entre guillemets les valeurs contenant des
espaces ou des caractères spéciaux, notamment `#`, et ne pas versionner les secrets.

Les paramètres Symfony peuvent être définis dans l'environnement du processus
ou dans les fichiers `.env*` décrits dans le [README](../README.md#installation).
Après modification, si la configuration a été compilée avec
`composer dump-env prod`, relancer cette commande. Une directive Apache
`SetEnv` ne configure que les requêtes web concernées, pas les commandes CLI.
Les variables signalées comme lues par **`getenv()`** doivent être disponibles
dans l'environnement PHP réel (serveur web/PHP-FPM et, si nécessaire, CLI) :
les définir uniquement dans `.env.local` ne suffit pas pour ces lectures.

### Symfony et connexion à la base de données

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `APP_ENV` | Chaîne | Non défini dans `config/` | Environnement Symfony ; définir `prod` en production, `dev` en développement, `test` pour les tests. |
| `APP_DEBUG` | Booléen | Non défini dans `config/` | Débogage Symfony ; définir `0` en production. Distinct de `PREVARISC_DEBUG_ENABLED`. |
| `APP_SECRET` | Chaîne secrète | Non défini | Secret Symfony à générer pour chaque installation ; voir le README. Ne pas le confondre avec le sel des anciens mots de passe. |
| `DATABASE_URL` | Chaîne (DSN Doctrine) | Non défini | **Connexion réellement utilisée par Doctrine** : `mysql://<utilisateur>:<mot-de-passe>@<hôte>:3306/<base>?serverVersion=11.8.8-MariaDB&charset=utf8mb4`. Adapter la version au serveur et encoder les caractères réservés des identifiants dans l'URL. |
| `TRUSTED_PROXIES` | Chaîne, adresses séparées par `,` | Aucune | Lu dans `public/index.php` : adresses des reverse proxies de confiance. Ne renseigner que les proxies maîtrisés. |
| `TRUSTED_HOSTS` | Chaîne (expression régulière) | Aucune | Lu dans `public/index.php` : motif des hôtes HTTP autorisés, par exemple `^prevarisc\.sdisxx\.fr$`. Un seul motif, pas une liste séparée par des virgules. |
| `VAR_DUMPER_SERVER` | Chaîne `hôte:port` | Non défini | Uniquement en `dev` : destination du serveur de dumps, préfixée par `tcp://` dans `config/packages/dev/debug.php`, par exemple `127.0.0.1:9912`. |

### Sécurité et diagnostic applicatif

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `PREVARISC_DEBUG_ENABLED` | Booléen | `0` | Active les diagnostics applicatifs, notamment les journaux de génération des rapports. N'active pas le débogage Symfony. |
| `PREVARISC_SECURITY_SALT` | Chaîne | `""` | Sel global utilisé pour vérifier les anciens mots de passe MD5 avant leur migration vers le hachage moderne. **Conserver la valeur de l'installation 2.x** ; il n'y a plus de sel prédéfini ni de contrainte de longueur de 32 caractères dans cette configuration. |
| `PREVARISC_ENFORCE_SECURITY` | Booléen | `0` | Active les contrôles renforcés sur les mots de passe locaux : au moins 8 caractères, avec minuscule, majuscule, chiffre et caractère spécial. |
| `PREVARISC_SECURITY_KEY` | Chaîne secrète | `""` | Clé d'accès à la synchronisation iCalendar. Vide : accès refusé. Également lue par **`getenv()`** pour produire et contrôler les liens de synchronisation. |

### Courriels

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `PREVARISC_MAIL_ENABLED` | Booléen | `0` | Active l'envoi des courriels ; sinon le transport est `null://null`. |
| `PREVARISC_MAIL_TRANSPORT` | Chaîne | `smtp` | Valeurs reconnues, sensibles à la casse : `smtp`, `sendmail` ou `mail` (ces deux dernières utilisent `sendmail://default`). Toute autre valeur désactive le transport. Ne pas utiliser `Sendmail`. |
| `PREVARISC_MAIL_HOST` | Chaîne | `localhost` | Adresse du serveur SMTP. |
| `PREVARISC_MAIL_PORT` | Chaîne | `""` | Port SMTP sous forme de chaîne, par exemple `"25"` ou `"587"`. Vide : le port n'est pas ajouté au DSN, le transport Symfony choisit son défaut. |
| `PREVARISC_MAIL_USERNAME` | Chaîne | `""` | Identifiant SMTP ; l'authentification n'est ajoutée que si l'identifiant **et** le mot de passe sont non vides. |
| `PREVARISC_MAIL_PASSWORD` | Chaîne secrète | `""` | Mot de passe SMTP. |
| `PREVARISC_MAIL_SENDER` | Chaîne (adresse email) | `noreply@prevarisc.local` | Adresse de l'expéditeur, à adapter à l'installation. |
| `PREVARISC_MAIL_SENDER_NAME` | Chaîne | `""` | Nom de l'expéditeur. |
| `MAILER_DSN` | Chaîne (ignorée) | Sans objet | Le nom est référencé dans `config/packages/mailer.php`, mais le processeur personnalisé ignore sa valeur et construit toujours le DSN depuis les variables `PREVARISC_MAIL_*`. Ne permet pas de les surcharger, même lorsque les courriels sont désactivés. |

### Authentification LDAP et NTLM

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `PREVARISC_LDAP_ENABLED` | Booléen | `0` | Active l'authentification LDAP. |
| `PREVARISC_LDAP_HOST` | Chaîne | `""` | Adresse IP ou nom du serveur LDAP, sans préfixe `ldap://`. |
| `PREVARISC_LDAP_PORT` | Entier | `0` | Port du serveur LDAP. `0` ou variable absente : `389` en LDAP, `636` en LDAPS. Un port non nul est utilisé tel quel. |
| `PREVARISC_LDAP_ENCRYPTION` | Chaîne | `none` | `none` : LDAP en clair ; `ssl` : LDAPS, chiffré dès la connexion. StartTLS n'est pas pris en charge. |
| `PREVARISC_LDAP_CA_CERT_FILE` | Chemin | `""` | Fichier de CA à utiliser pour LDAPS, par exemple `/etc/ssl/certs/ldap-ca.crt`. Vide : configuration LDAP du système. Ignoré en LDAP en clair. |
| `PREVARISC_LDAP_USERNAME` | Chaîne | `""` | DN ou UPN du compte de service utilisé pour rechercher le DN de l'utilisateur. Pour Active Directory : `svc-prevarisc@sdisxx.fr` ou `CN=Svc Prevarisc,CN=Users,DC=sdisxx,DC=fr`. Vide : bind direct. |
| `PREVARISC_LDAP_PASSWORD` | Chaîne secrète | `""` | Mot de passe du compte de service LDAP. |
| `PREVARISC_LDAP_BASEDN` | Chaîne | `""` | Base de recherche, par exemple `DC=sdisxx,DC=fr`. |
| `PREVARISC_LDAP_FILTER` | Chaîne | `(sAMAccountName=%s)` | Filtre de recherche ; `%s` représente le login. Pour OpenLDAP : `(uid=%s)`. |
| `PREVARISC_LDAP_ACCOUNT_FORM` | Chaîne | `""` | Format `sprintf` pour le bind direct, notamment en repli de la recherche : `%s@sdisxx.fr` ou `DOMAIN\%s`. Vide : login utilisé tel quel. |
| `PREVARISC_NTLM_ENABLED` | Booléen | `0` | Active l'authentification à partir de `REMOTE_USER`, fourni par le serveur web. |

Pour utiliser LDAPS, renseigner `PREVARISC_LDAP_ENCRYPTION=ssl` et, si nécessaire,
le port LDAPS dans `PREVARISC_LDAP_PORT`. Le nom `PREVARISC_LDAP_HOST` doit
correspondre au certificat du serveur. L'autorité de certification qui l'a signé
doit être approuvée par la bibliothèque LDAP du serveur applicatif. Pour un fichier
de CA spécifique, renseigner `PREVARISC_LDAP_CA_CERT_FILE` avec son chemin absolu.
Sinon, utiliser la configuration système : par exemple, configurer
`TLS_CACERT /chemin/vers/ca.pem` dans `/etc/ldap/ldap.conf` ou installer la CA
dans le magasin système utilisé par cette bibliothèque.
Le fichier doit être lisible par le processus PHP ; en conteneur, il doit être
disponible et configuré **dans le conteneur applicatif**. La validation du
certificat est obligatoire et une connexion LDAPS en échec ne revient pas en LDAP
en clair. Ne pas désactiver la vérification des certificats.

Après modification de la configuration, si `.env.local.php` est utilisé,
le régénérer avec `composer dump-env prod`, puis vider le cache Symfony avec
`php bin/console cache:clear --env=prod` et recharger le processus web.
En cas d'échec de connexion,
vérifier le port, le nom du serveur, la chaîne de certificats et la CA configurée
pour PHP, puis consulter le journal d'authentification.

### Authentification CAS

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `PREVARISC_CAS_ENABLED` | Booléen | `0` | Active l'authentification CAS. |
| `PREVARISC_CAS_HOST` | Chaîne | `""` | Nom du serveur CAS joignable par Prevarisc, sans protocole, par exemple `cas.sdisxx.fr`. |
| `PREVARISC_CAS_PORT` | Entier | `0` | Port CAS **à renseigner si CAS est activé**, généralement `443`. Le code ne fournit pas `443` par défaut. |
| `PREVARISC_CAS_PUBLIC_HOST` | Chaîne | `""` | Nom CAS accessible au navigateur s'il diffère du nom interne. Vide : pas de réécriture du nom interne. |
| `PREVARISC_CAS_PUBLIC_PORT` | Entier | `0` | Port public utilisé avec le nom public ; si `0`, repli sur `PREVARISC_CAS_PORT`. |
| `PREVARISC_CAS_CONTEXT` | Chaîne | `""` | Chemin du service CAS, par exemple `cas`. |
| `PREVARISC_CAS_VERSION` | Chaîne | `""` | Version du protocole (`1.0`, `2.0`, `3.0`). Vide : l'authentificateur utilise CAS `2.0`. |
| `PREVARISC_CAS_NO_SERVER_VALIDATION` | Booléen | `0` (chaîne vide en configuration) | Désactive la vérification du certificat du serveur CAS. À laisser désactivé en production. |
| `PREVARISC_CAS_NO_SSL` | Booléen | `0` (chaîne vide en configuration) | Force les URL CAS en HTTP ; réservé aux environnements de test sans TLS. |

### Proxy sortant

Ces variables configurent les appels cURL utilisant le service de proxy, notamment
le healthcheck Plat'AU ; il ne s'agit pas de la configuration du reverse proxy entrant.

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `PREVARISC_PROXY_ENABLED` | Booléen | `0` | Active le proxy sortant. |
| `PREVARISC_PROXY_PROTOCOL` | Chaîne convertie en entier | `"0"` | Valeur numérique du type de proxy cURL, par exemple `0` (HTTP), `5` (SOCKS5). L'ancienne chaîne `HTTP` est encore tolérée car convertie en `0` ; `HTTPS` est également convertie en `0` et ne sélectionne donc pas un proxy HTTPS. |
| `PREVARISC_PROXY_HOST` | Chaîne | `""` | Nom ou adresse IP du proxy. |
| `PREVARISC_PROXY_PORT` | Entier | `0` | Port du proxy, à adapter, par exemple `8080`. |
| `PREVARISC_PROXY_USERNAME` | Chaîne | `""` | Identifiant du proxy ; vide ou `"0"` : aucune authentification. |
| `PREVARISC_PROXY_PASSWORD` | Chaîne secrète | `""` | Mot de passe du proxy. |

### Plat'AU

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `PREVARISC_DEACTIVATE_PLATAU` | Booléen | `0` | `1` masque les affichages liés à Plat'AU dans Prevarisc ; ne désactive pas la passerelle externe. |
| `PREVARISC_PLATAU_NO_HEALTHCHECK` | Booléen | `0` | `1` masque le healthcheck de la page d'accueil sans masquer les autres affichages Plat'AU. |
| `PLATAU_CONFIG_FILE` | Chaîne (nom ou chemin relatif) | `config.json` | Fichier JSON de configuration de la passerelle, **relatif à `PREVARISC_PLATAU_PATH`**. Utilisé par le healthcheck pour lire `PISTE_CLIENT_ID` et `PISTE_CLIENT_SECRET` dans `platau.options`. Ne pas fournir un chemin absolu. |
| `PREVARISC_PLATAU_PATH` | Chaîne (chemin de répertoire) | `<répertoire parent de Prevarisc>/prevarisc-passerelle-platau` | Lu par **`getenv()`** dans le service de healthcheck, hors de `config/`. Répertoire de la passerelle contenant le fichier JSON et `src/Service/PlatauAbstract.php`. Absente ou vide : chemin par défaut. |

Pour une passerelle installée dans `/opt/prevarisc-passerelle-platau` dont le
fichier s'appelle `config-sdis.json`, la configuration Apache correspondante est :

```apache
SetEnv PREVARISC_PLATAU_PATH "/opt/prevarisc-passerelle-platau"
SetEnv PLATAU_CONFIG_FILE "config-sdis.json"
```

Le fichier recherché est alors
`/opt/prevarisc-passerelle-platau/config-sdis.json`, pas `config.json`.
Il doit être lisible par l'utilisateur PHP et rester hors du répertoire public
car il contient des identifiants secrets. Une erreur de lecture de ce fichier
est une erreur de configuration locale, pas une indication de maintenance Plat'AU.

### Pièces jointes et stockage

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `PREVARISC_DATASTORE_ADAPTER` | Chaîne | `Plugin_SimpleFileDataStore` | `Plugin_HashedFileDataStore` sélectionne le stockage hashé ; toute autre valeur sélectionne le stockage simple. Il ne s'agit plus du chargement d'une classe arbitraire implémentant une interface Zend. |
| `PREVARISC_DATASTORE_FILE_FORMAT` | Chaîne | `%NOM_PIECEJOINTE%%EXTENSION_PIECEJOINTE%` | Format du nom de téléchargement, pour les deux adaptateurs. Explicitement vide : erreur ; supprimer la variable pour revenir au défaut. Exemple : `%NUMEROID_ETABLISSEMENT%_%DATE_PIECEJOINTE%_%NOM_PIECEJOINTE%_%ID_PIECEJOINTE%%EXTENSION_PIECEJOINTE%`. Date d'ajout au format `Y-m-d`. Voir [Configuration du DataStore](tech/datastore_configuration.md). |
| `PREVARISC_REAL_DATA_PATH` | Chaîne (chemin physique) | `<racine de Prevarisc>/public/data` | Répertoire de stockage des fichiers. Les paramètres Symfony tels que `%kernel.project_dir%` sont résolus. Doit être accessible en lecture/écriture selon les usages de l'application. |
| `PREVARISC_DATA_PATH` | Chaîne (préfixe d'URL) | `/data` | Préfixe web d'accès aux médias et pièces jointes ; ce n'est pas un chemin physique. |

### Cartographie et personnalisations

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `PREVARISC_PLUGIN_IGNKEY` | Chaîne | `""` | Clés/thématiques IGN séparées par des virgules, par exemple `essentiels,adresse,administratif,cartes,cartovecto`. |
| `PREVARISC_IGN_AUTOCONF` | Chaîne (chemin web) | `""` | Chemin sous `public/` du fichier d'autoconfiguration IGN, par exemple `/ign/prevarisc/customConfig.json` ; voir les précisions ci-dessous. |
| `PREVARISC_CARTO_DEFAULT_LON` | Décimal | `2.71490430425517` | Longitude du centre cartographique par défaut. |
| `PREVARISC_CARTO_DEFAULT_LAT` | Décimal | `50.4727273438818` | Latitude du centre cartographique par défaut. |
| `PREVARISC_PLUGIN_GOOGLEMAPKEY` | Chaîne | `""` | Clé Google Maps. |
| `PREVARISC_PLUGIN_GEOCONCEPT_URL` | Chaîne | `""` | URL du serveur Geoconcept WMTS. |
| `PREVARISC_PLUGIN_GEOCONCEPT_LAYER` | Chaîne | `""` | Nom de la couche WMTS de base. |
| `PREVARISC_PLUGIN_GEOCONCEPT_APP_ID` | Chaîne | `""` | Identifiant de l'application pour les couches authentifiées. |
| `PREVARISC_PLUGIN_GEOCONCEPT_TOKEN` | Chaîne secrète | `""` | Jeton pour les couches authentifiées. |
| `PREVARISC_PLUGIN_GEOCONCEPT_PROJECTION` | Chaîne | `""` | Projection des couches Geoconcept. |
| `PREVARISC_PLUGIN_GEOCONCEPT_GEOCODER` | Chaîne | `""` | URL du géocodeur Geoconcept. |
| `PREVARISC_PLUGIN_GEOBRETAGNE` | Chaîne | `""` | URL du visualiseur cartographique Géobretagne (`sviewer`), utilisée par les personnalisations SDIS29. |
| `PREVARISC_THIRDPARTY_PLUGINS` | Chaîne, noms séparés par `;` | `""` | Active les portages Symfony reconnus : `Plugin_SDIS29`, `Plugin_FacteurCriticite`, `Plugin_LibelleDossierObservations`, `Plugin_LibelleDossierPrescriptions`. Exemple : `Plugin_SDIS29;Plugin_FacteurCriticite`. Ne charge plus de classes Zend arbitraires. |

`PREVARISC_IGN_AUTOCONF` peut être définie dans le VirtualHost Apache, par exemple
`SetEnv PREVARISC_IGN_AUTOCONF "/ign/prevarisc/customConfig.json"`.
Le fichier doit se trouver sous le dossier `public/` de l'application Symfony
(`DocumentRoot`). Le navigateur reçoit le chemin web du fichier, jamais son
chemin physique.
Si la variable est absente ou vide, aucune autoconfiguration locale n'est utilisée.
Si le fichier configuré est introuvable, un avertissement est journalisé et la
carte utilise le service IGN en ligne.

### Règles métier et tableau de bord

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `PREVARISC_COMMISSION_COMMUNALE_TYPE` | Entier | `2` | Identifiant du type de commission communale. |
| `PREVARISC_LOCAL_SOMMEIL_TYPES` | Chaîne, identifiants séparés par `;` | `7;11` | Types d'activité utilisés pour suggérer le classement en local à sommeil. |
| `PREVARISC_DESCRIPTIF_PERSONNALISE` | Booléen | `0` | Active le descriptif personnalisé de l'établissement au lieu du descriptif fixe. |
| `PREVARISC_RISQUES_NATURELS_EXEMPLES` | Chaîne | `""` | Exemples affichés dans l'aide à la saisie des risques naturels du descriptif fixe. Vide : aucune aide affichée pour ce champ. |
| `PREVARISC_RISQUES_TECHNOLOGIQUES_EXEMPLES` | Chaîne | `""` | Exemples affichés dans l'aide à la saisie des risques technologiques du descriptif fixe. Vide : aucune aide affichée pour ce champ. |
| `PREVARISC_UNITE_PERIODICITE_ANNEES` | Booléen | `0` | Affiche la périodicité en années sur la fiche récapitulative de l'établissement. |
| `PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE` | Booléen | `0` | Utilise la date de commission pour relancer la périodicité lorsque le dernier dossier donnant avis est un groupe de visite. Également lue par **`getenv()`** dans les calculs de périodicité et les rapports. |
| `PREVARISC_DOSSIERS_MAX_A_AFFICHER` | Entier | `5` | Nombre de dossiers du même type affichés dans la liste des dossiers d'un établissement. |
| `PREVARISC_DASHBOARD_NEXT_COMMISSIONS_DAYS` | Entier (jours) | `15` | Horizon des prochaines commissions dans le tableau de bord. |
| `PREVARISC_DASHBOARD_DOSSIERS_SANS_AVIS_DAYS` | Entier (jours) | `15` | Délai utilisé pour identifier les dossiers sans avis de commission après la date de commission. |
| `PREVARISC_DASHBOARD_COURRIER_SANS_REPONSE_DAYS` | Entier (jours) | `10` | Délai utilisé pour identifier les courriers sans réponse. |

### Calendriers et version affichée (hors de `config/`)

Ces variables sont toujours utilisées, mais lues directement par **`getenv()`**.

| Variable | Type | Défaut | Description |
| --- | --- | --- | --- |
| `PREVARISC_CALENDAR_REFRESH_TIME` | Chaîne (durée ISO 8601) | `PT5M` | Intervalle de rafraîchissement annoncé dans les flux iCalendar (5 minutes). Absente ou vide : défaut. |
| `PREVARISC_BRANCH` | Chaîne | Absente : version déterminée depuis Git | Si définie, sélectionne la version fournie par l'environnement, construite comme `v<PREVARISC_BRANCH>.<PREVARISC_REVISION>`. Exemple : `3.0` avec une révision `0` donne `v3.0.0`. Ce n'est pas une commande de changement de branche. |
| `PREVARISC_REVISION` | Chaîne | Aucun repli propre | Complément de version, utilisé uniquement si `PREVARISC_BRANCH` est définie. Renseigner les deux variables ensemble ; ce n'est pas un hash de commit. |

### Paramètres conservés pour compatibilité

Ces variables restent déclarées dans `config/prevarisc.php`, mais ne pilotent
plus les composants Symfony correspondants. Ne pas les utiliser à la place des
réglages actifs décrits ci-dessus.

| Variable | Type | Défaut | Usage actuel |
| --- | --- | --- | --- |
| `PREVARISC_DB_ADAPTER` | Chaîne | `mysql` | Paramètre conservé ; ne sélectionne pas le pilote Doctrine. Les anciens noms `Pdo_Mysql`, etc. ne configurent pas la connexion Symfony. |
| `PREVARISC_DB_CHARSET` | Chaîne | `utf8` | Paramètre conservé ; l'encodage réel est défini dans `DATABASE_URL`. |
| `PREVARISC_DB_HOST` | Chaîne | `localhost` | Information affichée dans l'administration, pas l'hôte utilisé par Doctrine. |
| `PREVARISC_DB_PORT` | Entier | `3306` | Information affichée dans l'administration, pas le port utilisé par Doctrine. |
| `PREVARISC_DB_DBNAME` | Chaîne | `PRV_prevarisc_v2` | Nom affiché dans l'administration, pas la base utilisée par Doctrine. |
| `PREVARISC_DB_USERNAME` | Chaîne | `""` | Paramètre conservé ; l'utilisateur réel est défini dans `DATABASE_URL`. |
| `PREVARISC_DB_PASSWORD` | Chaîne secrète | `""` | Paramètre conservé ; le mot de passe réel est défini dans `DATABASE_URL`. |
| `PREVARISC_CACHE_LIFETIME` | Entier (secondes) | `3600` | Alimente l'état et la durée affichés dans l'administration ; ne règle pas la durée des caches Symfony. |
| `PREVARISC_CACHE_ADAPTER` | Chaîne | `File` | Adaptateur affiché dans l'administration ; le défaut n'est plus `APC`. Ne sélectionne pas l'adaptateur Symfony. |
| `PREVARISC_CACHE_HOST` | Chaîne | `""` | Hôte du cache affiché dans l'administration. |
| `PREVARISC_CACHE_PORT` | Entier | `0` | Port du cache affiché dans l'administration. |
| `PREVARISC_CACHE_DIR` | Chaîne | `""` | Paramètre conservé, non raccordé aux pools de cache Symfony. |
| `PREVARISC_SESSION_MAX_LIFETIME` | Entier (secondes) | `3600` | Paramètre déclaré, non raccordé à la configuration de session Symfony. Ne modifie pas à lui seul la durée des sessions ; la session native utilise la configuration PHP. |

Les pools `cache.ign_capabilities` et `cache.search` utilisent
`cache.adapter.filesystem` dans `config/packages/cache.php`, indépendamment des
variables `PREVARISC_CACHE_*`. La procédure MySQL/Memcached historique ci-dessous
ne s'applique donc pas aux caches Symfony.

### Anciennes variables retirées du catalogue actif

`PREVARISC_APPLICATION_PATH`, `PREVARISC_COMMAND_PATH` et
`PREVARISC_PROXY_REQUEST_FULLURI` ne sont ni déclarées dans `config/` ni utilisées
par le code Symfony actuel. Elles ne configurent plus l'application 3.x.
La racine est déterminée par le noyau Symfony ; les commandes décrivent leurs
propres arguments/options et le proxy sortant utilise cURL.

## Installation de Prevarisc

* Depuis le navigateur Iceweasel, télécharger le fichier compressé de prevarisc depuis "https://github.com/atos-df-rennes/prevarisc-migration" dans votre dossier "Downloads"
* Taper : ```mv /home/$USER/Downloads/prevarisc-master.zip /var/www/``` ou $USER est votre nom d’utilisateur de la session
* Taper : ```7z x prevarisc-master.zip```
* Taper : ```mv prevarisc-master prevarisc```
* Taper : ```chmod 755 prevarisc```
* Taper : ```cd prevarisc```
* Taper : ```curl https://getcomposer.org/installer | php```
* Taper : ```php composer.phar install --prefer-source```
* Taper : ```chown –R www-data:www-data *```
* Taper : ```chmod –R 555 *```
* Taper : ```chmod –R 755 public/```

## Installation de la base de données

* Ouvrir MySQL WorkBench dans le menu Development
* Cliquer sur New Server Instance, cliquer sur Next, saisir le mot de passe "root", choisir MySQL installation type "Debian", Continue, Finish
* Cliquer sur Manage Import/Export puis sur Data Import/Restore
* Mot de passe : "root"
* Choisir Import from Self-Contained file et sélectionner le fichier prevarisc.sql dans le dossier prevarisc/docs/db
* Cliquer sur "Start Import" et fermer l’onglet quand l’opération est terminée
* Fermer MySQL Workbench

## Connexion d’un client Firefox

* Ouvrir le gestionnaire de machine Oracle VM VirtualBox
* Faire un clic droit sur Debian et choisir Configuration …
* Cliquer sur Réseau et choisir un mode d’accès réseau "Accès par pont"
* Ouvrir une invite de commande sur Debian, taper : ```su``` et le mot de passe, puis ```ifconfig``` et noter l’adresse IP eth0 (inet addr)
* Fermer l’invite de commande
* Sur le poste Windows, ouvrir le fichier C:\windows\system32\drivers\etc\hosts et ajouter la ligne avec l’adresse IP du serveur Debian suivi de "prevarisc.sdis??.fr" (ou sinon, faire un enregistrement DNS)
* Ouvrir Firefox et saisir http://prevarisc.sdis??.fr
* A ce point vous devez être capable d'accéder à Prevarisc ! Le premier compte utilisateur est ```root```, mot de passe ```root``` (à désactiver le plus rapidement possible pour des raisons de sécurité).


## Installation d'un cache mysql (optionnel)

> Nécessite une version de PHP > 5.4

* download du module php memcache
* Taper : ```tar -xvzf memcache-2.2.7.tgz```
* Taper : ```cd memcache-2.2.7```
* Taper : ```phpize```
* Taper : ```./configure```
* Taper : ```make```
* Taper : ```sudo make install```
* Vérifier que memcache est bien installé ```php -m | grep memcache``` sinon ajout de : ```extension=memcache.so``` dans le php.ini
* Jouer le sql d'activation du cache, taper : ```mysql -u root -p prevarisc < sql/init/enable_mysql_cache.sql```
* Taper : ```sudo /etc/init.d/mysql restart```
* Modifier le vhost apache pour modifier les variables PREVARISC_CACHE_ADAPTER en "Cache_MySQLMemcached" voir PREVARISC_CACHE_HOST si le backend est installé sur une machine distante.
* Taper : ```sudo /etc/init.d/httpd restart```

## Mise à jour de la base de données de Prevarisc

Depuis un PC windows, installer le logiciel [MySQL WorkBench](http://www.mysql.fr/products/workbench/).
Vous pouvez maintenant ouvrir le fichier "docs/MCD Prevarisc avec Mysql Workbench.mwb".

> Pourquoi utiliser ce logiciel ?
> Cela nous permettra de simplifier les mises à jour de la base via une simple synchronisation.

Ce fichier représente le MCD complet de Prevarisc. Pour synchoniser votre serveur de base de données avec ce MCD, vous devez :
* Menu Database > Synchronize model
* Configurer la connexion vers votre base de données
* Selectionner "prevarisc" et "next" jusqu'a l'intallation de la base de données

Pour ajouter les valeurs par défauts :
* Menu Database > Forward engineer
* Configurer la connexion vers votre base de données
* Cocher "Generate INSERT statements for tables"
* "next" jusqu'a l'insertion des données
