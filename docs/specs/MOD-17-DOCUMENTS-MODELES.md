# MOD-17 — Documents modèles

## 1. Objet et périmètre

Le module **Documents modèles** permet de gérer une bibliothèque de gabarits ODT utilisés pour générer automatiquement des documents officiels dans Prevarisc : rapports de visite, lettres de suite, courriers d'avis, comptes-rendus, etc.

Ces gabarits contiennent des champs de fusion (variables) qui sont automatiquement remplacés par les données réelles du dossier, de l'établissement ou de la commission au moment de la génération.

Le module couvre :

- L'administration centralisée des modèles par commission.
- La mise à disposition de modèles « transversaux » accessibles après verrouillage d'un dossier.
- Le lien avec la génération de documents depuis un dossier ou depuis le calendrier des commissions.

Le module **ne couvre pas** :

- La gestion des courriers types par membre de commission (convocations, ordres du jour). Ceux-ci sont gérés dans la fiche de chaque commission.
- La génération elle-même (traitée dans les modules Dossier et Calendrier des commissions).

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|---|---|
| **Administrateur** | Accède à l'écran de gestion des documents modèles (menu Administration). Ajoute, remplace et supprime les modèles. |
| **Instructeur / Préventionniste** | Utilise les modèles lors de la génération de rapports depuis un dossier. Ne peut pas modifier la bibliothèque de modèles. |
| **Membre de commission** | Destinataire indirect : reçoit les documents générés à partir des modèles (convocations, ordres du jour, procès-verbaux, comptes-rendus). |

---

## 3. Types de documents modèles

L'application distingue deux familles de modèles, stockés dans des emplacements séparés :

### 3.1 Modèles de rapports (par commission ou transversaux)

Ces modèles servent à générer des rapports depuis un dossier. Ils contiennent des champs de fusion relatifs au dossier et à l'établissement.

Exemples de champs de fusion disponibles :

| Champ | Donnée insérée |
|---|---|
| Date du jour | Date de génération du document |
| Type de dossier | Visite périodique, étude, réception de travaux, etc. |
| Nature du dossier | ERP, IGH, habitation, etc. |
| Libellé de la commission | Nom de la commission compétente |
| Identifiant établissement | Numéro Prevarisc de l'établissement |
| Objet du dossier | Description libre saisie dans le dossier |
| Analyse de risque | Anomalies constatées |
| Observations | Observations de l'instructeur |
| Descriptif | Descriptif technique du dossier |
| Criticité | Facteur de dangerosité |
| Adresse de l'établissement | Adresse complète (numéro, rue, code postal, commune) |
| Catégorie | Catégorie de l'ERP (1re à 5e) |
| Type et activité principale | Lettre de type et libellé d'activité |
| Types et activités secondaires | Lettres de types et libellés secondaires |
| Genre | Établissement, cellule, site, etc. |
| Effectifs | Public, personnel, hébergés, total |
| Coordonnées | Téléphone, fax, courriel de l'établissement |
| Dates | Date de visite, heure de visite, date de passage en commission |
| Hors délai | Oui / Non |
| Documents manquants | Liste et dates associées |
| Prescriptions | Réglementaires, d'exploitation (actuelles et reprises) |
| Informations de l'établissement parent | Pour les cellules rattachées à un ERP |
| Nom du maire | Maire de la commune de l'établissement |

### 3.2 Courriers types de commission

Gérés depuis la fiche de chaque commission (hors périmètre de ce module, mais en lien fonctionnel) :

| Type de courrier | Usage |
|---|---|
| Convocation en salle | Envoyée aux membres pour une commission en salle |
| Convocation de visite | Envoyée aux membres pour une visite sur site |
| Ordre du jour (ODJ) | Ordre du jour de la séance de commission |
| Compte-rendu (CR) | Compte-rendu de la séance, rattaché à la commission elle-même |

### 3.3 Document de compte-rendu de commission

Chaque commission peut avoir un modèle de compte-rendu qui lui est propre. Ce modèle est géré depuis la fiche de la commission (ajout, remplacement, suppression). Il est utilisé lors de la génération du compte-rendu d'une séance.

---

## 4. Organisation des modèles par commission

Les modèles de rapports sont organisés selon la hiérarchie suivante :

```
Documents modèles
├── Documents visibles après verrouillage (transversaux)
│   ├── modele-avis-favorable.odt
│   └── modele-avis-defavorable.odt
│
├── [Type de commission A]
│   ├── Commission A1
│   │   ├── rapport-visite-periodique.odt
│   │   └── lettre-de-suite.odt
│   └── Commission A2
│       └── rapport-etude.odt
│
└── [Type de commission B]
    └── Commission B1
        └── rapport-reception-travaux.odt
```

**Niveau 1 — Espace transversal :** modèles accessibles quel que soit le dossier. Ces modèles sont toujours proposés lorsque le dossier est verrouillé.

**Niveau 2 — Type de commission :** regroupement visuel des commissions par type (sous-commission départementale, commission communale, groupe de visite, etc.).

**Niveau 3 — Commission :** chaque commission dispose de son propre répertoire de modèles. Un répertoire est automatiquement créé lors de la création d'une commission.

---

## 5. Fonctionnalités

### 5.1 Consulter les modèles disponibles

**Accès :** Menu Administration → Gestion des documents.

**Affichage :**

1. L'écran affiche en premier les **documents visibles après verrouillage** dans un panel Bootstrap avec titre et badge compteur.
2. Ce panel est repliable : un clic sur le titre déroule/replie la liste des documents transversaux.
3. En dessous, les commissions sont regroupées par type de commission (titre de section).
4. Pour chaque commission, un panel Bootstrap affiche le nom suivie du nombre de modèles en badge.
5. Chaque panel est repliable : un clic sur le titre déroule/replie la liste des fichiers de la commission.
6. Un chevron (flèche) indique l'état du panel (dépliée vers le bas ou repliée vers la droite).
7. Les noms de fichiers sont affichés en texte brut, sans lien direct de téléchargement.
8. Pour chaque fichier, deux boutons d'action sont présents : « Télécharger » (accès au fichier) et « Supprimer » (suppression du modèle), alignés à droite.
9. Si une commission ne possède aucun modèle, le texte « Aucun document disponible » s'affiche dans le corps du panel, qui reste affiché avec badge 0.

### 5.2 Ajouter un modèle

**Déclencheur :** Bouton « Ajouter un document » en haut à droite de l'écran.

**Étapes :**

1. L'utilisateur clique sur le bouton « Ajouter un document ».
2. Une fenêtre modale s'ouvre avec le titre « Ajouter un document type ».
3. L'utilisateur sélectionne la commission de destination dans une liste déroulante. La première option est « Documents visibles après verrouillage ». Les commissions sont regroupées par type de commission.
4. L'utilisateur sélectionne un fichier ODT depuis son poste.
5. L'utilisateur clique sur « Envoyer ».
6. Le bouton « Envoyer » est désactivé et remplacé par une icône de rechargement animée avec le texte « Envoi en cours... ».
7. En cas de succès, la page se recharge et le nouveau modèle apparaît dans la liste de la commission sélectionnée.
8. En cas d'erreur, un message explicite est affiché.

### 5.3 Remplacer un modèle

Il n'existe pas de fonction « remplacer » explicite. Le remplacement s'effectue en envoyant un fichier portant le **même nom** que le fichier existant. Le nouveau fichier écrase l'ancien sans avertissement.

### 5.4 Supprimer un modèle

**Déclencheur :** Bouton « Supprimer » en face de chaque fichier dans la liste.

**Étapes :**

1. L'utilisateur clique sur le bouton « Supprimer ».
2. Une fenêtre de confirmation apparaît avec le message : *« Vous êtes sur le point de supprimer le fichier "[nom]". Une fois ce fichier supprimé il ne pourra plus être utilisé lors de la génération des rapports. »*
3. L'utilisateur clique sur « Supprimer » pour confirmer ou « Annuler » pour abandonner.
4. En cas de confirmation, le fichier est supprimé et la page se recharge automatiquement.
5. Après rechargement, la liste est mise à jour : le fichier supprimé disparaît et le compteur de la commission est décrémenté.
6. Si le compteur atteint zéro, le panel de la commission affiche « Aucun document disponible ».
7. En cas d'erreur (fichier introuvable, erreur système), la page se recharge et un message d'erreur est affiché.

---

## 6. Lien avec la génération de documents

### 6.1 Génération de rapports depuis un dossier

Depuis l'onglet « Rapport » d'un dossier :

1. La commission rattachée au dossier est affichée. L'utilisateur peut choisir une autre commission via une liste déroulante.
2. Les modèles de la commission sélectionnée sont affichés sous forme de boutons radio. L'utilisateur sélectionne un seul modèle.
3. L'utilisateur sélectionne un ou plusieurs établissements concernés par le dossier.
4. L'utilisateur clique sur « Générer le(s) document(s) ».
5. Un document ODT est généré pour chaque établissement coché, en fusionnant les données réelles dans le modèle choisi.
6. Le document généré est enregistré en pièce jointe du dossier.

**Comportement lié au verrouillage :**

- Si le dossier est **verrouillé**, seuls les « documents visibles après verrouillage » sont proposés. La liste des commissions n'est pas affichée.
- Si le dossier n'est **pas verrouillé**, la commission du dossier est présélectionnée et l'utilisateur peut naviguer entre toutes les commissions.

**Option spécifique :** pour les établissements de type « site » (activité principale correspondante), une case à cocher permet de ne pas reprendre les prescriptions des cellules rattachées.

### 6.2 Génération de documents depuis le calendrier des commissions

Depuis une séance de commission, quatre types de documents peuvent être générés :

| Document | Source du modèle | Destinataires |
|---|---|---|
| **Convocation** | Courrier de convocation rattaché à chaque membre | Membres de la commission concernés par les dossiers |
| **Ordre du jour** | Courrier ODJ rattaché à chaque membre | Membres de la commission |
| **Procès-verbal** | Modèles de rapports de la commission (même bibliothèque que le module) | Document officiel de la séance |
| **Compte-rendu** | Modèle de compte-rendu rattaché à la commission | Document officiel de la séance |

Pour les convocations et ordres du jour, la génération utilise les courriers types rattachés individuellement à chaque membre de commission. Chaque membre ne reçoit que les informations des dossiers correspondant à ses compétences (catégories, types d'activité, classes, types de dossier).

---

## 7. Règles de gestion

| Référence | Règle |
|---|---|
| **RG-DOC-001** | Seuls les fichiers au format ODT sont acceptés. Tout autre format est rejeté avec un message d'erreur explicite. |
| **RG-DOC-002** | Chaque commission dispose de son propre espace de stockage de modèles. Un espace est automatiquement créé lors de la création d'une nouvelle commission. |
| **RG-DOC-003** | L'espace « Documents visibles après verrouillage » est transversal, non rattaché à une commission. Il est accessible depuis tous les dossiers. |
| **RG-DOC-004** | Lorsqu'un dossier est verrouillé, seuls les modèles de l'espace transversal (après verrouillage) sont proposés à la génération. Les modèles spécifiques aux commissions ne sont plus accessibles. |
| **RG-DOC-005** | Lorsqu'un dossier n'est pas verrouillé, la commission du dossier est présélectionnée par défaut. L'utilisateur peut choisir les modèles d'une autre commission. |
| **RG-DOC-006** | L'envoi d'un fichier portant le même nom qu'un fichier existant écrase silencieusement l'ancien fichier, sans avertissement ni confirmation. |
| **RG-DOC-007** | La suppression d'un modèle est irréversible. Un message de confirmation est obligatoire avant suppression. Le message avertit que le fichier ne pourra plus être utilisé pour la génération. |
| **RG-DOC-008** | Le nom du fichier est conservé tel quel (nom original du fichier envoyé par l'utilisateur). Il sert d'identifiant unique au sein d'une commission. |
| **RG-DOC-009** | Les modèles sont triés par ordre alphabétique du nom de fichier dans chaque commission. |
| **RG-DOC-010** | L'écran de gestion des documents modèles est accessible depuis le menu Administration uniquement. |
| **RG-DOC-011** | Lors de la génération d'un rapport depuis un dossier, l'utilisateur doit sélectionner au moins un modèle et au moins un établissement. Si l'un des deux manque, un message d'avertissement s'affiche et la génération est bloquée. |
| **RG-DOC-012** | Un document est généré par établissement sélectionné. Si trois établissements sont cochés, trois documents sont produits à partir du même modèle. |
| **RG-DOC-013** | Les commissions sont regroupées visuellement par type de commission, aussi bien dans l'écran d'administration que dans les listes déroulantes de sélection. |
| **RG-DOC-014** | Le modèle de compte-rendu d'une commission est unique : l'envoi d'un nouveau modèle supprime automatiquement le précédent. |
| **RG-DOC-015** | Les courriers types des membres de commission (convocation salle, convocation visite, ordre du jour) sont distincts des modèles de rapports. Ils sont gérés depuis la fiche de la commission et non depuis l'écran d'administration des documents. |
| **RG-DOC-016** | Les champs de fusion présents dans un modèle qui ne correspondent à aucune donnée disponible sont remplacés par une valeur vide (chaîne vide). Aucune erreur n'est levée. |

---

## 8. Cas particuliers

### 8.1 Commission sans modèle

Une commission sans aucun modèle est affichée dans la liste d'administration sans compteur ni lien cliquable. Dans le formulaire de génération, la mention « Aucun fichier pour cette commission » est affichée.

### 8.2 Dossier sans commission rattachée

Si la commission du dossier n'est pas renseignée, un message d'avertissement est affiché : *« La commission du dossier n'est pas renseignée »*. L'utilisateur peut tout de même sélectionner une commission dans la liste déroulante.

### 8.3 Écrasement silencieux

L'envoi d'un fichier dont le nom est identique à un fichier déjà présent dans la même commission entraîne son remplacement sans confirmation. C'est le seul moyen de « mettre à jour » un modèle.

### 8.4 Établissement de type site (cellules rattachées)

Lors de la génération pour un établissement dont l'activité principale est « site », une option permet d'exclure les prescriptions issues des cellules rattachées. Par défaut, les prescriptions des cellules sont reprises.

### 8.5 Répertoire de documents inexistant

Si le répertoire de stockage d'une commission n'existe pas au moment de la consultation, l'application affiche une alerte non bloquante pour cette commission tout en continuant à afficher les éléments sans erreur. Cela permet à l'administrateur de visualiser les commissions fonctionnelles tout en étant averti des problèmes de répertoires manquants. La création du répertoire se fait automatiquement lors de la création de la commission, mais une intervention manuelle peut être nécessaire en cas de suppression accidentelle.

### 8.6 Suppression d'une commission

Lors de la suppression d'un membre de commission, ses courriers types (convocation salle, convocation visite, ordre du jour) sont automatiquement supprimés. En revanche, la suppression d'une commission ne supprime pas automatiquement les modèles de rapports associés.

### 8.7 Génération de convocations filtrée par compétences

Les convocations ne sont générées que pour les membres dont les compétences (catégories, types d'activité, classes, types de dossier) correspondent aux dossiers inscrits à l'ordre du jour. Un membre sans compétence sur les dossiers traités ne reçoit pas de convocation.

### 8.8 Contact de commune non renseigné

Lors de la génération de l'ordre du jour, si le contact de la mairie d'une commune concernée n'est pas renseigné, un avertissement est affiché. Les champs d'adresse de la mairie sont laissés vides dans le document généré.
