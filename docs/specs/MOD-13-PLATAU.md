# MOD-13 — Interopérabilité Plat'AU

## 1. Objet et périmètre

Plat'AU est la plateforme nationale des autorisations d'urbanisme. Elle permet aux services instructeurs de gérer les consultations liées aux permis de construire, d'aménager ou de démolir.

Dans Prevarisc, le module Plat'AU assure l'échange bidirectionnel entre l'application et la plateforme nationale :

- **Réception** de consultations émises par les services instructeurs via Plat'AU.
- **Transmission** de la prise en charge métier (PEC) et de l'avis rendu par la commission de sécurité.
- **Envoi** des pièces jointes associées au dossier vers Plat'AU.
- **Supervision** de la connexion à Plat'AU depuis l'en-tête de l'application.

L'utilisation de l'intégration avec Plat'AU dépend du paramétrage du service. Lorsqu'elle n'est pas activée, les informations Plat'AU du tableau de bord, l'indicateur de disponibilité et le filtre de recherche par origine ne sont pas affichés. Les dossiers reçus de Plat'AU ne peuvent alors pas être ouverts.

---

## 2. Acteurs concernés

| Acteur | Rôle |
|--------|------|
| **Préventionniste** | Traite les dossiers Plat'AU : rattache l'établissement, renseigne la complétude, émet un avis, sélectionne les pièces jointes à transmettre. |
| **Responsable de commission** | Supervise l'avancement des consultations Plat'AU via le tableau de bord. |
| **Administrateur** | Active ou désactive l'intégration et configure l'accès à Plat'AU. |
| **Plat'AU (système externe)** | Émet les consultations, reçoit les prises en charge, les avis et les pièces jointes. |

---

## 3. Flux d'échange avec Plat'AU

### 3.1 Réception d'une consultation

1. Plat'AU transmet à Prevarisc une **consultation** identifiée par un identifiant unique au format `XXX-XXX-XXX` (trois groupes de trois caractères alphanumériques séparés par des tirets).
2. Un **dossier** est créé dans Prevarisc et associé à cet identifiant de consultation.
3. Le dossier porte une **date de réponse attendue** fixée par le service instructeur.
4. Les pièces jointes reçues avec la consultation sont associées au dossier.
5. Le dossier apparaît dans le bloc du tableau de bord **« Dossiers Plat'AU à traiter »** tant qu'il n'est pas rattaché à un établissement.

### 3.2 Transmission d'une prise en charge (PEC)

1. Le préventionniste ouvre le dossier Plat'AU et renseigne son état de complétude (complet ou incomplet).
2. Cette action déclenche la préparation d'une **prise en charge métier** (PEC) destinée à Plat'AU.
3. La demande de prise en charge est envoyée à Plat'AU. Son statut évolue au fil du traitement (voir §4.1).
4. L'écran du dossier affiche le statut de la PEC et, le cas échéant, sa date de transmission.

### 3.3 Transmission d'un avis

1. Le préventionniste saisit l'**avis de la commission** sur le dossier (favorable, défavorable, etc.).
2. L'avis est préparé pour envoi vers Plat'AU.
3. Lors de la saisie d'un avis sur un dossier Plat'AU, le système propose au préventionniste de **sélectionner les pièces jointes** à envoyer conjointement (voir §3.4).
4. L'avis est envoyé à Plat'AU. Son statut évolue au fil du traitement (voir §4.1).
5. L'écran du dossier affiche le statut de l'avis et, le cas échéant, sa date de transmission.

### 3.4 Transmission des pièces jointes

1. Lorsque le préventionniste modifie la complétude ou l'avis d'un dossier Plat'AU, le système affiche la liste des pièces jointes **non encore envoyées** vers Plat'AU.
2. Le préventionniste **coche les pièces jointes** qu'il souhaite transmettre.
3. Un bandeau d'avertissement rappelle de sélectionner les documents à envoyer vers Plat'AU.
4. Lors de la sauvegarde du dossier, les pièces jointes cochées passent au statut **« En attente d'envoi »**, puis sont transmises à Plat'AU.
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
| Complétude renseignée (affichage uniquement) | Complétude renseignée sur Prevarisc (en attente de transmission à Plat'AU) | Bleu primaire (`text-primary`) | Le statut stocké est « En attente » et la complétude est renseignée, que le dossier soit complet ou incomplet. |
| Transmise | Prise en compte sur Plat'AU | Vert | La PEC a été transmise et acceptée par Plat'AU. |
| En attente de renvoi | Prise en compte sur Plat'AU (en attente de renvoi) | Orange | Une relance manuelle a été demandée, la PEC sera retransmise. |
| En erreur | En erreur sur Plat'AU | Rouge | La transmission de la PEC a échoué sur Plat'AU. |

Lorsque la complétude n'est pas renseignée dans Prevarisc (valeur `null`), le libellé « En attente de complétude sur Prevarisc » suffit à indiquer l'information manquante : aucun message informatif supplémentaire n'est affiché. Cette règle s'applique en consultation comme en modification du dossier.

#### Statuts de l'avis

| Statut | Libellé affiché | Couleur | Description |
|--------|----------------|---------|-------------|
| Inconnu | Statut inconnu | Gris | L'avis n'a pas encore été initié ou le statut n'est pas déterminé. |
| En cours | En attente d'avis sur Prevarisc | Bleu | L'avis n'a pas encore été renseigné dans Prevarisc. |
| Avis renseigné (affichage uniquement) | Avis renseigné sur Prevarisc (en attente de transmission à Plat'AU) | Bleu primaire (`text-primary`) | Un avis de commission est renseigné et le statut stocké est « En cours », « Inconnu » ou absent. |
| Traité | Traité sur Plat'AU | Vert | L'avis a été transmis et accepté par Plat'AU. |
| En attente de renvoi | Traité sur Plat'AU (en attente de renvoi) | Orange | Une relance manuelle a été demandée, l'avis sera retransmis. |
| En erreur | En erreur sur Plat'AU | Rouge | La transmission de l'avis a échoué sur Plat'AU. |

Ces états d'affichage sont recalculés à chaque chargement de la fiche, en consultation et en modification, à partir des données sauvegardées du dossier et du statut de transmission. Ils ne sont jamais stockés et ne déclenchent aucun envoi. Les statuts « Transmise », « Traité », « En attente de renvoi » et « En erreur » restent prioritaires, même si les informations du dossier sont ensuite modifiées ou retirées. Les dates de transmission, boutons de renvoi et calculs du temps restant conservent leur fonctionnement existant.

Un formulaire non sauvegardé, y compris après une soumission invalide, ne change pas ces états d'affichage. Après une sauvegarde ou une mise à jour par la passerelle, le prochain chargement reflète les données enregistrées ; une page déjà ouverte ne se rafraîchit pas automatiquement.

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

## 5. Disponibilité de Plat'AU

### 5.1 Indicateur dans l'en-tête

Si l'intégration est activée et si la vérification n'a pas été désactivée, un indicateur « Plat'AU » est affiché dans l'en-tête de toutes les pages, pour tous les utilisateurs connectés.

La disponibilité n'est pas vérifiée à chaque page : le dernier résultat est conservé pendant une durée configurable (5 minutes par défaut) et partagé entre tous les utilisateurs. Les résultats en erreur sont conservés pendant la même durée. Aucune vérification n'est planifiée : une nouvelle vérification n'a lieu qu'au premier affichage d'une page après expiration du dernier résultat.

- Si un résultat conservé est disponible, l'indicateur est affiché directement avec son état, sans requête supplémentaire.
- Sinon, l'indicateur est d'abord affiché en gris (« vérification en cours »), puis mis à jour en arrière-plan une fois la vérification terminée ; l'affichage de la page n'est jamais ralenti par la vérification.

### 5.2 Informations affichées

Un clic sur l'indicateur ouvre un panneau de détails (accessible au clavier) contenant l'état, le message, la version de Plat'AU lorsqu'elle est disponible, les informations de maintenance éventuelles, ainsi que l'heure de la dernière vérification (par exemple « Dernière vérification : 15h52 »). L'heure de la prochaine vérification n'est pas affichée, celle-ci dépendant du prochain affichage de page après expiration du résultat.

| Situation | Couleur de l'indicateur | Contenu |
|-----------|-------------------------|---------|
| Plat'AU est disponible | Vert | Confirmation de disponibilité, version, modèle et nomenclature lorsqu'ils sont disponibles |
| Plat'AU est disponible avec une maintenance programmée | Orange | Confirmation de disponibilité et avertissement présentant la maintenance (type, description, date et heure de la prochaine maintenance le cas échéant) |
| Plat'AU est indisponible ou la connexion n'a pas pu être établie | Rouge | Message d'erreur indiquant que Plat'AU est indisponible ou que la connexion depuis Prevarisc a échoué ; l'utilisateur est invité à réessayer ultérieurement ou à contacter son administrateur |
| Vérification en cours ou impossible à récupérer | Gris / Rouge | « Vérification en cours » ; en cas d'échec de la requête interne, indication que l'état est inconnu |

### 5.3 Réglage de la vérification

Selon le paramétrage du service, la vérification de disponibilité peut être désactivée tout en conservant les autres fonctions Plat'AU. Dans ce cas, l'indicateur n'est pas affiché.

La durée de conservation du résultat est paramétrable (`PREVARISC_PLATAU_HEALTHCHECK_CACHE_TTL`, en secondes, 300 par défaut).

### 5.4 Configuration de la vérification

- Les identifiants PISTE sont lus dans la configuration de la passerelle Plat'AU.
- Les adresses de PISTE et de Plat'AU sont celles de la configuration de la passerelle (`PLATAU_URL`, `PISTE_ACCESS_TOKEN_URL`) si elles y sont renseignées, sinon les adresses par défaut de la passerelle.
- Si le proxy sortant de Prevarisc est activé, il est utilisé pour contacter PISTE et Plat'AU.
- Le jeton PISTE est conservé séparément jusqu'à proche expiration, afin de ne pas le renouveler à chaque vérification.

---

## 6. Règles de gestion

### Activation du module

| # | Règle |
|---|-------|
| **RG-PLT-001** | L'utilisation de l'intégration Plat'AU dépend du paramétrage du service. Lorsqu'elle n'est pas activée, les informations Plat'AU du tableau de bord, l'indicateur de disponibilité et le filtre de recherche par origine ne sont pas affichés. |
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
| **RG-PLT-044** | Si le statut stocké de la PEC est « En attente » (`awaiting`) et que la complétude est renseignée (`false` ou `true`), afficher « Complétude renseignée sur Prevarisc (en attente de transmission à Plat'AU) » en `text-primary`, sans modifier le statut stocké. Si la complétude est retirée, afficher à nouveau l'attente de complétude. Les autres statuts restent inchangés. |

### Avis

| # | Règle |
|---|-------|
| **RG-PLT-012** | Dans la passerelle actuelle (comportement observé), le statut de l'avis est positionné à « En cours » après transmission de la PEC. Avant cette étape, il peut être absent ou « Inconnu ». |
| **RG-PLT-013** | L'avis est transmis lorsque le préventionniste saisit un avis de commission sur le dossier. |
| **RG-PLT-014** | La relance manuelle de l'avis n'est possible que si l'avis est au statut « Traité ». |
| **RG-PLT-015** | La relance remet l'avis au statut « En attente de renvoi ». La retransmission effective est assurée par le processus asynchrone. |
| **RG-PLT-045** | Si un avis de commission est renseigné et que le statut stocké est « En cours » (`in_progress`), « Inconnu » (`unknown`) ou absent (`null`), afficher « Avis renseigné sur Prevarisc (en attente de transmission à Plat'AU) » en `text-primary`, sans modifier le statut stocké. Cette règle couvre les avis favorable, défavorable et « Pas d'avis ». Si l'avis est retiré, reprendre le libellé du statut stocké ; pour « En cours », afficher « En attente d'avis sur Prevarisc ». Les autres statuts restent inchangés. |

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
| **RG-PLT-029** | Si le module Plat'AU est activé et si la vérification n'est pas désactivée, l'état de la connexion est affiché dans l'en-tête de toutes les pages. Le dernier résultat est conservé et partagé pendant la durée configurée ; s'il est disponible, il est affiché immédiatement, sinon la vérification est exécutée en arrière-plan lors de l'affichage de la page (voir §5.1). |
| **RG-PLT-034** | En cas de succès, les informations de version communiquées par Plat'AU sont affichées avec le message de confirmation dans le panneau de détails de l'indicateur. |
| **RG-PLT-035** | Si des informations de maintenance sont présentes (active ou programmée), l'indicateur est orange (en cas de succès) et un avertissement présentant la maintenance est affiché dans le panneau de détails, en plus du message de succès ou d'erreur. |

### Recherche

| # | Règle |
|---|-------|
| **RG-PLT-038** | La recherche de dossiers propose un filtre par **provenance** : « Provenant de Plat'AU » ou « Ne provenant pas de Plat'AU ». Ce filtre n'est visible que si le module est activé. |
| **RG-PLT-039** | La saisie d'un identifiant au format `XXX-XXX-XXX` dans le champ de recherche libre déclenche automatiquement une recherche par identifiant de consultation Plat'AU. |
| **RG-PLT-040** | L'export tableur des résultats de recherche inclut toujours une colonne « Identifiant Plat'AU », mais elle n'est peuplée que lorsque le filtre de provenance Plat'AU est sélectionné. |

### Droits d'accès

| # | Règle |
|---|-------|
| **RG-PLT-041** | L'accès au bloc « Dossiers Plat'AU à traiter » est soumis à un droit spécifique. |
| **RG-PLT-042** | L'accès au bloc « Dossiers Plat'AU avec des pièces jointes non envoyées » est soumis à un droit spécifique. |
| **RG-PLT-043** | L'accès au bloc « Dossiers Plat'AU avec de nouvelles pièces jointes » est soumis à un droit spécifique. |

---

## 7. Cas particuliers et limites

### 7.1 Statut inconnu

Lorsqu'une consultation est reçue sans information de statut pour la PEC ou l'avis, le système affiche « Statut inconnu » en gris. Ce cas survient lors de la réception initiale ou en cas d'incohérence de données. Pour l'avis, si un avis de commission est déjà renseigné, l'état d'affichage « Avis renseigné » remplace ce libellé (RG-PLT-045).

### 7.2 Dossier Plat'AU sans état de complétude

Si un dossier Plat'AU a une PEC « En attente » et que la complétude n'est pas renseignée dans Prevarisc, seul le libellé « En attente de complétude sur Prevarisc » est affiché, sans message d'alerte supplémentaire. Aucune transmission ne peut être déclenchée tant que la complétude n'est pas définie.

### 7.3 Stockage spécifique des pièces jointes Plat'AU

Les pièces jointes associées à un dossier Plat'AU utilisent un chemin de stockage distinct de celui des pièces jointes classiques. Toutes les opérations de lecture, d'ajout et de suppression de fichiers tiennent compte de cette particularité.

### 7.4 Intégration désactivée avec des dossiers Plat'AU existants

Si l'intégration est désactivée, les dossiers qui lui sont liés ne sont plus accessibles. Un message d'erreur s'affiche lors de leur ouverture.

### 7.5 Relance PEC limitée avant avis

La relance de la prise en charge n'est plus proposée dès lors qu'un avis a été transmis. Ce comportement garantit la cohérence chronologique des échanges avec Plat'AU : une fois l'avis transmis, la PEC ne doit plus être modifiée.

### 7.6 Sélection conditionnelle des pièces jointes à transmettre

La liste des pièces jointes à envoyer vers Plat'AU n'apparaît que dans deux contextes : la modification de la complétude et la saisie de l'avis. Si aucune pièce jointe non envoyée n'existe, aucune liste n'est affichée.

### 7.7 Détection des nouvelles pièces jointes

Chaque utilisateur voit comme nouvelles les pièces jointes ajoutées depuis sa dernière consultation de l'onglet du dossier.

### 7.8 Proxy réseau

Les communications avec les services PISTE et Plat'AU peuvent transiter par un proxy HTTP configurable (protocole, hôte, port, authentification). En l'absence de configuration proxy, les appels sont directs.

### 7.9 Pièces jointes avec métadonnées Plat'AU

Les pièces jointes transmises par Plat'AU peuvent porter des métadonnées supplémentaires : type, sous-type, date de dépôt et nature. Ces informations sont affichées dans la colonne description de la liste des pièces jointes lorsque la description libre est absente.

### 7.10 Information renseignée et transmission effective

L'état « renseigné » indique seulement la présence de l'information dans Prevarisc, pas une mise en file d'attente ni une garantie d'envoi. Selon le fonctionnement observé de la passerelle, l'avis saisi avant la transmission de la PEC peut déjà être affiché comme renseigné, alors que son envoi attend l'étape de prise en compte côté Plat'AU.

La passerelle actuelle ne transmet pas automatiquement une PEC pour une consultation refusée, sauf dans les cas de renvoi ou d'erreur qu'elle prend en charge. L'état d'affichage ne lève pas cette restriction. Si la passerelle remet la PEC au statut `awaiting`, le prochain chargement recalcule son affichage selon la complétude alors enregistrée. Ces contraintes décrivent le fonctionnement actuel observé de la passerelle, sans ajouter de nouvelle règle de transmission.
