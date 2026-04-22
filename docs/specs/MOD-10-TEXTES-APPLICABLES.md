# MOD-10 — Textes applicables

## 1. Objet et périmètre

Le module **Textes applicables** gère le référentiel des textes réglementaires utilisés dans le cadre de la prévention incendie. Il permet de :

- Administrer un catalogue centralisé de textes réglementaires (arrêtés, décrets, codes, circulaires, etc.).
- Associer ces textes aux **établissements** recevant du public (ERP).
- Associer ces textes aux **dossiers** (études, visites, groupes de visites).
- Alimenter le référentiel de **prescriptions** à partir des textes applicables.
- Produire les documents officiels (procès-verbaux, rapports de visite) en y intégrant les textes réglementaires applicables.

Le module est transversal : il sert de socle commun aux modules Établissement, Dossier et Prescription.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|---------------------|
| **Administrateur** | Crée, modifie, ordonne et masque les textes du référentiel. |
| **Préventionniste** | Consulte les textes, les associe aux établissements et aux dossiers lors de l'instruction. |
| **Membre de commission** | Consulte les textes applicables rattachés à un dossier dans les documents générés. |

L'accès à l'administration du référentiel est réservé à l'interface d'administration.

---

## 3. Structure du référentiel

### 3.1 Types de textes

Chaque texte applicable appartient à un **type de texte**. Les types servent à organiser l'affichage par catégorie (par exemple : « Arrêtés », « Codes », « Décrets », « Circulaires », etc.).

Un type de texte possède :

| Donnée | Description |
|--------|-------------|
| Identifiant | Identifiant unique du type. |
| Libellé du type | Nom affiché en en-tête de catégorie (ex. : « Arrêtés »). |

Les types sont triés par leur identifiant. Le référentiel des types est fixe et n'est pas modifiable depuis l'interface de gestion des textes applicables.

### 3.2 Données d'un texte applicable

Chaque texte applicable est décrit par les informations suivantes :

| Donnée | Description | Obligatoire |
|--------|-------------|:-----------:|
| Identifiant | Identifiant unique du texte. | Automatique |
| Libellé | Intitulé complet du texte réglementaire (peut contenir des retours à la ligne). | Oui |
| Type de texte | Catégorie à laquelle le texte est rattaché (voir § 3.1). | Oui |
| Visibilité | Indique si le texte est **affiché** ou **masqué** pour les utilisateurs lors de l'association aux établissements et dossiers. | Oui |
| Ordre d'affichage | Position du texte dans sa catégorie. Détermine l'ordre de présentation dans les listes. | Automatique |

### 3.3 Lien avec le référentiel de prescriptions

Lors de la création ou de la modification d'un texte applicable, l'administrateur peut choisir de **rendre ce texte disponible pour les prescriptions**. Dans ce cas, une entrée est automatiquement créée dans le référentiel des textes de prescriptions, avec le même libellé que le texte applicable.

Ce mécanisme permet de réutiliser un texte réglementaire comme base textuelle d'une prescription sans ressaisie.

> **Important** : ce lien est unidirectionnel. La création dans le référentiel de prescriptions se fait au moment de la sauvegarde. Toute modification ultérieure du texte applicable ne met pas à jour automatiquement le texte dans le référentiel de prescriptions.

---

## 4. Fonctionnalités — Administration du référentiel

L'administration est accessible depuis le menu d'administration de l'application.

### 4.1 Consulter les textes applicables

L'écran principal présente la liste de **tous** les textes applicables (visibles et masqués), regroupés par type de texte.

**Affichage :**

- Les textes sont regroupés sous le libellé de leur type (affiché en en-tête de section).
- Au sein de chaque type, les textes sont triés selon leur **ordre d'affichage**.
- Chaque texte affiche :
  - Son libellé (avec les éventuels retours à la ligne).
  - Un bouton « Éditer » permettant d'ouvrir le formulaire de modification.
  - Un indicateur visuel de visibilité (icône « visible » ou « non visible »).
- Un bouton « Ajouter un texte applicable » est disponible en haut de page.

### 4.2 Créer un texte applicable

L'utilisateur clique sur « Ajouter un texte applicable ». Une boîte de dialogue modale s'ouvre avec le formulaire suivant :

| Champ | Type de saisie | Valeur par défaut |
|-------|---------------|-------------------|
| Type de texte | Liste déroulante (tous les types disponibles) | Premier type de la liste |
| Libellé | Zone de texte multiligne (grande taille) | Vide |
| Visibilité | Liste déroulante : « Afficher » / « Masquer » | Afficher |
| Rendre disponible pour les prescriptions | Bouton radio : Oui / Non | Non |

**Comportement à la sauvegarde :**

1. Le texte est créé avec un **ordre d'affichage en fin de liste** (valeur maximale par défaut).
2. Si l'option « Rendre disponible pour les prescriptions » est à **Oui**, une entrée est ajoutée dans le référentiel des textes de prescriptions.
3. Un message de confirmation est affiché : *« Le texte a bien été sauvegardé »*.
4. L'utilisateur est redirigé vers la liste des textes applicables.

**En cas d'erreur :**

- Un message d'erreur est affiché : *« Erreur lors de la sauvegarde du texte »* accompagné du détail de l'erreur.
- L'utilisateur est redirigé vers la liste des textes applicables.

### 4.3 Modifier un texte applicable

L'utilisateur clique sur le bouton « Éditer » d'un texte existant. La même boîte de dialogue modale s'ouvre, pré-remplie avec les données actuelles du texte :

- Le type de texte est présélectionné.
- Le libellé est pré-rempli.
- La visibilité est présélectionnée.
- L'option « Rendre disponible pour les prescriptions » est à **Non** par défaut (même en édition).

Le comportement à la sauvegarde est identique à celui de la création (voir § 4.2).

> **Note** : il n'existe pas de fonction de suppression d'un texte applicable. Un texte peut uniquement être **masqué** (visibilité = « Masquer »).

### 4.4 Réordonner les textes

Au sein de chaque catégorie de type, les textes peuvent être **réordonnés par glisser-déposer**.

**Comportement :**

1. L'utilisateur déplace un texte dans la liste par glisser-déposer.
2. Le nouvel ordre est automatiquement sauvegardé en arrière-plan (sans rechargement de page).
3. Chaque texte de la catégorie reçoit un numéro d'ordre séquentiel en partant de zéro.
4. Le réordonnement est limité au sein d'une même catégorie de type. Il n'est pas possible de déplacer un texte d'une catégorie vers une autre.

---

## 5. Fonctionnalités — Application aux établissements et dossiers

### 5.1 Associer des textes à un établissement

Depuis la fiche d'un établissement, l'onglet « Textes applicables » permet de :

**Consulter les textes associés :**

- Les textes associés à l'établissement sont affichés, regroupés par type de texte.
- Chaque texte associé est marqué d'un symbole ✓ suivi de son libellé.
- Si aucun texte n'est associé, un message indique : *« Il n'y a pas de texte applicable »* avec un lien vers la modification.

**Modifier les associations :**

- L'utilisateur accède à l'écran de modification via le bouton « Modifier les textes applicables ».
- L'écran présente **tous les textes visibles** du référentiel, regroupés par type.
- Chaque texte est précédé d'une **case à cocher**.
- Les textes déjà associés à l'établissement sont **cochés et affichés en gras**.
- L'utilisateur coche ou décoche les textes souhaités puis clique sur « Sauvegarder ».

**Comportement à la sauvegarde :**

1. Pour chaque texte **décoché** : l'association entre le texte et l'établissement est supprimée (si elle existait).
2. Pour chaque texte **coché** : l'association est créée (si elle n'existait pas déjà).
3. Un message de confirmation est affiché : *« Les textes applicables ont bien été mis à jour »*.
4. L'utilisateur est redirigé vers la consultation des textes de l'établissement.

Un bouton « Annuler la modification » permet de revenir à la consultation sans sauvegarder.

### 5.2 Associer des textes à un dossier

Depuis la fiche d'un dossier, l'onglet « Textes applicables » fonctionne de manière identique à celui d'un établissement (voir § 5.1), avec les particularités suivantes :

**Consultation et modification :** même fonctionnement que pour un établissement (liste à cocher des textes visibles, regroupés par type).

**Héritage automatique à la création du dossier :**

Lors de la création d'un nouveau dossier de type **visite** ou **groupe de visites** portant certaines natures (visite périodique, visite de contrôle, visite inopinée — en visite simple ou en groupe de visites), les textes applicables de l'établissement rattaché sont **automatiquement copiés** vers le dossier.

**Synchronisation bidirectionnelle avec l'établissement :**

Lorsqu'un texte applicable est modifié dans un dossier de type **visite** ou **groupe de visites** :

- Si un texte est **ajouté** au dossier, il est également ajouté à l'établissement rattaché (s'il n'y est pas déjà).
- Si un texte est **retiré** du dossier, il est également retiré de l'établissement rattaché.

Cette synchronisation ne s'applique qu'aux dossiers de type visite et groupe de visites. Les dossiers de type étude ne déclenchent pas de mise à jour sur l'établissement.

**Génération de documents :**

Les textes applicables associés à un dossier sont récupérés (triés par type de texte) pour être intégrés dans les documents officiels générés (procès-verbaux, rapports).

---

## 6. Règles de gestion

| Règle | Intitulé | Description |
|-------|----------|-------------|
| **RG-TXT-001** | Visibilité par défaut | Seuls les textes dont la visibilité est à « Afficher » apparaissent dans les écrans d'association aux établissements et aux dossiers. Les textes masqués restent visibles dans l'écran d'administration uniquement. |
| **RG-TXT-002** | Classement par type | Les textes sont toujours regroupés par type de texte et triés selon leur ordre d'affichage au sein de chaque type. |
| **RG-TXT-003** | Ordre d'affichage par défaut | Tout texte nouvellement créé reçoit un ordre d'affichage en fin de liste (valeur maximale). |
| **RG-TXT-004** | Réordonnement local | Le réordonnement par glisser-déposer ne concerne que les textes d'un même type. Les numéros d'ordre sont recalculés séquentiellement à partir de zéro après chaque réordonnement. |
| **RG-TXT-005** | Pas de suppression | Un texte applicable ne peut pas être supprimé. Il peut uniquement être masqué (visibilité = « Masquer »). |
| **RG-TXT-006** | Alimentation des prescriptions | L'option « Rendre disponible pour les prescriptions » crée une entrée dans le référentiel de prescriptions au moment de la sauvegarde. Ce lien est unidirectionnel et non réversible depuis cet écran. |
| **RG-TXT-007** | Association unique | Un même texte ne peut être associé qu'une seule fois à un établissement donné et qu'une seule fois à un dossier donné. La clé d'association est le couple (texte, établissement) ou (texte, dossier). |
| **RG-TXT-008** | Héritage automatique — création de dossier | Lors de la création d'un dossier de visite ou groupe de visites de nature VP, VC ou VI rattaché à un établissement, les textes applicables de l'établissement sont automatiquement recopiés dans le dossier. |
| **RG-TXT-009** | Synchronisation visite → établissement (ajout) | Lorsqu'un texte est ajouté à un dossier de type visite ou groupe de visites, il est automatiquement ajouté à l'établissement rattaché, s'il n'y est pas déjà présent. |
| **RG-TXT-010** | Synchronisation visite → établissement (retrait) | Lorsqu'un texte est retiré d'un dossier de type visite ou groupe de visites, il est automatiquement retiré de l'établissement rattaché. |
| **RG-TXT-011** | Pas de synchronisation pour les études | Les dossiers de type étude ne déclenchent aucune synchronisation des textes applicables vers l'établissement. |
| **RG-TXT-012** | Sauvegarde en mode ajout/retrait | Lors de la sauvegarde des associations (établissement ou dossier), le système traite chaque texte individuellement : ajout si coché et absent, suppression si décoché et présent, aucune action sinon. |
| **RG-TXT-013** | Édition dans une modale | La création et la modification d'un texte s'effectuent dans une fenêtre modale sans quitter la liste principale. La sauvegarde actualise la liste. |
| **RG-TXT-014** | Intégration documentaire | Les textes applicables associés à un dossier sont récupérés triés par type pour être intégrés dans les documents générés (procès-verbaux, rapports de commission). |
| **RG-TXT-015** | Option prescriptions par défaut | L'option « Rendre disponible pour les prescriptions » est à « Non » par défaut, y compris lors de l'édition d'un texte existant. |

---

## 7. Cas particuliers

### 7.1 Établissement sans texte applicable

Si aucun texte n'est associé à un établissement, l'écran de consultation affiche un message explicite : *« Il n'y a pas de texte applicable »* et propose un lien direct vers l'écran de modification.

### 7.2 Dossier sans texte applicable

Même comportement que pour un établissement sans texte (message explicite et lien vers la modification).

### 7.3 Création d'un dossier visite sans établissement

L'héritage automatique des textes (RG-TXT-008) ne se déclenche que si un établissement est rattaché au dossier au moment de la création. Si le dossier est créé sans établissement, aucun texte n'est recopié.

### 7.4 Dossier visite rattaché à plusieurs établissements

La synchronisation visite → établissement (RG-TXT-009 et RG-TXT-010) ne porte que sur le **premier établissement** rattaché au dossier. Les éventuels établissements secondaires ne sont pas impactés.

### 7.5 Texte masqué déjà associé

Un texte rendu invisible (masqué) après avoir été associé à des établissements ou dossiers reste associé à ceux-ci. Il n'apparaît plus dans les écrans de modification (liste des textes cochables), mais son association existante est conservée.

### 7.6 Double alimentation du référentiel de prescriptions

Si un texte est modifié plusieurs fois avec l'option « Rendre disponible pour les prescriptions » cochée à chaque fois, une nouvelle entrée est créée dans le référentiel de prescriptions **à chaque sauvegarde**. Le système ne vérifie pas l'existence préalable d'une entrée identique.

### 7.7 Natures de dossiers déclenchant l'héritage automatique

L'héritage automatique (RG-TXT-008) ne se déclenche que pour les natures de dossiers suivantes :

| Nature | Description |
|--------|-------------|
| Visite périodique (simple) | VP |
| Visite de contrôle (simple) | VC |
| Visite inopinée (simple) | VI |
| Visite périodique (groupe) | VP groupe |
| Visite de contrôle (groupe) | VC groupe |
| Visite inopinée (groupe) | VI groupe |

Les autres natures de dossiers (études, levées de réserves, réceptions de travaux, etc.) ne bénéficient pas de cet héritage.
