# Migrer Prevarisc 2.5, 2.8 ou 2.10 vers 3.0

Ce guide s'adresse aux SDIS qui installent et mettent à jour eux-mêmes
Prevarisc depuis le dépôt Git. Il décrit les étapes nécessaires pour migrer
leur installation existante vers Prevarisc 3.0 avec le dépôt
[`atos-df-rennes/prevarisc-migration`](https://github.com/atos-df-rennes/prevarisc-migration).
Il ne nécessite aucun script de déploiement extérieur à ce dépôt.

> [!WARNING]
> N'utilisez en production qu'un **tag stable** (par exemple `v3.0.0` sans
> suffixe). Les tags `-rc.N` et les branches `release/*` sont réservés aux
> environnements de test et de recette. Consultez les
> [versions et tags publiés](README.md#versions) avant de commencer.

La procédure cible **Debian 12**, **PHP 8.5**, **MariaDB 11.8.8** et Apache
HTTP Server 2.4. La mise à niveau de l'OS n'est pas
détaillée ici : consultez la documentation Debian et assurez-vous que
l'installation est à jour avant la migration.

Lisez le guide en entier avant l'intervention. Les sections 3 à 8 décrivent les
changements à préparer ; la section 6.5 donne l'ordre de bascule en production.
Les commandes système nécessitent un compte administrateur. Exécutez Git,
Composer et les commandes Symfony avec le compte de déploiement, depuis le
répertoire indiqué, et arrêtez la procédure si une commande échoue.

## 1. Choisir la procédure correspondant à votre version

Vérifiez la version installée à partir de la version du dépôt et de son
arborescence. Ne vous fiez pas uniquement au nom du répertoire :

```sh
cd /chemin/vers/votre/prevarisc
git describe --tags --always
git remote -v
git status --short
ls
```

| Départ | Architecture et versions usuelles | Particularités |
| --- | --- | --- |
| **2.5** | Application Zend seule ; PHP 7.1.33 ; MySQL 5.6.28 ; Apache 2.4 (version à relever) | Pas d'application Symfony ni de `.env` Symfony. Des installations peuvent provenir de `SDIS62/prevarisc` ou du fork `atos-df-rennes/prevarisc`. |
| **2.8** | Zend et Symfony 4.4 côte à côte ; PHP 7.1.33 ; MySQL 5.6.51 ; Apache 2.4.59 | La configuration Symfony se trouve généralement dans `prevarisc-migration/.env`. |
| **2.10** | Zend et Symfony 4.4 côte à côte ; PHP 7.1.33 ; MySQL 5.6.51 ; Apache 2.4.59 | Même socle technique que 2.8 ; la table des versions de migrations Doctrine existe normalement déjà. |
| **3.0** | Symfony 7.4 ; PHP 8.5 ; MariaDB 11.8 ; Apache 2.4 | Symfony est la seule application ; le nom du répertoire reste libre (`prevarisc-migration/`, `prevarisc/`, etc.). |

Symfony **4.4 est bien la version attendue en 2.8 et en 2.10** : ces branches
fonctionnent avec PHP 7.1.33. La migration vers PHP 8.5 et Symfony 7.4 a lieu
au passage en 3.0.

Le renommage de `prevarisc-migration/` en `prevarisc/` est **optionnel** :
il permet de retirer un suffixe temporaire, mais n'est pas nécessaire au
fonctionnement de 3.0. Un clonage sans nom de destination conserve le nom
`prevarisc-migration/`. Les noms de répertoires de ce guide sont des exemples :
adaptez tous les chemins à votre installation, notamment dans Apache, les
variables d'environnement, les tâches planifiées et les intégrations.

> [!NOTE]
> Les versions ci-dessus sont les versions usuelles des branches. Une
> installation ancienne ou personnalisée peut différer : relevez les versions
> réellement installées (`php -v`, `mysql --version` ou `mariadb --version`,
> `apache2 -v`) et notez les modifications locales avant de poursuivre.

## 2. Préparer et sécuriser l'intervention

Effectuez d'abord la migration sur une copie ou un serveur de recette. Prévoyez
une fenêtre de maintenance : l'application doit être arrêtée pendant la
sauvegarde finale, la bascule du moteur de base de données et l'exécution des
migrations Doctrine.

Sur la copie de recette, utilisez une base et des fichiers distincts de la
production. Désactivez les envois de mails et les échanges Plat'AU réels afin
que les essais ne modifient pas les données ou les services de production.

### 2.1 Sauvegardes indispensables

Avant toute modification :

- sauvegardez la base et vérifiez que le fichier de dump est lisible et complet ;
- conservez une copie de l'application actuellement en service et relevez le
  commit ou le tag installé ;
- sauvegardez la configuration Apache, notamment les lignes `SetEnv`, les
  VirtualHosts, les alias et les règles d'accès ;
- sauvegardez le `php.ini` et la configuration MySQL/MariaDB ;
- sauvegardez les fichiers `.env` présents, y compris ceux des environnements
  additionnels et les surcharges `.env.local` / `.env.*.local` ;
- sauvegardez les données applicatives hors dépôt : pièces jointes, documents,
  exports et fichiers de stockage ;
- sauvegardez la configuration et les tâches planifiées de la passerelle
  Plat'AU, ainsi que les fichiers d'autoconfiguration IGN ou autres intégrations.

Exemple de dump d'une base MySQL 5.6 (à adapter au nom de la base et au compte
administrateur) :

```sh
mysqldump -u root -p \
  --routines --triggers --events --default-character-set=utf8 \
  --databases PRV_prevarisc_v2 \
  > /chemin/backup/prevarisc-mysql.sql
```

Ne vous contentez pas d'une sauvegarde du seul répertoire `/var/lib/mysql` :
elle ne constitue pas une méthode de transfert de MySQL 5.6 vers MariaDB 11.8.
Conservez l'ancienne installation et ses fichiers tant que la nouvelle version
n'a pas été validée.

### 2.2 Préparer Debian 12

La cible est Debian 12 à jour. Vérifiez également l'espace disque disponible,
l'accès aux dépôts de paquets nécessaires, les sauvegardes, l'heure système et
la résolution DNS. Si le serveur actuel utilise une ancienne version Debian,
traitez sa mise à niveau séparément et conformément à la documentation Debian ;
ne combinez pas une mise à niveau majeure de l'OS et de Prevarisc sans une
recette préalable.

## 3. Mettre à niveau PHP vers 8.5

La version de référence est **PHP 8.5.12** ; privilégiez le dernier correctif
de sécurité disponible de la branche 8.5. Prevarisc 3.0
utilise PHP chargé dans Apache par **mod_php**. Les versions d'Apache et PHP
doivent donc être configurées ensemble ; `mod_php` nécessite le MPM prefork,
pas `mpm_event`.

### 3.1 Installation par paquets (recommandée)

Debian 12 ne fournit pas PHP 8.5 dans ses dépôts standard. Utilisez un dépôt
tiers de paquets PHP de confiance, par exemple **packages.sury.org**, en suivant
sa documentation Debian officielle pour configurer la clé et le dépôt. N'ajoutez
pas une clé de dépôt sans vérifier sa provenance.

Installez PHP 8.5 et les extensions utilisées par l'application :

```sh
apt install \
  libapache2-mod-php8.5 php8.5-cli php8.5-common php8.5-opcache \
  php8.5-curl php8.5-gd php8.5-intl php8.5-ldap php8.5-mbstring \
  php8.5-mysql php8.5-xml php8.5-zip
```

Les paquets `php8.5-common` et `php8.5-mysql` fournissent notamment les
extensions standard et MySQLi/PDO MySQL. Vérifiez la disponibilité des paquets
dans le dépôt configuré, ainsi que les besoins propres à vos intégrations
(par exemple SOAP).

Activez le MPM et le module PHP correspondant aux paquets installés. L'exemple
ci-dessous suppose que le paquet a installé le module `php8.5`. Au moment de
la bascule, désactivez d'abord l'ancien module PHP s'il est encore activé
(par exemple `a2dismod php7.1` pour un module installé sous ce nom) ; ne chargez
pas simultanément les deux versions :

```sh
a2dismod mpm_event
a2enmod mpm_prefork
a2enmod php8.5
apache2ctl configtest
```

Ne redémarrez pas Apache avant d'avoir vérifié la configuration et la présence
des modules ; planifiez la coupure de service au moment de la bascule.

### 3.2 Compilation depuis les sources (alternative)

Vous pouvez compiler PHP avec le module Apache via `apxs2` plutôt qu'utiliser
les paquets. Cette approche implique de maintenir
les dépendances de compilation, le module Apache, le `php.ini` et son
`extension_dir`. La configuration de compilation doit couvrir au minimum les
extensions nécessaires : PDO MySQL, GD avec JPEG, EXIF, cURL, LDAP, Intl,
mbstring, XML, ZIP et OpenSSL. Les options précises dépendent des sources PHP
et des bibliothèques de développement installées.

Après compilation, vérifiez que le PHP en ligne de commande et celui chargé par
Apache sont tous deux en version 8.5 et chargent les extensions attendues.

### 3.3 Examiner l'ancien `php.ini`

Ne recopiez pas aveuglément le fichier PHP 7.1. Reprenez les réglages
nécessaires à votre exploitation (fuseau horaire, limites d'upload, mémoire,
sessions, journaux et OPcache), et vérifiez chaque directive avec PHP 8.5.

Parmi les directives ou sections anciennes à retirer si elles sont présentes :

| Directive/section | Action |
| --- | --- |
| `asp_tags`, `track_errors` | Retirer ; directives obsolètes ou supprimées. |
| `sql.safe_mode` et section `[SQL]` | Retirer. |
| `ibase.*` et section `[Interbase]` | Retirer si présents ; l'extension n'est plus fournie avec PHP. |
| `session.bug_compat_42`, `session.bug_compat_warn`, `session.hash_function`, `session.hash_bits_per_character` | Retirer. |
| `mysqli.reconnect` | Retirer. |
| `E_STRICT` dans `error_reporting` | Retirer de la valeur configurée. |
| Chargement explicite d'une extension OPcache (`zend_extension=...`) | Retirer si OPcache est intégré/chargé par le paquet ; conserver ses directives `opcache.*` si nécessaires. |

Les extensions à contrôler comprennent `curl`, `exif`, `fileinfo`, `gd`,
`intl`, `ldap`, `mbstring`, `mysqli`, `PDO`, `pdo_mysql`, `xml`, `zip`,
`sodium` et `zlib`. Certains modules, comme `fileinfo` ou `sodium`, sont inclus
dans les paquets communs de PHP et ne correspondent pas nécessairement à un
paquet séparé.

Les extensions `intl`, `sodium` et `zip` doivent notamment être présentes dans
la configuration 3.0. Avec les paquets Debian, les fichiers de configuration
des extensions sont normalement fournis et activés par les paquets : vérifiez
leur chargement plutôt que d'ajouter une seconde directive manuellement. Si
vous compilez PHP et que ces extensions sont produites en modules partagés,
ajoutez les directives `extension=intl`, `extension=sodium` et `extension=zip`
dans le fichier PHP chargé, selon le nom réel des modules compilés.

Vérifiez le PHP CLI :

```sh
php -v
php -m
```

Toutes les commandes `php` et Composer de la suite doivent utiliser PHP 8.5.
Si `php -v` indique encore PHP 7.1, corrigez la sélection du binaire CLI avant
de poursuivre ; activer `php8.5` dans Apache ne modifie pas à lui seul le PHP
en ligne de commande.

Vérifiez aussi le module Apache avec `apache2ctl -M` et, après la bascule,
contrôlez la version et les extensions réellement chargées par une requête
traitée par Apache. Un `php -m` CLI seul ne prouve pas la configuration du
module Apache.

## 4. Mettre à niveau le moteur de base de données

MariaDB 11.8.8 est le moteur recommandé pour Prevarisc 3.0. Deux cas de départ
sont à distinguer. Dans les deux cas, faites une sauvegarde vérifiée, arrêtez
l'application pendant la bascule et contrôlez les collations après reprise.

<a id="cas-a-mysql-vers-mariadb"></a>

### 4.1 Cas A — MySQL 5.6 vers MariaDB 11.8.8

Pour MySQL 5.6 (dont les paquets Oracle utilisés par certaines installations),
la migration est un **changement de moteur**, et non une simple mise à jour de
paquets :

- MariaDB ne peut pas reprendre directement le datadir MySQL 5.6 ;
- exportez les bases par dump logique, puis restaurez-les dans MariaDB ;
- les utilisateurs et privilèges MySQL ne sont pas repris comme des comptes
  MariaDB : recréez le compte applicatif après la bascule ;
- gardez le dump et l'ancien datadir archivés pour permettre un retour arrière.

Procédez d'abord sur une copie :

1. Exportez la base applicative avec `mysqldump`, comme décrit en section 2.1,
   et vérifiez le dump. N'incluez pas les bases système MySQL dans la restauration.
2. Installez MariaDB 11.8.8 avec un nouveau répertoire de données, en suivant
   la documentation officielle MariaDB correspondant à votre système.
3. Configurez les jeux de caractères et les collations selon la section 4.3
   avant de restaurer les données.
4. Restaurez le dump applicatif :

   ```sh
   mariadb -u root -p < /chemin/backup/prevarisc-mysql.sql
   ```

5. Exécutez `mariadb-upgrade` si la procédure de la version installée l'exige.
6. Recréez le compte applicatif (voir [Compte SQL](#annexe-compte-sql)) et
   vérifiez sa connexion à la base restaurée.

Ne purgez pas l'ancien serveur avant d'avoir produit et vérifié le dump et
d'avoir confirmé le plan de retour arrière.

### 4.2 Cas B — MariaDB 10.x vers MariaDB 11.8.8

Il s'agit d'une mise à niveau MariaDB. Prenez une sauvegarde logique vérifiée,
relevez la version mineure exacte et consultez les instructions officielles de
migration MariaDB pour déterminer le chemin pris en charge depuis cette version.
Ne supposez pas qu'un passage direct est possible depuis toute version 10.x.

Mettez à niveau les paquets MariaDB selon les dépôts de votre système, redémarrez
le service dans la fenêtre de maintenance, puis exécutez `mariadb-upgrade` si
la procédure de mise à niveau l'exige. Vérifiez l'état du service, la version,
la présence des bases et la connexion applicative avant de remettre le service
en production.

### 4.3 Configuration MariaDB et conservation des collations

Les paquets Oracle MySQL 5.6 lisaient parfois `/usr/my.cnf`. MariaDB ne lit pas
ce fichier. Déposez la configuration effective dans le répertoire MariaDB,
par exemple :

```text
/etc/mysql/mariadb.conf.d/70-prevarisc.cnf
```

Le numéro exact du préfixe doit rester supérieur à celui du fichier serveur du
paquet (généralement `50-server.cnf`) afin que les paramètres Prevarisc ne
soient pas écrasés. Exemple minimal à adapter, notamment pour l'adresse d'écoute
si l'application se connecte depuis un autre serveur :

```ini
[mysqld]
bind-address = 127.0.0.1
character-set-server = utf8mb3
collation-server = utf8mb3_general_ci
character_set_collations = utf8mb3=utf8mb3_general_ci
```

`character_set_collations` est important pour restaurer les bases MySQL 5.6
créées en `utf8` sans collation explicite. Depuis MariaDB 11.5, la collation
par défaut de `utf8mb3` peut différer de celle de MySQL 5.6. Sans cette
directive, les règles de comparaison et d'unicité peuvent changer silencieusement.
Ne remplacez pas ces paramètres par `utf8mb4_unicode_ci` sans décision et test
de migration distincts.

Après redémarrage, vérifiez les valeurs effectives et les collations des bases :

```sh
mariadb -NBe "SELECT @@version, @@bind_address, @@character_set_server, @@collation_server;"
mariadb -NBe "SELECT schema_name, default_collation_name FROM information_schema.schemata;"
```

Le nom du fichier de journal d'erreurs peut être conservé, mais son répertoire
doit être accessible en écriture par l'utilisateur système `mysql`. Vérifiez
également `bind-address` et les règles réseau lorsque les frontaux sont distants.

### 4.4 Peut-on rester sur MySQL ?

La connexion à MySQL peut continuer à fonctionner si le schéma est compatible
et si `DATABASE_URL` indique la version réelle du serveur (`serverVersion`).
MariaDB 11.8.8 reste le moteur recommandé et le chemin testé pour 3.0. Si vous
gardez MySQL, validez cette configuration en recette et vérifiez les
migrations, les collations et les fonctions SQL utilisées par l'application.

## 5. Adapter Apache

Apache peut rester installé si sa version 2.4 est compatible avec PHP 8.5 et
les modules nécessaires. Il est recommandé d'utiliser une version 2.4 récente,
maintenue et à jour en correctifs de sécurité. La configuration n'est pas
strictement inchangée : le point d'entrée devient le répertoire `public/` de
l'application Symfony 3.0.

Vérifiez au minimum :

- `DocumentRoot` et le bloc `<Directory>` pointent vers
  le sous-répertoire `public/` de l'application Symfony 3.0 réellement déployée
  (par exemple `/chemin/vers/prevarisc-migration/public`,
  `/chemin/vers/prevarisc/public` ou `/chemin/vers/prevarisc-3.0/public`) ;
- `mod_rewrite` est activé et les règles Symfony du VirtualHost sont présentes ;
- le module PHP et le MPM sont compatibles (`mod_php` avec prefork) ;
- les alias de données (par exemple `/data`) continuent de pointer vers le bon
  répertoire ;
- les variables `SetEnv PREVARISC_*` nécessaires à l'installation sont
  conservées, et les nouvelles variables de la configuration 3.0 sont
  examinées ;
- `apache2ctl configtest` réussit avant le redémarrage.

Un exemple de VirtualHost est disponible dans le [README](README.md#installation).
Le chemin `prevarisc/public` de cet exemple suppose un clonage dans `prevarisc/` ;
ce nom n'est pas imposé. Utilisez le même chemin réel dans `DocumentRoot` et
`<Directory>`, sans pointer vers la racine du dépôt ni vers l'ancien Zend.
Adaptez le reste à votre configuration existante plutôt que de remplacer sans
examen les règles d'authentification, de proxy, de TLS ou d'accès aux fichiers.

> [!NOTE]
> Sous Linux, l'accès à un serveur de fichiers Windows se fait normalement par
> un montage CIFS/SMB et par les droits du point de montage. Apache ne requiert
> pas `UNCList` pour accéder à un chemin Linux monté.

### Apache sous Windows : accès aux chemins UNC

Si Apache tourne sous Windows et doit accéder directement à un partage tel que
`\\serveur-fichiers\partage`, Apache 2.4.60 et versions ultérieures doivent
autoriser l'hôte UNC utilisé. Ajoutez la directive au niveau global du serveur
(pas dans un bloc `<VirtualHost>`), en limitant la liste aux seuls hôtes requis :

```apache
UNCList serveur-fichiers
```

Vérifiez également que le compte du service Apache a les droits Windows sur le
partage et le système de fichiers. Cette note ne remplace pas une procédure de
migration Windows complète.

<a id="deploy-app-3"></a>

## 6. Déployer l'application 3.0

### 6.1 Préparer un nouveau répertoire

Conservez l'application actuelle intacte et préparez un répertoire séparé pour
le code 3.0. Utilisez un tag stable publié ; ne déployez pas directement une
branche de développement ou de release.

Il s'agit d'une reprise d'installation existante, pas d'une installation
neuve : conservez la base et les données. Le clonage séparé ci-dessous permet
de changer de dépôt sans modifier l'historique ni les changements locaux de
l'ancien dépôt. Un simple `git pull` dans l'ancien Zend ne réalise pas cette
migration.

```sh
git clone https://github.com/atos-df-rennes/prevarisc-migration.git prevarisc-3.0
cd prevarisc-3.0
git fetch --tags origin
git checkout vX.Y.Z
```

Remplacez `vX.Y.Z` par le tag stable choisi. Gardez une copie ou un lien vers
le commit actuel pour revenir à la version précédente.

Dans cet exemple, `prevarisc-3.0/` est le répertoire choisi pour isoler la
nouvelle version : Apache devra donc pointer vers son sous-répertoire
`prevarisc-3.0/public`. Vous pouvez choisir un autre nom de destination libre,
y compris `prevarisc-migration/` ou `prevarisc/`, sans renommer ni écraser
l'ancienne installation. Aucun renommage supplémentaire n'est requis avant
la mise en service.

### 6.2 Créer ou reprendre le fichier `.env`

Prevarisc 3.0 utilise le `.env` Symfony. Ne publiez jamais ses secrets dans Git.

- En **2.5**, créez un `.env` depuis `.env.example`, générez un nouvel
  `APP_SECRET`, renseignez `DATABASE_URL` et les variables applicatives requises.
- En **2.8/2.10**, reprenez le `.env` de l'ancienne application Symfony,
  généralement dans `prevarisc-migration/` (ou le fichier propre à
  l'environnement), et comparez-le à `.env.example` de 3.0. Conservez
  les paramètres spécifiques, ajoutez/supprimez ceux requis par 3.0, et vérifiez
  particulièrement `DATABASE_URL`.

Un fichier `.env` existant ne doit pas être remplacé aveuglément par
`.env.example` : cela peut faire perdre les identifiants SQL, l'`APP_SECRET`
et les réglages mail ou Plat'AU.

Exemple minimal :

```dotenv
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=<secret-aleatoire>
DATABASE_URL="mysql://<utilisateur>:<mot-de-passe>@<hote>:3306/<base>?serverVersion=11.8.8-MariaDB&charset=utf8mb4"
```

Pour générer un secret aléatoire :

```sh
php -r "echo bin2hex(random_bytes(16)), PHP_EOL;"
```

Adaptez `serverVersion` au moteur réellement utilisé. Encodez dans l'URL les
caractères spéciaux du nom d'utilisateur ou du mot de passe. Les autres
variables (`PREVARISC_MAIL_*`, `PLATAU_CONFIG_FILE`, etc.) sont détaillées dans
`.env.example`.

Les paramètres historiques du VirtualHost en `SetEnv PREVARISC_*` restent
distincts de ce fichier : `.env` ne remplace pas à lui seul la configuration
Apache existante.

> [!IMPORTANT]
> Les directives Apache `SetEnv` ne sont pas transmises aux commandes lancées
> dans un terminal ni aux tâches cron. Rendez les paramètres nécessaires
> disponibles aussi pour ces commandes, par exemple dans le `.env` privé de
> la nouvelle installation. Ne copiez pas la syntaxe `SetEnv` dans ce fichier :
> utilisez la syntaxe `NOM="valeur"`. Les variables réelles de l'environnement
> sont prioritaires sur les fichiers `.env` ; évitez les valeurs contradictoires
> entre Apache, les fichiers et les tâches planifiées.

Reprenez notamment **à l'identique `PREVARISC_SECURITY_SALT`**, avec sa valeur
effective dans l'ancienne installation, même si elle n'apparaît pas dans
`.env.example`. Elle permet de vérifier les mots de passe historiques ; la
remplacer par un nouveau secret peut empêcher les utilisateurs de se connecter.
Elle est distincte de `APP_SECRET`.

Les variables `PREVARISC_*` disponibles ne sont pas toutes listées dans
`.env.example` : consultez également leurs définitions dans
[`config/prevarisc.php`](config/prevarisc.php) pour le tag choisi. Ne supprimez
pas un réglage existant au seul motif qu'il manque dans `.env.example`.
Ne recopiez pas l'ancien `.env.local.php` compilé : il sera régénéré pour 3.0.
Vérifiez aussi les surcharges `.env.local`, `.env.prod` et `.env.prod.local`
reprises, qui peuvent remplacer les valeurs de `.env`.

### 6.3 Dépendances, migrations et cache

Depuis la racine de l'application 3.0, après configuration de `.env` et accès à
la base, utilisez **Composer 2.x exécuté avec PHP 8.5**. Installez les versions
verrouillées du dépôt avec `install`, et non `update` :

```sh
APP_ENV=prod APP_DEBUG=0 composer install --no-dev --classmap-authoritative
composer check-platform-reqs --no-dev
composer dump-env prod
APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:migrations:status
APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:migrations:list
```

Composer exécute aussi les commandes du dépôt qui installent les ressources
publiques et génèrent les routes JavaScript. N'utilisez pas `--no-scripts`
pour contourner une erreur : corrigez sa cause avant de poursuivre. Aucune
installation npm n'est nécessaire pour cette procédure de production.

Après examen de l'état et de la liste des migrations, et **uniquement sur la
copie de recette ou pendant la coupure de production**, exécutez :

```sh
APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:migrations:migrate --no-interaction
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:migrations:status
```

Les commandes `status` et `list` indiquent les migrations présentes et leur
état ; elles ne détaillent pas toutes les modifications SQL. Consultez si
nécessaire les fichiers du répertoire `migrations/` du tag choisi et
assurez-vous que la sauvegarde de la base est disponible.
En 2.8/2.10, Symfony 4.4 et les migrations
Doctrine étaient déjà utilisés : vérifiez les migrations en attente, sans
supprimer ni recréer la table `doctrine_migration_versions`.

Pour une base historique **2.5**, la première migration 3.0 reconnaît une base
déjà initialisée (présence de la table `avis`) et saute la création initiale du
schéma ; les migrations suivantes appliquent les évolutions attendues. Cela ne
dispense pas d'une sauvegarde ni d'une recette sur une copie représentative.
N'exécutez pas en parallèle les anciens scripts `sql/migrations` Zend et les
migrations Doctrine 3.0.

Appliquez les permissions nécessaires selon l'utilisateur système du serveur
web. À minima, `var/` doit être accessible en écriture à Apache/PHP ; ne rendez
pas tout le code ou les secrets accessibles en écriture au serveur web.

### 6.4 Reprendre les fichiers et les réglages locaux

Un clonage Git ne reprend ni les fichiers téléversés ni les fichiers locaux
non versionnés de l'ancienne installation :

- conservez le répertoire de données existant ou recopiez-le intégralement
  vers le nouveau serveur, avec son arborescence et ses droits ;
- renseignez `PREVARISC_REAL_DATA_PATH` avec son chemin physique réel. Sans
  cette variable, 3.0 utilise `public/data` dans le nouveau dépôt, qui ne
  contient pas les fichiers de l'ancienne installation ;
- conservez `PREVARISC_DATA_PATH` (préfixe d'URL, par défaut `/data`) et les
  alias Apache correspondants ; ne confondez pas ce préfixe avec le chemin
  physique des fichiers ;
- conservez `PREVARISC_DATASTORE_ADAPTER` : changer d'adaptateur ne convertit
  pas l'arborescence des pièces jointes existantes ;
- reprenez les modèles de documents, les fichiers d'autoconfiguration IGN
  et les autres fichiers locaux utilisés. Un fichier IGN servi par une URL
  sous `public/` doit être présent dans le nouveau répertoire public ou
  accessible par un alias adapté ;
- examinez les modifications de code et plugins locaux avant la migration :
  ne recopiez pas des modules Zend dans Symfony en supposant qu'ils fonctionneront.
  Leur compatibilité doit être vérifiée en recette.

Vérifiez la lecture des fichiers existants et la création d'une nouvelle pièce
jointe avec le compte du serveur web. Conservez l'ancienne copie des fichiers
jusqu'à la validation de la migration.

### 6.5 Effectuer la bascule en production

Après réussite de la recette :

1. Préparez le nouveau dépôt, les dépendances et la configuration. N'exécutez
   pas les migrations contre la base de production encore utilisée par
   l'ancienne application.
2. Bloquez l'accès des utilisateurs à l'ancienne application et suspendez les
   tâches planifiées et intégrations susceptibles d'écrire en base ou dans les
   fichiers. L'arrêt du seul accès web ne suspend pas les tâches cron.
3. Effectuez les sauvegardes finales de la base et des fichiers, puis leur
   transfert final si vous changez de serveur. N'autorisez plus d'écriture
   dans l'ancienne installation.
4. Basculez le moteur SQL si nécessaire (section 4), vérifiez le compte SQL et
   mettez à jour la configuration de connexion.
5. Terminez la reprise des fichiers et réglages (sections 6.2 et 6.4),
   régénérez l'environnement compilé, puis appliquez les migrations et videz
   le cache comme décrit en section 6.3.
6. Activez PHP 8.5 dans Apache et la configuration du nouveau site (section 5).
   Sur Debian avec le service `apache2`, vérifiez puis redémarrez :

   ```sh
   apache2ctl configtest
   systemctl restart apache2
   ```

7. En maintenant l'accès limité aux personnes chargées de la vérification,
   effectuez les contrôles de la section 9. En cas d'erreur bloquante, ne
   rouvrez pas le service ; corrigez-la ou appliquez le retour arrière.
8. Mettez à jour et réactivez les tâches planifiées vers la nouvelle installation,
   vérifiez leur fonctionnement, puis rouvrez l'accès aux utilisateurs.
   Ne laissez pas les anciennes et nouvelles tâches actives simultanément.

## 7. Particularités selon la version de départ

### 7.1 Depuis Prevarisc 2.5

2.5 est une application Zend seule. La cible 3.0 ne contient plus le Zend :
la base et les fichiers de données doivent être repris, mais l'application,
ses modules et sa configuration doivent être adaptés.

1. **Confirmez le dépôt et les personnalisations.** Certaines installations
   proviennent de `SDIS62/prevarisc`, d'autres du fork
   `atos-df-rennes/prevarisc`. Comparez le commit installé et les différences
   locales (plugins, modules, scripts et correctifs) avant de cloner 3.0.
2. **Reprenez les variables Apache.** Inventoriez chaque `SetEnv` des
   VirtualHosts. Comparez les noms et valeurs à la configuration 3.0 et à
   `.env.example`. Conservez les valeurs métier encore utiles, migrez les
   paramètres d'application qui appartiennent désormais à `.env`, et retirez
   uniquement les directives explicitement obsolètes. Les clés spécifiques
   aux plugins ou à votre installation nécessitent une vérification manuelle ; ne les
   supprimez pas par simple différence de fichiers.
3. **Créez le `.env` 3.0.** Il n'y a pas de `.env` Symfony 2.5 à reprendre.
   Générez `APP_SECRET`, configurez la base et renseignez les variables
   applicatives requises.
4. **Migrez le moteur SQL** selon le cas MySQL 5.6.28 vers MariaDB 11.8.8
   décrit en [section 4.1](#cas-a-mysql-vers-mariadb). Pour
   conserver temporairement MySQL, définissez le `serverVersion` exact et
   testez le résultat.
5. **Déployez 3.0** dans un nouveau répertoire, pointez Apache sur son
   `public/`, conservez le chemin des données et exécutez les migrations
   Doctrine comme décrit en [section 6](#deploy-app-3).
6. **Validez les intégrations** : authentification, génération de documents,
   stockage de fichiers, modules/plugins convertis et passerelle Plat'AU.

Les scripts SQL des mises à jour Zend ne remplacent pas les migrations Doctrine.
Les différences entre les installations SDIS62 et le fork Atos, ainsi que les
personnalisations locales, doivent être traitées au cas par cas.

<a id="migration-depuis-28"></a>

### 7.2 Depuis Prevarisc 2.8

2.8 contient deux applications : le Zend historique (généralement dans
`prevarisc/`) et Symfony 4.4 (généralement dans `prevarisc-migration/`).
En 3.0, Symfony devient la seule application et le code Zend n'est plus
nécessaire. Cela n'impose pas de reprendre le nom du répertoire Zend pour
Symfony.

1. Sauvegardez les deux répertoires, la configuration et le `.env` situé
   généralement dans `prevarisc-migration/`.
2. Reprenez le `.env` Symfony dans le nouveau dépôt 3.0, comparez-le à
   `.env.example` et mettez à jour `DATABASE_URL` vers MariaDB 11.8.8 (ou
   la version exacte du moteur conservé).
3. Migrez PHP et le moteur de base selon les sections communes.
4. Pointez le `DocumentRoot` et le bloc `<Directory>` Apache vers le
   sous-répertoire `public/` de la nouvelle application 3.0, quel que soit le
   nom choisi pour son répertoire (voir section 5). Vérifiez que les variables
   `SetEnv`, les alias de données et les règles d'authentification ont été
   conservés.
5. Contrôlez l'état de `doctrine_migration_versions`, puis appliquez les
   migrations 3.0 en attente. Ne supprimez pas la table de suivi.
6. Vérifiez la configuration et les tâches Plat'AU. Si l'ancienne installation
   utilise un PHP séparé pour la passerelle, mettez à jour ses commandes et
   chemins pour utiliser la version PHP 8.5 prévue par votre déploiement.

### 7.3 Depuis Prevarisc 2.10

La procédure est la même que pour 2.8 : Symfony 4.4 et PHP 7.1.33 sont présents,
ainsi que le Zend historique. Vérifiez toutefois les modifications locales
apportées depuis 2.8, puis suivez les étapes de la
[section 7.2](#migration-depuis-28). En particulier :

- préservez le `.env` et les fichiers de configuration propres aux
  environnements additionnels ;
- vérifiez les migrations Doctrine en attente avant de les exécuter ;
- contrôlez les personnalisations Apache et les chemins de stockage ;
- validez les tâches planifiées et les intégrations Plat'AU après la bascule.

## 8. Passerelle Plat'AU et autres intégrations

Cette section ne concerne que les intégrations utilisées par votre SDIS.
La passerelle Plat'AU est un composant séparé ; le clonage de l'application ne
l'installe ni ne la met à jour. Vérifiez sa version, son
répertoire, ses identifiants et son fichier `config.json`. La variable
`PLATAU_CONFIG_FILE` désigne le fichier de configuration ; `PREVARISC_PLATAU_PATH`
peut indiquer le chemin de l'installation de la passerelle. Si cette dernière
n'est pas fournie, 3.0 recherche par défaut le répertoire frère
`prevarisc-passerelle-platau`.

`PLATAU_CONFIG_FILE` se configure dans `.env`. `PREVARISC_PLATAU_PATH` doit
être fourni dans l'environnement du processus qui en a besoin (par exemple via
`SetEnv` dans le VirtualHost pour les requêtes Apache, ou dans l'environnement
de la tâche planifiée concernée) :

```apache
SetEnv PREVARISC_PLATAU_PATH "/chemin/vers/prevarisc-passerelle-platau"
```

Lors d'un changement de répertoire ou de lien symbolique, vérifiez que ce
chemin pointe toujours vers la bonne installation. Mettez à jour les tâches
cron/scripts pour utiliser PHP 8.5 et les chemins de la nouvelle arborescence.
Ne supprimez pas la configuration précédente avant d'avoir testé les appels et
les échanges Plat'AU.

Vérifiez également les fichiers d'autoconfiguration IGN, les plugins tiers,
LDAP/CAS/NTLM, le SMTP, les proxys et les éventuels montages de fichiers.
Reportez les chemins et secrets dans leurs emplacements appropriés ; ne
commitez pas d'identifiants.

## 9. Contrôles après migration

Avant de remettre Prevarisc à disposition :

- [ ] `php -v` indique PHP 8.5.x ; le module chargé par Apache utilise bien PHP 8.5.
- [ ] `apache2ctl configtest` réussit ; Apache utilise le MPM et le module PHP
      attendus.
- [ ] MariaDB est actif et la version est celle attendue ; les valeurs de
      charset/collation et l'adresse d'écoute sont correctes.
- [ ] `DATABASE_URL` correspond au serveur réel ; l'application se connecte à
      la base attendue.
- [ ] Toutes les migrations Doctrine prévues ont été exécutées.
- [ ] `DocumentRoot` et `<Directory>` pointent vers le même sous-répertoire
      `public/` de l'application 3.0 réellement déployée ; la réécriture d'URL
      fonctionne et les alias de données restent accessibles.
- [ ] Les répertoires de cache, journaux, données et pièces jointes sont
      accessibles avec les bons droits.
- [ ] Connexion utilisateur, recherche, consultation d'un dossier, pièces
      jointes, génération de documents et exports fonctionnent.
- [ ] Un compte local existant peut se connecter avec son mot de passe
      habituel ; une pièce jointe antérieure peut être téléchargée et une
      nouvelle pièce peut être ajoutée.
- [ ] Le mode SSO des comptes a été vérifié après la reprise automatique :
      comptes à login renseigné sans mot de passe local → SSO ; fiches mairie
      sans login → non SSO si la valeur était NULL. Les connexions LDAP et
      locales sont vérifiées séparément, sans fallback, selon la
      [procédure de correction des comptes SSO](docs/guide/admin/utilisateurs/correction-authentification-sso.md).
      Prévenir les administrateurs qu'un compte LDAP encore classé local peut
      nécessiter de cocher la case SSO ; contrôler les journaux
      `var/log/authentication-AAAA-MM-JJ.log`.
- [ ] Les ressources CSS/JavaScript se chargent sans erreur et les écrans
      utilisant les routes JavaScript fonctionnent.
- [ ] Les tâches planifiées, mails, LDAP/SSO, IGN, plugins et Plat'AU sont
      vérifiés selon les fonctionnalités utilisées.
- [ ] Les journaux Apache, PHP, Symfony et MariaDB ne signalent pas d'erreur
      bloquante.

Contrôlez également les données sur des cas représentatifs (enregistrements,
pièces jointes, comptes, rôles et recherches). Une page d'accueil qui s'affiche
ne suffit pas à valider une migration.

## 10. Retour arrière

Les migrations Doctrine et le changement de base ne doivent pas être considérés
comme automatiquement réversibles. Pour revenir à l'ancienne version :

1. arrêtez l'application et les tâches planifiées ;
2. remettez l'ancien code et l'ancien VirtualHost/DocumentRoot ;
3. restaurez la base **depuis la sauvegarde prise avant migration**, si le
   schéma ou les données ont été modifiés ;
4. restaurez les fichiers `.env`, PHP et base correspondants à l'ancienne
   version ;
5. vérifiez l'application, les intégrations et les journaux avant de rouvrir
   l'accès.

Pour MySQL 5.6 vers MariaDB, le retour arrière peut nécessiter de réinstaller
les paquets MySQL d'origine et de remettre en service son ancien datadir ; un
datadir MariaDB 11.8 ne peut pas être réutilisé par MySQL 5.6. Préparez et
testez la procédure de restauration avant la bascule. Le retour de Debian 12
vers un ancien système n'est pas couvert par ce guide ; c'est une raison
supplémentaire de préférer une migration sur un nouveau serveur ou un snapshot
restaurable.

## Annexe A — Variables d'environnement et configuration

Consultez le `.env.example` et les définitions des variables applicatives dans
`config/prevarisc.php` du tag 3.0 choisi. Comparez-les aux fichiers de
l'installation, sans recopier les secrets dans un rapport ou un ticket public.

| Emplacement | Contenu courant | Action pendant la migration |
| --- | --- | --- |
| `.env`, `.env.local`, `.env.<environnement>`, `.env.<environnement>.local` | `APP_ENV`, `APP_DEBUG`, `APP_SECRET`, `DATABASE_URL`, mail, `PLATAU_CONFIG_FILE` et réglages applicatifs | Reprendre ou créer ; vérifier les priorités et le moteur ; ne pas exposer les secrets. |
| Environnement applicatif | `PREVARISC_SECURITY_SALT`, `PREVARISC_REAL_DATA_PATH`, `PREVARISC_DATA_PATH`, `PREVARISC_DATASTORE_ADAPTER` | Conserver le sel historique et l'adaptateur ; vérifier les chemins et leur disponibilité pour Apache et les commandes CLI. |
| VirtualHost Apache | `DocumentRoot`, `<Directory>` | Pointer vers le même sous-répertoire `public/` du répertoire 3.0 choisi ; aucun nom de répertoire n'est imposé. |
| VirtualHost Apache | `SetEnv PREVARISC_*` : chemins, base/config héritée, LDAP/CAS/NTLM, cartographie, sécurité, plugins et options métier | Conserver les réglages encore utilisés ; examiner les nouvelles variables ; valider le VirtualHost avec Apache. |
| Configuration MariaDB | charset, collation, adresse d'écoute, journalisation, réglages locaux | Déplacer hors de `/usr/my.cnf` si nécessaire ; contrôler la configuration réellement chargée. |
| Cron et scripts | Chemins de l'application et de Plat'AU, binaire PHP | Mettre à jour les chemins et utiliser PHP 8.5. |

`composer dump-env prod` compile les variables d'environnement en production.
Relancez cette commande après toute modification des fichiers `.env*`, puis
videz le cache en production afin que la configuration soit à jour. Une
modification des directives Apache nécessite aussi la prise en compte de la
nouvelle configuration par Apache.

<a id="annexe-compte-sql"></a>

## Annexe B Compte SQL

Adaptez le nom de base, l'hôte autorisé et le mot de passe à votre architecture.
Utilisez un secret fort et ne le placez pas dans un historique de commandes ou
un fichier versionné.

```sql
CREATE USER 'prevarisc'@'localhost' IDENTIFIED BY '<mot-de-passe>';
GRANT CREATE, DROP, ALTER, INDEX, CREATE VIEW,
      SELECT, INSERT, UPDATE, DELETE
ON `PRV_prevarisc_v2`.*
TO 'prevarisc'@'localhost';
```

Ces commandes supposent que la base applicative existe déjà ou a été restaurée.
Ne créez pas une base vide à la place de la base historique. Si le compte SQL
existe déjà, vérifiez ses droits et adaptez-les au lieu de rejouer `CREATE USER`.

Si le serveur SQL est distant, remplacez `localhost` par l'adresse du frontend
ou par une portée réseau strictement nécessaire. Les migrations Doctrine
requièrent les droits de modification de schéma ci-dessus. Appliquez une
politique de privilèges adaptée après la migration, sans retirer les droits
nécessaires au fonctionnement normal de l'application.

## Annexe C — Informations à joindre à un signalement

Lors d'une demande d'aide, fournissez les versions et les erreurs pertinentes,
après avoir retiré mots de passe, jetons, données personnelles et secrets :

```sh
git describe --tags --always
php -v
apache2 -v
mariadb --version
apache2ctl -M
```

Ajoutez le statut des migrations Doctrine, les extraits pertinents des journaux
Apache/PHP/Symfony/MariaDB et les symptômes observés. Ne joignez pas le fichier
`.env`, un dump SQL ou une configuration contenant des identifiants.
