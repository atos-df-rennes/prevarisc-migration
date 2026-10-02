# MOD-05 — Utilisateurs et droits d'accès

> **Version :** 1.0
> **Date :** Juillet 2025
> **Statut :** Spécification fonctionnelle issue de l'analyse du module existant

---

## 1. Objet et périmètre

Ce document décrit le fonctionnement du module de gestion des utilisateurs, des groupes et des droits d'accès de l'application Prevarisc.

Le périmètre couvre :

- La création, la modification et la désactivation des comptes utilisateurs.
- La gestion des groupes d'utilisateurs.
- La matrice de droits d'accès (privilèges par groupe).
- Les ressources spécialisées (restrictions d'accès à des sous-ensembles de données).
- L'authentification (quatre méthodes disponibles).
- La protection contre les tentatives d'intrusion par force brute.
- Le système d'alertes lié aux droits des utilisateurs.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|---|---|
| **Administrateur** | Crée et modifie les utilisateurs, les groupes, la matrice de droits et les ressources spécialisées. Accède à l'ensemble du panneau d'administration. |
| **Préventionniste** | Utilisateur courant de l'application. Ses droits dépendent de son groupe et de ses domaines de compétences (commissions, groupements de communes, commune). |
| **Chef de groupement** | Utilisateur dont le périmètre géographique est défini par un ou plusieurs groupements de communes. |
| **Membre de commission** | Utilisateur rattaché à une ou plusieurs commissions de sécurité. Ce rattachement conditionne l'accès aux établissements suivis par ces commissions. |

---

## 3. Gestion des utilisateurs

### 3.1 Profil d'un utilisateur

Un utilisateur est décrit par les informations suivantes :

**Données de connexion :**

- Identifiant de connexion (login unique).
- Mot de passe (ou authentification déléguée — voir section 6).
- Statut actif / inactif.
- Dernière adresse réseau authentifiée (pour la protection anti-brute force).
- Compteur de tentatives de connexion échouées.
- Date de dernière activité dans l'application.

**Informations personnelles :**

- Civilité.
- Nom.
- Prénom.
- Grade (visible uniquement pour certaines fonctions, par exemple « Sapeur-pompier »).
- Fonction (choisie dans un référentiel : préventionniste, secrétaire, directeur, etc.).
- Téléphone fixe, téléphone portable, fax.
- Adresse de courriel.
- Site web.
- Adresse postale (champ libre).
- Photo (avatar) téléversée par l'administrateur, redimensionnée automatiquement en trois formats (petit, moyen, grand).

**Domaines de compétences :**

- **Groupe d'utilisateurs** : chaque utilisateur appartient à exactement un groupe (voir section 4).
- **Commune** : une commune de rattachement optionnelle.
- **Commissions** : une ou plusieurs commissions de sécurité (sélection multiple).
- **Groupements de communes** : un ou plusieurs groupements de communes (sélection multiple).

**Préférences utilisateur :**

- Chaque utilisateur dispose de préférences personnelles (configuration du tableau de bord, blocs affichés, etc.) créées automatiquement lors de la création du compte.

### 3.2 Créer un utilisateur

**Acteur :** Administrateur.

**Déroulement :**

1. L'administrateur accède au panneau d'administration des utilisateurs.
2. Il clique sur « Ajouter un utilisateur ».
3. Il saisit les données de connexion : login, mot de passe (ou coche « Connexion via LDAP/CAS/NTLM » si l'un de ces modes est activé sur la plateforme).
4. Il choisit le groupe d'appartenance.
5. Il renseigne les informations personnelles (nom, prénom, fonction, coordonnées).
6. Il définit les domaines de compétences : commune, commissions, groupements.
7. Il peut téléverser une photo de profil.
8. Il valide le formulaire.

**Contrôles à la validation :**

- Le login doit être unique. Si un login identique existe déjà, l'enregistrement est refusé avec un message explicite.
- Si le mode de sécurité renforcée est activé, le mot de passe doit respecter une politique de complexité (voir RG-USR-010).
- Si la case « Connexion via LDAP/CAS/NTLM » est cochée, aucun mot de passe local n'est enregistré ; le mot de passe est stocké comme vide.

**Résultat :** L'utilisateur est créé avec le statut « actif » par défaut. Ses préférences utilisateur sont initialisées automatiquement.

### 3.3 Modifier un utilisateur

**Acteur :** Administrateur.

**Déroulement :**

1. L'administrateur sélectionne un utilisateur dans la liste (actifs ou inactifs).
2. Il accède au formulaire pré-rempli.
3. Il peut modifier toutes les informations sauf l'identifiant interne.
4. Le champ mot de passe est verrouillé par défaut. Un lien « Changer le mot de passe » permet de le déverrouiller.
5. Il peut modifier le groupe, les commissions, les groupements, la commune.
6. Il peut débloquer un utilisateur verrouillé par la protection anti-brute force en remettant le compteur de tentatives échouées à zéro.

**Contrôle :** Même unicité de login que lors de la création.

### 3.4 Activer / désactiver un utilisateur

- Un utilisateur possède un statut **actif** ou **inactif**, matérialisé par une case à cocher dans le formulaire de modification.
- Un utilisateur **inactif** ne peut pas se connecter. Le système vérifie ce statut au moment de l'authentification.
- Il n'existe pas de suppression définitive d'un utilisateur. La désactivation est le mécanisme prévu pour retirer l'accès.
- La liste des utilisateurs affiche séparément les utilisateurs actifs et les utilisateurs inactifs, avec leur compteur respectif.

**Indicateur de présence :** La liste affiche un badge « En ligne » ou « Hors ligne » selon la date de dernière activité de l'utilisateur, comparée à la durée de session configurée sur le serveur.

---

## 4. Groupes d'utilisateurs

### 4.1 Notion de groupe

Un groupe d'utilisateurs est un regroupement logique qui détermine :

- Les **droits d'accès** de tous les utilisateurs qui en font partie, via la matrice des droits (voir section 5).
- Le **libellé** affiché dans les listes et les filtres.
- Une **description** libre à destination des administrateurs.

Chaque utilisateur appartient à **un et un seul** groupe.

Le groupe est le **rôle** au sens du contrôle d'accès. Tous les privilèges sont définis au niveau du groupe, jamais au niveau de l'utilisateur individuel.

Il existe un **groupe par défaut** (le premier groupe créé dans le système). Il ne peut pas être supprimé.

### 4.2 Créer et modifier un groupe

**Acteur :** Administrateur.

**Créer un groupe :**

1. L'administrateur clique sur « Créer un groupe ».
2. Il saisit le libellé et la description.
3. Il valide.

Le groupe est créé sans aucun privilège. L'administrateur doit ensuite configurer ses droits dans la matrice (section 5).

**Modifier un groupe :**

1. L'administrateur sélectionne un groupe dans la barre latérale de la liste des utilisateurs.
2. Il clique sur « Modifier le groupe ».
3. Il peut modifier le libellé et la description.
4. Il valide.

La modification d'un groupe invalide le cache des droits d'accès pour tous les utilisateurs rattachés.

### 4.3 Supprimer un groupe

**Acteur :** Administrateur.

**Conditions :**

- Le groupe par défaut ne peut pas être supprimé.
- Lorsqu'un groupe est supprimé, **tous ses utilisateurs sont automatiquement transférés dans le groupe par défaut**.

**Déroulement :**

1. L'administrateur accède à la modification du groupe.
2. Il clique sur « Supprimer le groupe ».
3. Le système transfère les utilisateurs du groupe supprimé vers le groupe par défaut.
4. Le groupe et ses associations de privilèges sont supprimés.

---

## 5. Droits d'accès

### 5.1 Principe de la matrice de droits

Le système de contrôle d'accès repose sur trois concepts :

| Concept | Description |
|---|---|
| **Rôle** | Le groupe de l'utilisateur (ex : « Administrateurs », « Préventionnistes », « Secrétaires »). |
| **Ressource** | Un objet ou une fonctionnalité de l'application sur lequel on contrôle l'accès (ex : « Établissements », « Dossiers », « Créations », « Commissions »). |
| **Privilège** | Une action possible sur une ressource (ex : « Lecture », « Modifier », « Ajouter un établissement », « Verrouillage d'un dossier »). |

**Fonctionnement :**

- La matrice de droits est une grille croisée **groupes × (ressources + privilèges)**.
- Pour chaque groupe, l'administrateur coche ou décoche chaque privilège sur chaque ressource.
- Si un privilège est coché pour un groupe, tous les membres de ce groupe disposent de ce droit.
- Si un privilège n'est pas coché, l'accès est refusé et l'utilisateur est redirigé vers une page d'erreur « Accès non autorisé ».

**Organisation de la matrice :**

- La matrice est présentée sous forme d'accordéon : un panneau par groupe.
- Dans chaque panneau, un tableau liste les ressources avec, pour chacune, la liste de ses privilèges sous forme de cases à cocher.
- L'enregistrement de la matrice met à jour les droits immédiatement et vide le cache de contrôle d'accès.

**Exemple de ressources et privilèges courants :**

| Ressource | Privilèges possibles |
|---|---|
| Établissement (par genre/type) | Lecture, Modifier |
| Dossier (par nature) | Lecture, Modifier, Verrouillage d'un dossier |
| Créations | Ajouter un établissement, Ajouter un dossier |
| Commissions | Lecture, Modifier |
| Alertes | Alerte sur changement de statut, Alerte sur changement d'avis, Alerte sur changement de classement |

### 5.2 Ressources spécialisées

Au-delà des ressources génériques, le système permet de créer des **ressources spécialisées** qui restreignent l'accès à des sous-ensembles précis de données. C'est un mécanisme puissant et central dans Prevarisc.

#### 5.2.1 Ressources spécialisées pour les établissements

Une ressource spécialisée sur les établissements combine plusieurs critères de filtrage qui varient selon le **genre** de l'établissement.

> **Principe universel de sérialisation** : Lors de la création d'une ressource spécialisée, **seuls les critères visibles dans le formulaire front** sont sérialisés dans le nom de la ressource. Les paramètres **non affichés au formulaire** sont marqués `0` (ou `1` s'il s'agit de critères dynamiques utilisateur). Cette approche permet une configuration centralisée : les champs masqués au front auront toujours la valeur `0` en base, ce qui signifie "ignorer ce filtre" ou "s'adapter au runtime selon le profil de l'utilisateur".

Par exemple :
- Pour une **Cellule**, seul le type d'activité est sélectionnable au front → la catégorie est toujours `0`
- Pour une **Habitation**, seule la famille est sélectionnable → commissions/groupements ne sont pas exposées, donc toujours `0` ou `1` (remplaçable)
- Pour un **BUP**, aucun critère technique n'est sélectionnable → tous les critères (groupements, commune) sont toujours `0` ou `1`

**Établissement recevant du public (ERP) — Formulaire front affiche :** Types d'activité, Catégories

| Critère | Valeur en ressource | Notes |
|---|---|---|
| Types d'activité | ✅ Sérialisé | Sélection multiple au front (ex: `erp_2-5_3-7_...`) |
| Catégories | ✅ Sérialisé | Sélection multiple au front (ex: `erp_..._0_0_0`) |
| Commissions | `0` ou `1` | Paramètre invisible au front → toujours `0` (ignorer) ou `1` (remplaçable par profil utilisateur) |
| Groupements | `0` ou `1` | Paramètre invisible au front → toujours `0` ou `1` |
| Commune | `0` ou `1` | Paramètre invisible au front → toujours `0` ou `1` |

**Cellule — Formulaire front affiche :** Types d'activité UNIQUEMENT

| Critère | Valeur en ressource | Notes |
|---|---|---|
| Types d'activité | ✅ Sérialisé | Sélection multiple au front (ex: `cell_2-5_0`) |
| Catégories | `0` | Paramètre **invisible** au front → toujours `0` (ignorer) |

Le formulaire cellule reste inchangé. Si une ressource existante contient néanmoins une liste de catégories non nulle, l'accès exige une correspondance avec la **catégorie effective** de la cellule (RG-ETB-062), en plus du type d'activité. Les listes sont évaluées par identifiant exact ; `0` autorise toutes les valeurs, y compris une catégorie absente. Les ressources standard dont la catégorie est `0` conservent leurs droits.

**Habitation — Formulaire front affiche :** Familles UNIQUEMENT

| Critère | Valeur en ressource | Notes |
|---|---|---|
| Familles | ✅ Sérialisé | Sélection multiple au front (ex: `hab_1-3_0_0`) |
| Groupements | `0` ou `1` | Paramètre invisible au front → toujours `0` ou `1` |
| Commune | `0` ou `1` | Paramètre invisible au front → toujours `0` ou `1` |

**Immeuble de grande hauteur (IGH) — Formulaire front affiche :** Classes UNIQUEMENT

| Critère | Valeur en ressource | Notes |
|---|---|---|
| Classes | ✅ Sérialisé | Sélection multiple au front (ex: `igh_A-B_0_0_0`) |
| Commissions | `0` ou `1` | Paramètre invisible au front → toujours `0` ou `1` |
| Groupements | `0` ou `1` | Paramètre invisible au front → toujours `0` ou `1` |
| Commune | `0` ou `1` | Paramètre invisible au front → toujours `0` ou `1` |

**Bâtiment d'Utilité Publique (BUP), Camping, Manifestation temporaire, IOP — Formulaire front affiche :** Aucun critère

| Critère | Valeur en ressource | Notes |
|---|---|---|
| Groupements | `0` ou `1` | Aucun sélecteur au front → toujours `0` ou `1` |
| Commune | `0` ou `1` | Aucun sélecteur au front → toujours `0` ou `1` |

**Zone — Formulaire front affiche :** Classements UNIQUEMENT

| Critère | Valeur en ressource | Notes |
|---|---|---|
| Classements | ✅ Sérialisé | Sélection multiple au front (ex: `zone_1-3_0_0`) |
| Groupements | `0` ou `1` | Paramètre invisible au front → toujours `0` ou `1` |
| Commune | `0` ou `1` | Paramètre invisible au front → toujours `0` ou `1` |

**Interprétation des valeurs `0` et `1` pour les critères dynamiques :**

- `0` = **Ignorer ce filtre** — tout utilisateur a accès (sans considération de son profil pour ce critère)
- `1` = **Adapter au profil utilisateur** — seuls les utilisateurs ayant ce critère dans leur profil ont accès (ex: leurs commissions, groupements, commune)

Lorsqu'un critère est configuré sur `1`, le système injecte dynamiquement les valeurs réelles de l'utilisateur au moment du contrôle d'accès. Cela signifie que **deux utilisateurs du même groupe peuvent avoir accès à des établissements différents** en fonction de leurs domaines de compétences respectifs.

Chaque ressource spécialisée pour les établissements génère automatiquement deux privilèges : **Lecture** et **Modifier**.

#### 5.2.2 Ressources spécialisées pour les dossiers

Une ressource spécialisée sur les dossiers se définit par :

| Critère | Options |
|---|---|
| Natures de dossier | Une ou plusieurs natures sélectionnées, ou « Toutes les natures » |

Chaque ressource spécialisée pour les dossiers génère automatiquement trois privilèges : **Lecture**, **Modifier** et **Verrouillage d'un dossier**.

> ⚠️ **Limitation implémentée** : Le formulaire d'administration accepte une sélection de plusieurs natures, mais **les ressources multi-natures (ex: `dossier_1-2`) ne sont pas résolues par le moteur de contrôle d'accès**. Seules les ressources simples de type `dossier_<ID_NATURE>` sont traitées lors des vérifications d'accès. Cette limitation existe aussi dans l'implémentation legacy et n'a pas été changée lors de la migration.
>
> **Options de correction à envisager** :
> - Implémenter réellement la résolution des ressources multi-natures dans le moteur ACL.
> - Modifier le formulaire d'administration pour imposer une sélection unique de nature (remplacer le sélecteur multiple par un simple `<select>`).

#### 5.2.3 Nommage et affichage

- Chaque ressource spécialisée reçoit un nom descriptif automatique résumant ses critères (ex : « Établissement (Types J-L — Catégories 1-2 — Sur les commissions de l'utilisateur — Ignorer les groupements — Ignorer la commune) »).
- L'administrateur peut remplacer ce nom par un libellé personnalisé.
- Les ressources spécialisées apparaissent dans la matrice des droits au même titre que les ressources génériques.

#### 5.2.4 Mécanisme de résolution des droits pour un établissement

Lorsqu'un utilisateur tente d'accéder à un établissement, le système :

1. Détermine le genre de l'établissement (ERP, IGH, habitation, etc.).
2. Récupère les caractéristiques de l'établissement (type d'activité, catégorie, commission compétente, adresse).
3. Construit un identifiant de ressource spécifique à cet établissement.
4. Injecte dans les ressources spécialisées du groupe de l'utilisateur les valeurs réelles de ses commissions, groupements et commune.
5. Développe les combinaisons multivaluées (si la ressource couvre plusieurs types ou plusieurs groupements, toutes les variantes sont générées).
6. Vérifie si au moins une ressource du groupe autorise l'accès avec le privilège demandé (lecture ou modification).

Pour les **sites** (qui englobent plusieurs établissements enfants), le contrôle s'applique sur chacun des établissements du site.

Pour les **dossiers**, le système vérifie à la fois les droits sur le dossier (via sa nature) et les droits sur les établissements rattachés au dossier.

### 5.3 Configurer la matrice des droits

**Acteur :** Administrateur.

**Accès :** Menu d'administration > « Gérer les droits des groupes » > Onglet « Matrice des droits ».

**Déroulement :**

1. La matrice affiche un accordéon avec un panneau par groupe.
2. L'administrateur déplie le panneau du groupe à configurer.
3. Pour chaque ressource, il coche ou décoche les privilèges souhaités.
4. Il clique sur « Sauvegarder ».
5. Le cache des droits est vidé automatiquement. Les nouveaux droits s'appliquent immédiatement.

**Gestion des ressources spécialisées :**

L'onglet « Gestion des ressources spécialisées » permet à l'administrateur de :

- **Ajouter** une ressource spécialisée (établissement ou dossier) en définissant ses critères via un formulaire dédié.
- **Supprimer** une ressource spécialisée existante. La suppression entraîne la suppression en cascade de ses privilèges et de toutes les associations groupe–privilège correspondantes.

Les ressources spécialisées ajoutées apparaissent ensuite automatiquement dans la matrice des droits.

---

## 6. Authentification

### 6.1 Méthodes d'authentification disponibles

L'application supporte quatre méthodes d'authentification, activables par configuration de la plateforme. Elles sont évaluées dans un ordre de priorité précis.

#### 6.1.1 Authentification par base de données locale

- **Contexte d'utilisation :** Méthode par défaut, toujours disponible. Utilisée lorsqu'aucune authentification déléguée n'est activée.
- **Fonctionnement :** L'utilisateur saisit son identifiant et son mot de passe dans un formulaire de connexion. Le mot de passe est comparé à celui stocké dans l'application (haché avec un sel de sécurité propre à l'instance).
- **Cas d'usage :** SDIS de petite taille ou environnements sans annuaire centralisé.

#### 6.1.2 Authentification CAS (Central Authentication Service)

- **Contexte d'utilisation :** Environnements disposant d'un serveur d'authentification unique (SSO). Activée par configuration de la plateforme.
- **Fonctionnement :** L'utilisateur est automatiquement redirigé vers le portail d'authentification centralisé. Après validation de ses identifiants sur le portail, il est renvoyé vers Prevarisc. Le mot de passe n'est jamais saisi ni stocké dans Prevarisc.
- **Cas d'usage :** SDIS utilisant un portail SSO pour l'ensemble de leurs applications.
- **Particularité à la déconnexion :** La déconnexion de Prevarisc entraîne également la déconnexion du portail SSO.

#### 6.1.3 Authentification NTLM (Authentification Windows intégrée)

- **Contexte d'utilisation :** Environnements Windows avec un domaine Active Directory. Activée par configuration de la plateforme.
- **Fonctionnement :** L'identifiant Windows de l'utilisateur est transmis automatiquement par le navigateur (format DOMAINE\utilisateur). Aucune saisie de mot de passe n'est nécessaire dans l'application.
- **Cas d'usage :** SDIS dont les postes de travail sont intégrés à un domaine Windows.
- **Particularité à la déconnexion :** Un message spécifique informe l'utilisateur qu'il a été déconnecté (pas de redirection vers un portail tiers).

#### 6.1.4 Authentification LDAP (Annuaire d'entreprise)

- **Contexte d'utilisation :** Environnements disposant d'un annuaire LDAP. Peut être activée en complément de l'authentification locale.
- **Fonctionnement :** L'utilisateur saisit son identifiant et son mot de passe dans le formulaire standard. Le système tente d'abord l'authentification locale, puis, en cas d'échec, l'authentification auprès de l'annuaire LDAP.
- **Cas d'usage :** SDIS souhaitant centraliser la gestion des mots de passe dans un annuaire existant tout en conservant une possibilité d'authentification locale.

#### 6.1.5 Ordre d'évaluation des méthodes

Lorsque plusieurs méthodes sont activées simultanément :

1. Si CAS est activé : seul CAS est utilisé (pas de formulaire de saisie de mot de passe).
2. Si NTLM est activé : l'identifiant Windows est capturé automatiquement (pas de formulaire de saisie de mot de passe).
3. Si LDAP est activé : le formulaire est présenté, et le mot de passe est vérifié d'abord localement, puis auprès de l'annuaire LDAP.
4. Sinon : authentification locale uniquement.

Le système essaie les adaptateurs d'authentification dans l'ordre. Dès qu'un adaptateur valide les identifiants, la connexion est accordée.

**Indication dans le formulaire de création d'utilisateur :** Lorsqu'au moins un mode d'authentification déléguée (CAS, NTLM, LDAP) est activé, une case à cocher « Connexion via LDAP/CAS/NTLM » apparaît dans le formulaire de création/modification de l'utilisateur. Si cette case est cochée, aucun mot de passe local n'est défini.

### 6.2 Protection anti-brute force

Un mécanisme de protection est disponible lorsque le mode de sécurité renforcée est activé sur la plateforme.

**Fonctionnement :**

1. À chaque tentative de connexion échouée, le compteur de tentatives échouées de l'utilisateur est incrémenté.
2. Lorsque le compteur atteint le seuil de **2 tentatives échouées**, le système active une restriction par adresse réseau.
3. Seule l'adresse réseau ayant réussi la dernière connexion légitime est autorisée à poursuivre les tentatives. Toute tentative depuis une autre adresse est rejetée silencieusement.
4. En cas de connexion réussie, le compteur est remis à zéro et la dernière adresse réseau authentifiée est mise à jour.

**Déblocage :** Un administrateur peut manuellement débloquer un utilisateur verrouillé en remettant le compteur de tentatives à zéro dans le formulaire de modification de l'utilisateur. Un bouton « Débloquer » est présenté lorsque le compteur a atteint le seuil.

### 6.3 Connexion et déconnexion

**Connexion :**

1. L'utilisateur accède à l'application.
2. S'il n'est pas authentifié, il est redirigé vers la page de connexion.
3. L'URL initialement demandée est mémorisée. Après une connexion réussie, l'utilisateur est redirigé vers cette URL (ou vers la page d'accueil par défaut).
4. Si l'utilisateur est inexistant ou inactif, un message « Authentification invalide » est affiché, sans préciser la cause exacte (protection contre l'énumération de comptes).

**Déconnexion :**

1. L'utilisateur se déconnecte explicitement.
2. La date de dernière activité est mise à jour.
3. La session est entièrement détruite.
4. Selon le mode d'authentification :
   - CAS : l'utilisateur est déconnecté du portail SSO.
   - NTLM (sans mot de passe local) : un message de déconnexion est affiché.
   - Autres cas : redirection vers la page de connexion.

---

## 7. Règles de gestion

| Identifiant | Règle |
|---|---|
| **RG-USR-001** | Chaque utilisateur appartient à exactement un groupe. Il ne peut pas être sans groupe ni appartenir à plusieurs groupes simultanément. |
| **RG-USR-002** | L'identifiant de connexion (login) est unique dans l'ensemble du système. Toute tentative de création ou de modification avec un login existant est refusée. |
| **RG-USR-003** | Un utilisateur inactif ne peut pas se connecter. Le système vérifie le statut actif au moment de l'authentification. |
| **RG-USR-004** | La suppression d'un utilisateur n'existe pas. La désactivation (passage en inactif) est le seul mécanisme de retrait d'accès. |
| **RG-USR-005** | Les droits d'accès sont définis exclusivement au niveau du groupe, jamais au niveau individuel. |
| **RG-USR-006** | La suppression d'un groupe entraîne le transfert automatique de tous ses utilisateurs vers le groupe par défaut. |
| **RG-USR-007** | Le groupe par défaut (premier groupe du système) ne peut pas être supprimé. |
| **RG-USR-008** | Les ressources spécialisées sur les établissements sont paramétrées dynamiquement en fonction des commissions, groupements et commune de l'utilisateur connecté. Deux utilisateurs du même groupe peuvent donc avoir accès à des établissements différents. |
| **RG-USR-009** | Lorsqu'un utilisateur accède à un dossier, le système vérifie à la fois les droits sur la nature du dossier et les droits sur les établissements rattachés au dossier. L'accès n'est accordé que si les deux conditions sont remplies. |
| **RG-USR-010** | En mode de sécurité renforcée, le mot de passe doit contenir au minimum 8 caractères, dont au moins 1 minuscule, 1 majuscule, 1 chiffre et 1 caractère spécial. |
| **RG-USR-011** | Après 2 tentatives de connexion échouées en mode de sécurité renforcée, seule l'adresse réseau de la dernière connexion réussie peut continuer à tenter de s'authentifier. |
| **RG-USR-012** | Lorsqu'un utilisateur créé avec authentification déléguée (CAS, NTLM, LDAP) se connecte, aucun mot de passe local n'est vérifié. |
| **RG-USR-013** | Un message d'erreur générique « Authentification invalide » est affiché en cas d'échec, que la cause soit un identifiant inconnu, un compte inactif ou un mot de passe erroné. |
| **RG-USR-014** | L'enregistrement de la matrice des droits invalide immédiatement le cache de contrôle d'accès. Les nouveaux droits s'appliquent sans nécessité de reconnexion. |
| **RG-USR-015** | La création d'une ressource spécialisée sur les établissements génère automatiquement deux privilèges : Lecture et Modifier. |
| **RG-USR-016** | La création d'une ressource spécialisée sur les dossiers génère automatiquement trois privilèges : Lecture, Modifier et Verrouillage. |
| **RG-USR-017** | La suppression d'une ressource spécialisée supprime en cascade tous ses privilèges et les associations de droits correspondantes dans la matrice. |
| **RG-USR-018** | La date de dernière activité de l'utilisateur est mise à jour à chaque requête traitée par l'application et au moment de la déconnexion. |
| **RG-USR-019** | Les préférences utilisateur (configuration du tableau de bord) sont initialisées automatiquement à la création du compte. |
| **RG-USR-020** | Le grade n'est visible et modifiable dans le formulaire que lorsque la fonction sélectionnée le requiert (par exemple : sapeur-pompier). |
| **RG-USR-021** | Après une connexion réussie, l'utilisateur est redirigé vers l'URL qu'il avait initialement demandée, ou vers la page d'accueil par défaut. |
| **RG-USR-022** | Les utilisateurs ayant le privilège d'alerte approprié et dont les domaines de compétences (commune ou groupements de communes) correspondent à l'adresse de l'établissement concerné reçoivent une notification en cas de changement de statut, d'avis ou de classement d'un établissement. |
| **RG-USR-023** | L'accès à un établissement de type « site » nécessite que l'utilisateur ait les droits sur au moins un des établissements enfants du site, ou le droit de création d'établissement. |
| **RG-USR-024** | Le filtrage de la liste des utilisateurs par groupe est possible via la barre latérale du panneau d'administration. |

---

## 8. Cas particuliers

### 8.1 Utilisateur sans mot de passe local

Lorsqu'un utilisateur est créé avec la case « Connexion via LDAP/CAS/NTLM » cochée, son mot de passe local est vide. Cet utilisateur ne peut se connecter qu'avec la méthode d'authentification déléguée activée. Si cette méthode est désactivée ultérieurement, l'utilisateur ne pourra plus se connecter tant qu'un administrateur ne lui aura pas défini un mot de passe local.

### 8.2 Changement de mode d'authentification

Si un utilisateur possède un mot de passe local et qu'un administrateur coche « Connexion via LDAP/CAS/NTLM », le mot de passe local est supprimé. Le champ mot de passe est masqué dans le formulaire.

### 8.3 Utilisateur rattaché à aucune commission et aucun groupement

Un utilisateur peut ne pas être rattaché à des commissions ou des groupements. Dans ce cas :

- Les ressources spécialisées configurées avec « Sur les commissions de l'utilisateur » ou « Sur les groupements de l'utilisateur » ne correspondront à aucun critère pour cet utilisateur.
- En pratique, cet utilisateur n'aura accès qu'aux établissements dont les ressources spécialisées ignorent les commissions et les groupements.

### 8.4 Ressource spécialisée avec tous les critères à « Ignorer »

Si une ressource spécialisée d'établissement est créée en ignorant les commissions, les groupements et la commune, et en sélectionnant « Tous les types » et « Toutes les catégories », elle donne accès à tous les établissements du genre concerné, sans restriction géographique ni fonctionnelle.

### 8.5 Multi-commissions et multi-groupements

Un utilisateur peut être rattaché à plusieurs commissions et plusieurs groupements simultanément. Le système combine automatiquement ces rattachements : l'accès est accordé si l'établissement correspond à **l'une quelconque** des commissions ou à **l'un quelconque** des groupements de l'utilisateur (logique « OU »).

### 8.6 Préventionnistes suggérés pour un établissement

Le système peut suggérer automatiquement des préventionnistes pour un établissement donné, en suivant cet ordre de priorité :

1. Si l'établissement a un établissement parent et que des préventionnistes sont déjà rattachés à ce parent, ce sont eux qui sont suggérés.
2. Sinon, les préventionnistes rattachés aux groupements de communes couvrant la commune de l'établissement sont proposés.

### 8.7 Contacts et utilisateurs

Les informations personnelles d'un utilisateur (nom, prénom, fonction, coordonnées) sont partagées avec le référentiel des contacts. Un enregistrement de contact peut exister indépendamment d'un compte utilisateur. Les contacts qui ne sont pas associés à un compte utilisateur sont utilisés comme contacts externes (établissement, dossier, groupement, commission).

### 8.8 Système d'alertes et droits

Les alertes (changement de statut, changement d'avis, changement de classement d'un établissement) sont envoyées uniquement aux utilisateurs qui :

- Appartiennent à un groupe possédant le privilège d'alerte correspondant.
- Ont une adresse de courriel renseignée et non vide.
- Ont un périmètre géographique (commune directe ou communes de leurs groupements) incluant la commune de l'établissement concerné.

### 8.9 Cache et performance

Les droits d'accès et les données utilisateur sont mis en cache pour des raisons de performance. Ce cache est invalidé automatiquement :

- Lors de la modification d'un utilisateur.
- Lors de la modification ou de la suppression d'un groupe.
- Lors de l'enregistrement de la matrice des droits.
- Lors de la modification des préférences utilisateur.
