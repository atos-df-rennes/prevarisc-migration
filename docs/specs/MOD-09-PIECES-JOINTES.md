# MOD-09 — Pièces jointes

## 1. Objet et périmètre

Ce module permet de déposer, consulter, télécharger et supprimer des fichiers associés aux principales entités de Prevarisc. Il gère également la transmission de pièces jointes vers la plateforme Plat'AU pour les dossiers issus de cette source.

Le périmètre couvre :
- Le dépôt de fichiers (formulaire ou glisser-déposer)
- La consultation et le téléchargement unitaire ou groupé
- La suppression de fichiers
- La classification spécifique aux établissements (plans, diaporamas)
- La sélection de pièce jointe pour passage en commission
- L'export et le suivi de statut vers Plat'AU

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|---------------------|
| **Utilisateur métier** (instructeur, préventionniste) | Dépose, consulte, télécharge et supprime des pièces jointes. Sélectionne les pièces à envoyer vers Plat'AU. |
| **Plat'AU** (système externe) | Reçoit les pièces jointes exportées. Transmet les pièces jointes entrantes rattachées aux dossiers de consultation. |

---

## 3. Entités pouvant avoir des pièces jointes

| Entité parente | Description |
|----------------|-------------|
| **Dossier** | Dossier d'instruction (étude, visite, etc.). Un dossier peut être lié à Plat'AU. |
| **Établissement** | Établissement recevant du public (ERP) ou autre. Supporte une classification spéciale pour les images (plans, diaporamas). |
| **Date de commission** | Séance de commission de sécurité planifiée à une date donnée. |

Une pièce jointe est toujours rattachée à **une seule** entité parente.

Lors du dépôt sur un dossier, l'utilisateur peut en outre **lier la pièce jointe à un ou plusieurs établissements** associés à ce dossier.

---

## 4. Données d'une pièce jointe

### 4.1 Informations principales

| Donnée | Description |
|--------|-------------|
| Nom | Libellé affiché. Par défaut, le nom du fichier d'origine (sans extension). L'utilisateur peut le personnaliser au dépôt. |
| Extension | Extension du fichier (ex. `.pdf`, `.jpg`). |
| Description | Texte libre décrivant le contenu de la pièce jointe. |
| Date d'ajout | Date et heure de création de la pièce jointe dans le système. |

### 4.2 Métadonnées Plat'AU (dossiers Plat'AU uniquement)

Ces informations sont renseignées automatiquement par le système lorsque la pièce jointe provient de Plat'AU.

| Donnée | Description |
|--------|-------------|
| Identifiant Plat'AU | Identifiant de la pièce jointe dans Plat'AU (11 caractères). |
| Type | Catégorie du document telle que transmise par Plat'AU. |
| Sous-type | Sous-catégorie du document. |
| Nature | Nature du document (étiquette Plat'AU). |
| Date de dépôt | Date de dépôt du document dans Plat'AU. |
| Message d'erreur | Message d'erreur renvoyé par Plat'AU en cas d'échec d'export. |
| Date de notification | Date à laquelle le système a été notifié de l'arrivée de cette pièce jointe (utilisée pour le marquage « Nouveau »). |

### 4.3 Données de rattachement

| Contexte | Données complémentaires |
|----------|------------------------|
| Dossier | Indicateur de sélection pour passage en commission. |
| Établissement | Placement de l'image : liste principale, diaporama ou plans. |
| Date de commission | Aucune donnée complémentaire. |

### 4.4 Statut d'export Plat'AU

| Statut | Libellé affiché | Signification |
|--------|----------------|---------------|
| Non envoyé | « Non envoyé » | La pièce jointe n'a pas été sélectionnée pour envoi. Statut par défaut. |
| En attente d'envoi | « En attente d'envoi » | La pièce jointe a été sélectionnée pour export vers Plat'AU, en attente de traitement. |
| Vérification du statut | « Vérification du statut » | L'envoi a été effectué, le système attend la confirmation de Plat'AU. |
| Envoyé | « Envoyé » | Plat'AU a confirmé la bonne réception de la pièce jointe. |
| Erreur | « Erreur lors de l'envoi » | L'envoi vers Plat'AU a échoué. Un message d'erreur détaillé est disponible. |

---

## 5. Fonctionnalités

### 5.1 Déposer une pièce jointe

#### 5.1.1 Dépôt via formulaire

L'utilisateur clique sur « Ajouter une pièce jointe ». Une fenêtre de dialogue s'ouvre et propose :

1. **Sélection du fichier** à envoyer (obligatoire).
2. **Libellé** (facultatif) : si laissé vide, le nom d'origine du fichier est conservé.
3. **Description** (facultatif) : zone de texte libre.
4. **Établissements liés** (dossier uniquement) : liste de cases à cocher permettant de lier la pièce jointe à un ou plusieurs établissements rattachés au dossier.
5. **Placement de l'image** (établissement uniquement, si le fichier est une image) :
   - « Seulement dans la liste principale » (par défaut)
   - « Afficher l'image dans le diaporama »
   - « Afficher l'image dans les plans »

L'utilisateur valide. Une barre de progression s'affiche pendant le transfert. En cas de succès, la pièce jointe apparaît immédiatement dans la liste sans rechargement de page.

#### 5.1.2 Dépôt rapide (glisser-déposer)

L'utilisateur clique sur « Ajout rapide ». Une zone de glisser-déposer apparaît. L'utilisateur y dépose un ou plusieurs fichiers. Chaque fichier est envoyé automatiquement avec le nom d'origine et sans description. En cas de succès, la zone se masque et la pièce jointe apparaît dans la liste.

### 5.2 Consulter et télécharger une pièce jointe

#### 5.2.1 Liste des pièces jointes

La liste affiche, pour chaque pièce jointe accessible :
- **Nom** du fichier (cliquable, ouvre le téléchargement dans un nouvel onglet)
- **Description** ou, à défaut, les métadonnées Plat'AU (type, sous-type, date de dépôt, nature)
- **Date d'ajout**
- **Statut Plat'AU** (affiché uniquement pour les dossiers liés à Plat'AU)
- **Action de suppression** (si autorisée)

Les pièces jointes sont triées de la plus récente à la plus ancienne.

Les images (PNG, JPG, JPEG) s'ouvrent dans une visionneuse intégrée avec effet de transition.

#### 5.2.2 Marquage « Nouveau »

Pour les dossiers Plat'AU, les pièces jointes arrivées depuis la dernière visite de l'utilisateur sur l'onglet sont signalées par un badge « Nouveau ! ».

#### 5.2.3 Téléchargement unitaire

Un clic sur le nom du fichier déclenche le téléchargement. Le fichier est servi en téléchargement forcé (pièce jointe HTTP).

L'installation peut personnaliser le nom de téléchargement via `PREVARISC_DATASTORE_FILE_FORMAT`, pour les stockages simple et hashé. Le format s'applique aux téléchargements unitaires des pièces jointes de dossier (y compris les dossiers Plat'AU), d'établissement et de date de commission, ainsi qu'aux noms des entrées ZIP des dossiers. Si la variable est absente, la configuration Symfony fournit le format par défaut : le libellé de la pièce jointe suivi de son extension. Une variable explicitement vide est une configuration invalide et provoque une erreur ; pour retrouver le format standard, il faut supprimer la variable, pas la vider. Cette personnalisation ne modifie ni le libellé affiché, ni le fichier physique.

Le jeton `%DATE_PIECEJOINTE%` représente la date d'ajout ou de génération du document, au format `Y-m-d`, et non la date de la séance. Si cette date n'est pas renseignée, le jeton est vide. La valeur de `%NUMEROID_ETABLISSEMENT%` dépend de l'entité parente :
- Pour une pièce jointe d'établissement, le numéro de l'établissement est utilisé ; s'il n'est pas renseigné, son identifiant est utilisé.
- Pour une pièce jointe de dossier, les numéros des établissements liés au dossier sont utilisés. Si la description de la pièce contient le numéro d'un établissement lié, ce numéro est retenu ; sinon, les numéros disponibles sont assemblés avec `_`. Un établissement sans numéro est représenté par l'identifiant du dossier. Si aucun établissement n'est lié, l'identifiant du dossier est utilisé.
- Pour une pièce jointe de commission, `%NUMEROID_ETABLISSEMENT%` et `%ID_OBJECT%` représentent l'identifiant de la date de commission (séance), pas celui de la commission.

Cette configuration s'applique également aux convocations, ordres du jour, procès-verbaux et comptes-rendus générés. Par exemple, pour une séance d'identifiant `42`, une pièce jointe d'identifiant `123` générée le 5 octobre 2026, le format `%NUMEROID_ETABLISSEMENT%_%DATE_PIECEJOINTE%_%NOM_PIECEJOINTE%_%ID_PIECEJOINTE%%EXTENSION_PIECEJOINTE%` produit `42_2026-10-05_Ordre du jour_123.odt`.

#### 5.2.4 Téléchargement groupé (dossier uniquement)

Un bouton « Télécharger les pièces jointes » permet de télécharger l'ensemble des pièces jointes d'un dossier dans une archive ZIP. Le nom de chaque entrée de l'archive suit le même format configuré que le téléchargement unitaire.

### 5.3 Supprimer une pièce jointe

L'utilisateur clique sur l'icône de suppression. Une confirmation est demandée : « Êtes-vous sûr de vouloir supprimer cette pièce jointe ? ».

Si confirmé :
- Le fichier physique est supprimé du serveur.
- La miniature associée est supprimée le cas échéant.
- La liaison avec l'entité parente est supprimée.
- La pièce jointe est supprimée du système.
- La ligne disparaît de la liste sans rechargement de page.

### 5.4 Classification des pièces jointes d'établissement (plans, diaporamas)

Lors du dépôt d'une image sur un établissement, l'utilisateur choisit un emplacement de mise en avant :

| Placement | Effet |
|-----------|-------|
| Liste principale uniquement | L'image n'apparaît que dans la liste standard des pièces jointes. |
| Diaporama | L'image est affichée dans la galerie photos de la fiche établissement. Une miniature (largeur 450 px, proportions conservées) est générée automatiquement. |
| Plans | L'image est affichée dans la section plans de la fiche établissement. Une miniature (largeur 450 px, proportions conservées) est générée automatiquement. |

Seuls les formats image (JPG, JPEG, PNG, GIF) sont éligibles à la mise en avant. Pour tout autre format, l'option est ignorée.

### 5.5 Sélection pour passage en commission (dossier uniquement)

Depuis la liste des pièces jointes d'un dossier, chaque ligne comporte une case à cocher « Sélectionner pour la commission ». Lorsqu'une pièce jointe est cochée, toutes les autres cases sont désactivées : **une seule pièce jointe peut être sélectionnée à la fois** pour le passage en commission. Décocher la pièce jointe libère les autres cases.

---

## 6. Intégration Plat'AU

### 6.1 Pièces jointes à transmettre

Lors de l'enregistrement d'un dossier lié à Plat'AU, le système affiche la liste des pièces jointes au statut « Non envoyé ». L'utilisateur coche celles qu'il souhaite transmettre vers Plat'AU.

À la validation, les pièces jointes sélectionnées passent au statut « En attente d'envoi ».

### 6.2 Suivi du statut d'export

Pour les dossiers liés à Plat'AU, une colonne « Statut Plat'AU » apparaît dans la liste des pièces jointes. Le statut est représenté par un badge coloré :

| Couleur | Statut |
|---------|--------|
| Gris | Non envoyé |
| Bleu | En attente d'envoi |
| Orange | Vérification du statut |
| Vert | Envoyé |
| Rouge | Erreur lors de l'envoi |

En cas d'erreur, un pictogramme d'information affiche le message d'erreur détaillé au survol.

### 6.3 Relance manuelle

Lorsqu'une pièce jointe est en statut « Erreur », un lien « Renvoyer » apparaît à côté du badge d'erreur. En cliquant dessus, l'utilisateur repasse la pièce jointe au statut « En attente d'envoi » afin de relancer l'export.

### 6.4 Pièces jointes entrantes Plat'AU

Les pièces jointes transmises par Plat'AU lors des notifications sont automatiquement rattachées au dossier concerné. Elles portent les métadonnées Plat'AU (identifiant, type, sous-type, nature, date de dépôt) et sont stockées dans un espace de stockage dédié. Elles apparaissent avec le badge « Nouveau ! » jusqu'à la prochaine visite de l'utilisateur sur l'onglet pièces jointes du dossier.

---

## 7. Règles de gestion

| Règle | Énoncé |
|-------|--------|
| **RG-PJ-001** | Une pièce jointe est rattachée à exactement une entité parente : dossier, établissement ou date de commission. |
| **RG-PJ-002** | Les fichiers exécutables ou scripts (.php, .php4, .php5, .sh, .ksh, .csh) sont interdits au dépôt. Le système rejette le fichier avec un message d'erreur. |
| **RG-PJ-003** | Si aucun libellé n'est saisi par l'utilisateur, le nom d'origine du fichier (sans extension) est utilisé comme nom de la pièce jointe. |
| **RG-PJ-004** | La date d'ajout est enregistrée automatiquement au moment du dépôt (date et heure courantes). |
| **RG-PJ-005** | Lors du dépôt sur un dossier, l'utilisateur peut optionnellement lier la pièce jointe à un ou plusieurs établissements associés à ce dossier. |
| **RG-PJ-006** | La mise en avant dans le diaporama ou les plans ne s'applique qu'aux images (JPG, JPEG, PNG, GIF) déposées sur un établissement. |
| **RG-PJ-007** | Lorsqu'une image est placée dans le diaporama ou dans les plans, une miniature de 450 px de large est générée automatiquement (proportions conservées). |
| **RG-PJ-008** | La suppression d'une pièce jointe entraîne la suppression du fichier physique, de sa miniature éventuelle et de toutes ses liaisons. |
| **RG-PJ-009** | Le dépôt et la suppression sont interdits lorsque le dossier est verrouillé. |
| **RG-PJ-010** | La suppression est interdite pour les pièces jointes dont le statut Plat'AU est « En attente d'envoi », « Envoyé » ou « Vérification du statut ». |
| **RG-PJ-011** | Seules les pièces jointes dont le fichier physique est accessible en lecture sont affichées dans la liste. Les fichiers manquants ou illisibles sont masqués. |
| **RG-PJ-012** | Pour un dossier, une seule pièce jointe à la fois peut être sélectionnée pour le passage en commission. |
| **RG-PJ-013** | Toute nouvelle pièce jointe d'un dossier Plat'AU reçoit par défaut le statut d'export « Non envoyé ». |
| **RG-PJ-014** | La sélection d'une pièce jointe pour export vers Plat'AU passe son statut de « Non envoyé » à « En attente d'envoi ». |
| **RG-PJ-015** | La relance manuelle d'une pièce jointe en erreur repasse son statut à « En attente d'envoi ». |
| **RG-PJ-016** | Seules les pièces jointes au statut « Non envoyé » sont proposées à l'utilisateur pour l'export vers Plat'AU. |
| **RG-PJ-017** | L'identifiant Plat'AU de la pièce jointe est affiché sous le nom du fichier lorsqu'il est renseigné (dossiers Plat'AU uniquement). |
| **RG-PJ-018** | Les pièces jointes entrantes Plat'AU sont marquées « Nouveau » si leur date de notification est postérieure à la dernière visite de l'utilisateur sur l'onglet pièces jointes. La date de dernière visite est mise à jour à chaque consultation de l'onglet. |
| **RG-PJ-019** | Le téléchargement groupé en archive ZIP n'est disponible que pour les dossiers et uniquement lorsqu'au moins une pièce jointe existe. |
| **RG-PJ-020** | Si le fichier physique est introuvable au moment du téléchargement, le système renvoie une erreur « ressource introuvable ». |
| **RG-PJ-021** | Les pièces jointes des dossiers Plat'AU sont stockées dans un espace de stockage distinct de celui des dossiers classiques. |
| **RG-PJ-022** | Si la taille du fichier dépasse la limite autorisée par le serveur, le système affiche un message invitant l'utilisateur à contacter son service informatique. |
| **RG-PJ-023** | Le nom de téléchargement unitaire et les noms des entrées ZIP d'un dossier peuvent être personnalisés à l'échelle de l'installation, indépendamment du libellé affiché et du chemin de stockage, pour les adaptateurs simple et hashé. En l'absence de variable de configuration, le libellé suivi de l'extension est conservé. Une variable explicitement vide est rejetée avec une erreur de configuration. La date utilisée dans le nom est celle d'ajout ou de génération de la pièce jointe, au format `Y-m-d`. Le jeton `%NUMEROID_ETABLISSEMENT%` désigne le numéro de l'établissement parent (ou son identifiant à défaut), les numéros des établissements liés au dossier selon la règle décrite en §5.2.3, ou l'identifiant de la séance pour une pièce jointe de commission. |
| **RG-PJ-024** | Pour un dossier, la liste, le formulaire de dépôt, le dépôt, la suppression et la vérification après dépôt exigent les droits `view_doss` sur le dossier et `modification_odj`. Le téléchargement unitaire ou groupé exige `view_doss` ; la relance Plat'AU reste soumise à `edit_doss`. Le recours à `modification_odj` reprend le droit historique, avec maintien du contrôle de lecture et du verrouillage. |
| **RG-PJ-025** | Pour un établissement, la consultation exige `view_ets` et la gestion des pièces jointes exige `edit_ets`. Pour une date de commission, la gestion exige `modification_odj`. |

---

## 8. Cas particuliers

### 8.1 Dossier Plat'AU vs dossier classique

Le comportement diffère selon que le dossier est lié à Plat'AU ou non :

| Aspect | Dossier classique | Dossier Plat'AU |
|--------|-------------------|-----------------|
| Stockage des fichiers | Espace standard organisé par entité | Espace dédié Plat'AU, fichier nommé par identifiant interne |
| Colonne « Statut Plat'AU » | Non affichée | Affichée |
| Badge « Nouveau ! » | Non affiché | Affiché pour les pièces récentes |
| Identifiant Plat'AU | Non affiché | Affiché sous le nom du fichier |
| Métadonnées (type, sous-type, nature, date de dépôt) | Non renseignées | Affichées à la place de la description si celle-ci est vide |
| Bouton « Renvoyer » | Non affiché | Affiché en cas d'erreur d'export |

### 8.2 Description vs métadonnées Plat'AU

Lorsqu'une pièce jointe a une description textuelle, celle-ci est affichée en priorité. Si la description est absente mais que des métadonnées Plat'AU existent (type, sous-type, nature, date de dépôt), ces dernières sont affichées à la place.

### 8.3 Dossier verrouillé

Lorsque le dossier est verrouillé, les boutons de dépôt et de suppression n'apparaissent pas et ces actions sont interdites. La liste et le téléchargement restent accessibles aux utilisateurs disposant des droits correspondants.

### 8.4 Fichier physique absent

Si un fichier est référencé en base mais absent ou illisible sur le serveur, la pièce jointe est exclue de la liste affichée. En cas de tentative de téléchargement direct (par URL), le système renvoie une erreur « ressource introuvable ».

### 8.5 Dépassement de la taille limite

Si la taille du fichier dépasse la limite configurée sur le serveur, le système :
- Annule la création de la pièce jointe en base.
- Affiche un message : « Impossible de charger la pièce jointe. Veuillez contacter votre service informatique. »

### 8.6 Nommage configurable des téléchargements

Le format est appliqué aux pièces de dossier et aux entrées de leurs archives ZIP, y compris lorsque le dossier est lié à Plat'AU. Deux différences sont volontaires par rapport au comportement historique : le format est appliqué aux entrées ZIP, et le jeton `%NUMEROID_ETABLISSEMENT%` d'une pièce jointe d'établissement utilise le numéro métier de l'établissement plutôt que de rester vide lorsque ce numéro existe.
