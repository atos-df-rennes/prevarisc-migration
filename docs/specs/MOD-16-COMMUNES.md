# MOD-16 — Gestion des communes

## 1. Objet et périmètre

Ce module couvre l'administration du référentiel des communes utilisé dans l'application Prevarisc. Il permet de :

- Consulter la liste complète des communes enregistrées.
- Visualiser et mettre à jour les coordonnées de la mairie associée à chaque commune.
- Fusionner des communes dans le cadre de réformes territoriales (création de communes nouvelles).

Le référentiel des communes constitue une donnée de référence transversale : il est exploité par les modules Établissements (adresse), Groupements (périmètre géographique), Commissions de sécurité (règles de compétence) et Voirie (rattachement des rues).

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|---|---|
| **Administrateur** | Accède à la gestion des communes et à la fusion. Modifie les coordonnées de mairie. |
| **Utilisateur courant** | Utilise le référentiel communes en lecture seule via d'autres modules (recherche d'adresse, fiche établissement, etc.). |

> L'accès aux écrans d'administration des communes et de fusion est réservé aux utilisateurs disposant du profil administrateur (menu « Administration »).

---

## 3. Données d'une commune

### 3.1 Identité de la commune

| Donnée | Description |
|---|---|
| **Code INSEE** | Identifiant national unique de la commune. Sert de clé de référence dans tout le système. Non modifiable par l'utilisateur. |
| **Libellé** | Nom officiel de la commune. |
| **Code postal** | Code postal de la commune. |

### 3.2 Coordonnées de la mairie

Chaque commune peut être associée à une fiche de coordonnées représentant sa mairie. Cette fiche contient :

| Donnée | Description |
|---|---|
| **Civilité** | Civilité du contact (optionnel). |
| **Nom** | Nom du contact principal en mairie (50 caractères max). |
| **Prénom** | Prénom du contact (50 caractères max). |
| **Téléphone fixe** | Numéro de téléphone de la mairie (20 caractères max). |
| **Fax** | Numéro de fax (20 caractères max). |
| **Téléphone portable** | Numéro de portable (20 caractères max). |
| **Courriel** | Adresse e-mail (50 caractères max). |
| **Site web** | URL du site internet de la mairie (100 caractères max). |
| **Adresse — Numéro** | Numéro dans la voie (10 caractères max). |
| **Adresse — Rue** | Libellé de la rue (255 caractères max). |
| **Adresse — Code postal** | Code postal de l'adresse (5 caractères max). |
| **Adresse — Ville** | Ville de l'adresse (255 caractères max). |
| **Observation** | Texte libre pour toute information complémentaire. |

> Le champ « Fonction » du contact est automatiquement positionné sur la valeur « Mairie » et masqué à l'utilisateur. Le champ « Société » n'est pas affiché pour une commune.

---

## 4. Fonctionnalités — Administration des communes

### 4.1 Consulter la liste des communes

**Description :** L'administrateur accède à un écran listant l'ensemble des communes présentes dans le référentiel.

**Comportement :**

1. Le système affiche une liste déroulante contenant toutes les communes, triées par ordre alphabétique de libellé.
2. Si aucune commune n'existe, un message explicite indique « Il n'y a pas de communes dans la base de données ».
3. La sélection d'une commune dans la liste charge automatiquement sa fiche détaillée dans la zone d'affichage (sans rechargement complet de la page).
4. La première commune de la liste est sélectionnée et affichée par défaut à l'ouverture de l'écran.

### 4.2 Consulter et modifier la fiche d'une commune

**Description :** L'administrateur visualise les informations d'identité de la commune sélectionnée et peut modifier les coordonnées de sa mairie.

**Consultation :**

1. Le code INSEE et le code postal de la commune sont affichés en lecture seule.
2. Le formulaire de coordonnées de la mairie est affiché en dessous, pré-rempli avec les données existantes (le cas échéant).

**Modification :**

1. L'administrateur modifie les champs souhaités dans le formulaire de coordonnées.
2. Il clique sur « Sauvegarder ».
3. Le formulaire est soumis en POST. Le système enregistre les données en base.
4. La page est rechargée avec un retour à la liste des communes (sélecteur déroulant) en ayant préalablement sélectionné la commune qui vient d'être modifiée.
5. Cette sélection automatique du select déclenche le chargement AJAX de la fiche (comportement standard du chargement de commune).
6. Un message de confirmation « Sauvegarde réussie ! La commune [libellé] a été enregistrée » s'affiche via un flash message au-dessus de la liste.
7. Un message d'erreur s'affiche en cas d'échec, indiquant la cause.

**Création automatique de la fiche mairie :**

- Si la commune ne possède pas encore de fiche de coordonnées de mairie, le système en crée une automatiquement lors de la première sauvegarde.
- Si la fiche de coordonnées référencée n'existe plus (incohérence de données), le système en recrée une et met à jour la liaison.

---

## 5. Fonctionnalités — Fusion de communes

### 5.1 Contexte et déclenchement

La fusion de communes répond aux besoins des réformes territoriales (loi NOTRe, création de communes nouvelles). Elle permet de fusionner plusieurs communes existantes en une seule commune cible, en transférant automatiquement toutes les données rattachées.

**Accès :** Menu « Administration » > « Fusion des communes ».

**Déclenchement :** L'administrateur importe un fichier au format JSON décrivant les opérations de fusion à réaliser.

### 5.2 Processus de fusion

#### 5.2.1 Import du fichier de fusion

1. L'administrateur sélectionne un fichier de fusion via un formulaire de téléversement.
2. Le fichier doit être au format JSON. Un seul fichier est accepté par opération.
3. L'administrateur clique sur « Fusionner les communes ».
4. Le système valide le fichier puis exécute les opérations de fusion.

#### 5.2.2 Structure attendue du fichier de fusion

Le fichier décrit un ensemble d'opérations de fusion. Chaque opération contient :

| Élément | Description |
|---|---|
| **Code INSEE cible** | Le code INSEE de la commune résultante (commune nouvelle ou commune absorbante). |
| **Nom de la commune cible** | Le libellé attendu de la commune cible, utilisé pour vérification de cohérence. |
| **Liste des communes absorbées** | Pour chaque commune absorbée : son code INSEE. |

#### 5.2.3 Contrôles préalables (par opération de fusion)

Avant d'exécuter chaque fusion, le système vérifie :

1. **Existence de la commune cible** — Le code INSEE cible doit correspondre à exactement une commune existante dans le référentiel. Si le code est absent, la fusion est rejetée et une erreur est journalisée.
2. **Unicité du code INSEE cible** — Si plusieurs communes partagent le même code INSEE, la fusion est rejetée et une erreur est journalisée.
3. **Cohérence du libellé** — Le libellé de la commune cible en base doit correspondre exactement au nom fourni dans le fichier. En cas de divergence, la fusion est rejetée et une erreur est journalisée.

Si un contrôle échoue pour une opération, l'ensemble du traitement s'arrête et les erreurs sont signalées.

#### 5.2.4 Exécution de la fusion

Pour chaque commune absorbée, le système exécute les étapes suivantes dans l'ordre :

1. **Transfert des adresses d'établissements** — Toutes les adresses d'établissements rattachées à la commune absorbée sont transférées vers la commune cible (mise à jour du code INSEE).
2. **Transfert des voies** — Toutes les rues rattachées à la commune absorbée sont transférées vers la commune cible (mise à jour du code INSEE).
3. **Suppression des liaisons groupement–commune** — Les associations entre la commune absorbée et ses groupements intercommunaux sont supprimées.
4. **Suppression des règles de commission** — Les règles de compétence de commission de sécurité portant sur la commune absorbée sont supprimées.
5. **Suppression de la commune absorbée** — La commune absorbée est retirée du référentiel.

#### 5.2.5 Résultat

- En cas de succès, un message « Les communes ont été fusionnées » s'affiche.
- En cas d'erreur pendant le traitement, un message « La commande a retourné des erreurs, veuillez vérifier les logs d'erreur » s'affiche. Le détail des erreurs est disponible dans les journaux de l'application.
- Les deux messages (succès et erreur) peuvent s'afficher simultanément si certaines opérations ont réussi et d'autres ont échoué.

### 5.3 Impact sur les données existantes

| Domaine impacté | Action lors de la fusion |
|---|---|
| **Établissements** | L'adresse de chaque établissement situé dans une commune absorbée est automatiquement rattachée à la commune cible. |
| **Voirie (rues)** | Chaque rue de la commune absorbée est rattachée à la commune cible. Cela peut créer des doublons de noms de rue dans la commune cible. |
| **Groupements** | La liaison entre la commune absorbée et ses groupements est supprimée. La commune cible n'hérite pas automatiquement de ces appartenances. |
| **Commissions de sécurité** | Les règles de compétence liées à la commune absorbée sont supprimées. La commune cible n'hérite pas automatiquement de ces règles. |
| **Coordonnées de mairie** | La fiche de coordonnées de la mairie de la commune absorbée est supprimée avec la commune elle-même. Les coordonnées de la commune cible restent inchangées. |
| **Dossiers** | Les dossiers ne sont pas directement impactés. Leur rattachement géographique passe par l'adresse de l'établissement, qui est mise à jour. |

---

## 6. Règles de gestion

| Référence | Règle |
|---|---|
| **RG-CMN-001** | Le code INSEE est l'identifiant unique d'une commune dans le système. Il ne peut être ni modifié ni supprimé manuellement. |
| **RG-CMN-002** | Le libellé de la commune est affiché en lecture seule. La liste des communes est triée par ordre alphabétique du libellé. |
| **RG-CMN-003** | Chaque commune peut être associée à une seule fiche de coordonnées de mairie. |
| **RG-CMN-004** | Si aucune fiche de coordonnées n'existe pour une commune, elle est créée automatiquement lors de la première sauvegarde. |
| **RG-CMN-005** | Si la fiche de coordonnées référencée est introuvable (données incohérentes), le système en recrée une nouvelle et met à jour la référence. |
| **RG-CMN-006** | La fonction du contact mairie est fixée automatiquement à la valeur « Mairie » et n'est pas modifiable par l'utilisateur. |
| **RG-CMN-007** | Le champ « Société » n'est pas proposé sur la fiche d'une commune. |
| **RG-CMN-008** | La sauvegarde des coordonnées de mairie s'effectue via POST classique. Après enregistrement en base, le système redirige vers la liste des communes en sélectionnant automatiquement la commune modifiée (via un paramètre d'URL). Cette sélection déclenche le chargement AJAX de la fiche. L'utilisateur voit le message Flash de succès/erreur, puis la fiche mise à jour (ou inchangée en cas d'erreur). Pas de rechargement complet de page. |
| **RG-CMN-009** | Le fichier de fusion doit être au format JSON. Un seul fichier est accepté par opération d'import. |
| **RG-CMN-010** | Avant toute fusion, le code INSEE cible doit exister dans le référentiel et correspondre à une commune unique. |
| **RG-CMN-011** | Le libellé de la commune cible dans le fichier de fusion doit correspondre exactement au libellé en base. Toute divergence bloque l'opération. |
| **RG-CMN-012** | Lors d'une fusion, les adresses d'établissements des communes absorbées sont réaffectées à la commune cible. |
| **RG-CMN-013** | Lors d'une fusion, les voies (rues) des communes absorbées sont réaffectées à la commune cible. |
| **RG-CMN-014** | Lors d'une fusion, les liaisons groupement–commune des communes absorbées sont supprimées sans report sur la commune cible. |
| **RG-CMN-015** | Lors d'une fusion, les règles de commission portant sur les communes absorbées sont supprimées sans report sur la commune cible. |
| **RG-CMN-016** | Lors d'une fusion, la commune absorbée est définitivement supprimée du référentiel après le transfert de ses données. |
| **RG-CMN-017** | En cas d'échec d'un contrôle de cohérence sur une opération de fusion d'une commune donnée, l'opération est ignorée et le traitement continue avec la fusion suivante (s'il y en a). Les erreurs sont journalisées pour analyse. Cela permet le traitement partiel d'un lot : si fusion #1 échoue, les fusions #2 et #3 peuvent réussir si elles sont valides. |
| **RG-CMN-018** | La commune est une donnée de référence transversale. Sa suppression ou modification impacte les modules Établissements, Groupements, Commissions et Voirie. |
| **RG-CMN-019** | Le fichier de fusion est supprimé du serveur après traitement, qu'il ait réussi ou échoué. |

---

## 7. Cas particuliers

### 7.1 Commune sans fiche de coordonnées

Une commune peut ne pas avoir de fiche de coordonnées de mairie (valeur d'association à zéro ou fiche référencée inexistante). Dans ce cas :
- La section « Informations sur la commune » (code INSEE, code postal) s'affiche normalement.
- Le formulaire de coordonnées s'affiche vide, prêt à être rempli.
- La fiche est créée automatiquement à la première sauvegarde (RG-CMN-004, RG-CMN-005).

### 7.2 Commune cible absente du référentiel

Si le code INSEE de la commune cible n'existe pas dans le référentiel au moment de la fusion, l'opération est refusée. L'administrateur doit d'abord s'assurer que la commune cible a été créée dans le référentiel (import du code officiel géographique, par exemple).

### 7.3 Doublons de rues après fusion

La fusion transfère toutes les rues des communes absorbées vers la commune cible. Si des rues portent le même nom dans la commune cible et dans une commune absorbée, aucune déduplication automatique n'est effectuée. Les doublons éventuels doivent être traités manuellement.

### 7.4 Perte des rattachements groupement et commission

La fusion supprime les associations groupement–commune et commission–commune des communes absorbées. La commune cible ne récupère pas automatiquement ces rattachements. L'administrateur doit vérifier et, le cas échéant, reconfigurer manuellement :
- Les périmètres des groupements intercommunaux.
- Les règles de compétence des commissions de sécurité.

### 7.5 Fusion de multiples communes en une seule opération

Le fichier de fusion peut contenir plusieurs opérations de fusion indépendantes. Chaque opération est validée et exécutée séquentiellement. Si une opération échoue à la validation (contrôle de cohérence), le traitement s'interrompt immédiatement : les opérations suivantes ne sont pas exécutées.

### 7.6 Affichage embarqué de la fiche commune

La fiche de coordonnées d'une commune peut être affichée en mode embarqué dans d'autres contextes (par exemple depuis un autre module). Dans ce mode :
- Les informations d'identité (code INSEE, code postal) et le bouton de sauvegarde ne sont pas affichés.
- Seul le formulaire de coordonnées est visible.
