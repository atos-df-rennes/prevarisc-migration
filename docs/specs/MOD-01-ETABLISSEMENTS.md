# MOD-01 — Établissements

## 1. Objet et périmètre

Un **établissement** est une fiche centrale de Prevarisc. Elle représente tout lieu soumis à la réglementation en matière de prévention incendie et suivi par un SDIS : un ERP, un IGH, une habitation, un bâtiment à usage professionnel (BUP), un camping, une manifestation temporaire, un immeuble ouvert au public (IOP) ou une zone.

Le module Établissements permet de créer, consulter, modifier et supprimer la fiche d'un établissement. Il constitue le point d'entrée vers les dossiers (études, visites), les contacts, les pièces jointes, les textes réglementaires applicables, les avis et dérogations, et la planification des visites périodiques.

**Genres d'établissements gérés :**

| Genre | Description |
|-------|-------------|
| Site | Regroupement logique de plusieurs établissements sur un même lieu |
| Établissement (ERP) | Établissement recevant du public |
| Cellule | Sous-partie d'un ERP ou d'un IGH |
| Habitation | Bâtiment d'habitation |
| IGH | Immeuble de grande hauteur |
| BUP | Bâtiment à usage professionnel (ICPE possible) |
| Camping | Terrain de camping et de caravanage |
| Manifestation temporaire | Événement à durée limitée recevant du public |
| IOP | Installation ouverte au public |
| Zone | Périmètre réglementaire (zone industrielle, etc.) |

---

## 2. Acteurs concernés

| Acteur | Droits |
|--------|--------|
| **Préventionniste** | Consulte, crée et modifie les fiches des établissements de son secteur. Gère les contacts, pièces jointes, textes applicables, descriptifs, effectifs et dégagements. |
| **Administrateur** | Tous les droits du préventionniste, plus la suppression d'établissements et le changement de statut. Peut rétablir un établissement supprimé. |
| **Chef de groupement** | Consulte l'ensemble des fiches. Peut disposer de droits de modification selon le paramétrage des groupes. |
| **Utilisateur en lecture seule** | Consulte les fiches établissement sans possibilité de modification. |

> L'accès à certaines fonctionnalités (suppression, changement de statut, consultation des effectifs et dégagements, consultation des avis et dérogations) est conditionné par les droits du groupe de l'utilisateur connecté.

---

## 3. Données d'un établissement

### 3.1 Identification et classement

Chaque établissement possède les données d'identification suivantes :

- **Libellé** : nom courant de l'établissement (ex. : « Centre commercial Les Halles »).
- **Identifiant numérique** : numéro attribué automatiquement à la création, modifiable manuellement.
- **Identifiant complémentaire** : code conservé avec l'établissement. Il n'est pas généré automatiquement par Prevarisc.
- **Genre** : classification principale (site, ERP, cellule, habitation, IGH, BUP, camping, manifestation temporaire, IOP, zone).
- **Statut** : état courant de l'établissement (ex. : ouvert, fermé, projet). Seuls les utilisateurs autorisés peuvent le modifier.

Selon le genre, les données de classement supplémentaires sont :

| Genre | Données de classement |
|-------|----------------------|
| ERP / Cellule | Catégorie (1re à 5e), type d'activité principal (J, L, M, N, O, P, R, S, T, U, V, W, X, Y…), activités secondaires, indicateur ICPE, présence de locaux à sommeil, application du R143-20, application du R143-16 (droit public) |
| Habitation | Famille |
| IGH | Classe (GHA, GHO, GHR, GHS, GHU, GHW, GHZ, GHTC, ITGH) |
| BUP | Indicateur ICPE, rubriques ICPE (substance, activité, IED, produits dangereux) |
| Zone | Classement spécifique |
| Camping | Nombre d'emplacements par type |
| Manifestation / IOP | Effectifs public et personnel |

### 3.2 Localisation

- **Adresse(s)** : un établissement possède une ou plusieurs adresses postales composées de : numéro, voie (type et libellé), complément d'adresse, code INSEE de la commune, code postal, libellé de la commune.
- **Géolocalisation** : coordonnées GPS (latitude, longitude) associées à chaque adresse, obtenues via un service de géocodage (IGN Géoportail ou GeoConcept).
- **Groupements de communes** : un établissement est rattaché aux groupements (intercommunalités, EPCI) de la commune de sa première adresse.
- **Cartographie** : la fiche affiche la position sur une carte IGN, Google Maps ou GeoConcept selon la configuration du SDIS.

**Cas particulier — adresse des sites et cellules :**
- Un **site** n'a pas d'adresse propre : son adresse est celle de l'établissement enfant ayant le plus grand effectif total.
- Une **cellule** n'a pas d'adresse propre : son adresse est celle du site racine, obtenue en remontant la hiérarchie parent.

### 3.3 Informations techniques

#### Descriptif

L'établissement dispose de trois zones de texte libre :
- **Historique** : description chronologique de l'établissement.
- **Descriptif général** : description de l'activité et des locaux.
- **Dérogations accordées** : texte libre décrivant les dérogations octroyées.

Un **descriptif technique** structuré est également disponible, organisé en catégories thématiques (structure, désenfumage, moyens de secours, etc.), chaque catégorie contenant des champs typés (texte, numérique, oui/non).

En option, un **descriptif personnalisé** paramétrable remplace le descriptif d'origine. Il est structuré en rubriques configurables avec des champs de formulaire personnalisés (texte, nombre, date, liste, tableau).

#### Effectifs

| Genre | Effectifs saisis |
|-------|-----------------|
| ERP | Public, personnel, hébergé (si locaux à sommeil), justifiant le classement (si 5e catégorie) |
| Cellule | Public, personnel |
| IGH | Public, personnel |
| BUP | Personnel uniquement |
| Camping | Mobil-homes/habitations, autocaravanes, emplacements nus, autres emplacements |
| Manifestation / IOP | Public, personnel |

L'effectif total est calculé automatiquement : public + personnel (ou somme des emplacements pour les campings).

#### Effectifs et dégagements (module personnalisé)

Un onglet dédié « Effectifs et dégagements » permet de saisir des données structurées en rubriques personnalisables, selon le même principe que le descriptif personnalisé. Son accès dépend des droits de l'utilisateur.

#### Plans d'intervention

Pour les ERP, cellules, IGH et BUP, des plans d'intervention peuvent être enregistrés :
- Type de plan (ETARE, plan de situation, plan de masse…).
- Numéro du plan.
- Date du plan.
- Indicateur de mise à jour (à jour / pas à jour).

### 3.4 Avis et dérogations

- **Avis d'exploitation courant** : avis favorable ou défavorable issu du dernier dossier donnant avis (visite périodique, visite de réception, etc.). L'avis est déterminé automatiquement à partir du dossier le plus récent ayant un avis de commission rendu, parmi des natures de dossier spécifiques (PC initial, visite de réception, visite périodique, etc.).
- **Avis différé** : un dossier peut porter un avis différé. Si le dossier avec avis différé le plus récent est postérieur au dossier donnant avis, c'est l'avis différé qui s'affiche.
- **Historique des avis et dérogations** : liste chronologique de tous les avis et dérogations issus des dossiers liés à l'établissement, avec indication de leur levée éventuelle.
- **Dérogations originales** : texte libre issu du descriptif (zone « dérogations accordées »), affiché séparément dans l'onglet avis et dérogations.

L'accès à l'onglet avis et dérogations dépend des droits de l'utilisateur.

### 3.5 Textes réglementaires applicables

Des textes réglementaires peuvent être associés à un établissement parmi un référentiel prédéfini, organisé par catégorie thématique. L'utilisateur coche les textes applicables dans une liste structurée. L'affichage restitue les textes groupés par catégorie.

### 3.6 Contacts

Un établissement possède une liste de contacts. Chaque contact est caractérisé par :
- Nom et prénom.
- Société.
- Fonction (propriétaire, exploitant, directeur unique de sécurité — DUS, mandataire, etc.).
- Téléphones (fixe, mobile, fax).
- Courriel.
- Site web.
- Adresse postale.

Il est possible d'ajouter un **nouveau contact** (créé spécifiquement) ou de **rattacher un contact existant** déjà présent dans le système.

En consultation, les contacts des **établissements parents** sont affichés en complément des contacts propres à l'établissement.

### 3.7 Pièces jointes

Des fichiers peuvent être attachés à un établissement :
- **Documents** : tout type de fichier (PDF, DOC, etc.) — affichés sous forme de tableau (nom, description, date d'ajout).
- **Images** : fichiers JPG, JPEG, PNG, GIF — affichés en galerie miniatures avec visionneuse.

Chaque pièce jointe possède :
- Un libellé.
- Un fichier uploadé.
- Une description.
- Un mode de mise en avant :
  - Liste principale uniquement.
  - Diaporama (affichage en carrousel sur la fiche).
  - Plans (affichage dans le carrousel des plans).

L'ajout peut se faire par formulaire classique ou par glisser-déposer (ajout rapide).

Seules les pièces jointes physiquement présentes sur le système de fichiers sont affichées (les références vers des fichiers manquants sont filtrées).

### 3.8 Hiérarchie (site / cellules)

Les établissements s'organisent en arborescence parent-enfant :

```
Site
├── ERP 1
│   ├── Cellule A
│   └── Cellule B
├── ERP 2
├── Habitation
├── IGH
│   └── Cellule C
└── BUP
```

**Relations autorisées :**

| Parent | Enfants autorisés |
|--------|-------------------|
| Site | ERP, habitation, IGH, BUP, camping, manifestation, IOP |
| ERP | Cellule uniquement |
| IGH | Cellule uniquement |

**Contraintes :**
- Un site ne peut jamais être enfant d'un autre établissement.
- Un site ne peut pas avoir de père.
- Une cellule a obligatoirement pour père un ERP ou un IGH.
- Un ERP, une habitation, un IGH, un BUP, un camping, une manifestation ou un IOP a pour père un site.

---

## 4. Fonctionnalités

### 4.1 Consulter la fiche d'un établissement

**Description :** L'utilisateur accède à la fiche complète d'un établissement en lecture seule.

**Informations affichées :**
- Classement (genre, catégorie/famille/classe/classement, périodicité, activités).
- Indicateurs réglementaires (ICPE, R143-20, R143-16, locaux à sommeil).
- Rubriques ICPE (pour les BUP).
- Effectifs.
- Plans d'intervention.
- Établissements liés (enfants) avec filtre de recherche textuel.
- Carte(s) de localisation (IGN, Google Maps, GeoConcept).
- Diaporama photos et plans.
- Adresse(s) et groupements de communes.
- Coordonnées (téléphone, fax, courriel).
- Données prévention : commission compétente, préventionnistes affectés, dates de dernière et prochaine visite périodique, date du PC initial, présence d'un DUS, données pratiques (nombre de préventionnistes par visite, durée de visite).
- Avis d'exploitation courant (favorable / défavorable / avis différé).

**Accès :** Un bouton « Modifier la fiche » permet de basculer en mode édition. Un bouton de suppression est affiché si l'utilisateur dispose du droit de suppression.

### 4.2 Créer un établissement

**Description :** L'utilisateur crée un nouvel établissement en remplissant le formulaire d'édition.

**Données saisies :** Libellé, identifiant, statut, genre, données de classement selon le genre, activités, effectifs, plans, adresses, coordonnées, commission, préventionnistes, données pratiques, établissements liés (père et enfants).

**Comportement :**
- Le formulaire propose des **valeurs conseillées** (commission, périodicité, locaux à sommeil, préventionnistes) calculées dynamiquement en fonction du genre, de la commune, du type, de la catégorie et de l'établissement père.
- Les champs affichés varient dynamiquement selon le genre sélectionné.
- À la validation, le système enregistre l'établissement et sa fiche d'informations initiale datée du jour.
- Si l'établissement créé est un site sans aucun enfant, un avertissement invite l'utilisateur à ajouter des établissements enfants pour garantir les droits d'accès. Un site créé avec au moins un enfant ne déclenche pas cet avertissement.

### 4.3 Modifier un établissement

**Description :** L'utilisateur modifie les informations d'un établissement existant.

**Comportement :**
- La modification génère une **nouvelle fiche d'informations datée** du jour courant. Si une fiche existe déjà à la même date, elle est mise à jour (pas de doublon de fiche pour une même date).
- Avant la sauvegarde, une **boîte de confirmation** compare les valeurs saisies (commission, périodicité, locaux à sommeil, préventionnistes) aux valeurs conseillées par le système.
- Une ligne de la boîte de confirmation est masquée lorsque la valeur saisie est égale à la valeur conseillée. Pour les préventionnistes, l'égalité correspond au même ensemble d'utilisateurs, quel que soit l'ordre. La boîte n'apparaît pas si toutes ses lignes sont masquées. Accepter la suggestion de préventionnistes remplace les préventionnistes affectés.
- Si l'envoi de courriels est activé, le système détecte les changements de **statut** ou de **classement** (catégorie, type, activités) et propose d'envoyer une alerte par courriel.
- Après sauvegarde, l'utilisateur est redirigé et un message de succès est affiché.

### 4.4 Supprimer un établissement

**Description :** L'utilisateur disposant du droit de suppression peut supprimer un établissement.

**Comportement :**
- La suppression est **logique** (pas de suppression physique) : une date de suppression et l'identifiant de l'utilisateur ayant supprimé sont enregistrés.
- Tous les **dossiers liés** à l'établissement sont également supprimés (logiquement).
- Une boîte de confirmation demande la validation avant exécution.
- Après suppression, l'utilisateur est redirigé vers la liste de recherche.

**Rétablissement :** Un administrateur peut rétablir un établissement supprimé, ce qui efface la date de suppression et l'auteur de la suppression.

### 4.5 Gérer les contacts d'un établissement

**Ajouter un nouveau contact :** L'utilisateur saisit nom, prénom, société, fonction, téléphones, courriel, site web et adresse dans une fenêtre modale.

**Ajouter un contact existant :** L'utilisateur recherche et sélectionne un contact déjà existant dans le système pour le rattacher à l'établissement.

**Supprimer un contact :** L'utilisateur retire un contact de la fiche (dissociation, pas de suppression du contact lui-même).

**Consultation :** L'affichage montre les contacts propres à l'établissement et ceux hérités des établissements parents.

### 4.6 Gérer les pièces jointes

**Ajouter une pièce jointe :** Formulaire modal avec libellé, fichier, mode de mise en avant (liste, diaporama, plans) et description. Un mode d'ajout rapide par glisser-déposer est disponible.

**Supprimer une pièce jointe :** L'utilisateur supprime une pièce jointe depuis le tableau de gestion.

**Consultation :** Les pièces jointes sont séparées entre documents (tableau) et images (galerie miniatures).

### 4.7 Gérer les textes applicables

**Modification :** L'utilisateur coche ou décoche les textes applicables dans une liste structurée par catégorie thématique.

**Consultation :** Les textes cochés sont affichés groupés par catégorie avec un indicateur visuel (✓).

### 4.8 Consulter l'historique des dossiers

**Description :** L'onglet « Dossiers » affiche la liste des dossiers liés à l'établissement, classés en trois catégories :
- **Visites** (visites de commission, groupes de visite).
- **Études** (études de dossier).
- **Autres**.

Les dossiers sont affichés par lot avec un bouton « Voir plus » pour afficher les dossiers suivants. Chaque dossier permet d'accéder à sa fiche.

Un bouton permet de créer un nouveau dossier directement lié à l'établissement.

### 4.9 Consulter les effectifs et dégagements

**Description :** Un onglet dédié (dont le libellé est paramétrable) affiche les effectifs et dégagements sous forme de rubriques personnalisées.

**Modification :** L'utilisateur peut modifier les valeurs des rubriques, réordonner les éléments et gérer les tableaux de données.

L'accès dépend des droits de l'utilisateur.

### 4.10 Gérer les avis et dérogations

**Consultation :** L'onglet affiche :
- L'historique chronologique des avis et dérogations en vigueur, avec indication de leur levée éventuelle. Chaque entrée renvoie vers le dossier source.
- Les dérogations originales (texte libre issu du descriptif).

L'accès dépend des droits de l'utilisateur.

### 4.11 Gérer la hiérarchie parent/enfant

**En mode édition :**
- L'utilisateur peut désigner un **établissement père** en saisissant son libellé (autocomplétion).
- L'utilisateur peut ajouter des **établissements enfants** en saisissant leur libellé (autocomplétion) ou créer un enfant directement via un lien dédié.
- Les enfants et le père peuvent être retirés de la liste.

**Historique :** L'onglet « Historique » affiche l'évolution chronologique du libellé, du statut, de la catégorie, de la classe, de la famille, du type d'activité et de l'avis d'exploitation, avec les dates de début et de fin de chaque période et l'auteur de la modification.

---

## 5. Règles de gestion

### Identification

**RG-ETB-001** — Chaque établissement possède un identifiant unique attribué automatiquement à la création. L'utilisateur peut le remplacer par un identifiant personnalisé.

**RG-ETB-002** — L'identifiant complémentaire n'est pas calculé automatiquement par Prevarisc.

### Classement et genre

**RG-ETB-004** — Le genre détermine les champs affichés dans le formulaire et les données enregistrées. Un changement de genre réinitialise les champs spécifiques au genre précédent à vide.

**RG-ETB-005** — Seuls les ERP et les cellules disposent d'une catégorie (1re à 5e) et d'un type/activité principaux.

**RG-ETB-006** — Seules les habitations disposent d'une famille.

**RG-ETB-007** — Seuls les IGH disposent d'une classe.

**RG-ETB-008** — Seules les zones disposent d'un classement spécifique.

**RG-ETB-009** — Dans le formulaire d'édition, l'indicateur ICPE est proposé pour les BUP, mais pas pour les ERP. Lorsqu'il est activé pour un BUP, les rubriques ICPE (substance, activité, IED, produits dangereux) deviennent disponibles. En consultation, le badge ICPE est affiché si cette valeur est enregistrée, quel que soit le genre de l'établissement.

**RG-ETB-010** — Les activités secondaires (types et activités) ne sont disponibles que pour les ERP et les cellules.

**RG-ETB-011** — L'indicateur « locaux à sommeil » n'est disponible que pour les ERP. Il influe sur le calcul de la périodicité et de la commission compétente.

**RG-ETB-012** — L'indicateur R143-20 n'est disponible que pour les ERP et les cellules.

**RG-ETB-013** — L'indicateur R143-16 (droit public) n'est disponible que pour les ERP.

**RG-ETB-014** — L'effectif « justifiant le classement » n'est saisi que pour les ERP de 5e catégorie.

**RG-ETB-015** — L'effectif « hébergé » n'est saisi que pour les ERP avec locaux à sommeil.

### Statut

**RG-ETB-016** — Seuls les utilisateurs autorisés peuvent modifier le statut d'un établissement. Les autres utilisateurs voient le champ en lecture seule.

**RG-ETB-017** — Le statut par défaut à la création est le premier statut du référentiel (généralement « Projet »).

### Suppression et rétablissement

**RG-ETB-018** — La suppression d'un établissement est logique : la date de suppression et l'auteur sont enregistrés. L'établissement n'apparaît plus dans les recherches ni dans les fiches liées.

**RG-ETB-019** — La suppression d'un établissement entraîne la suppression (logique) de tous ses dossiers associés.

**RG-ETB-020** — Un établissement supprimé peut être rétabli par un administrateur. Le rétablissement efface la date de suppression et l'auteur.

**RG-ETB-021** — La suppression est réservée aux utilisateurs autorisés.

### Hiérarchie parent / enfant

**RG-ETB-022** — Un **site** ne peut contenir que des ERP, habitations, IGH, BUP, campings, manifestations temporaires et IOP comme enfants.

**RG-ETB-023** — Un **ERP** ou un **IGH** ne peut contenir que des **cellules** comme enfants.

**RG-ETB-024** — Un **site** ne peut jamais être enfant d'un autre établissement.

**RG-ETB-025** — Un **site** ne peut pas avoir de père.

**RG-ETB-026** — Une **cellule** doit avoir pour père un ERP ou un IGH.

**RG-ETB-027** — Un ERP, une habitation, un IGH, un BUP, un camping, une manifestation ou un IOP a pour père un site.

**RG-ETB-028** — La modification de la catégorie d'un ERP ne met pas automatiquement à jour la catégorie des cellules déjà rattachées. À la création ou au rattachement d'une cellule, celle-ci reprend la catégorie de son ERP parent (voir RG-ETB-029).

**RG-ETB-029** — Lorsqu'une cellule est créée ou rattachée à un ERP, elle hérite automatiquement de la **catégorie** de son parent.

### Adresse

**RG-ETB-030** — Un **site** n'a pas d'adresse propre. L'adresse affichée est celle de l'établissement enfant ayant le plus grand effectif total (public + personnel). Si le site n'a aucun enfant, aucune adresse n'est affichée.

**RG-ETB-031** — Une **cellule** n'a pas d'adresse propre. L'adresse affichée est celle du site racine, obtenue en remontant récursivement la hiérarchie parent jusqu'au sommet.

**RG-ETB-032** — Les adresses ne sont enregistrables que pour les genres suivants : ERP, habitation, IGH, BUP, camping, manifestation temporaire, IOP et zone.

**RG-ETB-033** — Une adresse nécessite au minimum une voie valide (identifiant de voie > 0) et un code INSEE de commune.

### Contacts

**RG-ETB-034** — Les contacts des établissements parents sont visibles en consultation depuis la fiche d'un établissement enfant, en complément des contacts propres.

**RG-ETB-035** — La suppression d'un contact depuis la fiche établissement ne supprime pas le contact du système ; elle dissocie uniquement le lien entre le contact et l'établissement.

**RG-ETB-036** — La présence d'un DUS (directeur unique de sécurité) est détectée automatiquement : elle est vraie si au moins un contact (propre ou hérité d'un parent) a la fonction « DUS ».

### Pièces jointes

**RG-ETB-037** — Les pièces jointes sont classées en trois placements : liste principale (valeur 0), diaporama (valeur 1), plans (valeur 2). Seules les images (JPG, JPEG, PNG) sont acceptées pour le diaporama et les plans.

**RG-ETB-038** — Les pièces jointes qui ne sont pas disponibles n'apparaissent pas dans la fiche.

### Historisation

**RG-ETB-039** — Chaque modification d'un établissement crée une **nouvelle fiche d'informations datée**. L'ensemble des fiches constitue l'historique de l'établissement. Si une fiche existe déjà à la date du jour, elle est mise à jour au lieu d'en créer une nouvelle.

**RG-ETB-040** — L'historique d'un établissement retrace l'évolution chronologique des données suivantes : libellé, statut, catégorie, classe, famille, type d'activité principal, et avis d'exploitation. Chaque entrée indique la date de début, la date de fin (ou « à aujourd'hui »), la valeur et l'auteur de la modification.

**RG-ETB-041** — L'historique des avis d'exploitation est reconstitué à partir des dossiers liés à l'établissement (études et visites) ayant un avis de commission rendu (favorable ou défavorable) et faisant partie des natures donnant avis.

### Alertes

**RG-ETB-042** — Si l'envoi de courriels est activé dans la configuration du SDIS, le système détecte lors de la modification si le **statut** ou le **classement** (catégorie, type principal, activité principale, activités secondaires) a changé. Le cas échéant, l'utilisateur peut envoyer une alerte par courriel.

**RG-ETB-043** — Les deux types d'alerte sont : changement de statut et changement de classement. Un seul type est retenu (le changement de classement prime si les deux se produisent).

### Données pratiques

**RG-ETB-044** — Les données pratiques (nombre de préventionnistes par visite, durée de visite) ne sont saisies que pour les ERP, cellules et IGH.

**RG-ETB-045** — Pour un **site**, la durée totale de visite est **calculée automatiquement** en additionnant pour chaque enfant : nombre de préventionnistes × durée de visite. Le nombre de préventionnistes par visite n'est pas applicable au site.

### Textes applicables

**RG-ETB-046** — Les textes applicables sont sélectionnés parmi un référentiel fixe organisé par catégorie. La modification consiste à cocher ou décocher des textes. La sauvegarde remplace intégralement la sélection précédente.

### Valeurs conseillées

**RG-ETB-047** — Lors de la création ou modification, le système propose des **valeurs conseillées** en temps réel, recalculées à chaque changement de genre, commune, catégorie, type ou parent :

| Donnée | Règle de calcul |
|--------|----------------|
| Périodicité (ERP) | Déterminée par le croisement catégorie + type + présence de locaux à sommeil dans le référentiel GN4 |
| Périodicité (IGH) | Déterminée par la classe dans le référentiel GN4 |
| Commission (ERP) | Commission du père si l'établissement a un père ; sinon, commission compétente déterminée par la commune + catégorie + type + locaux à sommeil |
| Commission (IGH) | Commission du père si disponible ; sinon, commission IGH par commune + classe |
| Locaux à sommeil | Oui par défaut si le type principal est un type concerné (par défaut : types O et U, configurable par le SDIS) |
| Préventionnistes | Site : préventionnistes de la commune ; cellule : préventionnistes du père ; autres genres : préventionnistes du père en priorité, sinon ceux de la commune. Pour le père, seuls les préventionnistes actifs de la dernière fiche sont proposés, sans doublon, triés par nom puis prénom. Pour la commune, les préventionnistes de tous les groupements couvrant la commune sont proposés, sans doublon, triés par type de groupement, libellé du groupement puis nom (sans filtre sur les comptes actifs). |

---

## 6. Commission compétente

**RG-ETB-048** — La commission compétente est affichée uniquement pour les **ERP** et les **IGH**.

**RG-ETB-049** — Pour un **ERP**, la commission est déterminée selon la priorité suivante :
1. Si l'ERP a un établissement père (site), la commission du père est proposée.
2. Sinon, la commission est recherchée dans le référentiel des compétences par croisement : code INSEE de la commune + catégorie + type principal + présence de locaux à sommeil.
3. Si aucune correspondance n'est trouvée, aucune commission n'est proposée.

**RG-ETB-050** — Pour un **IGH**, la commission est déterminée selon la priorité suivante :
1. Si l'IGH a un établissement père, la commission du père est proposée.
2. Sinon, la commission IGH est recherchée par croisement : code INSEE de la commune + classe.

**RG-ETB-051** — La commission reste modifiable manuellement par l'utilisateur, indépendamment de la valeur conseillée. Les commissions sont proposées dans une liste déroulante groupée par type de commission.

---

## 7. Périodicité de visite

**RG-ETB-052** — La périodicité de visite est exprimée en **mois**. Un paramétrage du SDIS permet de l'afficher en années et mois (ex. : « 3 ans » au lieu de « 36 mois », ou « 2 ans et 6 mois » pour 30 mois).

**RG-ETB-053** — Pour un **ERP**, la périodicité par défaut est calculée à partir du référentiel GN4 en croisant : catégorie + type principal + présence de locaux à sommeil. L'utilisateur peut la modifier manuellement.

**RG-ETB-054** — Pour un **IGH**, la périodicité par défaut est calculée à partir du référentiel GN4 en croisant : classe (avec catégorie=0 et local à sommeil=non).

**RG-ETB-055** — Pour un **site**, la périodicité affichée est la **périodicité la plus contraignante** (la plus courte) parmi ses ERP enfants ouverts ayant une périodicité non nulle. Un indicateur visuel le signale.

**RG-ETB-056** — Pour une **cellule**, la périodicité affichée est celle de son **ERP parent** (par remontée de la hiérarchie).

**RG-ETB-057** — Si la périodicité saisie diffère de la périodicité standard calculée par le GN4, un indicateur « périodicité non standard » est affiché sur la fiche.

**RG-ETB-058** — La date de **dernière visite périodique** est celle de la visite la plus récente parmi les dossiers de type « visite de commission » ou « groupe de visite » de nature périodique, ayant un avis de commission rendu. Selon la configuration, la date retenue peut être la date de commission (si postérieure à la date de visite) ou la date de visite.

**RG-ETB-059** — La date de **prochaine visite périodique** est calculée en ajoutant la périodicité (en mois) à la date de la dernière visite. Elle n'est calculée que si la périodicité est non nulle et qu'une dernière visite existe.

**RG-ETB-060** — Un **recalcul global des périodicités** peut être appliqué en masse sur tous les établissements, en mettant à jour la périodicité de la dernière fiche d'informations de chaque établissement à partir du référentiel GN4.

---

## 8. Cas particuliers et limites

### Établissements supprimés

Les établissements supprimés (logiquement) n'apparaissent plus dans les recherches ni dans les consultations de fiches. Ils peuvent être listés dans une vue d'administration dédiée qui affiche la date de suppression, l'auteur et la commune. Ils peuvent être rétablis.

### IGH

Les IGH partagent certaines caractéristiques avec les ERP (périodicité, commission, préventionnistes, effectifs) mais utilisent la **classe** au lieu de la catégorie et du type. Le calcul de la périodicité GN4 pour un IGH utilise la classe en lieu de type et fixe la catégorie à 0.

### Cellules

Les cellules sont rattachées à un ERP ou à un IGH. Elles :
- Héritent de la **catégorie** de leur parent ERP (affectation automatique).
- Héritent de la **périodicité** de leur parent via la hiérarchie.
- N'ont **pas d'adresse propre** (adresse remontée depuis le site racine).
- N'ont **pas de commission** propre.

### Sites

Les sites sont des conteneurs logiques. Ils :
- N'ont **pas d'adresse propre** (adresse de l'enfant au plus fort effectif).
- N'ont **pas de commission** propre.
- Ont une périodicité calculée (la plus contraignante des enfants ERP ouverts).
- Ont une durée de visite calculée (somme pondérée des durées de visite des enfants).
- Ne peuvent pas être enfants d'un autre établissement.

### Campings

Les campings utilisent un système d'effectifs spécifique basé sur les emplacements (mobil-homes, autocaravanes, emplacements nus, autres). Le total est calculé automatiquement.

### Avis différé

Si un dossier avec avis différé est postérieur au dernier dossier donnant avis, l'avis affiché sur la fiche est l'avis différé (et non l'avis du dossier). Cela permet de gérer les situations où un avis favorable est temporairement suspendu.

### Établissements liés et droits d'accès

Les droits d'accès à un **site** sont déterminés par les droits d'accès aux établissements enfants qui le composent. Un site sans enfant n'est potentiellement accessible à personne : un message d'avertissement est affiché lors de sa création sans enfant. La présence d'au moins un enfant évite cet avertissement.

### Date de PC initial

La date du permis de construire initial est retrouvée en cherchant le premier dossier de type « étude » et de nature « permis de construire » lié à l'établissement, trié par date d'insertion.

### Facteur de dangerosité

Un facteur de dangerosité peut être associé à l'établissement via le dossier donnant avis. Il est stocké au niveau du dossier et affiché sur la fiche de l'établissement.

### Présence d'un échéancier

Le système détecte automatiquement si un dossier de nature « échéancier » est lié à l'établissement. Cette information est disponible sur la fiche.

### Liste des ERP ouverts sous avis défavorable

Le système permet de lister les ERP (genre=Établissement) ouverts (statut=Ouvert) sous avis défavorable, avec des filtres optionnels par commission, commune et préventionniste.

### ERP sans préventionniste

Le système permet de lister les ERP ouverts ayant une périodicité non nulle mais n'ayant aucun préventionniste affecté.

### ERP sans prochaine visite périodique

Le système identifie les ERP ouverts dont la date de prochaine visite périodique est dépassée (date de dernière visite + périodicité < date courante).
