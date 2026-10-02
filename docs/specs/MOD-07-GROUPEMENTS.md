# MOD-07 — Groupements de communes

## 1. Objet et périmètre

Ce module permet de gérer les **groupements de communes** (intercommunalités, communautés de communes, communautés d'agglomération, métropoles, etc.) au sein de l'application Prevarisc.

Un groupement est une structure administrative qui regroupe plusieurs communes sous un même périmètre géographique. Il constitue un niveau d'organisation intermédiaire entre la commune et le département, utilisé pour :

- Rattacher des **communes** à une structure intercommunale.
- Affecter des **préventionnistes référents** au périmètre géographique du groupement.
- Propager automatiquement l'affectation de ces préventionnistes aux **établissements**, **cellules** et **sites** situés dans les communes du groupement.
- Définir le **périmètre géographique de compétence des commissions de sécurité**.

Le module est accessible depuis le menu d'administration de l'application.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|---------------------|
| **Administrateur** | Crée, modifie et supprime les groupements. Affecte les communes et les préventionnistes. Lance la réaffectation des préventionnistes aux établissements. |
| **Préventionniste** | Est affecté à un ou plusieurs groupements. Devient référent des établissements situés dans les communes du groupement. |
| **Utilisateur consultant** | Consulte la fiche d'un groupement (coordonnées, communes, préventionnistes, contacts). |

---

## 3. Données d'un groupement

### 3.1 Informations générales

| Donnée | Description | Obligatoire |
|--------|-------------|:-----------:|
| **Libellé** | Nom du groupement (ex. : « Rennes Métropole »). | Oui |
| **Type de groupement** | Catégorie du groupement choisie parmi un référentiel (ex. : communauté de communes, communauté d'agglomération, métropole, etc.). | Oui |

### 3.2 Communes membres

Un groupement contient une liste de **communes**, identifiées par leur code INSEE. Une commune est sélectionnée depuis le référentiel national des communes disponible dans l'application.

- Chaque association lie un groupement à une commune via son code INSEE.
- Une même commune peut appartenir à plusieurs groupements de types différents (ex. : une commune peut être membre d'une communauté de communes **et** d'un syndicat intercommunal).

### 3.3 Préventionnistes référents

Un groupement est associé à un ou plusieurs **préventionnistes** (agents de prévention). Chaque affectation enregistre :

| Donnée | Description |
|--------|-------------|
| **Préventionniste** | Utilisateur de l'application ayant la fonction de préventionniste. |
| **Date de début d'affectation** | Date et heure à laquelle le préventionniste a été affecté au groupement. |

La recherche de préventionnistes se fait par autocomplétion sur le nom de l'agent, filtrée sur la fonction « préventionniste ».

### 3.4 Coordonnées du groupement

Chaque groupement possède une fiche de coordonnées propre :

| Donnée | Description |
|--------|-------------|
| **Civilité** | Civilité du responsable (optionnelle). |
| **Nom** | Nom du responsable ou de l'interlocuteur. |
| **Prénom** | Prénom du responsable. |
| **Téléphone fixe** | Numéro de téléphone fixe. |
| **Téléphone portable** | Numéro de téléphone portable. |
| **Fax** | Numéro de fax. |
| **Courriel** | Adresse e-mail de contact. |
| **Adresse postale** | Numéro, rue, code postal et ville. |

### 3.5 Contacts liés

Un groupement peut être associé à des **contacts externes** (personnes non utilisatrices de l'application). Chaque contact dispose des mêmes champs que les coordonnées (nom, prénom, téléphone, courriel, adresse, fonction, société). Les contacts sont gérés via le module transversal de gestion des contacts, partagé avec les établissements, les dossiers et les commissions.

---

## 4. Fonctionnalités

### 4.1 Consulter la liste des groupements

**Description :** L'utilisateur accède à la page d'administration des groupements de communes. La page affiche une liste déroulante de tous les groupements existants, regroupés par type de groupement. Les groupements sont triés par ordre alphabétique au sein de chaque type.

**Pré-condition :** Au moins une commune doit exister dans le référentiel. Si aucune commune n'est présente, un message d'information est affiché à la place de l'interface de gestion.

**Résultat :** L'utilisateur peut sélectionner un groupement dans la liste pour en afficher le détail.

### 4.2 Consulter la fiche d'un groupement

**Description :** Lorsqu'un groupement est sélectionné dans la liste, sa fiche détaillée se charge dynamiquement. La fiche est organisée en quatre onglets :

1. **Général** — Libellé, type et liste des communes membres.
2. **Préventionnistes affectés** — Liste des préventionnistes du groupement, avec un lien vers leur profil utilisateur.
3. **Coordonnées** — Fiche de coordonnées du groupement (nom du responsable, téléphones, courriel, adresse).
4. **Contacts liés au groupement** — Liste des contacts externes associés.

**Vue en lecture seule :** Une vue simplifiée existe pour la consultation embarquée dans d'autres modules (ex. : fiche d'un établissement). Elle affiche le libellé, le type, les coordonnées et les préventionnistes sans possibilité de modification.

### 4.3 Créer un groupement

**Description :** L'utilisateur clique sur « Créer un nouveau groupement ». Une boîte de dialogue s'ouvre avec deux champs :

1. **Libellé du groupement** (saisie libre).
2. **Type de groupement** (liste déroulante issue du référentiel des types).

Après validation, le groupement est créé et automatiquement sélectionné dans la liste. L'utilisateur peut ensuite compléter la fiche (communes, préventionnistes, coordonnées, contacts) et sauvegarder.

**Résultat :** Le groupement apparaît dans la liste, classé sous le bon type.

### 4.4 Modifier un groupement

**Description :** L'utilisateur sélectionne un groupement existant, modifie les informations souhaitées sur les différents onglets, puis clique sur « Sauvegarder le groupement ».

Les modifications possibles sont :
- Changer le libellé ou le type.
- Ajouter ou retirer des communes via le mécanisme de double liste (liste des communes disponibles ↔ liste des communes du groupement).
- Ajouter ou retirer des préventionnistes via l'autocomplétion.
- Mettre à jour les coordonnées du groupement.
- Gérer les contacts liés.

**Mécanisme de gestion des communes :** L'interface présente deux listes côte à côte :
- À gauche : toutes les communes disponibles (code INSEE + libellé).
- À droite : les communes actuellement membres du groupement.
- Des boutons fléchés permettent de transférer les communes d'une liste à l'autre.
- Les communes déjà affectées au groupement sont grisées dans la liste de gauche.

**Résultat :** Lors de la sauvegarde, la totalité des associations communes/groupement et préventionnistes/groupement est recalculée (suppression puis recréation complète).

### 4.5 Supprimer un groupement

**Description :** L'utilisateur clique sur « Supprimer le groupement ». Une boîte de confirmation s'affiche avec le message : *« Confirmez-vous la suppression du groupement ? (cette action est irréversible) »*.

Après confirmation :
- Tous les contacts liés au groupement sont dissociés.
- Toutes les associations avec les communes sont supprimées.
- Toutes les affectations de préventionnistes sont supprimées.
- Le groupement lui-même est supprimé.

L'utilisateur est redirigé vers la liste des groupements.

**Résultat :** Le groupement n'existe plus. Les communes, les préventionnistes et les contacts ne sont pas supprimés en eux-mêmes : seuls les liens avec le groupement sont retirés.

### 4.6 Réassigner les préventionnistes aux établissements

**Description :** Un bouton « Affecter aux établissements » est disponible dans l'onglet « Préventionnistes affectés », à condition qu'au moins un préventionniste soit affecté au groupement.

Le déclenchement de cette action effectue les opérations suivantes :

1. **Suppression** de toutes les affectations existantes de préventionnistes aux établissements, cellules et sites situés dans les communes du groupement.
2. **Recalcul** des affectations : chaque préventionniste du groupement est affecté à tous les établissements (de genre « établissement »), cellules (de genre « cellule ») et sites (de genre « site ») dont l'adresse se situe dans l'une des communes du groupement.
   - Pour les **établissements** : on considère l'adresse directe de l'établissement.
   - Pour les **cellules** : on considère l'adresse de l'établissement parent (la cellule hérite du périmètre géographique de son établissement père).
   - Pour les **sites** : on considère l'adresse de l'établissement fils lié au site.

**Pré-condition :** Le groupement doit avoir au moins un préventionniste affecté. Le bouton n'apparaît pas sinon.

**Résultat :** Tous les établissements, cellules et sites du périmètre géographique du groupement sont désormais affectés aux préventionnistes du groupement.

---

## 5. Lien avec les commissions de sécurité

Les groupements de communes jouent un rôle central dans la définition du périmètre de compétence des **commissions de sécurité** :

- **Membres de commission :** Un membre de commission peut être rattaché à un groupement de communes. Cela signifie que ce membre siège au titre de l'intercommunalité (et non d'une commune en particulier). L'interface de configuration d'une commission permet de choisir entre « Groupement de commune » et « Commune » pour chaque membre.

- **Règles de compétence géographique :** Les règles d'une commission de sécurité peuvent être définies à l'échelle d'un groupement de communes. Cela permet de déterminer automatiquement si un établissement situé dans une commune du groupement relève de la compétence de la commission.

- **Courriers :** Lors de la génération de courriers liés aux commissions, le système distingue les membres rattachés à un groupement de ceux rattachés directement à une commune (pour l'édition des procès-verbaux notamment).

- **Contrôle d'accès :** Le périmètre géographique des groupements est utilisé dans le calcul des droits d'accès. Le système détermine quels groupements couvrent un établissement donné (via le code INSEE de son adresse) pour évaluer si un utilisateur a le droit d'y accéder.

---

## 6. Règles de gestion

| N° | Règle |
|----|-------|
| **RG-GRP-001** | Un groupement possède obligatoirement un libellé et un type. |
| **RG-GRP-002** | Le type de groupement est choisi parmi un référentiel prédéfini (ex. : communauté de communes, communauté d'agglomération, métropole, syndicat intercommunal, etc.). |
| **RG-GRP-003** | Un groupement peut contenir zéro, une ou plusieurs communes. Les communes sont identifiées par leur code INSEE. |
| **RG-GRP-004** | Une même commune peut appartenir à plusieurs groupements, y compris de types différents. |
| **RG-GRP-005** | Un groupement peut avoir zéro, un ou plusieurs préventionnistes référents. |
| **RG-GRP-006** | Seuls les utilisateurs ayant la fonction « préventionniste » peuvent être affectés à un groupement. |
| **RG-GRP-007** | Chaque affectation d'un préventionniste à un groupement enregistre la date et l'heure de début de l'affectation (date du jour au moment de la sauvegarde). |
| **RG-GRP-008** | Lors de la sauvegarde d'un groupement, les associations communes et préventionnistes sont intégralement recalculées : toutes les associations existantes sont supprimées, puis les nouvelles sont créées. |
| **RG-GRP-009** | La suppression d'un groupement entraîne la suppression en cascade de toutes ses associations : contacts liés, communes membres et préventionnistes affectés. |
| **RG-GRP-010** | La suppression d'un groupement ne supprime ni les communes, ni les préventionnistes, ni les contacts eux-mêmes. Seuls les liens sont retirés. |
| **RG-GRP-011** | La réaffectation des préventionnistes aux établissements ne peut être déclenchée que si le groupement possède au moins un préventionniste. |
| **RG-GRP-012** | La réaffectation supprime d'abord toutes les affectations préventionniste-établissement existantes dans le périmètre du groupement, puis les recrée à partir de la liste actuelle des préventionnistes du groupement. |
| **RG-GRP-013** | La réaffectation traite trois catégories d'entités : les établissements (adresse directe), les cellules (adresse héritée de l'établissement parent) et les sites (adresse de l'établissement fils lié). |
| **RG-GRP-014** | Seule la dernière version des informations d'un établissement est prise en compte lors de la réaffectation des préventionnistes. |
| **RG-GRP-016** | La liste des groupements est affichée regroupée par type, puis triée alphabétiquement par libellé au sein de chaque type. |
| **RG-GRP-017** | Si aucune commune n'existe dans le référentiel, l'interface de gestion des groupements est remplacée par un message d'information. |
| **RG-GRP-018** | Les coordonnées d'un groupement (nom du responsable, téléphones, courriel, adresse) sont optionnelles. Si aucune coordonnée n'existe lors de la première sauvegarde, une fiche vide est créée automatiquement. |
| **RG-GRP-019** | Le champ civilité des coordonnées peut être laissé vide (valeur nulle acceptée). |
| **RG-GRP-020** | La suppression d'un groupement est irréversible. Une confirmation est demandée à l'utilisateur avant toute suppression. |
| **RG-GRP-021** | Un groupement est utilisable comme périmètre géographique dans les règles de compétence des commissions de sécurité. |
| **RG-GRP-022** | Un membre de commission de sécurité peut être rattaché à un groupement (au lieu d'une commune) pour représenter l'intercommunalité. |

---

## 7. Cas particuliers

### 7.1 Groupement sans commune

Un groupement peut être créé sans aucune commune. Dans ce cas :
- Le groupement existe dans la liste mais n'a aucun périmètre géographique effectif.
- La réaffectation des préventionnistes n'aura aucun effet (aucun établissement ne sera trouvé).
- Si ce groupement est utilisé dans une règle de commission, aucun établissement ne relèvera de cette commission via ce groupement.

### 7.2 Groupement sans préventionniste

Un groupement peut exister sans préventionniste affecté. Le bouton « Affecter aux établissements » n'est alors pas affiché.

### 7.3 Coordonnées absentes

Si un groupement n'a jamais eu de coordonnées renseignées et qu'on le modifie pour la première fois, une fiche de coordonnées vide est créée automatiquement avant la mise à jour.

### 7.4 Commune membre de plusieurs groupements

Ce cas est fonctionnellement normal. Une commune peut être membre de plusieurs groupements simultanément (par exemple, une communauté de communes et un syndicat de traitement des eaux). Les préventionnistes de chaque groupement seront tous affectés aux établissements de cette commune lors de la réaffectation.

### 7.5 Mise à jour des affectations

La réaffectation des préventionnistes utilise le libellé du groupement. Si ce libellé est modifié, il faut relancer l'affectation pour que les changements soient répercutés aux établissements.
