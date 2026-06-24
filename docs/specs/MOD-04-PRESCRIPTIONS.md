# MOD-04 — Prescriptions réglementaires

## 1. Objet et périmètre

Ce document décrit le module de gestion des **prescriptions réglementaires** de l'application Prevarisc.

Le module couvre deux périmètres distincts :

- **Le référentiel des prescriptions** : ensemble structuré de catégories, textes réglementaires, articles, prescriptions types et rappels réglementaires. Ce référentiel est administré de manière centralisée et sert de socle commun à tous les dossiers.
- **L'application aux dossiers** : utilisation opérationnelle du référentiel pour associer, rédiger, modifier, réordonner et reprendre des prescriptions dans le cadre d'un dossier de prévention incendie (étude ou visite).

Le module s'inscrit dans le processus métier suivant : lors d'une étude de dossier ou d'une visite de commission, le préventionniste formule des prescriptions à l'attention de l'exploitant de l'ERP. Ces prescriptions peuvent être des rappels réglementaires génériques, des exigences liées à l'exploitation ou des recommandations d'amélioration du niveau de sécurité.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|-------------------|
| **Préventionniste** | Consulte, ajoute, modifie, réordonne et reprend des prescriptions dans les dossiers. Recherche des prescriptions types par navigation ou par mots-clés. |
| **Administrateur du référentiel** | Crée et modifie les catégories, textes réglementaires, articles du référentiel hiérarchique. Gère les prescriptions types et les rappels réglementaires. Gère les listes de textes et d'articles utilisables dans les prescriptions. Réordonne les éléments du référentiel. |

---

## 3. Structure du référentiel

### 3.1 Hiérarchie : Catégories → Textes → Articles

Le référentiel des prescriptions est organisé selon une **arborescence à trois niveaux** :

```
Catégorie (ex. : "Sécurité incendie")
  └─ Texte réglementaire (ex. : "Arrêté du 25 juin 1980")
       └─ Article (ex. : "Article GN 6")
```

- **Catégorie** : niveau le plus haut du classement. Regroupe des textes réglementaires par thématique. Chaque catégorie possède un libellé et un numéro d'ordre.
- **Texte réglementaire** : rattaché à une catégorie. Représente un texte de loi, un arrêté, un décret ou tout autre référentiel normatif. Possède un libellé et un numéro d'ordre au sein de sa catégorie.
- **Article** : rattaché à un texte réglementaire. Représente un article précis au sein d'un texte. Possède un libellé et un numéro d'ordre au sein de son texte.

Cette hiérarchie sert de **classement pour les prescriptions types** (cf. §3.2). Une prescription type peut être positionnée à n'importe quel niveau : directement dans une catégorie, dans un texte, dans un article, voire sans aucun rattachement.

### 3.2 Types de prescriptions

Un **type de prescription** (ou « prescription type ») est un modèle de prescription réutilisable. Il est composé de :

- **Un libellé** : le texte descriptif de la prescription (contenu métier).
- **Un emplacement** dans la hiérarchie du référentiel : catégorie, texte et/ou article auxquels la prescription se rapporte. Cet emplacement est facultatif à chaque niveau (une prescription peut exister sans être rattachée à la hiérarchie).
- **Une ou plusieurs associations texte/article** : chaque prescription type est liée à au moins une paire (texte de la liste, article de la liste). Ces associations indiquent les fondements réglementaires précis de la prescription. Une même prescription type peut citer plusieurs articles de plusieurs textes.

Le préventionniste peut parcourir les prescriptions types par la hiérarchie (catégorie → texte → article) ou les rechercher par mots-clés, puis les ajouter directement à un dossier.

### 3.3 Rappels réglementaires

Les **rappels réglementaires** sont des prescriptions prédéfinies dans le référentiel, distinctes des prescriptions types. Ils sont destinés à être insérés automatiquement ou manuellement dans les dossiers.

Chaque rappel réglementaire possède :

- **Un libellé** : le contenu du rappel.
- **Un type d'application** : « étude » ou « visite ». Détermine dans quel contexte de dossier le rappel est pertinent.
- **Un indicateur de visibilité** : « ajouté par défaut » (oui/non). Lorsque l'option est activée, le rappel est automatiquement proposé à la création d'un nouveau dossier du type correspondant.
- **Une ou plusieurs associations texte/article** : les fondements réglementaires du rappel, selon le même mécanisme que les prescriptions types.

Les rappels réglementaires sont administrés dans un écran dédié, séparé de l'arborescence des prescriptions types.

### 3.4 Listes de textes et d'articles

En complément de la hiérarchie (catégorie → texte → article) qui sert au classement des prescriptions types, le référentiel dispose de **deux listes plates** :

- **Liste des textes** : catalogue de tous les textes réglementaires utilisables dans les associations. Chaque texte possède un libellé et un indicateur de visibilité (visible/masqué). Seuls les textes visibles apparaissent lors de la saisie d'une prescription.
- **Liste des articles** : catalogue de tous les articles réglementaires utilisables dans les associations. Mêmes caractéristiques que les textes.

Ces listes sont utilisées dans trois contextes :
1. Lors de la création ou la modification d'une **prescription type** (association texte/article).
2. Lors de la création ou la modification d'un **rappel réglementaire** (association texte/article).
3. Lors de la création ou la modification d'une **prescription de dossier** (association texte/article).

> **Important** : Il existe un texte et un article « par défaut » (valeur technique de substitution) qui sont utilisés lorsque l'utilisateur ne sélectionne pas de texte ou d'article. Ces éléments par défaut sont masqués dans les interfaces de gestion des listes.

---

## 4. Fonctionnalités — Administration du référentiel

### 4.1 Gérer les catégories

**Description** : L'administrateur du référentiel peut créer et modifier des catégories de prescriptions.

**Créer une catégorie** :
1. L'administrateur clique sur « Ajouter » depuis l'écran principal de gestion des prescriptions types.
2. Une boîte de dialogue s'ouvre avec un champ « Libellé ».
3. L'administrateur saisit le libellé et valide.
4. La catégorie est créée avec un numéro d'ordre égal au maximum existant + 1 (ajout en fin de liste).

**Modifier une catégorie** :
1. L'administrateur clique sur « Modifier » en regard de la catégorie.
2. La boîte de dialogue s'ouvre pré-remplie avec le libellé actuel.
3. L'administrateur modifie le libellé et valide.

### 4.2 Gérer les textes réglementaires

Le module distingue deux types de gestion des textes :

#### 4.2.1 Textes dans la hiérarchie (au sein d'une catégorie)

**Créer un texte dans une catégorie** :
1. L'administrateur déplie une catégorie pour afficher ses textes.
2. Il clique sur « Ajouter un type de disposition ».
3. Une boîte de dialogue s'ouvre avec un champ « Libellé ».
4. Le texte est créé rattaché à la catégorie, avec un numéro d'ordre en fin de liste.

**Modifier un texte de la hiérarchie** :
1. L'administrateur clique sur « Modifier » en regard du texte.
2. Il modifie le libellé et valide.

#### 4.2.2 Textes dans la liste plate (listes prédéfinies)

**Consulter la liste des textes** :
1. L'administrateur accède à « Gestion des textes » depuis l'écran principal.
2. La liste affiche tous les textes avec leur indicateur de visibilité.

**Ajouter un texte à la liste** :
1. L'administrateur clique sur « Ajouter un texte ».
2. Il saisit le libellé et choisit la visibilité (Afficher / Masquer).
3. Il valide.

**Modifier un texte de la liste** :
1. L'administrateur clique sur « Modifier » en regard du texte.
2. Il peut modifier le libellé et la visibilité.
3. Il valide.

**Supprimer et remplacer un texte** :
1. Depuis l'écran de modification d'un texte, l'administrateur clique sur « Supprimer le texte et le remplacer par un autre ».
2. La liste des textes disponibles (hors celui à supprimer et hors texte par défaut) est affichée.
3. L'administrateur sélectionne le texte de remplacement.
4. Un message d'avertissement indique que l'opération est irréversible et que toutes les prescriptions existantes seront mises à jour.
5. Après confirmation, le texte supprimé est remplacé dans l'ensemble des prescriptions (prescriptions de dossier, prescriptions types, rappels réglementaires), puis le texte original est supprimé.

### 4.3 Gérer les articles

La gestion des articles suit le même principe à deux niveaux que les textes.

#### 4.3.1 Articles dans la hiérarchie (au sein d'un texte)

**Créer un article dans un texte** :
1. L'administrateur déplie un texte pour afficher ses articles.
2. Il clique sur « Ajouter un article ».
3. Il saisit le libellé et valide.
4. L'article est créé avec un numéro d'ordre en fin de liste.

**Modifier un article de la hiérarchie** :
1. L'administrateur clique sur « Modifier » en regard de l'article.
2. Il modifie le libellé et valide.

#### 4.3.2 Articles dans la liste plate

Le fonctionnement est identique à celui des textes de la liste plate (ajout, modification, suppression avec remplacement). La suppression d'un article entraîne le même mécanisme de remplacement global dans toutes les prescriptions existantes.

### 4.4 Gérer les types de prescriptions

**Créer une prescription type** :
1. L'administrateur choisit un point d'insertion dans la hiérarchie (bouton « Ajouter une prescription » au niveau catégorie, texte ou article) ou sans rattachement.
2. Une fenêtre modale s'ouvre avec :
   - L'emplacement dans la hiérarchie (modifiable par navigation catégorie → texte → article, avec possibilité de revenir en arrière à chaque niveau).
   - Un sélecteur de texte et un sélecteur d'article (issus des listes prédéfinies visibles).
   - Un bouton « Ajouter l'association » pour créer une paire texte/article.
   - Un tableau listant les associations déjà ajoutées, avec la possibilité de les retirer.
   - Un champ « Contenu » (zone de texte libre).
3. L'administrateur doit ajouter au moins une association texte/article et saisir un contenu.
4. Il valide pour sauvegarder.

**Modifier une prescription type** :
1. L'administrateur clique sur « Éditer » en regard de la prescription type.
2. La fenêtre modale s'ouvre pré-remplie avec l'emplacement, les associations et le contenu.
3. L'administrateur peut modifier l'emplacement, ajouter/retirer des associations et modifier le contenu.
4. Lors de la sauvegarde, les anciennes associations sont supprimées et remplacées par les nouvelles.

**Gestion des rappels réglementaires** :

Voir §3.3 pour la description. L'écran dédié permet :
1. De consulter la liste des rappels, séparés en deux sections : « Pour les études » et « Pour les visites ».
2. D'ajouter un nouveau rappel en précisant le type (étude/visite), la visibilité par défaut, les associations texte/article et le contenu.
3. De modifier un rappel existant.

### 4.5 Réordonner les éléments du référentiel

L'administrateur peut modifier l'ordre d'affichage des éléments par **glisser-déposer** :

- **Catégories** : réorganisables entre elles.
- **Textes** : réorganisables au sein de leur catégorie.
- **Articles** : réorganisables au sein de leur texte.
- **Prescriptions types** : réorganisables au sein de leur emplacement.

Le nouvel ordre est sauvegardé automatiquement après chaque glisser-déposer.

---

## 5. Fonctionnalités — Application aux dossiers

### 5.1 Consulter les prescriptions d'un dossier

**Description** : Le préventionniste consulte les prescriptions rattachées à un dossier.

**Présentation** : Les prescriptions sont affichées en trois sections distinctes :

1. **Rappels réglementaires** : prescriptions de type « rappel réglementaire ».
2. **Prescriptions liées à l'exploitation** : prescriptions numérotées portant sur l'exploitation de l'ERP.
3. **Recommandations liées à l'amélioration du niveau de sécurité** : prescriptions numérotées portant sur des améliorations recommandées.

> **Note** : Un mécanisme de plugin permet de renommer ces trois catégories (ex. : « Essais réalisés », « Prescriptions », « Conclusions types »).

Pour chaque prescription, sont affichés :
- Le ou les textes réglementaires associés.
- Le ou les articles associés.
- Le numéro d'ordre (pour les prescriptions liées à l'exploitation et à l'amélioration ; les rappels réglementaires ne portent pas de numéro).
- Le contenu de la prescription.
- Le cas échéant, l'indication de reprise avec un lien vers le dossier d'origine et sa date de création.
- Le cas échéant, la date de levée et le justificatif de levée.

**Accès** : Si le dossier est verrouillé, les prescriptions sont consultables mais non modifiables (pas de boutons d'action).

### 5.2 Ajouter une prescription à un dossier

Le préventionniste dispose de **trois modes d'ajout** :

#### 5.2.1 Ajouter une prescription type

1. Le préventionniste clique sur « Ajouter une prescription type ».
2. Une fenêtre modale s'ouvre avec deux modes de recherche :
   - **Par classement** : navigation dans la hiérarchie catégorie → texte → article. Les prescriptions types rattachées à l'emplacement courant sont affichées.
   - **Par mots-clés** : saisie d'un ou plusieurs mots-clés séparés par des espaces. La recherche porte sur le libellé de la prescription, le libellé du texte et le libellé de l'article. Les résultats sont affichés avec les mots-clés surlignés.
3. Le préventionniste sélectionne la catégorie de classement dans le dossier : « Rappels réglementaires », « Liée à l'exploitation » ou « Liée à l'amélioration ».
4. Il clique sur « Ajouter » en regard de la prescription souhaitée.
5. La prescription est ajoutée au dossier avec un numéro d'ordre calculé automatiquement.
6. Le bouton « Ajouter » est désactivé après l'ajout pour éviter les doublons accidentels.

#### 5.2.2 Ajouter une prescription libre

1. Le préventionniste clique sur « Ajouter une prescription ».
2. Un formulaire dédié s'ouvre avec :
   - La sélection de la catégorie de classement (rappel réglementaire / exploitation / amélioration).
   - Un sélecteur de texte et d'article (issus des listes prédéfinies visibles).
   - Un bouton « Ajouter l'association » pour constituer la liste des fondements réglementaires.
   - Un tableau des associations avec possibilité de retrait.
   - Un champ « Contenu » (zone de texte libre).
   - Un champ « Date de levée » (optionnel).
   - Un champ « Justification de la levée » (optionnel).
3. Le préventionniste doit saisir au minimum une association texte/article et un contenu.
4. Il valide. La prescription est créée avec un numéro d'ordre en fin de liste de la catégorie choisie.

#### 5.2.3 Remarque sur la numérotation

Lors de chaque ajout de prescription, la numérotation est recalculée. Les prescriptions liées à l'exploitation sont numérotées séquentiellement à partir de 1. Les prescriptions liées à l'amélioration continuent la numérotation à la suite.

### 5.3 Modifier une prescription d'un dossier

1. Le préventionniste clique sur « Modifier » en regard de la prescription.
2. Le formulaire d'édition s'ouvre avec les données actuelles pré-remplies.
3. Le préventionniste peut :
   - Modifier la catégorie de classement (rappel / exploitation / amélioration).
   - Modifier ou ajouter des associations texte/article.
   - Modifier le contenu.
   - Renseigner ou modifier une date de levée.
   - Renseigner ou modifier un justificatif de levée.
   - Supprimer la prescription (avec confirmation en deux étapes).
4. Cas particulier — **prescription issue d'un type** : si la prescription a été ajoutée depuis une prescription type du référentiel, le premier enregistrement de modifications **détache la prescription du type**. Elle devient alors une prescription libre, pleinement éditable. Les associations texte/article de la prescription type d'origine sont reprises comme associations spécifiques de la prescription de dossier.
5. Si la catégorie de classement change, la numérotation de l'ancienne et de la nouvelle catégorie est recalculée.

### 5.4 Réordonner les prescriptions d'un dossier

Le préventionniste peut modifier l'ordre des prescriptions par **glisser-déposer** au sein de chaque section (rappels réglementaires, exploitation, amélioration).

Le réordonnancement met à jour les numéros d'ordre :
- Les rappels réglementaires sont renumérotés indépendamment.
- Les prescriptions d'exploitation et d'amélioration partagent une numérotation continue : les prescriptions d'exploitation sont numérotées à partir de 1, et celles d'amélioration continuent la séquence.

### 5.5 Rechercher une prescription par mots-clés

1. Depuis l'écran d'ajout de prescription type, le préventionniste bascule en mode « Recherche par mots-clés ».
2. Il saisit un ou plusieurs mots-clés séparés par des espaces.
3. La recherche est effectuée sur :
   - Le libellé des prescriptions types.
   - Le libellé des textes associés.
   - Le libellé des articles associés.
4. Les résultats sont affichés sous forme de liste avec les mots-clés surlignés dans le texte, l'article et le contenu.
5. Le préventionniste peut directement ajouter une prescription depuis les résultats.

### 5.6 Reprendre les prescriptions d'un dossier antérieur

**Description** : Le préventionniste peut reprendre l'ensemble des prescriptions d'un ou plusieurs dossiers antérieurs du même établissement.

**Prérequis** : Le dossier courant ne doit concerner qu'**un seul établissement**. Si le dossier est lié à plusieurs établissements, la fonctionnalité est indisponible et un message explicatif est affiché.

**Processus** :
1. Le préventionniste clique sur « Reprendre les prescriptions d'un dossier précédent ».
2. Une fenêtre modale affiche la liste de tous les dossiers de l'établissement, classés par type :
   - Visites
   - Études
   - Autres
3. Le dossier courant est exclu de la liste.
4. Le préventionniste peut **sélectionner un ou plusieurs dossiers** (sélection multiple par cases à cocher).
5. Il clique sur « Reprendre les prescriptions ».
6. Pour chaque dossier sélectionné, les trois catégories de prescriptions sont reprises :
   - Rappels réglementaires
   - Prescriptions liées à l'exploitation
   - Recommandations liées à l'amélioration
7. Chaque prescription reprise reçoit un nouveau numéro d'ordre calculé dans le dossier cible. Une renumérotation globale est effectuée après chaque reprise afin d'assurer une séquence continue sans doublons.
8. La prescription reprise est marquée avec la **référence du dossier d'origine** (lien vers le dossier source et date de création), visible dans l'affichage sous forme d'un badge « Reprise du dossier [Nom ou Type - Nature] créé le [date] ».
9. Les prescriptions types reprises conservent leur lien avec le type de prescription du référentiel.
10. Les prescriptions libres reprises voient leurs associations texte/article dupliquées.

**Exclusion** : Les prescriptions ayant une **date de levée renseignée** dans le dossier d'origine ne sont **pas reprises**. Seules les prescriptions non levées sont copiées.

---

## 6. Règles de gestion

| Identifiant | Règle |
|-------------|-------|
| **RG-PRE-001** | Le référentiel est structuré en trois niveaux : catégorie → texte réglementaire → article. Chaque élément enfant est rattaché à exactement un élément parent. |
| **RG-PRE-002** | Une prescription type doit être associée à au moins une paire (texte, article) et posséder un contenu non vide pour être enregistrée. |
| **RG-PRE-003** | Une prescription type peut être positionnée à n'importe quel niveau de la hiérarchie (catégorie seule, catégorie + texte, catégorie + texte + article) ou sans aucun rattachement hiérarchique. |
| **RG-PRE-004** | L'ordre d'affichage des catégories, textes, articles et prescriptions types est personnalisable par glisser-déposer. Le numéro d'ordre est recalculé après chaque réorganisation. |
| **RG-PRE-005** | Chaque nouvelle catégorie est ajoutée en dernière position (numéro d'ordre = maximum existant + 1). |
| **RG-PRE-006** | Chaque nouveau texte dans la hiérarchie est ajouté en dernière position au sein de sa catégorie. |
| **RG-PRE-007** | Chaque nouvel article dans la hiérarchie est ajouté en dernière position au sein de son texte. |
| **RG-PRE-008** | Un rappel réglementaire est de type « étude » ou « visite ». Il possède un indicateur d'ajout par défaut qui détermine s'il est automatiquement inséré à la création d'un dossier du type correspondant. |
| **RG-PRE-009** | Les textes et articles de la liste prédéfinie possèdent un indicateur de visibilité. Seuls les éléments visibles sont proposés lors de la saisie d'une prescription. |
| **RG-PRE-010** | La suppression d'un texte ou d'un article de la liste prédéfinie nécessite de choisir un élément de remplacement. L'ancien élément est remplacé dans toutes les prescriptions existantes (dossier, type, rappel réglementaire) avant d'être supprimé. Cette opération est irréversible. |
| **RG-PRE-011** | Il existe un texte par défaut et un article par défaut qui servent de valeur de substitution lorsque l'utilisateur ne sélectionne pas de texte ou d'article. Ces éléments sont masqués dans les écrans de gestion des listes. |
| **RG-PRE-012** | Les prescriptions d'un dossier sont classées en trois catégories : rappels réglementaires (type 0), prescriptions liées à l'exploitation (type 1) et recommandations liées à l'amélioration (type 2). |
| **RG-PRE-013** | Les prescriptions liées à l'exploitation et à l'amélioration portent un numéro d'ordre. Les rappels réglementaires n'en portent pas dans l'affichage. |
| **RG-PRE-014** | La numérotation des prescriptions d'exploitation commence à 1. Celle des recommandations d'amélioration continue la séquence (premier numéro = dernier numéro d'exploitation + 1). |
| **RG-PRE-015** | Lors de l'ajout, de la modification (avec changement de catégorie), de la suppression ou du réordonnancement d'une prescription, la numérotation de toutes les prescriptions du dossier est recalculée. |
| **RG-PRE-016** | L'ajout d'une prescription de dossier (libre) nécessite au moins une association texte/article et un contenu non vide. |
| **RG-PRE-017** | La modification d'une prescription issue d'un type entraîne le détachement de la prescription de son type d'origine. La prescription devient une prescription libre avec ses propres associations texte/article. |
| **RG-PRE-018** | La suppression d'une prescription de dossier requiert une confirmation en deux étapes pour éviter les suppressions accidentelles. |
| **RG-PRE-019** | La fonctionnalité de reprise de prescriptions n'est disponible que si le dossier courant ne concerne qu'un seul établissement. |
| **RG-PRE-020** | Lors d'une reprise, seules les prescriptions **non levées** du dossier source sont copiées. Les prescriptions ayant une date de levée sont exclues. |
| **RG-PRE-021** | Les prescriptions reprises conservent la référence du dossier d'origine (identifiant du dossier source). Cette information est affichée sous forme de badge avec un lien vers le dossier d'origine. |
| **RG-PRE-022** | La reprise permet la sélection multiple de dossiers sources. Les prescriptions des dossiers sélectionnés sont copiées séquentiellement. |
| **RG-PRE-023** | Un dossier verrouillé interdit toute modification des prescriptions. Les prescriptions restent consultables mais les boutons d'ajout, modification, suppression, réordonnancement et reprise ne sont pas disponibles. |
| **RG-PRE-024** | La recherche par mots-clés de prescriptions types effectue une recherche de type « contient » (non sensible à la casse) sur le libellé de la prescription, le libellé du texte et le libellé de l'article. Plusieurs mots-clés séparés par des espaces sont traités en « OU ». |
| **RG-PRE-025** | Une prescription de dossier peut avoir une date de levée et un justificatif de levée. Ces champs sont optionnels et renseignés lors de la modification de la prescription. |

---

## 7. Cas particuliers

### 7.1 Levée de prescription

Une prescription peut être **levée** lorsque l'exploitant a satisfait à l'exigence. La levée se caractérise par :
- Une **date de levée** : date à laquelle la prescription a été considérée comme satisfaite.
- Un **justificatif de levée** : texte libre expliquant les raisons de la levée.

L'information de levée est affichée sur la prescription sous forme d'un badge « Levée le [date] » accompagné d'une icône d'information permettant de consulter le justificatif au survol.

Les prescriptions levées **ne sont pas reprises** lors d'une reprise de prescriptions depuis un dossier antérieur (cf. RG-PRE-020).

### 7.2 Prescriptions reprises et modifiées

Lorsqu'une prescription est reprise depuis un dossier antérieur, elle conserve la référence de son dossier d'origine. Si la prescription est ensuite modifiée dans le dossier courant, elle reste marquée comme reprise mais le contenu modifié est sauvegardé dans le dossier courant.

Si une prescription reprise était liée à un type de prescription du référentiel, la première modification détache la prescription de ce type (cf. RG-PRE-017).

### 7.3 Prescription sans article ou sans texte référencé

Lorsqu'une association texte/article est créée sans que l'utilisateur ne sélectionne de texte ou d'article, le système utilise une valeur par défaut (cf. RG-PRE-011). Cette valeur représente l'absence de texte ou d'article spécifique.

### 7.4 Prescription type ajoutée puis modifiée dans un dossier

Lorsqu'une prescription type est ajoutée à un dossier, le contenu affiché est celui du référentiel. Si le préventionniste modifie cette prescription dans le dossier :
1. Le lien avec le type de prescription du référentiel est rompu.
2. Le contenu du type est recopié comme contenu libre de la prescription de dossier.
3. Les associations texte/article du type sont dupliquées en tant qu'associations propres à la prescription de dossier.
4. La prescription devient pleinement éditable (contenu, associations, catégorie de classement, levée).

Les modifications ultérieures du type de prescription dans le référentiel n'affectent plus cette prescription de dossier.

### 7.5 Reprise impossible (dossier multi-établissements)

Si le dossier courant est lié à plusieurs établissements, la reprise de prescriptions est impossible. Le bouton de reprise est affiché mais désactivé, et un message explicatif est visible au survol : « Pour pouvoir utiliser cette fonctionnalité, le dossier ne doit concerner qu'un seul établissement. »

### 7.6 Reprise depuis plusieurs dossiers

Lors de la reprise, le préventionniste peut sélectionner plusieurs dossiers sources. Les prescriptions de chaque dossier sont copiées séquentiellement dans le dossier cible. Une renumérotation globale est effectuée à l'issue de l'import pour garantir une numérotation continue et sans doublon dans le dossier cible.

### 7.7 Remplacement d'un texte ou d'un article dans le référentiel

Lorsqu'un texte ou un article est supprimé via le mécanisme de remplacement :
- Toutes les prescriptions de dossier utilisant cet élément voient leur association mise à jour vers le nouvel élément.
- Toutes les prescriptions types utilisant cet élément voient leur association mise à jour.
- Tous les rappels réglementaires utilisant cet élément voient leur association mise à jour.
- L'ancien élément est ensuite définitivement supprimé.

Cette opération est globale et irréversible. Un avertissement est affiché avant confirmation.
