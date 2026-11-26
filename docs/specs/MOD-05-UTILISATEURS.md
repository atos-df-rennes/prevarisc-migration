# MOD-05 — Utilisateurs et droits d'accès

---

## 1. Objet et périmètre

Ce document décrit le fonctionnement du module de gestion des utilisateurs, des groupes et des droits d'accès de l'application Prevarisc.

Le périmètre couvre :

- La création, la modification et la désactivation des comptes utilisateurs.
- La suppression définitive des utilisateurs d'un groupe par une commande d'exploitation.
- La gestion des groupes d'utilisateurs.
- La matrice des droits d'accès par groupe.
- Les règles d'accès qui limitent les établissements ou dossiers qu'un groupe peut consulter.
- L'authentification (quatre méthodes disponibles).
- La protection contre les tentatives répétées de connexion.
- Le système d'alertes lié aux droits des utilisateurs.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|---|---|
| **Administrateur** | Crée et modifie les utilisateurs, les groupes, les droits d'accès et les règles qui limitent l'accès à certains établissements ou dossiers. Accède à l'ensemble du panneau d'administration. |
| **Préventionniste** | Utilisateur courant de l'application. Ses droits dépendent de son groupe et de ses domaines de compétences (commissions, groupements de communes, commune). |
| **Chef de groupement** | Utilisateur dont le périmètre géographique est défini par un ou plusieurs groupements de communes. |
| **Membre de commission** | Utilisateur rattaché à une ou plusieurs commissions de sécurité. Ce rattachement conditionne l'accès aux établissements suivis par ces commissions. |

---

## 3. Gestion des utilisateurs

### 3.1 Profil d'un utilisateur

Un utilisateur est décrit par les informations suivantes :

**Données de connexion :**

- Identifiant de connexion (unique).
- Mot de passe, sauf si le compte utilise le mode de connexion de l'organisation (voir section 6).
- Statut actif / inactif.
- Tentatives de connexion infructueuses.
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
3. Il saisit l'identifiant et le mot de passe, ou indique que le compte utilise le mode de connexion de l'organisation.
4. Il choisit le groupe d'appartenance.
5. Il renseigne les informations personnelles (nom, prénom, fonction, coordonnées).
6. Il définit les domaines de compétences : commune, commissions, groupements.
7. Il peut téléverser une photo de profil.
8. Il valide le formulaire.

**Contrôles à la validation :**

- L'identifiant doit être unique. Si un identifiant identique existe déjà, l'enregistrement est refusé avec un message explicite.
- Si le mode de sécurité renforcée est activé, le mot de passe doit respecter une politique de complexité (voir RG-USR-010).
- Si le compte utilise le mode de connexion de l'organisation, aucun mot de passe Prevarisc n'est défini.

**Résultat :** L'utilisateur est créé avec le statut « actif » par défaut. Ses préférences utilisateur sont initialisées automatiquement.

### 3.3 Modifier un utilisateur

**Acteur :** Administrateur.

**Déroulement :**

1. L'administrateur sélectionne un utilisateur dans la liste (actifs ou inactifs).
2. Il accède au formulaire pré-rempli.
3. Il peut modifier toutes les informations sauf l'identifiant de connexion.
4. Le champ mot de passe est verrouillé par défaut. Un lien « Changer le mot de passe » permet de le déverrouiller.
5. Il peut modifier le groupe, les commissions, les groupements, la commune.
6. Il peut débloquer un utilisateur dont le compte a été bloqué après des tentatives de connexion infructueuses.

**Contrôle :** Même unicité de l'identifiant que lors de la création.

### 3.4 Activer / désactiver un utilisateur

- Un utilisateur possède un statut **actif** ou **inactif**, matérialisé par une case à cocher dans le formulaire de modification.
- Un utilisateur **inactif** ne peut pas se connecter. Le système vérifie ce statut au moment de l'authentification.
- L'interface ne propose pas de suppression d'un utilisateur. La désactivation est le mécanisme prévu pour retirer l'accès depuis l'application. La suppression définitive est réservée à l'exploitant, par groupe, via une commande (voir section 3.5).
- La liste des utilisateurs affiche séparément les utilisateurs actifs et les utilisateurs inactifs, avec leur compteur respectif.

**Indicateur de présence :** La liste affiche un badge « En ligne » ou « Hors ligne » selon la dernière activité de l'utilisateur et la durée de connexion autorisée.

### 3.5 Supprimer définitivement les utilisateurs d'un groupe

**Acteur :** Exploitant de l'application (accès en ligne de commande au serveur). Cette fonction n'est pas disponible dans l'interface web.

**Préconditions :** La commande est exécutée en mode interactif. Une exécution non interactive est refusée.

**Déroulement :**

1. L'exploitant lance la commande, éventuellement en mode simulation.
2. Le système liste les groupes avec leur nombre d'utilisateurs. L'exploitant sélectionne un groupe.
3. Si le groupe est protégé, le système refuse l'opération (voir règles ci-dessous).
4. Le système affiche un récapitulatif : nombre total d'utilisateurs du groupe, nombre d'utilisateurs supprimables et nombre d'utilisateurs conservés avec le motif de leur conservation.
5. L'exploitant confirme explicitement. La réponse par défaut est le refus ; sans confirmation, rien n'est supprimé.
6. Le système supprime définitivement chaque utilisateur supprimable, puis affiche un bilan.

**Périmètre :** Tous les utilisateurs du groupe sélectionné sont concernés, actifs comme inactifs. Aucun autre filtre n'est appliqué.

**Groupes protégés :** Le groupe par défaut et tout groupe disposant du droit d'administration ne peuvent pas être vidés par cette commande.

**Utilisateurs conservés :** Un utilisateur encore référencé par des données métier n'est pas supprimé :

- créateur d'un dossier ;
- préventionniste d'un dossier, d'un établissement ou d'un groupement de communes ;
- auteur d'une version de l'historique d'un établissement ;
- auteur de la suppression d'un dossier ou d'un établissement ;
- auteur d'une actualité.

**Données supprimées avec l'utilisateur :** compte, informations personnelles (nom, prénom, coordonnées, etc.), préférences, rattachements aux commissions et aux groupements de communes.

**Fiche d'informations partagée :** Si la fiche d'informations personnelles de l'utilisateur est également utilisée ailleurs (coordonnées de la mairie d'une commune, groupement de communes, contact d'établissement, de dossier, de groupement ou de commission, membre de commission, autre compte), le compte est supprimé mais la fiche est conservée intacte, et ce cas est tracé. La référence d'un membre de commission suffit à conserver la fiche, même si celle-ci n'est plus associée aux contacts de la commission. L'application ne crée pas de tels partages : ils proviennent de liaisons faites manuellement en base de données sur certaines installations.

**Mode simulation :** Le système effectue la sélection, l'analyse et le récapitulatif sans rien supprimer.

**Erreurs :** Une erreur lors de la suppression d'un utilisateur n'interrompt pas le traitement. L'utilisateur concerné est conservé intact et les autres utilisateurs sont traités. La commande se termine en échec si au moins une erreur est survenue.

**Journal d'audit :** Chaque exécution est tracée dans un journal dédié, conservé 30 jours : lancement (mode simulation ou non), groupe sélectionné, refus d'un groupe protégé, nombres d'utilisateurs concernés, supprimables et conservés, identifiant technique et motif de chaque utilisateur conservé, identifiant technique de chaque utilisateur supprimé, erreurs, annulation par l'exploitant et bilan final. Le journal ne contient que des identifiants techniques, aucune donnée personnelle.

---

## 4. Groupes d'utilisateurs

### 4.1 Notion de groupe

Un groupe d'utilisateurs est un regroupement logique qui détermine :

- Les **droits d'accès** de tous les utilisateurs qui en font partie, via la matrice des droits (voir section 5).
- Le **libellé** affiché dans les listes et les filtres.
- Une **description** libre à destination des administrateurs.

Chaque utilisateur appartient à **un et un seul** groupe.

Le groupe détermine le rôle de l'utilisateur dans l'application. Les droits d'accès sont définis pour le groupe et s'appliquent à tous ses membres.

Il existe un **groupe par défaut** (le premier groupe créé dans le système). Il ne peut pas être supprimé.

### 4.2 Créer et modifier un groupe

**Acteur :** Administrateur.

**Créer un groupe :**

1. L'administrateur clique sur « Créer un groupe ».
2. Il saisit le libellé et la description.
3. Il valide.

Le groupe est créé sans aucun droit particulier. L'administrateur doit ensuite définir ses droits d'accès (section 5).

**Modifier un groupe :**

1. L'administrateur sélectionne un groupe dans la barre latérale de la liste des utilisateurs.
2. Il clique sur « Modifier le groupe ».
3. Il peut modifier le libellé et la description.
4. Il valide.

La modification d'un groupe met à jour les droits des utilisateurs qui en font partie.

### 4.3 Supprimer un groupe

**Acteur :** Administrateur.

**Conditions :**

- Le groupe par défaut ne peut pas être supprimé.
- Lorsqu'un groupe est supprimé, **tous ses utilisateurs sont automatiquement transférés dans le groupe par défaut**.

**Déroulement :**

1. L'administrateur accède à la modification du groupe.
2. Il clique sur « Supprimer le groupe ».
3. Le système transfère les utilisateurs du groupe supprimé vers le groupe par défaut.
4. Le groupe et les droits qui lui sont associés sont supprimés.

---

## 5. Droits d'accès

### 5.1 Principes des droits d'accès

Les droits d'accès sont définis à partir des groupes d'utilisateurs, des parties de l'application concernées et des actions autorisées :

**Fonctionnement de la matrice :**

- L'administrateur choisit les droits accordés à chaque groupe.
- Les droits sélectionnés s'appliquent à tous les membres du groupe.
- Une action non autorisée reste inaccessible à l'utilisateur.

**Séparation des droits :** la consultation et la modification sont deux droits distincts. Des droits spécifiques peuvent aussi autoriser la création, le verrouillage ou l'envoi d'alertes.

**Organisation de la matrice :**

- La matrice présente un panneau par groupe.
- Dans chaque panneau, des cases à cocher permettent de choisir les actions autorisées.
- Les nouveaux droits s'appliquent dès l'enregistrement de la matrice.

**Exemples de droits courants :**

| Partie de l'application | Droits possibles |
|---|---|
| Établissement (par genre/type) | Lecture, Modifier |
| Dossier (par nature) | Lecture, Modifier, Verrouillage d'un dossier |
| Créations | Ajouter un établissement, Ajouter un dossier |
| Commissions | Lecture, Modifier |
| Alertes | Alerte sur changement de statut, Alerte sur changement d'avis, Alerte sur changement de classement |

### 5.2 Limiter l'accès à certains établissements ou dossiers

En complément des droits généraux, l'administrateur peut définir des règles qui limitent les établissements ou les dossiers accessibles aux utilisateurs.

#### 5.2.1 Accès aux établissements

L'administrateur peut définir des règles d'accès selon le genre de l'établissement et ses caractéristiques :

| Genre | Caractéristiques proposées |
|---|---|
| ERP | Type d'activité et catégorie |
| Cellule | Type d'activité |
| Habitation | Famille |
| IGH | Classe |
| Zone | Classement |
| BUP, camping, manifestation temporaire et IOP | Aucun critère de classement supplémentaire |

Le formulaire cellule reste inchangé et ne propose pas de critère de catégorie. Si une règle d'accès existante contient néanmoins une liste de catégories, l'accès exige une correspondance avec la **catégorie effective** de la cellule (RG-ETB-062), en plus du type d'activité. Les identifiants sont comparés exactement ; la valeur `0` autorise toutes les valeurs, y compris une catégorie absente. Les règles standard dont la catégorie est `0` conservent leurs droits.

Les règles peuvent aussi tenir compte des commissions, groupements ou communes auxquels l'utilisateur est rattaché. Deux utilisateurs d'un même groupe peuvent ainsi avoir accès à des établissements différents selon leur périmètre.

Chaque règle distingue les droits de consultation et de modification.

#### 5.2.2 Accès aux dossiers

Les règles d'accès aux dossiers peuvent porter sur leur nature et déterminer les droits de consultation, de modification et de verrouillage.

> **Limite actuelle** : même si le formulaire permet de sélectionner plusieurs natures de dossier pour une règle d'accès, cette sélection multiple n'est pas prise en compte lors du contrôle des droits. Le comportement attendu pour ce réglage reste à préciser.

Pour accéder à un dossier, l'utilisateur doit disposer des droits requis pour le dossier et pour les établissements qui lui sont rattachés. L'accès à un site dépend des droits accordés sur les établissements qui le composent.

### 5.3 Configurer la matrice des droits

**Acteur :** Administrateur.

**Accès :** Menu d'administration > « Gérer les droits des groupes » > Onglet « Matrice des droits ».

**Déroulement :**

1. La matrice affiche un accordéon avec un panneau par groupe.
2. L'administrateur déplie le panneau du groupe à configurer.
3. Il coche ou décoche les droits souhaités pour chaque partie de l'application.
4. Il clique sur « Sauvegarder ».
5. Les nouveaux droits s'appliquent immédiatement.

**Gestion des règles d'accès spécifiques :**

L'administrateur peut gérer les règles qui limitent l'accès à certains établissements ou dossiers :

- **Ajouter** une règle d'accès et définir les critères concernés.
- **Supprimer** une règle d'accès. Les droits associés sont également supprimés.

Une ressource établissement peut comporter une sélection étendue de types d'activité, par exemple tous les types sauf les établissements pénitentiaires. Les types sélectionnés restent les seuls autorisés par ce critère ; les droits des ressources existantes restent inchangés.

Un formulaire d'ajout invalide ne crée ni ressource ni privilège ; l'administrateur est informé de l'échec.

Les règles ajoutées apparaissent ensuite dans la matrice des droits.

---

## 6. Authentification

### 6.1 Modes de connexion disponibles

Le mode de connexion dépend du paramétrage de l'organisation. L'utilisateur peut utiliser son compte Prevarisc, être reconnu par sa connexion Windows, passer par un portail de connexion commun à plusieurs applications ou se connecter avec les identifiants de l'annuaire de son organisation.

#### 6.1.1 Connexion avec un compte Prevarisc

- **Contexte d'utilisation :** Méthode utilisée sur le formulaire pour les comptes dont la case « Connexion via LDAP/CAS/NTLM » est décochée, y compris lorsque LDAP est activé.
- **Fonctionnement :** L'utilisateur saisit son identifiant et son mot de passe sur la page de connexion. Le mot de passe est vérifié à partir de celui stocké dans l'application.

#### 6.1.2 Connexion par portail de l'organisation

- **Contexte d'utilisation :** Organisations utilisant un portail de connexion commun à plusieurs applications.
- **Fonctionnement :** L'utilisateur est redirigé vers ce portail, puis revient dans Prevarisc après s'être connecté.
- **Particularité à la déconnexion :** La déconnexion de Prevarisc entraîne également la déconnexion du portail.

#### 6.1.3 Reconnaissance de la connexion Windows

- **Contexte d'utilisation :** Organisations où les postes de travail sont gérés dans un domaine Windows.
- **Fonctionnement :** L'utilisateur est reconnu à partir de sa connexion Windows, sans ressaisir son mot de passe dans Prevarisc.
- **Particularité à la déconnexion :** Un message informe l'utilisateur qu'il a été déconnecté.

#### 6.1.4 Connexion par annuaire de l'organisation

- **Contexte d'utilisation :** Organisations qui gèrent les comptes dans un annuaire LDAP commun. Ce mode peut être activé en complément de l'authentification locale.
- **Fonctionnement :** L'utilisateur saisit son identifiant et son mot de passe dans le formulaire standard. Pour un compte dont la case « Connexion via LDAP/CAS/NTLM » est cochée, le système vérifie uniquement le mot de passe auprès de LDAP. Un échec LDAP ne déclenche jamais une vérification locale.
- **Transport :** La liaison avec l'annuaire utilise LDAP en clair par défaut. L'exploitant peut choisir LDAPS pour chiffrer les échanges dès la connexion ; le certificat du serveur doit être approuvé. L'exploitant peut préciser un fichier d'autorité de certification ; sinon, la configuration système est utilisée. Si la connexion sécurisée échoue, l'authentification est refusée sans repli vers LDAP en clair.
- **Cas d'usage :** SDIS souhaitant centraliser la gestion des mots de passe dans un annuaire existant tout en conservant une possibilité d'authentification locale.

#### 6.1.5 Ordre d'évaluation des méthodes

Lorsque plusieurs modes sont activés, Prevarisc utilise :

1. Si CAS est activé : seul CAS est utilisé (pas de formulaire de saisie de mot de passe).
2. Sinon, si NTLM est activé et que l'identité Windows est fournie (`REMOTE_USER`) : cette identité est utilisée sans mot de passe local.
3. Sinon, le formulaire est utilisé : case cochée et LDAP activé → LDAP uniquement ; case décochée → base locale uniquement, même si LDAP est activé.
4. Si la case est cochée mais LDAP désactivé, la connexion par formulaire est refusée.

Dans tous les cas, le compte doit exister dans Prevarisc et être actif. CAS et NTLM conservent leur priorité indépendamment de la case du compte. Aucun échec ne provoque un basculement entre LDAP et la base locale.

**Compte utilisateur :** Lorsque l'organisation utilise un mode de connexion externe, le formulaire de création ou de modification propose une option indiquant que le compte dépend de ce mode. Dans ce cas, l'utilisateur ne dispose pas d'un mot de passe Prevarisc.

### 6.2 Protection contre les tentatives répétées

Une mesure de protection est disponible lorsque le mode de sécurité renforcée est activé.

**Fonctionnement :**

1. À chaque tentative de connexion échouée pour un compte identifié, le compteur de tentatives échouées de l'utilisateur est incrémenté. Un formulaire contenant un identifiant ou un mot de passe vide est refusé avant la recherche du compte, sans incrémenter de compteur.
2. Après **2 tentatives échouées**, les tentatives suivantes sont limitées à l'adresse utilisée lors de la dernière connexion réussie.
3. En cas de connexion réussie, le compteur est remis à zéro et la dernière adresse réseau authentifiée est mise à jour.

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
3. Selon le mode de connexion :
   - Connexion par portail : l'utilisateur est également déconnecté du portail.
   - Connexion Windows sans mot de passe Prevarisc : un message de déconnexion est affiché.
   - Autres modes : retour à la page de connexion.

---

## 7. Règles de gestion

| Identifiant | Règle |
|---|---|
| **RG-USR-001** | Chaque utilisateur appartient à exactement un groupe. Il ne peut pas être sans groupe ni appartenir à plusieurs groupes simultanément. |
| **RG-USR-002** | L'identifiant de connexion est unique. Toute tentative de création ou de modification avec un identifiant existant est refusée. |
| **RG-USR-003** | Un utilisateur inactif ne peut pas se connecter. Le système vérifie le statut actif au moment de l'authentification. |
| **RG-USR-004** | L'interface ne permet pas de supprimer un utilisateur : la désactivation (passage en inactif) est le mécanisme de retrait d'accès dans l'application. La suppression définitive n'est possible que par la commande d'exploitation décrite en section 3.5. |
| **RG-USR-005** | Les droits d'accès sont définis exclusivement au niveau du groupe, jamais au niveau individuel. |
| **RG-USR-006** | La suppression d'un groupe entraîne le transfert automatique de tous ses utilisateurs vers le groupe par défaut. |
| **RG-USR-007** | Le groupe par défaut (premier groupe du système) ne peut pas être supprimé. |
| **RG-USR-008** | Les règles qui limitent l'accès aux établissements tiennent compte des commissions, groupements et communes associés à l'utilisateur. Deux utilisateurs du même groupe peuvent donc avoir accès à des établissements différents. |
| **RG-USR-009** | Lorsqu'un utilisateur accède à un dossier, le système vérifie à la fois les droits sur la nature du dossier et les droits sur les établissements rattachés au dossier. L'accès n'est accordé que si les deux conditions sont remplies. |
| **RG-USR-010** | En mode de sécurité renforcée, le mot de passe doit contenir au minimum 8 caractères, dont au moins 1 minuscule, 1 majuscule, 1 chiffre et 1 caractère spécial. |
| **RG-USR-011** | Après 2 tentatives de connexion échouées en mode de sécurité renforcée, les nouvelles tentatives sont limitées à l'adresse utilisée lors de la dernière connexion réussie. |
| **RG-USR-012** | CAS et NTLM restent prioritaires lorsqu'ils prennent en charge la requête. Sinon, la case d'authentification déléguée enregistrée sur le compte sélectionne LDAP (si activé) ou, lorsqu'elle est décochée, la base locale. Aucun mot de passe local n'est vérifié pour un compte SSO. |
| **RG-USR-013** | Un message d'erreur générique « Authentification invalide » est affiché en cas d'échec, que la cause soit un identifiant inconnu, un compte inactif ou un mot de passe erroné. |
| **RG-USR-014** | Les droits définis dans la matrice s'appliquent dès leur enregistrement, sans reconnexion. |
| **RG-USR-015** | Une règle d'accès aux établissements distingue les droits de consultation et de modification. |
| **RG-USR-016** | Une règle d'accès aux dossiers distingue les droits de consultation, de modification et de verrouillage. |
| **RG-USR-017** | La suppression d'une règle d'accès supprime également les droits qui lui sont associés. |
| **RG-USR-019** | Les préférences utilisateur (configuration du tableau de bord) sont initialisées automatiquement à la création du compte. |
| **RG-USR-020** | Dans le formulaire utilisateur, le champ grade est affiché uniquement lorsque la fonction sélectionnée est « Préventionniste » ; pour toute autre fonction, il est masqué et sa valeur est vidée. |
| **RG-USR-021** | Après une connexion réussie, l'utilisateur est redirigé vers l'URL qu'il avait initialement demandée, ou vers la page d'accueil par défaut. |
| **RG-USR-022** | Les utilisateurs dont le groupe les autorise à recevoir une alerte et dont le périmètre (commune ou groupement) correspond à l'établissement reçoivent une notification en cas de changement de statut, d'avis ou de classement. |
| **RG-USR-023** | L'accès à un établissement de type « site » nécessite que l'utilisateur ait les droits sur au moins un des établissements enfants du site, ou le droit de création d'établissement. |
| **RG-USR-024** | Le filtrage de la liste des utilisateurs par groupe est possible via la barre latérale du panneau d'administration. |
| **RG-USR-025** | Les droits de consultation et de modification sont distincts et peuvent être attribués séparément. |
| **RG-USR-026** | La suppression définitive porte sur tous les utilisateurs d'un seul groupe, choisi interactivement, sans autre filtre. Elle n'est exécutée qu'après confirmation explicite de l'exploitant ; une exécution non interactive est refusée. |
| **RG-USR-027** | Le groupe par défaut et tout groupe disposant du droit d'administration ne peuvent pas être vidés par la suppression définitive. |
| **RG-USR-028** | Un utilisateur encore référencé par des données métier (créateur ou préventionniste de dossier, préventionniste d'établissement ou de groupement, auteur d'historique d'établissement, de suppression ou d'actualité) est conservé et le motif est tracé. |
| **RG-USR-029** | La suppression définitive d'un utilisateur supprime aussi ses informations personnelles, ses préférences et ses rattachements aux commissions et aux groupements. Si la fiche d'informations personnelles est partagée avec d'autres données (commune, groupement, contact, membre de commission, autre compte), elle est conservée intacte et ce cas est tracé. |
| **RG-USR-030** | Une erreur sur un utilisateur n'interrompt pas la suppression des autres ; l'utilisateur en erreur reste intact. |
| **RG-USR-031** | Le mode simulation produit l'analyse et les traces sans rien supprimer. |
| **RG-USR-032** | Chaque exécution de la suppression définitive est tracée dans un journal d'audit dédié conservé 30 jours, ne contenant que des identifiants techniques. |
| **RG-USR-033** | Il n'existe aucun repli entre LDAP et la base locale. L'échec du mécanisme sélectionné termine la tentative, avec un message générique et une trace de diagnostic indiquant le mécanisme concerné, sans mot de passe ni hachage. Un échec local ne prouve pas que le compte relève de LDAP. |
| **RG-USR-034** | Le formulaire de connexion refuse un identifiant ou un mot de passe vide avant toute recherche de compte ou vérification LDAP/locale. Ce refus est journalisé sans incrémenter de compteur d'échecs. |

---

## 8. Cas particuliers

### 8.1 Utilisateur sans mot de passe local

Lorsqu'un utilisateur est créé avec la case « Connexion via LDAP/CAS/NTLM » cochée, son mot de passe local est vide. Pour permettre la connexion par formulaire avec un mot de passe Prevarisc, un administrateur doit décocher la case et définir un mot de passe local. Si aucun mécanisme délégué n'est activé, la case n'est pas affichée et l'équipe d'exploitation doit intervenir pour préparer la remise en état.

Lors de la reprise des données, les comptes à login renseigné et mot de passe NULL passent à SSO si leur valeur était NULL ou fausse. Les autres valeurs NULL deviennent fausses, notamment pour les fiches sans login (mairies utilisées uniquement pour leurs informations). Les choix SSO vrais déjà enregistrés sont conservés. Un mot de passe présent conduit à conserver un mode local présumé : un administrateur doit vérifier et corriger ce choix si le compte doit relever de LDAP.

### 8.2 Changement de mode d'authentification

Si un administrateur associe un compte au mode de connexion de l'organisation, son mot de passe Prevarisc est supprimé et le champ correspondant est masqué dans le formulaire.

Pour revenir à une connexion locale par formulaire, l'administrateur décoche la case et définit un nouveau mot de passe. Il vérifie ensuite la connexion avec l'utilisateur. Les journaux servent au diagnostic ; ils ne déclenchent aucune reclassification automatique.

### 8.3 Utilisateur rattaché à aucune commission et aucun groupement

Un utilisateur peut ne pas être rattaché à des commissions ou des groupements. Dans ce cas :

- Les règles d'accès fondées sur les commissions ou les groupements de l'utilisateur ne lui donnent accès à aucun établissement.
- Il conserve l'accès aux établissements couverts par des règles qui ne tiennent pas compte des commissions ni des groupements.

### 8.4 Ressource spécialisée avec tous les critères à « Ignorer »

Si une règle d'accès aux établissements ne tient compte ni des commissions, ni des groupements, ni de la commune, et couvre tous les types et toutes les catégories, elle donne accès à tous les établissements du genre concerné.

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

- Appartiennent à un groupe autorisé à recevoir l'alerte correspondante.
- Ont une adresse de courriel renseignée et non vide.
- Ont un périmètre géographique (commune directe ou communes de leurs groupements) incluant la commune de l'établissement concerné.
