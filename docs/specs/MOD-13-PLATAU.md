# MOD-13 — Interopérabilité Plat'AU

## 1. Objet et périmètre

Plat'AU est la plateforme nationale des autorisations d'urbanisme. Elle permet aux services instructeurs de gérer les consultations liées aux permis de construire, d'aménager ou de démolir.

Dans Prevarisc, le module Plat'AU assure l'échange bidirectionnel entre l'application et la plateforme nationale :

- **Réception** de consultations émises par les services instructeurs via Plat'AU.
- **Transmission** de la prise en charge métier (PEC) et de l'avis rendu par la commission de sécurité.
- **Envoi** des pièces jointes associées au dossier vers Plat'AU.
- **Supervision** de la connexion à Plat'AU depuis le tableau de bord.

Le module est activable ou désactivable au niveau de la configuration de l'environnement. Lorsqu'il est désactivé, les blocs du tableau de bord Plat'AU, la vérification de connexion et le filtre de recherche par provenance sont masqués. L'ouverture d'un dossier issu de Plat'AU alors que le module est désactivé provoque une erreur.

---

## 2. Acteurs concernés

| Acteur | Rôle |
|--------|------|
| **Préventionniste** | Traite les dossiers Plat'AU : rattache l'établissement, renseigne la complétude, émet un avis, sélectionne les pièces jointes à transmettre. |
| **Responsable de commission** | Supervise l'avancement des consultations Plat'AU via le tableau de bord. |
| **Administrateur** | Active ou désactive le module Plat'AU, configure les identifiants de connexion PISTE. |
| **Plat'AU (système externe)** | Émet les consultations, reçoit les prises en charge, les avis et les pièces jointes. |

---

## 3. Flux d'échange avec Plat'AU

### 3.1 Réception d'une consultation

1. Plat'AU transmet à Prevarisc une **consultation** identifiée par un identifiant unique au format `XXX-XXX-XXX` (trois groupes de trois caractères alphanumériques séparés par des tirets).
2. Un **dossier** est créé dans Prevarisc et associé à cet identifiant de consultation.
3. Le dossier porte une **date de réponse attendue** fixée par le service instructeur.
4. Les pièces jointes accompagnant la consultation sont stockées dans un répertoire dédié aux dossiers Plat'AU.
5. Le dossier apparaît dans le bloc du tableau de bord **« Dossiers Plat'AU à traiter »** tant qu'il n'est pas rattaché à un établissement.

### 3.2 Transmission d'une prise en charge (PEC)

1. Le préventionniste ouvre le dossier Plat'AU et renseigne son état de complétude (complet ou incomplet).
2. Cette action déclenche la préparation d'une **prise en charge métier** (PEC) destinée à Plat'AU.
3. La PEC est transmise de manière asynchrone. Son statut évolue au fil du traitement (voir §4.1).
4. L'écran du dossier affiche le statut de la PEC et, le cas échéant, sa date de transmission.

### 3.3 Transmission d'un avis

1. Le préventionniste saisit l'**avis de la commission** sur le dossier (favorable, défavorable, etc.).
2. L'avis est préparé pour envoi vers Plat'AU.
3. Lors de la saisie d'un avis sur un dossier Plat'AU, le système propose au préventionniste de **sélectionner les pièces jointes** à envoyer conjointement (voir §3.4).
4. L'avis est transmis de manière asynchrone. Son statut évolue au fil du traitement (voir §4.1).
5. L'écran du dossier affiche le statut de l'avis et, le cas échéant, sa date de transmission.

### 3.4 Transmission des pièces jointes

1. Lorsque le préventionniste modifie la complétude ou l'avis d'un dossier Plat'AU, le système affiche la liste des pièces jointes **non encore envoyées** vers Plat'AU.
2. Le préventionniste **coche les pièces jointes** qu'il souhaite transmettre.
3. Un bandeau d'avertissement rappelle de sélectionner les documents à envoyer vers Plat'AU.
4. Lors de la sauvegarde du dossier, les pièces jointes cochées passent au statut **« En attente d'envoi »** et sont transmises de manière asynchrone.
5. L'écran des pièces jointes d'un dossier Plat'AU affiche une colonne supplémentaire indiquant le **statut d'envoi** de chaque pièce (voir §4.1).
6. Les nouvelles pièces jointes reçues de Plat'AU sont signalées par un badge **« Nouveau ! »** dans la liste des pièces jointes du dossier.

---

## 4. Suivi des transmissions

### 4.1 Statuts de transmission

#### Statuts de la prise en charge (PEC)

| Statut | Libellé affiché | Couleur | Description |
|--------|----------------|---------|-------------|
| Inconnu | Statut inconnu | Gris | La PEC n'a pas encore été initiée ou le statut n'est pas déterminé. |
| En attente | En attente de complétude sur Prevarisc | Bleu | Le dossier a été reçu mais la complétude n'a pas encore été renseignée. |
| Transmise | Prise en compte sur Plat'AU | Vert | La PEC a été transmise et acceptée par Plat'AU. |
| En attente de renvoi | Prise en compte sur Plat'AU (en attente de renvoi) | Orange | Une relance manuelle a été demandée, la PEC sera retransmise. |
| En erreur | En erreur sur Plat'AU | Rouge | La transmission de la PEC a échoué sur Plat'AU. |

Lorsque le statut est « En attente » et que l'état de complétude du dossier n'est pas renseigné dans Prevarisc, un message d'information invite le préventionniste à modifier le dossier pour le compléter.

#### Statuts de l'avis

| Statut | Libellé affiché | Couleur | Description |
|--------|----------------|---------|-------------|
| Inconnu | Statut inconnu | Gris | L'avis n'a pas encore été initié ou le statut n'est pas déterminé. |
| En cours | En attente de complétude sur Prevarisc | Bleu | L'avis est en préparation dans Prevarisc. |
| Traité | Traité sur Plat'AU | Vert | L'avis a été transmis et accepté par Plat'AU. |
| En attente de renvoi | Traité sur Plat'AU (en attente de renvoi) | Orange | Une relance manuelle a été demandée, l'avis sera retransmis. |
| En erreur | En erreur sur Plat'AU | Rouge | La transmission de l'avis a échoué sur Plat'AU. |

#### Statuts d'envoi des pièces jointes

| Statut | Libellé affiché | Couleur | Description |
|--------|----------------|---------|-------------|
| Non envoyée | Non envoyée | Gris | La pièce jointe n'a pas été sélectionnée pour envoi. |
| En attente d'envoi | En attente d'envoi | Bleu | La pièce jointe est en file d'attente pour transmission. |
| En attente de retour | En attente de retour | Orange | La pièce jointe a été envoyée, en attente de confirmation de Plat'AU. |
| Envoyée | Envoyée | Vert | La pièce jointe a été transmise et acceptée par Plat'AU. |
| En erreur | En erreur | Rouge | L'envoi a échoué. Un message d'erreur est affiché. Un lien **« Renvoyer »** permet de relancer. |

### 4.2 Relance manuelle

En cas d'échec ou pour forcer un renvoi, l'utilisateur dispose de trois mécanismes de relance :

| Élément | Condition d'affichage du bouton de relance | Effet |
|---------|-------------------------------------------|-------|
| **PEC** | La PEC est au statut « Transmise » **et** aucun avis n'a encore été transmis. | Remet la PEC au statut « En attente de renvoi » pour retransmission. |
| **Avis** | L'avis est au statut « Traité ». | Remet l'avis au statut « En attente de renvoi » pour retransmission. |
| **Pièce jointe** | La pièce jointe est au statut « En erreur ». | Remet la pièce jointe au statut « En attente d'envoi » pour retransmission. |

**Restriction de suppression** : une pièce jointe dont le statut est « En attente d'envoi », « Envoyée » ou « En attente de retour » **ne peut pas être supprimée** par l'utilisateur.

---

## 5. Supervision de la connexion

### 5.1 Vérification automatique au chargement du tableau de bord

À chaque affichage du tableau de bord, si le module Plat'AU est activé et si la vérification n'est pas explicitement désactivée, le système effectue un **contrôle de santé** de la connexion à Plat'AU.

### 5.2 Processus de vérification

1. Le système s'authentifie auprès du service d'authentification **PISTE** (protocole OAuth2, mode « client credentials ») à l'aide des identifiants configurés par l'administrateur.
2. Le jeton d'accès obtenu est réutilisé tant qu'il est valide (avec une marge de sécurité de 5 minutes avant expiration).
3. Le système interroge le point de vérification de santé de Plat'AU avec ce jeton.
4. La réponse est analysée : l'état général et l'état de la base de données de Plat'AU sont vérifiés.
5. Les informations de version et de maintenance (si présentes) sont extraites de la réponse.

### 5.3 Résultats affichés

#### En cas de succès

| Élément | Affichage |
|---------|-----------|
| Message | « La connexion à Plat'AU a fonctionnée. » |
| Type | Information (bleu) |
| Pièces supplémentaires | Version Plat'AU, version du modèle, version nomenclature (si disponibles) affichées en caractères réduits |

#### En cas de maintenance programmée (même en cas de succès)

| Élément | Affichage |
|---------|-----------|
| Message | Toast indépendant sans fermeture automatique avec les informations de maintenance en cours ou prévue |
| Type | Avertissement (orange) |
| Contenu | Type de maintenance, description, date et heure de la prochaine maintenance (le cas échéant) |

#### En cas d'erreur

| Condition | Message affiché | Type d'alerte |
|-----------|----------------|---------------|
| Prevarisc : configuration ou réseau | « La connexion à Plat'AU depuis Prevarisc a échouée. Veuillez suivre les instructions du Manuel Utilisateur §6.17.2 » | Erreur (rouge) |
| Plat'AU : état général dégradé | « Plat'AU est en erreur (état général dégradé). Veuillez réessayer ultérieurement. » | Erreur (rouge) |
| Plat'AU : base de données dégradée | « Plat'AU est en erreur (base de données dégradée). Veuillez réessayer ultérieurement. » | Erreur (rouge) |
| Plat'AU : code HTTP non-2xx | « Plat'AU est en erreur (HTTP XXX). Veuillez réessayer ultérieurement. » | Erreur (rouge) |
| Erreur technique | « Une erreur s'est produite lors de la vérification de la connexion à Plat'AU. » | Erreur (rouge) |

### 5.4 Classification des erreurs

Le système différencie deux origines d'erreur :

- **Erreur Prevarisc** : Impossible de contacter Plat'AU (configuration absente, réseau indisponible, token PISTE inaccessible, réponse JSON malformée).
- **Erreur Plat'AU** : Plat'AU a répondu mais signale un problème (code HTTP 5xx, état général dégradé, base de données dégradée).

Cette distinction guide l'administrateur vers le responsable approprié (configuration locale vs. incident sur Plat'AU).

### 5.5 Désactivation de la vérification

La vérification peut être désactivée indépendamment du module Plat'AU via un paramètre de configuration dédié. Cela permet de conserver les fonctionnalités Plat'AU sans vérifier la connexion à chaque chargement du tableau de bord.

---

## 6. Règles de gestion

### Activation du module

| # | Règle |
|---|-------|
| **RG-PLT-001** | Le module Plat'AU peut être activé ou désactivé par configuration d'environnement. Lorsqu'il est désactivé, les blocs du tableau de bord, la vérification de connexion et le filtre de recherche par provenance sont masqués. |
| **RG-PLT-002** | Si un dossier est issu de Plat'AU (identifiant de consultation présent) et que le module est désactivé, l'ouverture de ce dossier est bloquée avec un message d'erreur. |

### Identification des dossiers

| # | Règle |
|---|-------|
| **RG-PLT-003** | Un dossier est considéré comme provenant de Plat'AU si et seulement s'il possède un identifiant de consultation Plat'AU. |
| **RG-PLT-004** | L'identifiant de consultation Plat'AU est au format `XXX-XXX-XXX` (trois groupes de trois caractères alphanumériques majuscules, séparés par des tirets). |
| **RG-PLT-005** | L'identifiant de consultation est affiché dans l'en-tête du dossier et dans la section « Informations sur Plat'AU ». |

### Réception de consultation

| # | Règle |
|---|-------|
| **RG-PLT-006** | Chaque consultation reçue de Plat'AU est associée à un dossier Prevarisc et porte une date de réponse attendue. |
| **RG-PLT-007** | Le temps restant avant la date de réponse attendue est calculé et affiché dans le dossier avec un code couleur indiquant l'urgence. |

### Prise en charge (PEC)

| # | Règle |
|---|-------|
| **RG-PLT-008** | À la réception d'une consultation, le statut de la PEC est initialisé à « En attente ». |
| **RG-PLT-009** | La PEC est transmise lorsque le préventionniste renseigne l'état de complétude du dossier (complet ou incomplet). |
| **RG-PLT-010** | La relance manuelle de la PEC n'est possible que si la PEC est au statut « Transmise » **et** qu'aucun avis n'a encore été transmis (pas de date d'avis). |
| **RG-PLT-011** | La relance remet la PEC au statut « En attente de renvoi ». La retransmission effective est assurée par le processus asynchrone. |

### Avis

| # | Règle |
|---|-------|
| **RG-PLT-012** | À la réception d'une consultation, le statut de l'avis est initialisé à « En cours ». |
| **RG-PLT-013** | L'avis est transmis lorsque le préventionniste saisit un avis de commission sur le dossier. |
| **RG-PLT-014** | La relance manuelle de l'avis n'est possible que si l'avis est au statut « Traité ». |
| **RG-PLT-015** | La relance remet l'avis au statut « En attente de renvoi ». La retransmission effective est assurée par le processus asynchrone. |

### Pièces jointes

| # | Règle |
|---|-------|
| **RG-PLT-016** | Les pièces jointes d'un dossier Plat'AU sont stockées dans un emplacement spécifique, distinct du stockage standard des pièces jointes Prevarisc. |
| **RG-PLT-017** | Seules les pièces jointes au statut « Non envoyée » sont proposées à l'utilisateur pour sélection et envoi vers Plat'AU. |
| **RG-PLT-018** | La sélection de pièces jointes à envoyer est proposée lorsque l'utilisateur modifie la complétude ou l'avis d'un dossier Plat'AU. |
| **RG-PLT-019** | L'envoi effectif est déclenché lors de la sauvegarde du dossier. Les pièces cochées passent au statut « En attente d'envoi ». |
| **RG-PLT-020** | Une pièce jointe au statut « En attente d'envoi », « Envoyée » ou « En attente de retour » ne peut pas être supprimée. |
| **RG-PLT-021** | En cas d'erreur d'envoi, un message d'erreur est affiché et un lien « Renvoyer » permet de relancer l'envoi unitairement. La relance remet la pièce au statut « En attente d'envoi ». |
| **RG-PLT-022** | Les nouvelles pièces jointes reçues de Plat'AU sont signalées par un badge « Nouveau ! » basé sur la date de dernière consultation de la page par l'utilisateur. |
| **RG-PLT-023** | Chaque pièce jointe issue de Plat'AU affiche son identifiant Plat'AU d'origine sous le nom du fichier. |

### Tableau de bord

| # | Règle |
|---|-------|
| **RG-PLT-024** | Le tableau de bord affiche trois blocs dédiés à Plat'AU, visibles uniquement si le module est activé et si l'utilisateur dispose des droits nécessaires. |
| **RG-PLT-025** | Le bloc **« Dossiers Plat'AU à traiter »** liste les dossiers issus de Plat'AU qui ne sont pas encore rattachés à un établissement. |
| **RG-PLT-026** | Le bloc **« Dossiers Plat'AU avec des pièces jointes non envoyées »** liste les dossiers dont l'avis a été traité sur Plat'AU mais qui comportent des pièces jointes en erreur, en attente d'envoi ou en attente de retour. |
| **RG-PLT-027** | Le bloc **« Dossiers Plat'AU avec de nouvelles pièces jointes »** liste les dossiers ayant reçu de nouvelles pièces jointes depuis la dernière consultation de la page par l'utilisateur. |
| **RG-PLT-028** | La consultation du bloc « Dossiers Plat'AU à traiter » met à jour la date de dernière visite, ce qui réinitialise la détection des nouveautés. |

### Supervision de la connexion

| # | Règle |
|---|-------|
| **RG-PLT-029** | La vérification de connexion est exécutée automatiquement au chargement du tableau de bord si le module Plat'AU est activé et si la vérification n'est pas désactivée. |
| **RG-PLT-030** | L'authentification PISTE utilise le protocole OAuth2 en mode « client credentials ». Les identifiants sont définis dans la configuration Plat'AU. |
| **RG-PLT-031** | Le jeton d'authentification PISTE est mis en cache et réutilisé tant qu'il est valide, avec une marge de sécurité de 5 minutes avant son expiration. |
| **RG-PLT-032** | La communication avec Plat'AU peut transiter par un serveur proxy si celui-ci est configuré dans l'environnement. |
| **RG-PLT-033** | Le système détecte le code HTTP de la réponse Plat'AU et classe l'erreur comme « Prevarisc » (impossible de joindre) ou « Plat'AU » (réponse 5xx ou état dégradé). |
| **RG-PLT-034** | En cas de succès, les informations de version Plat'AU (version, versionModel, versionNomenclature) sont extraites et affichées en caractères réduits dans le toast. |
| **RG-PLT-035** | Si des informations de maintenance sont présentes (active ou programmée), un toast indépendant sans fermeture automatique est affiché en parallèle du toast de succès/erreur. |
| **RG-PLT-036** | Les messages d'erreur et de maintenance sont construits côté serveur, en français et échappés HTML pour prévenir les injections XSS. |
| **RG-PLT-037** | En cas de maintenance programmée, la date et heure sont formatées côté serveur au format « JJ/MM/AAAA à HHhMM ». |

### Recherche

| # | Règle |
|---|-------|
| **RG-PLT-038** | La recherche de dossiers propose un filtre par **provenance** : « Provenant de Plat'AU » ou « Ne provenant pas de Plat'AU ». Ce filtre n'est visible que si le module est activé. |
| **RG-PLT-039** | La saisie d'un identifiant au format `XXX-XXX-XXX` dans le champ de recherche libre déclenche automatiquement une recherche par identifiant de consultation Plat'AU. |
| **RG-PLT-040** | L'export tableur des résultats de recherche inclut une colonne « Identifiant Plat'AU » lorsque le filtre de provenance Plat'AU est sélectionné. |

### Droits d'accès

| # | Règle |
|---|-------|
| **RG-PLT-041** | L'accès au bloc « Dossiers Plat'AU à traiter » est soumis à un droit spécifique. |
| **RG-PLT-042** | L'accès au bloc « Dossiers Plat'AU avec des pièces jointes non envoyées » est soumis à un droit spécifique. |
| **RG-PLT-043** | L'accès au bloc « Dossiers Plat'AU avec de nouvelles pièces jointes » est soumis à un droit spécifique. |

---

## 7. Cas particuliers et limites

### 7.1 Statut inconnu

Lorsqu'une consultation est reçue sans information de statut pour la PEC ou l'avis, le système affiche « Statut inconnu » en gris. Ce cas survient lors de la réception initiale ou en cas d'incohérence de données.

### 7.2 Dossier Plat'AU sans état de complétude

Si un dossier Plat'AU a une PEC « En attente » et que la complétude n'est pas renseignée dans Prevarisc, un message d'alerte invite le préventionniste à modifier le dossier pour renseigner l'information. Aucune transmission ne peut être déclenchée tant que la complétude n'est pas définie.

### 7.3 Stockage spécifique des pièces jointes Plat'AU

Les pièces jointes associées à un dossier Plat'AU utilisent un chemin de stockage distinct de celui des pièces jointes classiques. Toutes les opérations de lecture, d'ajout et de suppression de fichiers tiennent compte de cette particularité. Ce fonctionnement s'applique de manière transparente à l'ensemble de la gestion des pièces jointes (consultation, ajout, suppression, vérification d'existence).

### 7.4 Module désactivé avec dossiers Plat'AU existants

Si le module est désactivé alors que des dossiers Plat'AU existent en base, ces dossiers ne sont plus accessibles. Toute tentative d'ouverture d'un dossier portant un identifiant de consultation Plat'AU provoque une erreur explicite.

### 7.5 Relance PEC limitée avant avis

La relance de la prise en charge n'est plus proposée dès lors qu'un avis a été transmis. Ce comportement garantit la cohérence chronologique des échanges avec Plat'AU : une fois l'avis transmis, la PEC ne doit plus être modifiée.

### 7.6 Sélection conditionnelle des pièces jointes à exporter

La liste des pièces jointes à envoyer vers Plat'AU n'apparaît que dans deux contextes : la modification de la complétude et la saisie de l'avis. Si aucune pièce jointe non envoyée n'existe, aucune liste n'est affichée.

### 7.7 Détection des nouvelles pièces jointes

La détection de nouvelles pièces jointes repose sur la date de dernière visite de l'utilisateur sur la page des pièces jointes du dossier. Cette date est propre à chaque session utilisateur. Deux utilisateurs différents verront donc des pièces « nouvelles » de manière indépendante.

### 7.8 Proxy réseau

Les communications avec les services PISTE et Plat'AU peuvent transiter par un proxy HTTP configurable (protocole, hôte, port, authentification). En l'absence de configuration proxy, les appels sont directs.

### 7.9 Pièces jointes avec métadonnées Plat'AU

Les pièces jointes transmises par Plat'AU peuvent porter des métadonnées supplémentaires : type, sous-type, date de dépôt et nature. Ces informations sont affichées dans la colonne description de la liste des pièces jointes lorsque la description libre est absente.
