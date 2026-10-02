# MOD-13 — Interopérabilité Plat'AU

## 1. Objet et périmètre

Plat'AU est la plateforme nationale des autorisations d'urbanisme. Elle permet aux services instructeurs de gérer les consultations liées aux permis de construire, d'aménager ou de démolir.

Dans Prevarisc, le module Plat'AU assure l'échange bidirectionnel entre l'application et la plateforme nationale :

- **Réception** de consultations émises par les services instructeurs via Plat'AU.
- **Transmission** de la prise en charge métier (PEC) et de l'avis rendu par la commission de sécurité.
- **Envoi** des pièces jointes associées au dossier vers Plat'AU.
- **Supervision** de la connexion à Plat'AU depuis le tableau de bord.

L'utilisation de l'intégration avec Plat'AU dépend du paramétrage du service. Lorsqu'elle n'est pas activée, les informations Plat'AU du tableau de bord, la vérification de la disponibilité et le filtre de recherche par origine ne sont pas affichés. Les dossiers reçus de Plat'AU ne peuvent alors pas être ouverts.

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

## 5. Disponibilité de Plat'AU

### 5.1 Vérification à l'ouverture du tableau de bord

À l'ouverture du tableau de bord, Prevarisc vérifie la disponibilité de Plat'AU si l'intégration est activée et si cette vérification n'a pas été désactivée.

### 5.2 Informations affichées

#### Lorsque Plat'AU est disponible

| Élément | Affichage |
|---------|-----------|
| Message | Confirmation que Plat'AU est disponible |
| Type | Information (bleu) |
| Informations supplémentaires | Version de Plat'AU et informations associées, lorsqu'elles sont disponibles |

#### En cas de maintenance programmée

| Élément | Affichage |
|---------|-----------|
| Message | Avertissement présentant la maintenance en cours ou prévue |
| Type | Avertissement (orange) |
| Contenu | Type de maintenance, description, date et heure de la prochaine maintenance (le cas échéant) |

#### Lorsque Plat'AU ou la connexion est indisponible

Un message d'erreur informe l'utilisateur que Plat'AU est indisponible ou que la connexion n'a pas pu être établie. Selon le problème signalé, il est invité à réessayer ultérieurement ou à contacter son administrateur.

### 5.3 Réglage de la vérification

Selon le paramétrage du service, la vérification de disponibilité peut être désactivée tout en conservant les autres fonctions Plat'AU. Dans ce cas, la disponibilité n'est plus vérifiée à chaque ouverture du tableau de bord.

---

## 6. Règles de gestion

### Activation du module

| # | Règle |
|---|-------|
| **RG-PLT-001** | L'utilisation de l'intégration Plat'AU dépend du paramétrage du service. Lorsqu'elle n'est pas activée, les informations Plat'AU du tableau de bord, la vérification de disponibilité et le filtre de recherche par origine ne sont pas affichés. |
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
| **RG-PLT-011** | La relance remet la PEC au statut « En attente de renvoi » ; Plat'AU reçoit la demande lors du traitement suivant. |

### Avis

| # | Règle |
|---|-------|
| **RG-PLT-012** | À la réception d'une consultation, le statut de l'avis est initialisé à « En cours ». |
| **RG-PLT-013** | L'avis est transmis lorsque le préventionniste saisit un avis de commission sur le dossier. |
| **RG-PLT-014** | La relance manuelle de l'avis n'est possible que si l'avis est au statut « Traité ». |
| **RG-PLT-015** | La relance remet l'avis au statut « En attente de renvoi » ; Plat'AU reçoit la demande lors du traitement suivant. |

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
| **RG-PLT-034** | En cas de succès, les informations de version communiquées par Plat'AU sont affichées avec le message de confirmation. |
| **RG-PLT-035** | Si des informations de maintenance sont présentes (active ou programmée), un toast indépendant sans fermeture automatique est affiché en parallèle du toast de succès/erreur. |

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

Lorsqu'une consultation est reçue sans information de statut pour la PEC ou l'avis, le système affiche « Statut inconnu » en gris. Ce cas survient lors de la réception initiale ou en cas d'incohérence de données.

### 7.2 Dossier Plat'AU sans état de complétude

Si un dossier Plat'AU a une PEC « En attente » et que la complétude n'est pas renseignée dans Prevarisc, un message d'alerte invite le préventionniste à modifier le dossier pour renseigner l'information. Aucune transmission ne peut être déclenchée tant que la complétude n'est pas définie.

### 7.3 Intégration désactivée avec des dossiers Plat'AU existants

Si l'intégration est désactivée, les dossiers qui lui sont liés ne sont plus accessibles. Un message d'erreur s'affiche lors de leur ouverture.

### 7.4 Relance PEC limitée avant avis

La relance de la prise en charge n'est plus proposée dès lors qu'un avis a été transmis. Ce comportement garantit la cohérence chronologique des échanges avec Plat'AU : une fois l'avis transmis, la PEC ne doit plus être modifiée.

### 7.5 Sélection conditionnelle des pièces jointes à transmettre

La liste des pièces jointes à envoyer vers Plat'AU n'apparaît que dans deux contextes : la modification de la complétude et la saisie de l'avis. Si aucune pièce jointe non envoyée n'existe, aucune liste n'est affichée.

### 7.6 Détection des nouvelles pièces jointes

Chaque utilisateur voit comme nouvelles les pièces jointes ajoutées depuis sa dernière consultation de l'onglet du dossier.

### 7.7 Pièces jointes avec informations Plat'AU

Les pièces jointes transmises par Plat'AU peuvent comporter un type, un sous-type, une date de dépôt et une nature. Ces informations sont affichées à la place de la description lorsqu'elle est absente.
