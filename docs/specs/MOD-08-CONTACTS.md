# MOD-08 — Contacts

## 1. Objet et périmètre

Le module Contacts gère l'ensemble des personnes physiques ou morales en relation avec les entités métier de Prevarisc. Un contact représente un interlocuteur identifié par son nom, son rôle (fonction) et ses coordonnées.

Un contact est une fiche d'information indépendante d'un compte utilisateur. Il peut être rattaché à une ou plusieurs entités simultanément. Il n'a pas accès à l'application.

Le module couvre :

- La création, la consultation, la modification et la suppression de contacts.
- L'association d'un contact à une ou plusieurs entités.
- La recherche par autocomplétion pour réutiliser un contact existant.
- L'héritage des contacts depuis un établissement parent vers ses cellules.

---

## 2. Acteurs concernés

| Acteur | Rôle |
|--------|------|
| Préventionniste | Consulte, crée, modifie et supprime les contacts associés aux établissements, dossiers, groupements et commissions. |
| Secrétariat | Gère les contacts des commissions et peut mettre à jour les coordonnées. |
| Administrateur | Dispose de tous les droits sur les contacts. |
| Utilisateur en lecture seule | Consulte les fiches contacts sans pouvoir les modifier (mode verrouillé). |

---

## 3. Données d'un contact

### 3.1 Identité

| Donnée | Description | Obligatoire | Longueur max |
|--------|-------------|:-----------:|:------------:|
| Civilité | Liste de valeurs : Monsieur, Madame | Non | — |
| Nom | Nom de famille du contact | Non | 50 |
| Prénom | Prénom du contact | Non | 50 |
| Société | Raison sociale de l'organisme | Non | — |

### 3.2 Fonction

Le contact se voit attribuer une **fonction** parmi une liste de référence :

| Fonction |
|----------|
| Préfet |
| Maire |
| Maître d'ouvrage |
| Maître d'œuvre |
| Pétitionnaire demandeur |
| Contrôleur technique (organisme agréé) |
| Exploitant |
| Directeur unique de sécurité (DUS) |
| Responsable de sécurité |
| Participant |
| Demandeur |
| Simple utilisateur |
| Préventionniste |
| Secrétariat |
| Service informatique |
| Propriétaire |
| Utilisateur spécial |

> La fonction « Maire » n'est pas proposée lors de la création d'un nouveau contact. Elle n'est visible que lors de la modification d'un contact déjà existant portant cette fonction.

### 3.3 Coordonnées

| Donnée | Longueur max |
|--------|:------------:|
| Téléphone fixe | 20 |
| Fax | 20 |
| Téléphone portable | 20 |
| Courriel | 50 |
| Site web | 100 |

### 3.4 Adresse postale

| Donnée | Longueur max |
|--------|:------------:|
| Numéro de voie | 10 |
| Rue | 255 |
| Code postal | 5 |
| Ville | 255 |

### 3.5 Observation

Champ texte libre permettant de saisir des remarques complémentaires sur le contact.

---

## 4. Entités pouvant avoir des contacts

Un contact peut être associé aux quatre entités suivantes :

| Entité | Description |
|--------|-------------|
| **Établissement** | Tout ERP, IGH ou autre établissement suivi dans Prevarisc. |
| **Dossier** | Dossier d'étude ou de visite rattaché à un établissement. |
| **Groupement** | Groupement d'établissements (site, zone, etc.). |
| **Commission** | Commission de sécurité (sous-commission, commission plénière, etc.). |

Un même contact peut être rattaché à plusieurs entités de types différents. Par exemple, un exploitant peut être à la fois contact d'un établissement et d'un dossier.

---

## 5. Fonctionnalités

### 5.1 Consulter les contacts d'une entité

L'utilisateur accède à la liste des contacts depuis l'onglet ou la section « Contacts » de la fiche d'une entité (établissement, dossier, groupement ou commission).

**Affichage :**

- Chaque contact est présenté sous forme de **fiche** indiquant :
  - Nom et prénom
  - Fonction et société
  - Téléphone fixe et portable (séparés par « / » si les deux sont renseignés)
  - Fax
  - Courriel (lien mailto cliquable)
  - Site web (lien cliquable)
  - Adresse complète (numéro, rue, code postal, ville)
  - Observations
- Les contacts sont triés par **ordre alphabétique du nom**.
- Si aucun contact n'est rattaché, le message « Aucun contact. » est affiché.

**Cas de l'établissement :** Lorsqu'un établissement possède des parents hiérarchiques, les contacts de ces parents sont affichés dans une section distincte intitulée « Contacts des établissements parents », en lecture seule, au-dessus des contacts propres à l'établissement (voir § 6).

### 5.2 Ajouter un contact

Deux modes d'ajout sont proposés (boutons visibles uniquement si l'entité n'est pas verrouillée) :

#### 5.2.1 Créer un nouveau contact

1. L'utilisateur clique sur « Ajouter un nouveau contact ».
2. Une fenêtre modale s'ouvre avec le formulaire de saisie.
3. L'utilisateur remplit les champs souhaités (fonction, civilité, nom, prénom, société, coordonnées, adresse, observations).
4. À la validation, le contact est créé et immédiatement associé à l'entité courante.
5. La liste des contacts se rafraîchit automatiquement.

#### 5.2.2 Associer un contact existant

1. L'utilisateur clique sur « Ajouter un contact existant ».
2. Une fenêtre modale s'ouvre avec un champ de recherche par nom.
3. L'utilisateur saisit au moins 3 caractères ; une liste de suggestions apparaît (voir § 5.5).
4. L'utilisateur sélectionne le contact souhaité.
5. Le contact est associé à l'entité courante sans duplication de la fiche.
6. La liste des contacts se rafraîchit automatiquement.

### 5.3 Modifier un contact

1. L'utilisateur clique sur l'icône de modification (crayon) présente sur la fiche du contact.
2. Une fenêtre modale s'ouvre avec le formulaire pré-rempli des données du contact.
3. L'utilisateur modifie les champs souhaités.
4. À la validation, les modifications sont enregistrées.
5. La liste des contacts se rafraîchit automatiquement.

> **Attention :** La modification porte sur la fiche du contact elle-même. Si ce contact est rattaché à plusieurs entités, le changement se répercute partout.

### 5.4 Supprimer un contact

1. L'utilisateur clique sur le bouton de suppression (×) présent sur la fiche du contact.
2. Une fenêtre de confirmation s'affiche : « Voulez-vous vraiment supprimer ce contact ? »
3. L'utilisateur confirme ou annule.
4. Le comportement dépend de l'état du contact (voir RG-CTT-006 et RG-CTT-007).

### 5.5 Rechercher un contact existant (autocomplétion)

La recherche permet de retrouver un contact déjà enregistré dans le système pour l'associer à une entité.

**Fonctionnement :**

- L'utilisateur saisit au minimum **3 caractères**.
- La recherche porte sur le **nom**, le **prénom** et le **nom de société** (recherche partielle, insensible à la position dans le texte).
- Seuls les contacts qui **ne sont pas des utilisateurs de l'application** apparaissent dans les résultats.
- La liste propose jusqu'à **100 résultats**, affichés sous la forme « Nom Prénom ».

---

## 6. Héritage des contacts (établissement parent → cellules)

Lorsqu'un établissement est une **cellule** (sous-établissement rattaché à un établissement parent), l'application applique un mécanisme d'héritage :

- L'application remonte la **hiérarchie des établissements parents** (jusqu'à deux niveaux maximum : parent direct et grand-parent).
- Les contacts de chaque établissement parent sont récupérés et affichés dans une section dédiée « Contacts des établissements parents ».
- Ces contacts hérités sont affichés en **lecture seule** : pas de bouton de modification ni de suppression.
- Ils apparaissent au-dessus des contacts propres à l'établissement courant, séparés par un trait horizontal.
- Si l'établissement n'a aucun parent, cette section n'apparaît pas.

Ce mécanisme permet notamment de visualiser le **Directeur unique de sécurité (DUS)** défini au niveau d'un établissement parent sans avoir à le dupliquer sur chaque cellule.

---

## 7. Règles de gestion

| Règle | Intitulé | Description |
|-------|----------|-------------|
| **RG-CTT-001** | Unicité de l'association | Un même contact ne peut être associé qu'une seule fois à une entité donnée. La clé d'unicité est le couple (entité, contact). |
| **RG-CTT-002** | Partage entre entités | Un contact peut être associé simultanément à plusieurs entités de types différents (un établissement, un dossier, un groupement, une commission). |
| **RG-CTT-003** | Modification globale | La modification d'une fiche contact est répercutée sur toutes les entités auxquelles ce contact est rattaché. Il n'existe pas de surcharge locale des informations. |
| **RG-CTT-004** | Civilité facultative | La civilité est optionnelle. Si l'utilisateur ne choisit pas de civilité (valeur « -- »), aucune civilité n'est enregistrée. |
| **RG-CTT-005** | Fonction « Maire » masquée en création | Lors de la création d'un nouveau contact (hors contexte commune), la fonction « Maire » n'est pas proposée dans la liste déroulante. Elle reste visible uniquement en édition d'un contact existant qui porte déjà cette fonction. |
| **RG-CTT-006** | Suppression avec dissociation | Lors de la suppression d'un contact, si celui-ci est encore rattaché à au moins une autre entité (quel que soit le type), seule l'association avec l'entité courante est supprimée. La fiche du contact est conservée. |
| **RG-CTT-007** | Suppression définitive | Lors de la suppression d'un contact, si celui-ci n'est rattaché à aucune autre entité, la fiche du contact est définitivement supprimée ainsi que son association. |
| **RG-CTT-008** | Verrouillage | Lorsqu'une entité est en mode verrouillé (lecture seule), les boutons « Ajouter un nouveau contact », « Ajouter un contact existant », « Modifier » et « Supprimer » sont masqués. Seule la consultation est possible. |
| **RG-CTT-009** | Héritage parent sur 2 niveaux | Pour un établissement de type cellule, les contacts sont hérités du parent direct et du grand-parent (maximum deux niveaux de remontée). |
| **RG-CTT-010** | Contacts hérités en lecture seule | Les contacts provenant d'un établissement parent sont affichés en lecture seule. Aucune action (modification, suppression) n'est possible depuis la fiche de la cellule. |
| **RG-CTT-011** | Tri alphabétique | Les contacts d'une entité sont systématiquement triés par nom de famille, par ordre alphabétique croissant. |
| **RG-CTT-012** | Recherche minimum 3 caractères | L'autocomplétion de recherche de contact existant ne se déclenche qu'à partir de 3 caractères saisis. |
| **RG-CTT-013** | Exclusion des utilisateurs | La recherche de contacts existants exclut les personnes disposant d'un compte utilisateur dans l'application. Seuls les contacts « purs » (sans compte) sont proposés. |
| **RG-CTT-015** | Fonction DUS et héritage | Le Directeur unique de sécurité (DUS) déclaré au niveau d'un établissement parent est automatiquement visible sur les cellules enfants via le mécanisme d'héritage, sans duplication de la fiche. |
| **RG-CTT-016** | Recherche contact pour un dossier | Un dossier peut récupérer les contacts de son établissement de rattachement, filtrés par fonction. Ce mécanisme est utilisé notamment pour pré-remplir le DUS lors de la création d'un dossier. |

---

## 8. Cas particuliers

### 8.1 Contact partagé entre un établissement et un dossier

Lorsqu'un dossier est créé pour un établissement, les contacts de cet établissement peuvent être repris dans le dossier. En particulier, le contact ayant la fonction « Directeur unique de sécurité » est recherché automatiquement parmi les contacts de l'établissement pour être associé au dossier.

### 8.2 Fonction masquée selon le contexte

- **Contexte « commune »** : le champ fonction est masqué dans l'interface mais pré-sélectionné sur « Maire ».
- **Contexte « groupement coordonnées »** : le champ fonction n'est pas affiché.
- **Contexte « utilisateur »** : un champ supplémentaire « Groupe » est affiché, permettant d'affecter le contact à un groupe d'utilisateurs.

### 8.3 Suppression et vérification multi-entités

Lors de la suppression, le système vérifie l'ensemble des associations du contact sur les quatre types d'entités (établissement, dossier, groupement, commission). Si au moins une association subsiste sur n'importe quel type d'entité, la fiche contact est conservée (seule la liaison courante est retirée).

### 8.4 Modification et impact transversal

La fiche contact est unique et partagée. Modifier le numéro de téléphone d'un exploitant depuis la fiche d'un établissement met immédiatement à jour cette information partout où ce contact apparaît (autres établissements, dossiers, commissions, groupements).

### 8.5 Affichage adapté selon le type d'entité

La présentation des fiches contacts est légèrement ajustée selon le type d'entité :
- Pour un **dossier**, les fiches sont affichées sur 3 colonnes.
- Pour les autres entités (établissement, groupement, commission), les fiches sont affichées sur 4 colonnes.
