# MOD-21 — Administration système

## 1. Objet et périmètre

Ce module fournit un **panneau d'administration en lecture seule** qui centralise les informations de configuration, d'état et de supervision de l'application Prevarisc.

Il permet à un administrateur de vérifier d'un seul coup d'œil :

- la version déployée et la disponibilité d'une mise à jour ;
- la configuration des services cartographiques ;
- les paramètres de connexion à la base de données ;
- la méthode d'authentification active ;
- l'état du cache applicatif ;
- la liste des utilisateurs actuellement connectés ;
- les composants tiers activés.

**Le module est strictement consultatif** : aucune action de modification n'est proposée depuis cette interface.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|---|---|
| **Administrateur système** | Accède au panneau d'administration pour superviser la configuration et l'état de l'application. |
| **Chef de projet / Responsable applicatif** | Consulte les informations de version et de mise à jour pour piloter les montées de version. |

> **Restriction d'accès** : seul un utilisateur disposant des droits d'administration peut accéder à ce panneau. Le menu d'administration dédié encadre cette page.

---

## 3. Informations affichées

Le panneau est organisé en deux blocs visuels : « Informations générales » et « Utilisateurs connectés ».

### 3.1 Version et état du déploiement

| Donnée | Description |
|---|---|
| **Version applicative** | Version de l'instance Prevarisc déployée. En mode Git, la branche et le commit courants sont lus dans le dépôt de l'application, sans dépendre du nom de son dossier ni du dépôt legacy. Le nom de branche est conservé tel quel, par exemple `release/3.0@abc1234`. |
| **Indicateur de mise à jour** | En mode Git, comparaison entre le commit courant de l'instance et le dernier commit de la même branche sur le dépôt officiel `atos-df-rennes/prevarisc-migration`. Si la branche locale est indisponible, si HEAD est détachée ou si cette branche n'existe pas sur le dépôt distant (404), la comparaison se fait avec `main`. Une différence affiche le badge informatif « Mise à jour disponible ». Une instance sur `develop` ou `release/3.1` n'est donc pas comparée systématiquement à `main`. |

> La vérification de mise à jour est **toujours activée**, même quand la branche de déploiement est explicitement définie dans la configuration de l'environnement. Cela permet à l'administrateur de savoir si une nouvelle version est disponible, indépendamment du mode de déploiement. La requête distante gère les erreurs réseau sans exposer les défaillances à l'utilisateur.

### 3.2 Configuration des services cartographiques

Trois fournisseurs de cartographie peuvent être configurés indépendamment :

#### 3.2.1 Service IGN (Institut Géographique National)

| Donnée | Description |
|---|---|
| **Clé(s) API IGN** | Une ou plusieurs clés d'accès au service IGN, affichées individuellement. Si aucune clé n'est configurée, le message « Aucune clé » est affiché. |

#### 3.2.2 Service Google Maps

| Donnée | Description |
|---|---|
| **Clé API Google Maps** | Clé d'accès au service Google Maps. Si aucune clé n'est configurée, le message « Aucune clé » est affiché. |

#### 3.2.3 Service Geoconcept

| Donnée | Description |
|---|---|
| **État** | « Désactivé » si aucune adresse de service n'est configurée. |
| **Adresse du service** | URL du serveur Geoconcept. |
| **Couche cartographique** | Nom de la couche utilisée. |
| **Identifiant d'application** | Identifiant de l'application auprès du service. |
| **Projection** | Système de projection cartographique. Si non paramétrée, le message « Non paramétrée » est affiché. |
| **Jeton d'accès** | Jeton d'authentification auprès du service. |
| **Adresse du géocodeur** | URL du service de géocodage associé. |

### 3.3 Chemin des fichiers

| Donnée | Description |
|---|---|
| **Chemin des fichiers** | Chemin complet du répertoire où les fichiers de l'application sont stockés. Ce répertoire contient les documents, les pièces jointes et autres fichiers gérés par Prevarisc. |

### 3.4 Connexion à la base de données

| Donnée | Description |
|---|---|
| **Nom de la base** | Nom de la base de données utilisée par l'application. |
| **Adresse du serveur** | Hôte et port du serveur de base de données (format `hôte:port`). Le port par défaut MySQL (3306) est toujours affiché. |

### 3.5 Méthode d'authentification

Le panneau affiche le mode de configuration prioritaire parmi les quatre possibles :

| Méthode | Informations complémentaires affichées |
|---|---|
| **CAS** (Central Authentication Service) | Aucune. |
| **NTLM + Base de données** | Aucune. |
| **LDAP + Base de données** | Adresse du serveur LDAP, port et base de recherche (DN). |
| **Base de données seule** (par défaut) | Aucune. |

Ce libellé ne décrit pas une chaîne de fallbacks : sur le formulaire, LDAP ou la base locale est sélectionné selon la case d'authentification déléguée du compte (MOD-05 §6.1.5). CAS et NTLM gardent leur priorité lorsqu'ils prennent en charge la requête.

Une information complémentaire est affichée indépendamment de la méthode :

| Donnée | Description |
|---|---|
| **Renforcement de la sécurité** | Indique si le mode de sécurité renforcée est activé ou désactivé. |

### 3.6 État du cache

| Donnée | Affiché si le cache est… |
|---|---|
| **État** (activé / désactivé) | Toujours affiché. |
| **Type d'adaptateur** | Activé uniquement. Nom du système de cache utilisé (ex. : Memcached, Redis…). |
| **Adresse du serveur de cache** | Activé uniquement. Hôte et port du serveur de cache. |
| **Durée de vie** | Activé uniquement. Durée de vie des entrées de cache, exprimée en secondes. |

Si le cache est désactivé, seul le libellé « Désactivé » est affiché.

### 3.7 Sessions utilisateurs actives

Un tableau liste les utilisateurs **actuellement connectés** à l'application :

| Colonne | Description |
|---|---|
| **Identifiant** | Nom d'utilisateur (login). |
| **Nom et prénom** | Nom complet de l'utilisateur. |
| **Groupe** | Groupe d'appartenance de l'utilisateur. |
| **Fonction** | Fonction occupée par l'utilisateur. |
| **État** | Badge « En ligne » pour chaque utilisateur listé. |

Si aucun utilisateur n'est connecté, le message « Aucun résultat. » est affiché à la place du tableau.

### 3.8 Composants tiers

| Donnée | Description |
|---|---|
| **API HTTP** | Indique si l'API HTTP est activée ou désactivée. L'API est considérée comme activée lorsqu'une clé de sécurité API est configurée. |
| **Proxy HTTP** | Indique si le proxy HTTP sortant est activé ou désactivé. |
| **Plugins tiers** | Liste des plugins tiers installés, séparés par des virgules. Si aucun plugin n'est installé, le message « Aucun » est affiché. |

### 3.9 Paramètres de plateforme

Cette section regroupe les paramètres de configuration spécifiques à la plateforme de déploiement :

#### 3.9.1 Relance de périodicité

| Donnée | Description |
|---|---|
| **Relance périodicité** | Base de calcul de la relance automatique des dossiers : « Date de commission » si la relance est basée sur la date de commission, ou « Date de visite » si elle est basée sur la date de visite. |

#### 3.9.2 Unité de périodicité

| Donnée | Description |
|---|---|
| **Unité periodicité** | Unité utilisée pour exprimer les délais de renouvellement des dossiers : « Années » si les durées sont exprimées en années, ou « Mois » si elles sont exprimées en mois. |

#### 3.9.3 Connexion à Plat'AU

| Donnée | Description |
|---|---|
| **Connexion à Plat'AU** | État de la connexion au service Plat'AU : « Activée » si la plateforme Plat'AU est accessible et configurée, ou « Désactivée » si le service est désactivé. |

#### 3.9.4 Nombre de dossiers affichés

| Donnée | Description |
|---|---|
| **Nb dossiers affichés** | Nombre maximal de dossiers affichés dans les listes de l'application. Ce paramètre contrôle la pagination ou la limitation des résultats de recherche. |

---

## 4. Règles de gestion

### Version et déploiement

| # | Règle |
|---|---|
| **RG-ADM-001** | La version applicative est toujours affichée en haut du panneau d'administration. |
| **RG-ADM-002** | En mode Git, la vérification compare le commit courant de l'instance au dernier commit de sa branche sur `atos-df-rennes/prevarisc-migration`. Si la branche locale est indisponible, si HEAD est détachée ou si la branche distante est absente (404), la branche de référence est `main`. En mode version définie par l'environnement, la comparaison des tags est conservée sur ce même dépôt. |
| **RG-ADM-003** | Si la révision locale diffère de la révision distante, le badge « Mise à jour disponible » est affiché. |
| **RG-ADM-004** | La vérification de mise à jour est toujours effectuée, indépendamment de la configuration de la branche de déploiement. Cela offre une visibilité complète sur la disponibilité d'une nouvelle version, quel que soit le contexte de déploiement. |
| **RG-ADM-005** | Si la vérification de mise à jour échoue (réseau indisponible, délai d'attente dépassé, échec du repli sur `main`), aucune erreur n'est affichée à l'utilisateur et le badge de mise à jour n'apparaît pas. L'échec est journalisé. En mode Git, chaque requête au dépôt officiel conserve un délai d'attente de **3 secondes**. Seule l'absence de la branche distante (404) déclenche un repli après une première requête ; les autres erreurs ne déclenchent pas de nouvelle comparaison avec `main`. |

### Services cartographiques

| # | Règle |
|---|---|
| **RG-ADM-006** | Les clés API IGN sont affichées individuellement. Plusieurs clés peuvent être configurées (séparées par des virgules). |
| **RG-ADM-007** | Si aucune clé IGN n'est configurée, le texte « Aucune clé » est affiché. |
| **RG-ADM-008** | Si aucune clé Google Maps n'est configurée, le texte « Aucune clé » est affiché. |
| **RG-ADM-009** | Le service Geoconcept est considéré comme désactivé si aucune adresse de service n'est configurée. Dans ce cas, seul « Désactivé » est affiché. |
| **RG-ADM-010** | Si le service Geoconcept est actif, ses six paramètres de configuration sont affichés : adresse du service, couche cartographique, identifiant d'application, projection, jeton d'accès et adresse du géocodeur. |
| **RG-ADM-011** | Si la projection Geoconcept n'est pas configurée, la valeur par défaut « Non paramétrée » est affichée. |

### Authentification

| # | Règle |
|---|---|
| **RG-ADM-012** | Le libellé de configuration suit la priorité CAS → NTLM → LDAP → Base de données seule. Pour l'authentification effective, CAS puis NTLM avec identité Windows fournie sont prioritaires ; sinon le compte sélectionne LDAP ou la base locale, sans fallback (MOD-05). |
| **RG-ADM-013** | Si la méthode CAS est activée, le libellé « CAS » est affiché. |
| **RG-ADM-014** | Si la méthode NTLM est activée (et CAS ne l'est pas), le libellé « NTLM + BDD » est affiché. |
| **RG-ADM-015** | Si la méthode LDAP est activée (et ni CAS ni NTLM ne le sont), le libellé « LDAP + BDD » est affiché, suivi de l'adresse du serveur LDAP, du port et de la base de recherche. Le port par défaut est 389 s'il n'est pas explicitement configuré. LDAP et BDD correspondent à des comptes distincts selon leur case SSO, sans fallback entre les deux. |
| **RG-ADM-016** | Si aucune méthode externe n'est activée, le libellé « BDD » (base de données seule) est affiché. |
| **RG-ADM-017** | L'état du renforcement de sécurité (activé / désactivé) est affiché indépendamment de la méthode d'authentification choisie. |

### Cache

| # | Règle |
|---|---|
| **RG-ADM-018** | Si le cache est activé, trois informations sont affichées : le type d'adaptateur, l'adresse du serveur de cache et la durée de vie des entrées en secondes. |
| **RG-ADM-019** | Si le cache est désactivé, seul le texte « Désactivé » est affiché. Aucune information complémentaire n'est visible. |

### Sessions utilisateurs

| # | Règle |
|---|---|
| **RG-ADM-020** | Un utilisateur est considéré comme « connecté » si sa dernière action enregistrée remonte à moins de la durée de vie maximale d'une session (telle que configurée sur le serveur). |
| **RG-ADM-021** | La liste des utilisateurs connectés est limitée à un échantillon de 1 000 utilisateurs au maximum pour le calcul. |
| **RG-ADM-022** | Chaque utilisateur connecté est affiché avec son identifiant, son nom et prénom, son groupe, sa fonction et le badge « En ligne ». |
| **RG-ADM-023** | Si aucun utilisateur n'est connecté, le message « Aucun résultat. » est affiché à la place du tableau. |

### Composants tiers

| # | Règle |
|---|---|
| **RG-ADM-024** | L'API HTTP est considérée comme activée si et seulement si une clé de sécurité API est configurée (valeur non vide). |
| **RG-ADM-025** | Le proxy HTTP est considéré comme activé si son paramètre de configuration vaut explicitement la valeur d'activation. |
| **RG-ADM-026** | Les plugins tiers sont affichés sous forme de liste à plat séparée par des virgules. Si aucun plugin n'est configuré, le texte « Aucun » est affiché. |

### Paramètres de plateforme

| # | Règle |
|---|---|
| **RG-ADM-029** | Le chemin des fichiers est affiché à titre informatif pour permettre à l'administrateur de localiser le répertoire de stockage des fichiers gérés par l'application. |
| **RG-ADM-030** | La base de calcul de la relance automatique (commission ou visite) est affichée pour indiquer le critère utilisé pour évaluer les délais de renouvellement des dossiers. |
| **RG-ADM-031** | L'unité de périodicité (années ou mois) est affichée pour indiquer l'unité de temps utilisée dans le calcul des délais de renouvellement. |
| **RG-ADM-032** | L'état de la connexion à Plat'AU est affiché comme « Activée » ou « Désactivée » selon la configuration de la plateforme. |
| **RG-ADM-033** | Le nombre maximal de dossiers affichés est indiqué pour documenter la limite de pagination appliquée aux listes de dossiers. |

### Accès et sécurité

| # | Règle |
|---|---|
| **RG-ADM-034** | Le panneau d'administration est accessible uniquement depuis le menu d'administration. L'utilisateur doit disposer des droits d'administration. |
| **RG-ADM-035** | Le panneau est en lecture seule. Aucune action de modification de configuration n'est proposée. |

---

## 5. Cas particuliers

| Cas | Comportement attendu |
|---|---|
| **Réseau indisponible lors de la vérification de mise à jour** | Aucune erreur n'est affichée. Le badge de mise à jour n'apparaît simplement pas. L'administrateur ne peut pas distinguer « application à jour » de « vérification impossible ». |
| **Branche de déploiement définie** | La vérification de mise à jour reste **activée** et en continu. L'administrateur bénéficie d'une visibilité complète sur la disponibilité d'une nouvelle version, sans distinction de contexte de déploiement. |
| **Aucun service cartographique configuré** | Chaque service affiche indépendamment « Aucune clé » ou « Désactivé ». L'application reste fonctionnelle sans cartographie. |
| **Plusieurs clés IGN** | Les clés multiples (séparées par des virgules dans la configuration) sont affichées chacune sur une ligne distincte. |
| **Port de base de données non configuré** | L'application n'étant officiellement compatible qu'avec MySQL, le port par défaut 3306 est toujours affiché au format `hôte:3306`. |
| **Port LDAP non configuré** | Le port par défaut 389 est utilisé dans l'affichage. |
| **Projection Geoconcept absente** | La valeur « Non paramétrée » est affichée comme valeur par défaut. |
| **Aucun utilisateur connecté** | Le tableau des sessions est remplacé par le message « Aucun résultat. ». |
| **Plus de 1 000 utilisateurs dans l'annuaire** | Seuls les 1 000 premiers utilisateurs sont évalués pour déterminer les sessions actives. Des sessions actives au-delà de cette limite pourraient ne pas être affichées. |
| **Aucun plugin tiers configuré** | Le texte « Aucun » est affiché. |
| **Clé de sécurité API absente ou vide** | L'API HTTP est affichée comme « Désactivé ». |
