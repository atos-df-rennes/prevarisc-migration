# MOD-17 — Documents modèles

## 1. Objet et périmètre

Le module **Documents modèles** permet de gérer une bibliothèque de modèles de documents texte au format ODT, compatible avec les logiciels de traitement de texte. Ils servent à générer automatiquement des documents officiels dans Prevarisc : rapports de visite, lettres de suite, courriers d'avis, comptes-rendus, etc.

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
| Catégorie | Catégorie effective de l'établissement (1re à 5e) ; pour une cellule, celle du parent direct non supprimé (RG-ETB-062) |
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

**Niveau 3 — Commission :** chaque commission dispose de son propre répertoire de modèles. Un répertoire est automatiquement créé lors de la création d'une commission. La suppression de la commission entraîne une tentative de suppression de ce répertoire (RG-DOC-020).

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
3. L'utilisateur choisit une ou plusieurs destinations dans des cases à cocher, regroupées par type de commission. La destination transversale « Disponibles après verrouillage » est également proposée. Il s'agit d'une sélection multiple par cases à cocher, et non d'une liste déroulante multiple.
4. L'utilisateur sélectionne un fichier ODT depuis son poste.
5. L'utilisateur clique sur « Envoyer ».
6. Le bouton « Envoyer » est désactivé et remplacé par une icône de rechargement animée avec le texte « Envoi en cours... ».
7. Un seul fichier est envoyé ; en cas de succès, il est copié dans l'espace de chacune des destinations sélectionnées. La page se recharge et le modèle apparaît dans les espaces concernés.
8. En cas d'erreur de validation ou de copie, un message d'erreur est affiché. **Limite technique constatée :** les copies sont réalisées successivement sans annulation des copies déjà effectuées ; une erreur en cours de traitement peut donc laisser certaines destinations mises à jour et d'autres non. Ce comportement technique n'établit pas une règle métier d'envoi partiel.

### 5.3 Remplacer un modèle

Il n'existe pas de fonction « remplacer » explicite. Le remplacement s'effectue en envoyant un fichier portant le **même nom** qu'un fichier déjà présent dans un espace sélectionné. Le nouveau fichier écrase l'ancien sans avertissement.

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

Le modèle sélectionné doit toujours être présent dans la bibliothèque de la commission choisie (ou dans l'espace transversal). Un fichier absent, un autre format ou un chemin sortant de cette bibliothèque est refusé. Un modèle stocké sur un partage réseau Windows (chemin UNC) doit pouvoir être utilisé comme un modèle stocké localement.

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
| **Procès-verbal** | Courrier de procès-verbal rattaché à un membre de la commission | Document officiel de la séance |
| **Compte-rendu** | Modèle de compte-rendu rattaché à la commission | Document officiel de la séance |

Pour les convocations et ordres du jour, la génération utilise les courriers types rattachés individuellement à chaque membre de commission. Chaque membre ne reçoit que les informations des dossiers correspondant à ses compétences (catégories, types d'activité, classes, types de dossier).

---

## 7. Règles de gestion

| Référence | Règle |
|---|---|
| **RG-DOC-001** | Seuls les documents au format ODT, compatible avec les logiciels de traitement de texte, sont acceptés. Tout autre format est refusé avec un message explicite. |
| **RG-DOC-002** | Chaque commission dispose de son propre espace de stockage de modèles. Un espace est automatiquement créé lors de la création d'une nouvelle commission. Sa suppression est tentée lorsque la commission est supprimée (RG-DOC-020). |
| **RG-DOC-003** | L'espace « Documents visibles après verrouillage » est transversal, non rattaché à une commission. Il est accessible depuis tous les dossiers. |
| **RG-DOC-004** | Lorsqu'un dossier est verrouillé, seuls les modèles de l'espace transversal (après verrouillage) sont proposés à la génération. Les modèles spécifiques aux commissions ne sont plus accessibles. |
| **RG-DOC-005** | Lorsqu'un dossier n'est pas verrouillé, la commission du dossier est présélectionnée par défaut. L'utilisateur peut choisir les modèles d'une autre commission. |
| **RG-DOC-006** | L'envoi d'un fichier portant le même nom qu'un fichier existant dans un espace de destination sélectionné écrase silencieusement l'ancien fichier de cet espace, sans avertissement ni confirmation. |
| **RG-DOC-007** | La suppression d'un modèle est irréversible. Un message de confirmation est obligatoire avant suppression. Le message avertit que le fichier ne pourra plus être utilisé pour la génération. |
| **RG-DOC-008** | Le nom du fichier est conservé tel quel (nom original du fichier envoyé par l'utilisateur). Il sert d'identifiant unique au sein de chaque espace de stockage, commission ou transversal. |
| **RG-DOC-009** | Les modèles sont triés par ordre alphabétique du nom de fichier dans chaque commission. |
| **RG-DOC-010** | L'écran de gestion des documents modèles est accessible depuis le menu Administration uniquement. |
| **RG-DOC-011** | Lors de la génération d'un rapport depuis un dossier, l'utilisateur doit sélectionner au moins un modèle et au moins un établissement. Si l'un des deux manque, un message d'avertissement s'affiche et la génération est bloquée. |
| **RG-DOC-012** | Un document est généré par établissement sélectionné. Si trois établissements sont cochés, trois documents sont produits à partir du même modèle. |
| **RG-DOC-013** | Les commissions sont regroupées visuellement par type de commission, aussi bien dans l'écran d'administration que dans les listes déroulantes de sélection. |
| **RG-DOC-014** | Le modèle de compte-rendu d'une commission est unique : l'envoi d'un nouveau modèle supprime automatiquement le précédent. |
| **RG-DOC-015** | Les courriers types des membres de commission (convocation salle, convocation visite, ordre du jour et, le cas échéant, procès-verbal) sont distincts des modèles de rapports. Ils sont gérés depuis la fiche de la commission et non depuis l'écran d'administration des documents. |
| **RG-DOC-016** | Les champs de fusion présents dans un modèle qui ne correspondent à aucune donnée disponible sont remplacés par une valeur vide (chaîne vide). Aucune erreur n'est levée. |
| **RG-DOC-017** | Lors de l'ajout d'un modèle, l'utilisateur peut sélectionner plusieurs commissions et/ou l'espace transversal. Le même fichier est copié dans chaque espace sélectionné ; s'il y porte le nom d'un fichier existant, celui-ci est remplacé selon RG-DOC-006. |
| **RG-DOC-018** | Le champ `categorieEtab` utilise la catégorie effective définie par RG-ETB-062, pour les rapports de dossier et les documents de commission. Pour une cellule, la catégorie du parent direct non supprimé remplace la catégorie propre, y compris lorsqu'elle est vide. Le rapport conserve le premier mot du libellé (ex. `5ème`), les documents de commission conservent le libellé complet. |
| **RG-DOC-019** | Dans un rapport de dossier, les coordonnées du service instructeur sont celles du service désigné par le libellé et le type enregistrés sur le dossier. Pour une commune, la recherche historique porte sur les communes dont le libellé contient le libellé renseigné, en retenant le libellé le plus court, puis sur sa fiche mairie. Pour un groupement, la recherche porte sur le libellé exact, puis sur sa fiche de coordonnées propre (pas ses contacts liés). Sans type, sans service correspondant ou sans fiche de coordonnées, prénom, nom et courriel restent vides. Aucun repli sur la commune de l'établissement n'est effectué. Le libellé renseigné reste affiché, y compris en saisie libre. |
| **RG-DOC-020** | Lorsqu'une commission est supprimée, l'application tente de supprimer son répertoire de modèles de rapports et son contenu. Si cette suppression échoue, la commission reste supprimée et le répertoire peut subsister. Cette suppression ne concerne pas le modèle de compte-rendu propre à la commission, qui est stocké séparément. |

La règle RG-DOC-019 reprend la résolution historique des coordonnées, confirmée pour ce correctif ; le maintien du libellé affiché et l'absence de type pour une saisie libre sont les comportements convenus. Un dossier sans type doit être corrigé par resélection du service et sauvegarde (MOD-02), puis son rapport régénéré. Les rapports déjà produits ne sont pas modifiés.

---

## 8. Cas particuliers

### 8.1 Commission sans modèle

Une commission sans aucun modèle est affichée dans la liste d'administration sans compteur ni lien cliquable. Dans le formulaire de génération, la mention « Aucun fichier pour cette commission » est affichée.

### 8.2 Dossier sans commission rattachée

Si la commission du dossier n'est pas renseignée, un message d'avertissement est affiché : *« La commission du dossier n'est pas renseignée »*. L'utilisateur peut tout de même sélectionner une commission dans la liste déroulante.

### 8.3 Écrasement silencieux

L'envoi d'un fichier dont le nom est identique à un fichier déjà présent dans un espace ciblé entraîne son remplacement sans confirmation. Si plusieurs destinations sont sélectionnées, ce remplacement s'applique indépendamment dans chacune d'elles. C'est le seul moyen de « mettre à jour » un modèle.

### 8.4 Établissement de type site (cellules rattachées)

Lors de la génération pour un établissement dont l'activité principale est « site », une option permet d'exclure les prescriptions issues des cellules rattachées. Par défaut, les prescriptions des cellules sont reprises.

### 8.5 Documents indisponibles

Si les documents d'une commission ne sont pas disponibles, un avertissement est affiché pour cette commission. Les documents des autres commissions restent consultables.

### 8.6 Suppression d'un membre ou d'une commission

**Suppression d'un membre :** ses courriers types (convocation salle, convocation visite, ordre du jour et, le cas échéant, procès-verbal) sont supprimés. Lorsqu'un membre est supprimé avec sa commission, ses courriers types le sont également. Le modèle de procès-verbal est un courrier du membre, distinct des modèles de rapports de la commission.

**Suppression d'une commission :** l'application tente de supprimer ses modèles de rapports avec le répertoire propre à la commission. Si cette suppression échoue, la commission reste supprimée et le répertoire peut subsister. Le modèle de compte-rendu propre à la commission est stocké séparément et n'est pas supprimé avec ce répertoire.

Le legacy conservait le répertoire des modèles de rapports lors de la suppression d'une commission ; la tentative de suppression actuelle est un changement par rapport à ce comportement historique. Pour la suppression d'un membre, le legacy tentait de supprimer les fichiers ODJ et convocations, mais ne les supprimait pas effectivement en raison du chemin utilisé. Il ne traitait pas le courrier de procès-verbal du membre. L'application actuelle supprime les courriers types définis pour le membre, y compris son procès-verbal le cas échéant.

### 8.7 Génération de convocations filtrée par compétences

Les convocations ne sont générées que pour les membres dont les compétences (catégories, types d'activité, classes, types de dossier) correspondent aux dossiers inscrits à l'ordre du jour. Un membre sans compétence sur les dossiers traités ne reçoit pas de convocation.

### 8.8 Contact de commune non renseigné

Lors de la génération de l'ordre du jour, si le contact de la mairie d'une commune concernée n'est pas renseigné, un avertissement est affiché. Les champs d'adresse de la mairie sont laissés vides dans le document généré.

### 8.9 Erreur lors d'un ajout vers plusieurs destinations

Le traitement technique actuel copie le fichier successivement dans les espaces sélectionnés. Si une copie échoue après une ou plusieurs copies réussies, celles déjà réalisées ne sont pas annulées. L'état peut donc être partiel ; ce cas est à distinguer d'un résultat garanti tout-ou-rien, qui n'est pas implémenté.
