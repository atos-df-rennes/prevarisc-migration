# MOD-20 — Rétablissement d'éléments supprimés

## 1. Objet et périmètre

Ce module permet aux utilisateurs habilités de **consulter les éléments supprimés** (dossiers et établissements) et de les **rétablir** afin qu'ils redeviennent actifs dans l'application.

**Périmètre fonctionnel :**

- Consultation de la liste des dossiers supprimés.
- Consultation de la liste des établissements supprimés.
- Rétablissement unitaire d'un dossier.
- Rétablissement unitaire d'un établissement.

Le module ne gère pas la suppression elle-même. Il intervient uniquement en aval, une fois qu'un élément a été marqué comme supprimé.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|---------------------|
| **Administrateur habilité « suppression de dossiers »** | Peut consulter et rétablir les dossiers supprimés. |
| **Administrateur habilité « suppression d'établissements »** | Peut consulter et rétablir les établissements supprimés. |
| **Utilisateur sans habilitation** | Ne peut pas accéder au module. Un message d'interdiction lui est affiché. |

L'accès au module est contrôlé par deux privilèges distincts et indépendants :

- Le droit de supprimer (et donc rétablir) des **dossiers**.
- Le droit de supprimer (et donc rétablir) des **établissements**.

Un utilisateur peut disposer de l'un, de l'autre, ou des deux droits.

---

## 3. Principe de suppression logique

L'application Prevarisc ne supprime jamais physiquement un dossier ou un établissement. Elle applique une **suppression logique** :

- Lors de la suppression, une **date de suppression** est enregistrée sur l'élément, ainsi que l'**identité de l'utilisateur** ayant effectué la suppression.
- Tout élément portant une date de suppression est **exclu des résultats** de recherche, des listes et de la consultation courante. Il est considéré comme « supprimé » du point de vue métier.
- Le rétablissement consiste à **effacer la date de suppression** et l'identité du supprimeur, ce qui rend l'élément à nouveau visible et exploitable.

**Suppression en cascade :** lorsqu'un établissement est supprimé, tous ses dossiers associés sont également marqués comme supprimés. En revanche, le rétablissement ne déclenche aucune cascade (voir section « Cas particuliers »).

---

## 4. Fonctionnalités

### 4.1 Consulter les éléments supprimés

L'écran principal du module présente deux onglets :

1. **Onglet « Dossiers »** — affiché uniquement si l'utilisateur possède le droit de suppression des dossiers.
2. **Onglet « Établissements »** — affiché uniquement si l'utilisateur possède le droit de suppression des établissements.

L'onglet « Dossiers » est actif par défaut.

**Pour chaque dossier supprimé, les informations affichées sont :**

| Information | Description |
|-------------|-------------|
| Supprimé par | Nom de l'utilisateur ayant effectué la suppression, accompagné de son avatar. Si l'auteur est inconnu, la mention « Inconnu » est affichée avec un avatar par défaut. |
| Date de suppression | Affichée au format lisible (ex. : « Le lundi 12 janvier 2025 »). |
| Nom du dossier | Composé du type de dossier, de la nature du dossier et de l'objet du dossier, séparés par des tirets. L'objet est tronqué à 70 caractères avec la mention « (...) » si nécessaire. |
| Établissement lié | Libellé de l'établissement associé au dossier, affiché entre crochets devant le nom du dossier. |
| Lien de consultation | Le nom du dossier est un lien cliquable vers la fiche du dossier. |
| Bouton « Rétablir le dossier » | Permet de rétablir le dossier en un clic. |

Le compteur total d'éléments supprimés est affiché en haut de chaque onglet.

La liste est triée par **date de suppression décroissante** (le plus récemment supprimé en premier).

**Pour chaque établissement supprimé, les informations affichées sont :**

| Information | Description |
|-------------|-------------|
| Supprimé par | Nom de l'utilisateur ayant effectué la suppression, accompagné de son avatar. Si l'auteur est inconnu, la mention « Inconnu » est affichée avec un avatar par défaut. |
| Date de suppression | Affichée au format lisible. |
| Nom de l'établissement | Libellé de l'établissement, suivi du nom de la commune. |
| Bouton « Rétablir l'établissement » | Permet de rétablir l'établissement en un clic. |

La liste est triée par **date de suppression décroissante**.

---

### 4.2 Rétablir un établissement

**Déclencheur :** L'utilisateur clique sur le bouton « Rétablir l'établissement ».

**Traitement :**

1. La date de suppression de l'établissement est effacée.
2. L'identité du supprimeur est effacée.
3. L'établissement redevient visible dans les recherches et les listes.
4. Le cache de recherche est entièrement vidé pour refléter le changement.
5. Un message de succès est affiché : « L'établissement a bien été rétabli ».
6. L'utilisateur est redirigé vers la page précédente (l'écran de gestion des éléments supprimés).

---

### 4.3 Rétablir un dossier

**Déclencheur :** L'utilisateur clique sur le bouton « Rétablir le dossier ».

**Traitement :**

1. La date de suppression du dossier est effacée.
2. L'identité du supprimeur est effacée.
3. Le dossier redevient visible dans les recherches et les listes.
4. Le cache de recherche est entièrement vidé pour refléter le changement.
5. Un message de succès est affiché : « Le dossier a bien été rétabli ».
6. L'utilisateur est redirigé vers la page précédente.

---

## 5. Règles de gestion

| Référence | Règle |
|-----------|-------|
| **RG-RET-001** | L'accès au module de rétablissement nécessite au minimum l'un des deux droits suivants : droit de suppression des dossiers ou droit de suppression des établissements. |
| **RG-RET-002** | Un utilisateur ne possédant aucun de ces deux droits se voit afficher un message d'interdiction d'accès. Aucun onglet ne lui est présenté. |
| **RG-RET-003** | Un utilisateur possédant uniquement le droit de suppression des dossiers ne voit que l'onglet « Dossiers ». |
| **RG-RET-004** | Un utilisateur possédant uniquement le droit de suppression des établissements ne voit que l'onglet « Établissements ». |
| **RG-RET-005** | Un utilisateur possédant les deux droits voit les deux onglets. |
| **RG-RET-006** | Les listes d'éléments supprimés sont triées par date de suppression décroissante (le plus récent en premier). |
| **RG-RET-007** | L'objet d'un dossier supprimé est tronqué à 70 caractères dans la liste. Le texte complet est accessible au survol de la souris (info-bulle). |
| **RG-RET-008** | Le rétablissement d'un dossier efface sa date de suppression et l'identité du supprimeur. Le dossier redevient actif immédiatement. |
| **RG-RET-009** | Le rétablissement d'un établissement efface sa date de suppression et l'identité du supprimeur. L'établissement redevient actif immédiatement. |
| **RG-RET-010** | Le rétablissement d'un élément provoque l'invalidation complète du cache de recherche. |
| **RG-RET-011** | Un message de succès est affiché après chaque rétablissement réussi. |
| **RG-RET-012** | Après le rétablissement, l'utilisateur est automatiquement redirigé vers la page d'où il a déclenché l'action. |
| **RG-RET-013** | Lorsqu'un dossier est associé à un établissement unique, la suppression du dossier entraîne son marquage. Lorsque le dossier est partagé entre plusieurs établissements, il n'est supprimé logiquement que si la demande provient d'un contexte mono-établissement. |
| **RG-RET-014** | La suppression d'un établissement entraîne automatiquement la suppression logique de tous ses dossiers associés. Le rétablissement de l'établissement, en revanche, **ne rétablit pas automatiquement** ses dossiers. |
| **RG-RET-015** | Un établissement marqué comme supprimé est inaccessible. Toute tentative de consultation de sa fiche génère un message d'erreur indiquant que l'établissement n'existe pas. |
| **RG-RET-016** | Le rétablissement est une action immédiate, sans demande de confirmation préalable. |
| **RG-RET-017** | Lors de la suppression d'un dossier, sa date de passage en commission est également supprimée. Le rétablissement ne restaure pas cette date. |
| **RG-RET-018** | Aucune pagination n'est appliquée sur les listes d'éléments supprimés. Tous les éléments sont affichés en une seule page. |

---

## 6. Cas particuliers et limites

### Rétablissement partiel après suppression en cascade

Lorsqu'un établissement est supprimé, tous ses dossiers sont également marqués comme supprimés. Le rétablissement de l'établissement seul **ne rétablit pas les dossiers**. L'utilisateur doit rétablir chaque dossier individuellement depuis l'onglet « Dossiers ». Cela peut entraîner un état incohérent temporaire où un établissement actif possède des dossiers encore marqués comme supprimés.

### Perte de la date de passage en commission

Lors de la suppression d'un dossier, la date de passage en commission est supprimée. Le rétablissement du dossier ne restaure pas cette information. L'utilisateur devra la ressaisir manuellement si nécessaire.

### Supprimeur inconnu

Si l'utilisateur ayant effectué la suppression a été supprimé du système ou si l'information n'a pas été enregistrée (données historiques), la mention « Inconnu » est affichée à la place du nom.

### Absence de confirmation

Le rétablissement se déclenche en un seul clic, sans boîte de dialogue de confirmation. Une erreur de manipulation entraîne un rétablissement immédiat. L'élément peut être resupprimé depuis sa fiche, mais cela constitue une opération distincte.

### Absence de pagination

Les listes d'éléments supprimés ne sont pas paginées. Si le volume d'éléments supprimés est important, la page peut devenir lente à charger et difficile à parcourir.

### Dossiers multi-établissements

Un dossier peut être associé à plusieurs établissements. Lors de la suppression depuis un contexte mono-établissement, le système vérifie si le dossier est partagé. Si c'est le cas, le dossier n'est pas marqué comme supprimé (il reste visible pour les autres établissements). Si le dossier n'est associé qu'à un seul établissement, il est supprimé logiquement normalement.
