# Migrer Prevarisc 2.5, 2.8 ou 2.10 vers 3.0

Ce guide s'adresse aux équipes qui exploitent Prevarisc installé directement
depuis Git, sans livraison ni accompagnement de l'équipe Prevarisc. Il décrit
les changements d'infrastructure et d'application nécessaires pour passer à
Prevarisc 3.0.

> [!WARNING]
> N'utilisez en production qu'un **tag stable** (par exemple `v3.0.0` sans
> suffixe). Les tags `-rc.N` et les branches `release/*` sont réservés aux
> environnements de test et de recette. Consultez les
> [versions et tags publiés](README.md#versions) avant de commencer.

La procédure cible **Debian 12**, **PHP 8.5**, **MariaDB 11.8.8** et Apache
HTTP Server 2.4. L'équipe a testé Debian 12. La mise à niveau de l'OS n'est pas
détaillée ici : consultez la documentation Debian et assurez-vous que
l'installation est à jour avant la migration.

## 1. Choisir la procédure correspondant à votre version

Vérifiez la version installée à partir de la version du dépôt et de son
arborescence. Ne vous fiez pas uniquement au nom du répertoire :

```sh
cd /chemin/vers/votre/prevarisc
git describe --tags --always
ls
```

| Départ | Architecture et versions usuelles | Particularités |
| --- | --- | --- |
| **2.5** | Application Zend seule ; PHP 7.1.33 ; MySQL 5.6.28 ; Apache 2.4 (version à relever) | Pas d'application Symfony ni de `.env` Symfony. Des installations peuvent provenir de `SDIS62/prevarisc` ou du fork `atos-df-rennes/prevarisc`. |
| **2.8** | Zend et Symfony 4.4 côte à côte ; PHP 7.1.33 ; MySQL 5.6.51 ; Apache 2.4.59 | La configuration Symfony se trouve généralement dans `prevarisc-migration/.env`. |
| **2.10** | Zend et Symfony 4.4 côte à côte ; PHP 7.1.33 ; MySQL 5.6.51 ; Apache 2.4.59 | Même socle technique que 2.8 ; la table des versions de migrations Doctrine existe normalement déjà. |
| **3.0** | Symfony 7.4 ; PHP 8.5 ; MariaDB 11.8 ; Apache 2.4 | Le répertoire de l'application devient `prevarisc/`; le Zend n'est plus livré. |

Symfony **4.4 est bien la version attendue en 2.8 et en 2.10** : ces branches
fonctionnent avec PHP 7.1.33. La migration vers PHP 8.5 et Symfony 7.4 a lieu
au passage en 3.0.

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

### 2.1 Sauvegardes indispensables

Avant toute modification :

- sauvegardez la base et vérifiez que le fichier de dump est lisible et complet ;
- conservez une copie de l'application actuellement en service et relevez le
  commit ou le tag installé ;
- sauvegardez la configuration Apache, notamment les lignes `SetEnv`, les
  VirtualHosts, les alias et les règles d'accès ;
- sauvegardez le `php.ini` et la configuration MySQL/MariaDB ;
- sauvegardez les fichiers `.env` présents, y compris ceux des environnements
  additionnels ;
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
de sécurité disponible de la branche 8.5. Si une exploitation impose
strictement le numéro 8.5.12, vérifiez que cette version précise est encore
disponible dans le dépôt et qu'elle reçoit les correctifs requis. Prevarisc 3.0
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
ci-dessous suppose que le paquet a installé le module `php8.5` :

```sh
a2dismod mpm_event
a2enmod mpm_prefork
a2enmod php8.5
apache2ctl configtest
```

Ne redémarrez pas Apache avant d'avoir vérifié la configuration et la présence
des modules ; planifiez la coupure de service au moment de la bascule.

### 3.2 Compilation depuis les sources (alternative)

La livraison Prevarisc compile PHP avec Apache via `apxs2`. Cette approche est
possible si vous gérez vous-même la compilation, mais elle implique de maintenir
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

Le script `crossgrade_mysql_to_mariadb.sh` du dépôt de livraison décrit et
automatise ce chemin pour les installations qui disposent de ces scripts.
Si vous gérez une installation Git sans ces scripts, préparez la procédure
équivalente et faites-la d'abord sur une copie. Ne purgez pas l'ancien serveur
avant d'avoir produit et vérifié le dump et d'avoir confirmé le plan de retour
arrière.

Après installation de MariaDB, restaurez le dump applicatif puis exécutez
`mariadb-upgrade` selon la procédure de la version installée. Recréez ensuite le
compte applicatif (voir [Compte SQL](#annexe-compte-sql)).

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
  `/chemin/vers/prevarisc/public` ;
- `mod_rewrite` est activé et les règles Symfony du VirtualHost sont présentes ;
- le module PHP et le MPM sont compatibles (`mod_php` avec prefork) ;
- les alias de données (par exemple `/data`) continuent de pointer vers le bon
  répertoire ;
- les variables `SetEnv PREVARISC_*` nécessaires à l'installation sont
  conservées, et les nouvelles variables de la configuration 3.0 sont
  examinées ;
- `apache2ctl configtest` réussit avant le redémarrage.

Un exemple de VirtualHost est disponible dans le [README](README.md#installation).
Adaptez-le à votre configuration existante plutôt que de remplacer sans examen
les règles d'authentification, de proxy, de TLS ou d'accès aux fichiers.

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

```sh
git clone https://github.com/atos-df-rennes/prevarisc-migration.git prevarisc-3.0
cd prevarisc-3.0
git fetch --tags origin
git checkout vX.Y.Z
```

Remplacez `vX.Y.Z` par le tag stable choisi. Gardez une copie ou un lien vers
le commit actuel pour revenir à la version précédente.

### 6.2 Créer ou reprendre le fichier `.env`

Prevarisc 3.0 utilise le `.env` Symfony. Ne publiez jamais ses secrets dans Git.

- En **2.5**, créez un `.env` depuis `.env.example`, générez un nouvel
  `APP_SECRET`, renseignez `DATABASE_URL` et les variables applicatives requises.
- En **2.8/2.10**, reprenez le `.env` de `prevarisc-migration/` (ou le fichier
  propre à l'environnement) et comparez-le à `.env.example` de 3.0. Conservez
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

### 6.3 Dépendances, migrations et cache

Depuis la racine de l'application 3.0, après configuration de `.env` et accès à
la base :

```sh
composer install --no-dev --classmap-authoritative
composer dump-env prod
APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:migrations:status
APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:migrations:migrate --no-interaction
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

La commande `doctrine:migrations:status` permet d'inspecter les migrations
avant leur exécution. Examinez les changements annoncés et assurez-vous que la
sauvegarde de la base est disponible. En 2.8/2.10, Symfony 4.4 et les migrations
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
   aux plugins ou au client nécessitent une vérification manuelle ; ne les
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

2.8 contient deux applications : le Zend historique (`prevarisc/`) et
Symfony 4.4 (`prevarisc-migration/`). En 3.0, Symfony devient l'application
principale et le code Zend n'est plus nécessaire.

1. Sauvegardez les deux répertoires, la configuration et le `.env` situé
   généralement dans `prevarisc-migration/`.
2. Reprenez le `.env` Symfony dans le nouveau dépôt 3.0, comparez-le à
   `.env.example` et mettez à jour `DATABASE_URL` vers MariaDB 11.8.8 (ou
   la version exacte du moteur conservé).
3. Migrez PHP et le moteur de base selon les sections communes.
4. Pointez le `DocumentRoot` Apache vers `prevarisc/public`. Vérifiez que les
   variables `SetEnv`, les alias de données et les règles d'authentification
   ont été conservés.
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

La passerelle Plat'AU est un composant séparé. Vérifiez sa version, son
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
- [ ] Le DocumentRoot pointe vers `public/`, la réécriture d'URL fonctionne,
      et les alias de données restent accessibles.
- [ ] Les répertoires de cache, journaux, données et pièces jointes sont
      accessibles avec les bons droits.
- [ ] Connexion utilisateur, recherche, consultation d'un dossier, pièces
      jointes, génération de documents et exports fonctionnent.
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

La liste faisant autorité est le `.env.example` livré avec le tag 3.0 choisi.
Comparez-le aux fichiers de l'installation, sans recopier les secrets dans un
rapport ou un ticket public.

| Emplacement | Contenu courant | Action pendant la migration |
| --- | --- | --- |
| `.env` / `.env.<environnement>` | `APP_ENV`, `APP_DEBUG`, `APP_SECRET`, `DATABASE_URL`, mail, `PLATAU_CONFIG_FILE` | Reprendre ou créer ; comparer au nouvel `.env.example` ; vérifier le moteur et ne pas exposer les secrets. |
| VirtualHost Apache | `SetEnv PREVARISC_*` : chemins, base/config héritée, LDAP/CAS/NTLM, cartographie, sécurité, plugins et options métier | Conserver les réglages encore utilisés ; examiner les nouvelles variables ; valider le VirtualHost avec Apache. |
| Configuration MariaDB | charset, collation, adresse d'écoute, journalisation, réglages locaux | Déplacer hors de `/usr/my.cnf` si nécessaire ; contrôler la configuration réellement chargée. |
| Cron et scripts | Chemins de l'application et de Plat'AU, binaire PHP | Mettre à jour les chemins et utiliser PHP 8.5. |

`composer dump-env prod` compile les variables d'environnement en production.
Relancez cette commande après toute modification de `.env` afin que la
configuration compilée soit à jour.

<a id="annexe-compte-sql"></a>

## Annexe B Compte SQL

Adaptez le nom de base, l'hôte autorisé et le mot de passe à votre architecture.
Utilisez un secret fort et ne le placez pas dans un historique de commandes ou
un fichier versionné.

```sql
CREATE DATABASE `PRV_prevarisc_v2`
  CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci;

CREATE USER 'prevarisc'@'localhost' IDENTIFIED BY '<mot-de-passe>';
GRANT CREATE, DROP, ALTER, INDEX, CREATE VIEW,
      SELECT, INSERT, UPDATE, DELETE
ON `PRV_prevarisc_v2`.*
TO 'prevarisc'@'localhost';
```

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
