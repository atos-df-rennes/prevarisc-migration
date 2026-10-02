# MOD-16 — Gestion des communes

## 1. Objet et périmètre

Ce module couvre l'administration du référentiel des communes utilisé dans l'application Prevarisc. Il permet de :

- Consulter la liste complète des communes enregistrées.
- Visualiser et mettre à jour les coordonnées de la mairie associée à chaque commune.
- Fusionner des communes dans le cadre de réformes territoriales (création de communes nouvelles).

La liste des communes est utilisée pour renseigner les adresses, les groupements, les compétences des commissions de sécurité et les rues.

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
2. Si aucune commune n'existe, un message indique « Il n'y a pas de communes dans le référentiel ».
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
3. L'administrateur clique sur « Sauvegarder ».
4. Un message confirme l'enregistrement ou indique l'échec.
5. La fiche de la commune modifiée est de nouveau affichée.

**Création automatique de la fiche mairie :**

- Si la commune ne possède pas encore de fiche de coordonnées de mairie, le système en crée une automatiquement lors de la première sauvegarde.
- Si la fiche de coordonnées référencée n'existe plus (incohérence de données), le système en recrée une et met à jour la liaison.

---

## 5. Fonctionnalités — Fusion de communes

### 5.1 Contexte et déclenchement

La fusion de communes répond aux besoins des réformes territoriales (loi NOTRe, création de communes nouvelles). Elle permet de fusionner plusieurs communes existantes en une seule commune cible, en transférant automatiquement toutes les données rattachées.

**Accès :** Menu « Administration » > « Fusion des communes ».

**Déclenchement :** L'administrateur importe un fichier décrivant les opérations de fusion à réaliser.

### 5.2 Processus de fusion

#### 5.2.1 Import du fichier de fusion

1. L'administrateur sélectionne un fichier de fusion via un formulaire de téléversement.
2. Un seul fichier de fusion peut être traité à la fois.
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

1. **Existence de la commune cible** — Le code INSEE cible doit correspondre à une commune existante. Si ce n'est pas le cas, la fusion est refusée et un message le signale.
2. **Unicité du code INSEE cible** — Si plusieurs communes partagent le même code INSEE, la fusion est refusée et un message le signale.
3. **Cohérence du nom** — Le nom de la commune cible doit correspondre à celui indiqué. En cas de différence, la fusion est refusée et un message le signale.

Si un contrôle échoue pour une opération, cette fusion est refusée et signalée. Les opérations suivantes du même fichier sont ensuite examinées.

#### 5.2.4 Exécution de la fusion

Pour chaque commune absorbée, le système exécute les étapes suivantes dans l'ordre :

1. **Transfert des adresses d'établissements** — Toutes les adresses d'établissements de la commune absorbée sont rattachées à la commune cible.
2. **Transfert des voies** — Toutes les rues de la commune absorbée sont rattachées à la commune cible.
3. **Suppression des liaisons groupement–commune** — Les associations entre la commune absorbée et ses groupements intercommunaux sont supprimées.
4. **Suppression des règles de commission** — Les règles de compétence de commission de sécurité portant sur la commune absorbée sont supprimées.
5. **Suppression de la commune absorbée** — La commune absorbée est retirée du référentiel.

#### 5.2.5 Résultat

- En cas de succès, un message « Les communes ont été fusionnées » s'affiche.
- Si certaines fusions échouent, un avertissement précise les communes concernées. Les fusions réussies sont également signalées.

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
| **RG-CMN-008** | Après la sauvegarde des coordonnées, la fiche de la commune modifiée est affichée avec un message de confirmation ou d'erreur. |
| **RG-CMN-009** | Un seul fichier de fusion peut être traité à la fois. Il décrit la commune cible et les communes à réunir. |
| **RG-CMN-010** | Avant toute fusion, le code INSEE cible doit exister dans le référentiel et correspondre à une commune unique. |
| **RG-CMN-011** | Le libellé de la commune cible dans le fichier de fusion doit correspondre exactement au libellé en base. Toute divergence bloque l'opération. |
| **RG-CMN-012** | Lors d'une fusion, les adresses d'établissements des communes absorbées sont réaffectées à la commune cible. |
| **RG-CMN-013** | Lors d'une fusion, les voies (rues) des communes absorbées sont réaffectées à la commune cible. |
| **RG-CMN-014** | Lors d'une fusion, les liaisons groupement–commune des communes absorbées sont supprimées sans report sur la commune cible. |
| **RG-CMN-015** | Lors d'une fusion, les règles de commission portant sur les communes absorbées sont supprimées sans report sur la commune cible. |
| **RG-CMN-016** | Lors d'une fusion, la commune absorbée est définitivement supprimée du référentiel après le transfert de ses données. |
| **RG-CMN-017** | Si une opération de fusion échoue à un contrôle de cohérence, elle est refusée et signalée. Les opérations suivantes du même fichier sont examinées. Certaines communes peuvent donc être fusionnées même si d'autres ne le sont pas. |
| **RG-CMN-018** | La commune est une donnée de référence transversale. Sa suppression ou modification impacte les modules Établissements, Groupements, Commissions et Voirie. |

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
