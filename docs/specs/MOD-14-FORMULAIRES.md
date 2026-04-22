# MOD-14 — Formulaires personnalisés

## 1. Objet et périmètre

Le module **Formulaires personnalisés** permet à un SDIS de définir librement la structure des informations descriptives associées aux établissements et aux dossiers. Plutôt que d'imposer un jeu figé de champs, le système offre une interface d'administration dans laquelle on crée des **capsules**, des **rubriques** et des **champs** dont le type, le nom et l'ordre sont entièrement configurables.

Les données saisies dans ces formulaires apparaissent dans deux contextes de consultation et d'édition :

- Le **descriptif d'un établissement** (onglets « Descriptif » et « Effectifs & dégagements »).
- Le **descriptif d'un dossier** (onglets personnalisés du dossier, incluant les vérifications techniques et les effectifs & dégagements).

Le module couvre donc deux axes :

| Axe | Description |
|-----|-------------|
| **Administration** | Concevoir la structure des formulaires (capsules, rubriques, champs, valeurs de liste, ordonnancement). Accessible depuis le back-office d'administration. |
| **Utilisation** | Consulter et remplir les champs définis par l'administration, dans les fiches établissement et dossier. |

---

## 2. Acteurs concernés

| Acteur | Rôle vis-à-vis du module |
|--------|--------------------------|
| **Administrateur Prevarisc** | Crée, modifie, supprime et ordonne les capsules, rubriques et champs. Importe et exporte des configurations. |
| **Agent instructeur / Préventionniste** | Consulte et saisit les valeurs des champs personnalisés dans les descriptifs d'établissements et de dossiers. |
| **Lecteur** | Consulte les valeurs sans pouvoir les modifier. Les cases à cocher et les champs de type « Parent » restent visibles en lecture seule. |

---

## 3. Structure d'un formulaire personnalisé

Un formulaire personnalisé s'organise selon une hiérarchie à trois niveaux :

```
Capsule (onglet)
 └── Rubrique (section pliable)
      └── Champ (saisie unitaire)
           └── [Champ enfant] (si le champ est de type « Parent »)
```

### 3.1 Capsules

Une **capsule** représente un onglet thématique dans l'interface. Elle porte :

- Un **nom affiché** (modifiable par l'administrateur).
- Un **nom interne** (identifiant technique non visible de l'utilisateur, invariant).

Les capsules sont réparties en deux familles :

| Famille | Capsules prédéfinies |
|---------|----------------------|
| **Établissement** | « Descriptif de l'établissement », « Effectifs & dégagements de l'établissement » |
| **Dossier** | Toutes les autres capsules (ex. : vérifications techniques, effectifs & dégagements du dossier, etc.) |

L'administrateur peut **renommer** une capsule mais ne peut ni en créer de nouvelles ni en supprimer.

### 3.2 Rubriques

Une **rubrique** est une section au sein d'une capsule. Elle porte :

- Un **nom** (texte libre, obligatoire).
- Un indicateur **« Afficher par défaut »** : détermine si la rubrique est visible dès l'ouverture du descriptif ou masquée.
- Un **rang d'affichage** (position ordinale au sein de la capsule).
- Un état **ouverte / repliée** : dans le descriptif, chaque rubrique se présente sous forme de panneau pliable.

L'administrateur peut :

- Ajouter une rubrique dans une capsule.
- Modifier le nom et l'option d'affichage par défaut.
- Supprimer une rubrique (et tous ses champs associés).
- Réordonner les rubriques par glisser-déposer.

L'utilisateur final peut, lors de la consultation d'un descriptif :

- Plier ou déplier chaque rubrique.
- Masquer ou afficher les rubriques à l'aide d'une table des matières latérale avec recherche textuelle et case « Tous ».

### 3.3 Champs

Un **champ** est l'unité de saisie. Il porte :

- Un **nom** (texte libre, obligatoire, 255 caractères maximum).
- Un **type** (cf. section 3.4).
- Un **rang d'affichage** (position ordinale au sein de la rubrique).
- Un éventuel **champ parent** (lorsqu'il s'agit d'un champ enfant d'un champ de type « Parent »).

L'administrateur peut :

- Ajouter un champ à une rubrique.
- Modifier le nom d'un champ existant.
- Supprimer un champ.
- Réordonner les champs par glisser-déposer.

> **Restriction :** le type d'un champ ne peut **pas** être modifié après sa création.

### 3.4 Types de champs disponibles

| # | Type | Comportement à la saisie | Comportement en lecture seule |
|---|------|--------------------------|-------------------------------|
| 1 | **Texte** | Champ texte court (une ligne). | Affiche le texte brut. |
| 2 | **Texte long** | Zone de texte multiligne. | Affiche le texte avec retours à la ligne. |
| 3 | **Liste** | Liste déroulante à choix unique parmi des valeurs prédéfinies. Option vide « Sélectionnez un élément » par défaut. | Affiche la valeur sélectionnée. |
| 4 | **Nombre** | Champ numérique (valeur minimale : 0). | Affiche la valeur numérique. |
| 5 | **Case à cocher** | Case cochée ou décochée. Reste cliquable même en lecture seule si le mode le permet ; sinon désactivée. | Affiche l'état coché/décoché (champ désactivé). |
| 6 | **Parent** | Conteneur regroupant des champs enfants. Deux modes d'affichage possibles (cf. ci-dessous). | Affichage identique, mais les champs enfants sont en lecture seule. |
| 7 | **Date** | Champ texte avec un bouton « Aujourd'hui » pré-remplissant la date du jour. | Affiche la date formatée avec le nom du jour. |

#### Le type « Parent » en détail

Un champ de type « Parent » ne porte pas de valeur propre ; il sert de conteneur pour des **champs enfants**. Deux modes de rendu coexistent :

| Mode | Description |
|------|-------------|
| **Ligne unique** | Les champs enfants sont affichés côte à côte dans un tableau à une seule ligne. Chaque enfant occupe une colonne. |
| **Tableau** | Les champs enfants forment les colonnes d'un tableau à lignes multiples. L'utilisateur peut **ajouter**, **supprimer** et **réordonner** des lignes par glisser-déposer. |

Le choix du mode se fait via la case « Afficher comme tableau » lors de l'édition du champ parent. Un champ enfant peut être de n'importe quel type **sauf** « Parent » (pas d'imbrication récursive).

---

## 4. Fonctionnalités

### 4.1 Administrer les capsules et rubriques

**Écran principal (liste des formulaires)**

L'écran d'administration présente l'ensemble des capsules, séparées en deux panneaux :

1. **Onglets de l'établissement** — capsules liées au descriptif et aux effectifs de l'établissement.
2. **Onglets du dossier** — toutes les autres capsules.

Pour chaque capsule, on voit :

- Le nom affiché, avec un bouton pour le **renommer** (via une fenêtre modale).
- Un formulaire d'ajout de rubrique (nom + option « Afficher par défaut »).
- La liste des rubriques existantes sous forme de tableau (nom, affichage par défaut, actions modifier / supprimer), réordonnable par glisser-déposer.

**Renommer une capsule**

L'administrateur clique sur l'icône de modification à côté du nom de la capsule. Une fenêtre modale s'ouvre avec un champ « Nouveau nom ». À la validation, le nom est mis à jour sans rechargement de la page.

**Ajouter une rubrique**

L'administrateur saisit le nom et coche éventuellement « Afficher par défaut », puis clique sur « Ajouter ». La rubrique est ajoutée en dernière position dans la capsule et apparaît immédiatement dans la liste.

**Modifier une rubrique**

Un clic sur l'icône de modification ouvre l'écran de détail de la rubrique. L'administrateur peut y modifier le nom, l'option d'affichage par défaut, et gérer les champs de la rubrique. Un bouton « Sauvegarder la rubrique » enregistre les modifications ; un bouton « Annuler » ramène à la liste.

**Supprimer une rubrique**

Un clic sur l'icône de suppression supprime la rubrique et tous ses champs. L'opération est immédiate et redirige vers la liste des formulaires.

**Réordonner les rubriques**

L'administrateur fait glisser une ligne du tableau vers la position souhaitée. La nouvelle position est enregistrée automatiquement.

### 4.2 Ajouter, modifier, supprimer un champ

**Ajouter un champ**

Depuis l'écran de détail d'une rubrique, l'administrateur renseigne :

- Le **nom** du champ (obligatoire).
- Le **type** du champ (sélection dans la liste des types disponibles).
- Si le type est « Liste » : les **valeurs** initiales de la liste.

Le champ est ajouté en dernière position dans la rubrique et s'affiche immédiatement dans le tableau récapitulatif.

**Ajouter un champ enfant (sous un Parent)**

Depuis l'écran d'édition d'un champ de type « Parent », un formulaire dédié permet d'ajouter des champs enfants. La liste des types proposés **exclut** le type « Parent ».

**Modifier un champ**

L'écran d'édition du champ affiche :

- Le nom de la rubrique d'appartenance (et le nom du champ parent, le cas échéant).
- Le **nom de fusion** — identifiant utilisé pour le publipostage, calculé automatiquement à partir de la capsule, de la rubrique et du champ. Pour un champ de type « Parent » en mode tableau, un ensemble de noms de fusion de colonnes et un nom de boucle sont affichés.
- Un champ de saisie pour le **nom**.
- Le **type** (affiché mais non modifiable).
- Pour un type « Parent » : la case « Afficher comme tableau ».
- Pour un type « Liste » : l'interface de gestion des valeurs (cf. § 4.3).

À la sauvegarde, les modifications sont enregistrées et l'utilisateur est redirigé vers l'écran parent (rubrique ou champ parent).

**Supprimer un champ**

Un clic sur l'icône de suppression supprime le champ. Si le champ est un enfant, la redirection se fait vers le champ parent ; sinon, vers la rubrique.

**Réordonner les champs**

Les champs sont réordonnables par glisser-déposer dans le tableau, tant au niveau d'une rubrique qu'au sein d'un champ parent.

### 4.3 Gérer les valeurs de liste

Lorsqu'un champ est de type « Liste », l'administrateur gère ses valeurs possibles :

- **Ajouter une valeur** : un bouton « Ajouter une valeur » insère dynamiquement un champ de saisie. Plusieurs valeurs peuvent être ajoutées avant d'enregistrer.
- **Modifier une valeur existante** : chaque valeur est présentée dans un champ texte modifiable.
- **Supprimer une valeur** : un bouton « Supprimer » à côté de chaque valeur la retire immédiatement.
- **Réordonner les valeurs** : les valeurs sont réordonnables par glisser-déposer.

Les ajouts et modifications de valeurs sont enregistrés lors de la sauvegarde du champ. Les suppressions sont immédiates (sans rechargement de page).

> **Alerte** : si une liste ne contient aucune valeur, un message d'avertissement est affiché à l'administrateur : *« La liste ne possède aucune valeur, vous devriez en ajouter. »*

### 4.4 Réordonner rubriques et champs

Le réordonnancement repose sur un mécanisme de glisser-déposer (drag & drop) disponible à trois niveaux :

| Niveau | Contexte |
|--------|----------|
| **Rubriques** | Tableau des rubriques dans l'écran principal, par capsule. |
| **Champs** | Tableau des champs dans l'écran de détail d'une rubrique. |
| **Champs enfants** | Tableau des champs enfants dans l'écran d'un champ parent. |
| **Valeurs de liste** | Liste des valeurs d'un champ de type « Liste ». |
| **Lignes de tableau** | Lignes d'un champ « Parent » en mode tableau, côté saisie utilisateur. |

Chaque élément possède un **index de position** mis à jour à chaque réordonnancement. L'ordre d'affichage tient compte de cet index, puis du nom en cas d'index indéfini.

### 4.5 Importer une configuration

L'administrateur peut importer ou exporter la structure complète des formulaires personnalisés.

**Exporter**

Un bouton « Exporter la configuration » génère un fichier téléchargeable contenant la définition de toutes les capsules, rubriques, champs et valeurs de liste.

**Importer**

Un bouton « Importer une configuration » ouvre une fenêtre modale proposant :

- Un champ de téléversement pour le **fichier de configuration**.
- Une case à cocher **« Supprimer la configuration existante »**.

Si cette case est cochée, la configuration en place est intégralement supprimée **ainsi que toutes les données saisies associées**, avant d'appliquer la nouvelle. Un message d'aide avertit de cette conséquence.

Un retour visuel indique le succès ou l'échec de l'import.

---

## 5. Utilisation dans les descriptifs

### 5.1 Descriptif d'établissement

La fiche d'un établissement comporte deux onglets personnalisables :

1. **Descriptif** — regroupe les rubriques de la capsule « Descriptif de l'établissement ».
2. **Effectifs & dégagements** — regroupe les rubriques de la capsule « Effectifs & dégagements de l'établissement ».

Le nom de chaque onglet correspond au nom affiché de la capsule (modifiable en administration).

Chaque onglet propose :

- Un mode **consultation** : les valeurs sont affichées en lecture seule. Si un champ n'a aucune donnée, la mention *« Aucune donnée »* est affichée. Si aucun champ n'est défini dans une rubrique, un message invite à contacter un administrateur.
- Un mode **édition** : les champs sont éditables selon leur type.

Une **table des matières** latérale liste les rubriques, avec :

- Une **barre de recherche** pour filtrer les rubriques par nom.
- Une case **« Tous »** pour afficher ou masquer l'ensemble des rubriques d'un coup.
- Une case par rubrique pour l'afficher ou la masquer individuellement.
- Un lien cliquable vers l'ancre de chaque rubrique visible.
- Un bouton de repli pour réduire la table des matières.

L'état d'affichage (visible/masqué) de chaque rubrique est **mémorisé par établissement** : chaque utilisateur (ou établissement) peut avoir un paramétrage d'affichage différent du réglage par défaut.

### 5.2 Descriptif de dossier

La fiche d'un dossier comporte des onglets personnalisables similaires, alimentés par les capsules de la famille « Dossier ». Le fonctionnement est identique à celui du descriptif d'établissement, avec les mêmes modes consultation / édition et la même table des matières.

Les capsules « dossier » incluent notamment les onglets de vérifications techniques et d'effectifs & dégagements du dossier.

Le descriptif de dossier peut également être inclus dans la **génération de documents** (publipostage), où chaque champ est identifié par son **nom de fusion**.

---

## 6. Règles de gestion

| Identifiant | Règle |
|-------------|-------|
| **RG-FOR-001** | Une capsule ne peut être ni créée ni supprimée. Seul son nom affiché est modifiable. |
| **RG-FOR-002** | Le nom d'une capsule ne peut pas être vide. |
| **RG-FOR-003** | Le nom d'une rubrique est obligatoire. |
| **RG-FOR-004** | Une rubrique est toujours rattachée à exactement une capsule. |
| **RG-FOR-005** | Lorsqu'une rubrique est supprimée, tous ses champs (et leurs valeurs saisies) sont supprimés en cascade. |
| **RG-FOR-006** | Le rang initial d'une nouvelle rubrique correspond au nombre de rubriques déjà existantes dans la capsule (ajout en fin de liste). |
| **RG-FOR-007** | Le nom d'un champ est obligatoire et ne doit pas dépasser 255 caractères. |
| **RG-FOR-008** | Le type d'un champ est défini à la création et ne peut plus être modifié ensuite. |
| **RG-FOR-009** | Le rang initial d'un nouveau champ correspond au nombre de champs déjà existants dans sa rubrique (ou dans son parent, pour un champ enfant). |
| **RG-FOR-010** | Un champ de type « Liste » sans aucune valeur déclenche un avertissement visuel en administration. |
| **RG-FOR-011** | Un champ de type « Parent » sans aucun champ enfant déclenche un avertissement visuel en administration. |
| **RG-FOR-012** | Un champ enfant (rattaché à un parent) ne peut pas être de type « Parent ». L'imbrication à plusieurs niveaux est interdite. |
| **RG-FOR-013** | Un champ de type « Parent » peut être affiché soit en mode « ligne unique », soit en mode « tableau ». Ce choix est modifiable après la création. |
| **RG-FOR-014** | En mode « tableau », l'utilisateur peut ajouter, supprimer et réordonner des lignes de données. |
| **RG-FOR-015** | En mode consultation, lorsqu'un champ simple (Texte, Texte long, Liste, Nombre, Date) n'a aucune valeur, le système affiche *« Aucune donnée »*. |
| **RG-FOR-016** | En mode consultation, si une rubrique ne contient aucun champ défini, le message *« Aucun champ renseigné. Contactez un administrateur Prevarisc. »* est affiché. |
| **RG-FOR-017** | Chaque champ possède un **nom de fusion** calculé automatiquement à partir du nom interne de la capsule, du nom de la rubrique et du nom du champ. Ce nom est utilisé pour le publipostage. |
| **RG-FOR-018** | Pour un champ de type « Parent » en mode tableau, le nom de fusion prend la forme d'une boucle avec un nom de boucle et un nom de colonne par champ enfant. |
| **RG-FOR-019** | L'affichage ou le masquage d'une rubrique dans un descriptif est mémorisé par entité (établissement ou dossier). La valeur par défaut est celle définie en administration (« Afficher par défaut »). |
| **RG-FOR-020** | L'import d'une configuration avec l'option « Supprimer la configuration existante » entraîne la suppression de toutes les données saisies associées aux formulaires existants. |
| **RG-FOR-021** | Les rubriques, les champs et les valeurs de liste sont triés par leur rang d'affichage (croissant), puis par leur nom en cas de rang indéfini. |
| **RG-FOR-022** | Un champ de type « Nombre » accepte uniquement des valeurs supérieures ou égales à zéro. |
| **RG-FOR-023** | Un champ de type « Date » dispose d'un raccourci « Aujourd'hui » qui pré-remplit la date du jour. |
| **RG-FOR-024** | Un champ de type « Liste » propose systématiquement une option vide par défaut (« Sélectionnez un élément »). |
| **RG-FOR-025** | Un champ de type « Case à cocher » est toujours visible, y compris en mode lecture ; il est alors désactivé pour empêcher toute modification. |
| **RG-FOR-026** | Le nom de fusion d'un champ enfant dans un parent non-tableau est composé du nom interne de la capsule, du nom de la rubrique, du nom du parent et du nom de l'enfant. |
| **RG-FOR-027** | Les capsules de la famille « Établissement » sont identifiées par les noms internes « descriptifEtablissement » et « effectifsDegagementsEtablissement ». Toutes les autres capsules appartiennent à la famille « Dossier ». |

---

## 7. Cas particuliers

### 7.1 Rubrique sans champ

Si une rubrique a été créée en administration mais qu'aucun champ n'y a été ajouté, elle apparaît dans le descriptif avec le message d'information décrit en **RG-FOR-016**. L'utilisateur ne peut rien saisir.

### 7.2 Liste vide

Un champ de type « Liste » créé sans valeurs est fonctionnellement inutilisable : la liste déroulante ne contient que l'option vide « Sélectionnez un élément ». L'administration affiche un avertissement (**RG-FOR-010**).

### 7.3 Parent sans enfant

Un champ de type « Parent » sans champ enfant est affiché avec un avertissement en administration (**RG-FOR-011**). Dans le descriptif, aucun contenu n'est rendu pour ce champ.

### 7.4 Suppression en cascade

- Supprimer une **rubrique** entraîne la suppression de tous ses champs, champs enfants et valeurs de liste associées.
- Supprimer un **champ parent** entraîne la suppression de tous ses champs enfants.
- Supprimer une **valeur de liste** est immédiate et ne nécessite pas de sauvegarde du champ.

### 7.5 Import destructif

L'option « Supprimer la configuration existante » lors de l'import est une opération irréversible. Le message d'aide précise explicitement : *« La suppression de la configuration existante entraînera également la suppression de toutes les données associées »*.

### 7.6 Nom de fusion pour le publipostage

Le nom de fusion suit la convention : `{capsule-rubrique-champ}`. Les espaces et apostrophes dans les noms sont remplacés par des tirets bas, et le tout est mis en minuscules. Pour les tableaux, une syntaxe de boucle est utilisée avec des marqueurs `BEGIN` / `END`.

### 7.7 Affichage personnalisé par entité

Lorsqu'un utilisateur masque ou affiche une rubrique dans le descriptif d'un établissement ou d'un dossier donné, cette préférence est enregistrée pour cette entité. Elle prévaut sur le réglage par défaut défini en administration. Un autre établissement ou dossier conserve son propre paramétrage.

### 7.8 Données de tableau

En mode tableau (champ parent), chaque ligne contient une valeur par colonne (champ enfant). Lors de la sauvegarde, le système compare l'état initial et l'état final des lignes pour déterminer les insertions, mises à jour et suppressions à effectuer. Les lignes sont ré-indexées après chaque réordonnancement.
