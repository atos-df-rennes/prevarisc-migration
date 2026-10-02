# MOD-03 — Commissions de sécurité

---

## 1. Objet et périmètre

Ce document décrit le fonctionnement du module **Commissions de sécurité** de l'application Prevarisc, utilisée par les SDIS (Services Départementaux d'Incendie et de Secours) pour la gestion de la prévention incendie des ERP (Établissements Recevant du Public) et des IGH (Immeubles de Grande Hauteur).

Le module couvre :

- La **création et l'administration** des commissions de sécurité et de leurs sous-commissions.
- La **définition des règles de compétence** territoriale et technique de chaque commission.
- La **gestion des membres** et de leurs périmètres d'intervention.
- La **planification des séances** (calendrier, récurrence, séances liées).
- La **constitution de l'ordre du jour** d'une séance (affectation de dossiers, gestion horaire ou séquentielle).
- La **génération des documents officiels** : convocations, ordres du jour, procès-verbaux, comptes-rendus.
- L'**application automatique** des règles de compétence aux établissements.
- Le téléchargement du calendrier dans un format pouvant être importé dans un logiciel d'agenda.

Le module ne couvre pas la gestion des dossiers eux-mêmes (études, visites, prescriptions), qui relèvent du module Dossiers.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|---------------------|
| **Administrateur** | Crée et configure les types de commissions, les commissions elles-mêmes, et les règles de compétence. Accède à la gestion des commissions via le menu d'administration. |
| **Secrétariat de commission** | Planifie les séances, constitue l'ordre du jour, génère les convocations, ordres du jour, procès-verbaux et comptes-rendus. Gère les pièces jointes. |
| **Président de commission** | Consulte le calendrier des séances et l'ordre du jour. Reçoit les documents générés. |
| **Membre de commission** | Reçoit les convocations et ordres du jour filtrés selon son périmètre de compétence (catégories d'ERP, types d'activité, classes IGH, types et natures de dossier). |
| **Préventionniste** | Apparaît sur les documents générés en tant que rapporteur du dossier. |
| **Maire / Autorité de police** | Destinataire de convocations spécifiques, identifié par commune. |

---

## 3. Structure d'une commission

### 3.1 Types de commissions

Chaque commission est rattachée à un **type de commission**. Les types de commission structurent l'organisation hiérarchique des commissions de sécurité du département. Exemples courants :

- Commission consultative départementale de sécurité et d'accessibilité (CCDSA)
- Sous-commission départementale
- Commission communale
- Commission intercommunale
- Commission d'arrondissement

Chaque type possède une **fréquence de périodicité** (utilisée pour la planification récurrente des séances).

Une commission de type « communal » définit sa compétence par **commune** plutôt que par **groupement de communes**.

### 3.2 Membres d'une commission

Chaque commission possède une liste de **membres**. Un membre est caractérisé par :

- Un **libellé** (nom ou fonction du membre).
- Un indicateur de **présence** : **Membre Obligatoire**, **Membre Facultatif** ou **Membre Pour information**. Cet indicateur est purement informatif et n'est pas utilisé à des fins de filtrage ou de traitement automatique.
- Un éventuel rattachement à un **groupement** (gendarmerie, police, etc.).

Chaque membre peut être **spécialisé** selon cinq axes de compétence :

| Axe de compétence | Description |
|-------------------|-------------|
| **Catégories d'ERP** | Le membre n'intervient que pour les ERP d'une ou plusieurs catégories données (1re à 5e). |
| **Types d'activité** | Le membre n'intervient que pour certains types d'activité (J, L, M, N, O, P, R, S, T, U, V, W, X, Y…). |
| **Classes IGH** | Le membre n'intervient que pour certaines classes d'IGH (GHA, GHO, GHR, GHS, GHU, GHW, GHZ…). |
| **Types de dossier** | Le membre n'intervient que pour certains types de dossier (étude, visite périodique, visite de réception…). |
| **Natures de dossier** | Des natures peuvent être sélectionnées pour un membre. Cette sélection ne filtre actuellement pas les dossiers dans les documents générés. |

Lorsqu'un axe est coché pour toutes les valeurs, le membre est compétent pour l'ensemble de cet axe (affichage simplifié « λ »).

Les spécialisations des membres servent au **filtrage** lors de la génération des documents : chaque membre ne reçoit que les dossiers relevant de son périmètre. La sélection des natures de dossier ne participe actuellement pas à ce filtrage.

### 3.3 Règles de compétence

Les règles de compétence définissent **pour quels établissements** une commission est compétente. Chaque commission peut posséder plusieurs règles. Chaque règle combine les critères suivants :

| Critère | Description |
|---------|-------------|
| **Zone géographique** | Soit une **commune** (code INSEE) pour les commissions communales, soit un **groupement de communes** pour les commissions intercommunales, d'arrondissement ou départementales. |
| **Catégories d'ERP** | Une ou plusieurs catégories (1re à 5e). |
| **Types d'activité** | Un ou plusieurs types d'activité ERP. |
| **Classes IGH** | Une ou plusieurs classes IGH. |
| **Présence de locaux à sommeil** | Oui / Non. |
| **Étude ou visite** | Précise si la règle concerne les études, les visites ou les deux. |

Une règle est satisfaite lorsque **tous** les critères renseignés correspondent aux caractéristiques de l'établissement.

### 3.4 Documents modèles associés

Chaque commission peut disposer de **modèles de documents texte** au format ODT, compatible avec les logiciels de traitement de texte :

| Document modèle | Niveau | Description |
|-----------------|--------|-------------|
| **Convocation en visite** | Par membre | Modèle de courrier de convocation pour les séances de type visite. |
| **Convocation en salle** | Par membre | Modèle de courrier de convocation pour les séances en salle. |
| **Ordre du jour** | Par membre | Modèle d'ordre du jour envoyé à chaque membre. |
| **Procès-verbal** | Par membre (hors groupement) | Modèle de procès-verbal. |
| **Compte-rendu** | Par commission | Modèle unique de compte-rendu pour la commission. |

Lors de la création des documents, les informations de la commission, de la séance, des dossiers et des établissements sont reprises automatiquement depuis les modèles.

---

## 4. Fonctionnalités

### 4.1 Administrer les commissions (création, modification, suppression)

**Accès :** Menu d'administration → Gestion des commissions.

L'administrateur dispose d'une interface listant les **types de commissions** sous forme de menu horizontal. En sélectionnant un type, la liste des commissions associées s'affiche sous forme d'**accordéon**, chaque commission étant dépliable pour accéder à ses onglets de configuration.

**Créer une commission :**

1. L'administrateur ouvre la boîte de dialogue de gestion des commissions.
2. Il saisit le **libellé** de la commission et sélectionne un **type de commission**.
3. Il peut ajouter plusieurs commissions en lot (ajout de lignes dynamiques).
4. Il valide : les commissions sont créées et les documents peuvent y être associés.

**Modifier une commission :**

1. L'administrateur peut modifier le libellé et le type d'une commission existante.
2. La modification se fait en ligne, via le formulaire de gestion en lot.

**Supprimer une commission :**

1. La suppression d'une commission entraîne la suppression en cascade de :
   - Tous les **contacts** associés.
   - Tous les **membres** et leurs documents modèles.
   - Toutes les **règles de compétence**.
2. La commission est définitivement retirée du système.

### 4.2 Configurer les règles de compétence

**Accès :** Accordéon de la commission → Onglet « Champ de compétence de la commission ».

L'interface présente un tableau avec les colonnes :

- Zone géographique (commune ou groupement)
- ERP : Catégorie, Type
- IGH : Classe
- Local à sommeil
- Étude / visite

**Ajouter une règle :**

1. Le secrétariat clique sur « Ajouter une règle ».
2. Une ligne vierge est ajoutée au tableau.
3. Il sélectionne une zone géographique :
   - Pour une commission communale : saisie avec autocomplétion de la commune (minimum 3 caractères).
   - Pour une commission non communale : sélection d'un groupement de communes.
4. Il coche les catégories, types d'activité, classes IGH, locaux à sommeil et types (étude/visite) applicables.
5. Il enregistre les règles.

**Appliquer les règles :**

1. Le secrétariat clique sur « Appliquer les règles ».
2. Le système parcourt **tous les établissements** du département (ERP et IGH) et affecte automatiquement la commission à ceux qui correspondent aux règles définies (cf. section 6).

**Supprimer une règle :**

1. La suppression d'une règle entraîne la suppression de tous ses critères associés (types, catégories, classes, locaux à sommeil, étude/visite).

### 4.3 Gérer les membres

**Accès :** Accordéon de la commission → Onglet « Membres de la commission ».

**Ajouter un membre :**

1. Le secrétariat clique sur « Ajouter un membre ».
2. Un membre vierge est créé avec un libellé vide et un indicateur de présence « Membre Facultatif » par défaut.

**Configurer un membre :**

1. Saisie du **libellé** (nom, fonction).
2. Indication de la **présence** : **Membre Obligatoire**, **Membre Facultatif** ou **Membre Pour information** (informatif uniquement).
3. Éventuel rattachement à un **groupement** (pour les membres représentant une entité intercommunale : gendarmerie, police…).
4. Sélection des **périmètres de compétence** :
   - Catégories d'ERP (cases à cocher).
   - Types d'activité ERP (cases à cocher, affichés par libellé de type).
   - Classes IGH (cases à cocher).
   - Types de dossier (cases à cocher).
   - Natures de dossier (cases à cocher).
5. Enregistrement.

**Supprimer un membre :**

1. La suppression entraîne :
   - La suppression de tous les **documents modèles** (courriers) associés au membre.
   - La suppression de toutes les **spécialisations** (catégories, types, classes, types de dossier, natures de dossier).

### 4.4 Gérer le calendrier des séances

**Accès :** Menu principal → Calendrier des commissions.

L'interface présente :

- Un **panneau latéral** listant les commissions regroupées par type, avec un code couleur par type d'événement.
- Un **calendrier interactif** (vues : mois, semaine, jour) avec navigation par mois/année.
- Les week-ends ne sont pas affichés par défaut.

**Légende des couleurs :**

| Couleur | Type d'événement |
|---------|-----------------|
| Bleu | Séance en salle |
| Orange | Groupe de visite |
| Vert | Visite de sécurité |

**Créer une ou plusieurs séances :**

1. Le secrétariat sélectionne une commission dans le panneau latéral.
2. Il clique sur une date dans le calendrier pour ouvrir la boîte de dialogue de création.
3. Il saisit :
   - Le **libellé** de la séance (optionnel).
   - Le **type d'événement** : en salle, visite de sécurité, ou groupe de visite.
   - Une ou plusieurs **dates** avec heures de début et de fin.
4. **Mode multi-dates :** le secrétariat peut ajouter plusieurs dates pour une même séance (par exemple, une séance en salle le lundi suivie d'une visite le mardi). La première date devient la date « principale » et les suivantes sont des **dates liées**.
5. **Mode récurrent :** en activant la récurrence, le secrétariat définit :
   - Une **périodicité** (1 à 4 semaines).
   - Une **date de fin** de récurrence.
   - Le système crée les dates selon la périodicité choisie. Les dates ainsi créées ne sont pas rattachées à une date principale.

**Modifier une séance :**

1. En cliquant sur un événement existant, le secrétariat peut :
   - Modifier le **libellé** (répercuté sur toutes les dates liées).
   - Modifier le **type d'événement** (répercuté sur toutes les dates liées).
   - Modifier la **date et les horaires** d'une date spécifique.
   - **Ajouter une date** supplémentaire à la séance.
   - **Promouvoir** une date liée en date principale.
   - **Supprimer** une date liée (impossible de supprimer la date principale directement).
2. Par glisser-déposer sur le calendrier, le secrétariat peut **déplacer** une séance à une autre date/heure.
3. Par redimensionnement, le secrétariat peut modifier l'heure de fin d'une séance.

**Supprimer une séance :**

1. Une boîte de dialogue de confirmation demande validation avant suppression.

### 4.5 Gérer l'ordre du jour

**Accès :** Depuis le calendrier, clic sur une séance → écran de gestion de l'ordre du jour.

L'écran se compose de :

- Un **en-tête** affichant le type, le nom de la commission, la date en français et les dates liées.
- Un **panneau latéral gauche** listant les dossiers affectés à cette date mais non encore planifiés à un créneau horaire précis.
- Un **calendrier horaire** (vue journée) affichant les dossiers déjà planifiés dans leurs créneaux.

**Deux modes de gestion :**

| Mode | Description |
|------|-------------|
| **Gestion horaire** | Chaque dossier est positionné sur un créneau horaire précis (heure de début, heure de fin). Le calendrier horaire est actif avec des créneaux de 5 minutes. |
| **Gestion séquentielle** | Les dossiers sont ordonnés par un numéro d'ordre, sans créneau horaire. L'interface permet le réordonnancement par glisser-déposer. |

**Basculer entre les modes :**

1. Le secrétariat active ou désactive la gestion horaire via un bouton de la barre latérale.
2. Le passage en mode séquentiel **réinitialise** tous les créneaux horaires et attribue un numéro d'ordre séquentiel à chaque dossier.

**Affecter un dossier à un créneau :**

1. Le secrétariat glisse un dossier du panneau gauche vers le calendrier horaire.
2. Le dossier reçoit automatiquement une durée par défaut de **5 minutes**.
3. Le secrétariat peut ajuster la durée par redimensionnement et l'heure par glisser-déposer.

**Réordonner les dossiers (mode séquentiel) :**

1. Le secrétariat modifie l'ordre des dossiers par glisser-déposer.
2. Les numéros d'ordre sont recalculés automatiquement.

**Affichage des dossiers :**

Chaque dossier dans le calendrier affiche :
- Le nom de l'établissement (et de son établissement parent le cas échéant).
- La ou les communes de l'adresse.
- La nature du dossier.
- L'objet du dossier.
- Les documents d'urbanisme liés.
- Le nom du préventionniste rapporteur.

**Code couleur des dossiers :**

| Couleur | Signification |
|---------|--------------|
| Orange | Dossier non verrouillé (en cours de rédaction). |
| Bleu | Dossier verrouillé (finalisé). |

### 4.6 Générer les documents officiels

**Accès :** Écran de gestion de l'ordre du jour → barre latérale → liens de génération.

Quatre types de documents peuvent être générés :

#### 4.6.1 Convocations

La génération produit un document par membre de la commission.

Le document est construit à partir du modèle de courrier du membre (convocation en visite ou convocation en salle, selon le type de séance).

Le contenu est organisé :

- **Par commune** pour les membres non rattachés à un groupement : le document inclut les informations du maire (adresse de la mairie) et les dossiers situés dans la commune.
- **Par groupement** pour les membres rattachés à un groupement : le document inclut les informations du groupement et tous les dossiers.

Pour chaque dossier inclus, le document contient :
- Le nom de l'établissement et de son parent éventuel.
- L'adresse complète.
- La nature et l'objet du dossier.
- Les documents d'urbanisme liés.
- Le nom du préventionniste rapporteur.
- Les avis de dérogation (du dossier, de l'établissement et des établissements enfants).
- Les données des formulaires personnalisés (vérifications techniques, descriptif de l'établissement, effectifs et dégagements).

Le filtrage s'applique selon les spécialisations du membre (catégories, types d'activité, classes, types de dossier). **Les natures de dossier sont stockées en base mais ne participent pas au filtrage lors de la génération des documents.**

#### 4.6.2 Ordres du jour

La génération produit un document par membre, à partir de son modèle d'ordre du jour.

Le contenu suit la même logique que les convocations (par commune ou par groupement), avec le même niveau de détail sur chaque dossier.

Le document inclut en outre la liste de tous les membres de la commission.

L'ordre des dossiers respecte l'ordre défini dans la gestion de l'ordre du jour (horaire ou séquentiel).

#### 4.6.3 Procès-verbaux

La génération produit un document par membre (uniquement les membres non rattachés à un groupement).

En plus des informations des dossiers, le procès-verbal inclut pour chaque dossier :
- L'**avis émis** : Favorable, Défavorable, ou Réservé.
- Les **prescriptions** classées par type :
  - Prescriptions réglementaires (nouvelles, reprises, actuelles).
  - Prescriptions d'exploitation (nouvelles, reprises, actuelles).
  - Prescriptions d'amélioration (nouvelles, reprises, actuelles).
- Chaque prescription comprend un numéro, un libellé et une référence d'article réglementaire.

#### 4.6.4 Comptes-rendus

La génération produit un compte-rendu pour la commission, à partir de son modèle.

Pour chaque dossier, le compte-rendu inclut :
- L'identifiant de l'établissement.
- Le nom de l'établissement et de son parent éventuel.
- La catégorie, le type et la classe de l'établissement.
- L'adresse complète.
- Les effectifs (personnel et public).
- Le type de passage (étude, visite de commission, groupe de visite).
- La nature et l'objet du dossier.
- Les observations.
- L'avis (Favorable, Défavorable, Réservé).

### 4.7 Télécharger le calendrier

**Accès :** Panneau latéral du calendrier → « Synchroniser le calendrier » ou « Exporter pour Outlook ».

Un lien permet de s'abonner au calendrier d'une commission depuis un agenda externe, par exemple Outlook ou Google Agenda.

L'URL est construite avec l'identifiant de l'utilisateur et une clé de sécurité applicative.

Cette fonctionnalité n'est disponible que si une clé de sécurité est configurée dans l'application.

---

## 5. Règles de gestion

### Création et administration

**RG-COM-001** — Chaque commission est obligatoirement rattachée à un type de commission.

**RG-COM-002** — Le libellé d'une commission est obligatoire et sert d'identifiant fonctionnel.

**RG-COM-003** — La suppression d'une commission entraîne la suppression en cascade de ses contacts, de ses membres (et de leurs documents modèles), et de ses règles de compétence.

**RG-COM-004** — L'administrateur peut créer plusieurs commissions en lot lors d'une même opération.

### Règles de compétence

**RG-COM-010** — Chaque règle de compétence est rattachée à une seule commission.

**RG-COM-011** — Pour une commission de type communal, la zone géographique d'une règle est une **commune** identifiée par son code INSEE. Pour tout autre type, la zone est un **groupement de communes**.

**RG-COM-012** — Une règle combine les critères suivants, tous obligatoires : zone géographique, catégories d'ERP, types d'activité, classes IGH, présence de locaux à sommeil, étude/visite.

**RG-COM-013** — Une commission peut posséder plusieurs règles de compétence. Un établissement est couvert par la commission s'il satisfait au moins une de ses règles.

**RG-COM-014** — Lors de la sauvegarde d'une règle, les critères existants sont intégralement remplacés par les nouveaux critères saisis (logique de remplacement complet).

**RG-COM-015** — L'application des règles est une opération explicite, déclenchée manuellement par l'utilisateur. Elle met à jour la commission de rattachement de chaque établissement du département correspondant aux règles.


### Membres

**RG-COM-020** — Un membre est toujours rattaché à une seule commission.

**RG-COM-021** — Un membre peut être spécialisé sur zéro ou plusieurs catégories, types d'activité, classes IGH, types de dossier et natures de dossier. La sélection des natures de dossier ne filtre actuellement pas les dossiers retenus dans les documents générés.

**RG-COM-022** — Un membre peut être rattaché à un groupement (pour les représentants de services extérieurs : gendarmerie, police, etc.). Dans ce cas, ses documents sont générés par groupement et non par commune.

**RG-COM-023** — Le procès-verbal n'est généré que pour les membres **non rattachés** à un groupement.

**RG-COM-024** — Lors de la suppression d'un membre, les modèles de documents qui lui sont associés sont également supprimés.

**RG-COM-025** — L'indicateur de présence prend trois valeurs : **Membre Obligatoire**, **Membre Facultatif**, **Membre Pour information**. Ces valeurs sont purement informatives et ne conditionnent aucun traitement automatique.

### Calendrier et séances

**RG-COM-030** — Chaque séance est rattachée à une commission et possède une date, une heure de début, une heure de fin, un libellé et un type d'événement.

**RG-COM-031** — Les types d'événement sont : **en salle**, **visite de sécurité**, **groupe de visite**.

**RG-COM-032** — Plusieurs dates peuvent être liées entre elles pour former une **séance multi-dates**. La première date créée est la date **principale** ; les suivantes sont des **dates liées** qui référencent la principale.

**RG-COM-033** — La modification du libellé ou du type d'événement de la date principale est **répercutée** sur toutes les dates liées.

**RG-COM-034** — Une date liée peut être **promue** en date principale. L'ancienne date principale devient alors une date liée.

**RG-COM-035** — Seules les dates liées peuvent être supprimées individuellement. La suppression de la date principale nécessite la suppression de la séance entière.

**RG-COM-036** — En mode récurrent, le système crée des séances à intervalle régulier (1 à 4 semaines) entre la date de début et la date de fin. Ces dates ne sont pas rattachées à la première date comme le sont les dates ajoutées en mode multi-dates (voir RG-COM-032).

**RG-COM-037** — Le déplacement d'une séance (par glisser-déposer) met à jour la date de commission et, le cas échéant, les horaires des dossiers affectés.

### Ordre du jour

**RG-COM-040** — Un dossier est affecté à une date de commission via une **affectation**. L'affectation porte un numéro d'ordre, et optionnellement une heure de début et une heure de fin.

**RG-COM-041** — Deux modes de gestion de l'ordre du jour coexistent :
- **Mode horaire** : chaque dossier est positionné sur un créneau (heure de début, heure de fin).
- **Mode séquentiel** : les dossiers sont classés par un numéro d'ordre, sans horaire.

**RG-COM-042** — Le passage du mode horaire au mode séquentiel **réinitialise** les horaires de tous les dossiers affectés et leur attribue un numéro d'ordre séquentiel (0, 1, 2, …).

**RG-COM-043** — Lors de l'affectation d'un dossier à un créneau horaire, une durée par défaut de **5 minutes** est attribuée.

**RG-COM-044** — Le réordonnancement des dossiers (mode séquentiel) réinitialise les horaires et recalcule les numéros d'ordre séquentiellement.

### Documents officiels

**RG-COM-050** — Les documents modèles doivent être au format ODT, compatible avec les logiciels de traitement de texte.

**RG-COM-051** — Les informations de la séance, des dossiers et des établissements sont reprises automatiquement dans les documents générés à partir des modèles.

**RG-COM-052** — Les convocations et ordres du jour sont **filtrés par membre** : chaque membre ne reçoit que les dossiers correspondant à ses spécialisations (catégories, types d'activité, classes IGH, types de dossier, communes). **Cette règle ne s'applique pas aux membres rattachés à un groupement : pour ces membres, tous les dossiers de la séance sont inclus dans l'ordre du jour, sans filtrage par compétence.**

**RG-COM-053** — Pour les membres rattachés à un groupement, les documents sont organisés **par groupement** (adresse du groupement). Pour les autres membres, ils sont organisés **par commune** (adresse de la mairie, informations du maire).

**RG-COM-054** — Les convocations et ordres du jour incluent les **avis de dérogation** à trois niveaux : ceux du dossier, ceux de l'établissement, et ceux des établissements enfants (établissements rattachés).

**RG-COM-055** — Le procès-verbal inclut les **prescriptions** classées en trois familles (réglementaires, d'exploitation, d'amélioration) et trois temporalités (nouvelles, reprises, actuelles). Chaque prescription porte un numéro, un libellé et une référence d'article.

**RG-COM-056** — Le compte-rendu est un document unique par commission, non filtré par membre. Il inclut l'avis émis pour chaque dossier (Favorable, Défavorable, Réservé).

**RG-COM-057** — Lors de l'upload d'un nouveau document modèle, l'ancien fichier est automatiquement supprimé.

**RG-COM-058** — Un message d'erreur est affiché si le modèle de document d'un membre est absent lors de la tentative de génération.

### Pièces jointes

**RG-COM-060** — Des pièces jointes peuvent être associées à une date de commission.

---

## 6. Détermination automatique de la commission compétente

La détermination de la commission compétente pour un établissement donné est un mécanisme central du module. L'algorithme fonctionne différemment selon que l'établissement est un ERP classique ou un IGH.

### 6.1 Données d'entrée

Pour un **ERP classique**, les données d'entrée sont :
- La **commune** de l'établissement (code INSEE).
- La **catégorie** de l'ERP (1re à 5e).
- Le **type d'activité** principal.
- La **présence de locaux à sommeil** (oui/non).

Pour un **IGH**, les données d'entrée sont :
- La **commune** de l'établissement (code INSEE).
- La **classe** de l'IGH (GHA, GHO, GHR, etc.).
- La **présence de locaux à sommeil** (oui/non).

### 6.2 Algorithme pour un ERP classique

1. Récupérer la liste de tous les **types de commissions**, triés par **ordre décroissant** de priorité (les sous-commissions communales sont évaluées avant les commissions départementales).

2. Pour chaque type de commission, dans l'ordre :
   a. Rechercher toutes les **règles de compétence** qui correspondent simultanément à :
      - La **catégorie** de l'établissement.
      - Le **type d'activité** de l'établissement.
      - La **présence de locaux à sommeil** de l'établissement.
      - Le type de commission courant.
   b. Pour chaque règle trouvée :
      - Si la règle porte sur une **commune** : vérifier que le code INSEE de la commune correspond à celui de l'établissement. Si oui → **commission trouvée**, arrêt de l'algorithme.
      - Si la règle porte sur un **groupement** : vérifier que la commune de l'établissement appartient au groupement. Si oui → **commission trouvée**, arrêt de l'algorithme.

3. Si aucune règle ne correspond après le parcours de tous les types → **aucune commission compétente**.

### 6.3 Algorithme pour un IGH

1. Récupérer la liste de tous les **types de commissions** (en excluant le type CCDSA, identifiant 5, qui n'est pas compétent pour les IGH au niveau du module).

2. Pour chaque type de commission :
   a. Rechercher toutes les **règles de compétence** qui correspondent simultanément à :
      - La **classe IGH** de l'établissement.
      - La **présence de locaux à sommeil** de l'établissement.
      - Le type de commission courant.
   b. Pour chaque règle trouvée :
      - Si la règle porte sur une **commune** : vérifier la correspondance du code INSEE. Si oui → **commission trouvée**.
      - Si la règle porte sur un **groupement** : vérifier l'appartenance de la commune au groupement. Si oui → **commission trouvée**.

3. Si aucune règle ne correspond → **aucune commission compétente**.

### 6.4 Principe de priorité

L'algorithme parcourt les types de commissions du **plus spécifique au plus général** (tri décroissant des identifiants de type). Cela signifie qu'une sous-commission communale sera trouvée avant une commission départementale si les deux sont compétentes.

**Dès qu'une commission est trouvée, l'algorithme s'arrête.** Un établissement ne peut être rattaché qu'à une seule commission par cet algorithme.

### 6.5 Application en lot

L'opération « Appliquer les règles » utilise une variante de cet algorithme :

1. Le système récupère les règles de la commission sélectionnée (avec résolution des groupements en listes de communes).
2. Pour chaque établissement du département (ERP et IGH ayant une commune renseignée) :
   - **ERP** : vérifier la correspondance sur commune + locaux à sommeil + type d'activité + catégorie.
   - **IGH** : vérifier la correspondance sur commune + classe IGH.
3. Tous les établissements correspondants sont rattachés à la commission en une seule opération.

---

## 7. Cas particuliers

### 7.1 Commissions multiples avec règles chevauchantes

L'algorithme de détermination automatique retourne la **première** commission trouvée selon l'ordre de priorité des types. Si deux commissions de même type couvrent le même territoire et les mêmes critères, c'est la première règle satisfaite qui l'emporte (comportement indéterministe dans ce cas).

L'opération « Appliquer les règles » ne traite que la commission sélectionnée : elle peut **écraser** un rattachement précédent si un établissement correspondait déjà à une autre commission.

### 7.2 Établissements sans commission

Si aucune règle de compétence ne correspond à un établissement, celui-ci reste sans commission de rattachement. L'algorithme retourne un résultat nul.

### 7.3 Séances multi-dates (visites groupées)

Une séance peut combiner plusieurs dates (par exemple : une journée de visite le mardi et une séance en salle le jeudi). Toutes les dates partagent le même libellé et le même type d'événement. L'ordre du jour et les documents sont générés sur la base de la date principale, mais incluent les informations de toutes les dates liées.

### 7.4 Membres rattachés à un groupement

Les membres rattachés à un groupement (gendarmerie, police, préfecture…) reçoivent des documents organisés par groupement et non par commune. Le procès-verbal n'est **pas** généré pour ces membres.

### 7.5 Absence de modèle de document

Si un membre ne dispose pas du modèle de document requis pour une génération (convocation, ordre du jour, procès-verbal), un **message d'erreur** est affiché pour ce membre, mais la génération continue pour les autres membres.

### 7.6 Établissements avec parent

Lorsqu'un établissement est rattaché à un établissement parent (par exemple, un commerce dans un centre commercial), les documents générés mentionnent le **nom de l'établissement parent** en plus du nom de l'établissement concerné.

### 7.7 Avis de dérogation multi-niveaux

Les avis de dérogation sont collectés à trois niveaux pour chaque dossier :
1. Les dérogations propres au **dossier**.
2. Les dérogations de l'**établissement**.
3. Les dérogations des **établissements enfants** (établissements rattachés).

### 7.8 Droits d'accès

L'accès au calendrier des commissions est contrôlé par deux permissions :
- **Administration** : accès à la gestion et à la configuration des commissions.
- **Voir tous les événements** : possibilité d'afficher les événements de toutes les commissions simultanément, quel que soit le filtre de commission actif.

### 7.9 Données de fusion des formulaires personnalisés

Lors de la génération des convocations, les données des formulaires personnalisés de l'établissement et du dossier sont incluses :
- Vérifications techniques.
- Descriptif de l'établissement.
- Effectifs et dégagements (au niveau du dossier et de l'établissement).

Ces données enrichissent les documents officiels au-delà des informations standard.
