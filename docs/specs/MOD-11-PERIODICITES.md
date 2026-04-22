# MOD-11 — Périodicités

## 1. Objet et périmètre

Le module Périodicités définit la **fréquence réglementaire des visites de contrôle** pour les établissements recevant du public (ERP) et les immeubles de grande hauteur (IGH).

Il s'appuie sur une **matrice de référence** qui croise plusieurs caractéristiques d'un établissement pour déterminer automatiquement sa périodicité de visite, exprimée **en mois**.

Ce module couvre :

- La consultation et la modification de la matrice de référence des périodicités.
- L'application en masse des périodicités de la matrice à l'ensemble des établissements existants.
- Le calcul automatique de la périodicité par défaut lors de la création ou la modification d'un établissement.
- Le calcul de la date de prochaine visite périodique.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|---------------------|
| **Administrateur** | Consulte, modifie la matrice de référence et lance l'application en masse sur les établissements. |
| **Préventionniste** | Consulte la périodicité d'un établissement sur sa fiche. Peut modifier manuellement la périodicité d'un établissement individuel lors de l'édition de sa fiche. |
| **Tout utilisateur** | Consulte la périodicité affichée sur la fiche récapitulative d'un établissement (ERP, IGH, site). |

---

## 3. La matrice des périodicités

### 3.1 Axes de la matrice

La matrice de référence est organisée différemment selon le genre d'établissement.

#### Pour les ERP

La matrice croise **trois axes** :

1. **Catégorie** — La catégorie de classement de l'ERP (1re à 5e catégorie).
2. **Type d'activité** — Le type d'activité réglementaire de l'ERP (J, L, M, N, O, P, R, S, T, U, V, W, X, Y, etc.).
3. **Locaux à sommeil** — Présence ou absence de locaux à sommeil (oui / non).

Chaque combinaison unique de ces trois axes produit une valeur de périodicité.

#### Pour les IGH

La matrice utilise **un seul axe** :

1. **Classe d'IGH** — La classe de l'immeuble de grande hauteur (GHA, GHO, GHR, GHS, GHU, GHW, GHZ, GHTC, ITGH, etc.).

La catégorie est fixée à une valeur neutre (zéro) et le critère locaux à sommeil n'est pas applicable.

### 3.2 Valeurs de périodicité

- Les valeurs sont exprimées en **mois** (nombre entier).
- La saisie est limitée à **trois chiffres** maximum (soit de 0 à 999 mois).
- Une valeur à **0** signifie qu'aucune visite périodique n'est requise pour cette combinaison.
- Une valeur à **0** est affichée visuellement avec une opacité réduite pour indiquer l'absence de périodicité.

---

## 4. Fonctionnalités

### 4.1 Consulter la matrice

**Accès** : Menu d'administration.

**Description** : L'écran affiche deux tableaux distincts.

#### Tableau ERP

- **En-têtes de colonnes** : les types d'activité, regroupés par blocs de 7 pour faciliter la lecture.
- **Sous-en-têtes** : pour chaque type, deux colonnes — « sans locaux à sommeil » et « avec locaux à sommeil ».
- **Lignes** : les catégories d'ERP (1re à 5e catégorie).
- **Cellules** : la valeur de périodicité en mois, dans un champ de saisie.

**Cas particulier — types obligatoirement avec locaux à sommeil** : certains types d'activité sont considérés comme ayant **obligatoirement** des locaux à sommeil (par défaut les types O et R, configurable par l'administrateur système). Pour ces types, la colonne « sans locaux à sommeil » est masquée (champ invisible non modifiable).

#### Tableau IGH

- **En-têtes de colonnes** : les classes d'IGH.
- **Une seule ligne** sans distinction de catégorie ni de locaux à sommeil.
- **Cellules** : la valeur de périodicité en mois.

### 4.2 Modifier une périodicité

**Accès** : Même écran que la consultation.

**Description** : L'administrateur modifie directement les valeurs dans les champs de saisie du tableau.

**Action « Sauvegarder »** : enregistre toutes les modifications de la matrice. Un message de confirmation s'affiche en cas de succès, un message d'erreur en cas d'échec.

**Comportement** :

- Si une combinaison (catégorie, type, locaux à sommeil) n'existe pas encore en base, elle est créée automatiquement.
- Si elle existe déjà, la valeur est mise à jour.

### 4.3 Appliquer les périodicités aux établissements

**Accès** : Bouton « Appliquer sur TOUS les établissements » sur l'écran de la matrice.

**Description** : Cette action propage les valeurs de la matrice vers l'ensemble des établissements existants.

**Comportement** :

- Une demande de confirmation est affichée avant l'exécution (« Êtes-vous sûr de vouloir appliquer ces paramètres à l'ensemble des établissements ? »).
- L'application met à jour la périodicité de chaque établissement en fonction de sa catégorie, son type d'activité et la présence de locaux à sommeil, en se basant sur sa **fiche d'informations la plus récente** (dernière date d'historique).
- La correspondance s'effectue par jointure entre la matrice de référence et les caractéristiques courantes de chaque établissement.
- L'opération est transactionnelle : en cas d'erreur, aucune modification n'est effectuée.
- Le cache de recherche est vidé intégralement après l'application.
- Un message de succès ou d'erreur est affiché après l'opération.

---

## 5. Impact sur les établissements

### 5.1 Calcul automatique de la périodicité par défaut

Lors de la création ou de la modification d'un établissement, le système propose automatiquement une **périodicité par défaut** issue de la matrice de référence :

- **Pour un ERP** : la périodicité est recherchée à partir de la catégorie, du type d'activité et de la présence de locaux à sommeil.
- **Pour un IGH** : la périodicité est recherchée à partir de la classe d'IGH (la catégorie est fixée à zéro, les locaux à sommeil ne s'appliquent pas).

L'utilisateur peut ensuite modifier manuellement cette valeur sur la fiche de l'établissement.

### 5.2 Indicateur de périodicité non standard

Sur la fiche récapitulative d'un établissement (ERP ou IGH), si la périodicité enregistrée **diffère** de la périodicité par défaut issue de la matrice, un indicateur visuel (icône d'avertissement) signale une « périodicité non standard ».

### 5.3 Périodicité d'un site

Un site ne possède pas de périodicité propre. Sa périodicité affichée est la **périodicité la plus contraignante** (la plus courte, en nombre de mois) parmi ses établissements ERP enfants ouverts. Un indicateur visuel précise cette règle de calcul.

Les établissements enfants filtrés sont :

- De genre « Établissement » (ERP).
- En statut « ouvert ».
- Ayant une périodicité non nulle et non vide.

### 5.4 Périodicité d'une cellule

Une cellule ne définit pas sa propre périodicité. Elle hérite de la périodicité de son **établissement parent**.

### 5.5 Calcul de la date de prochaine visite

La date de prochaine visite périodique est calculée ainsi :

1. Le système recherche la **dernière visite périodique** de l'établissement, parmi les dossiers de type « visite de commission » et « groupe de visite » ayant un avis de commission rendu, de nature « périodique » (et autres natures donnant avis).
2. La date de référence est la **date de visite** de ce dernier dossier.
3. La périodicité de l'établissement (en mois) est **ajoutée** à cette date pour obtenir la date prévisionnelle de la prochaine visite.
4. Si la périodicité est à zéro, aucune prochaine visite n'est calculée.

### 5.6 Unité d'affichage

La périodicité est affichée par défaut **en mois**. Un paramètre de configuration optionnel permet d'afficher la périodicité **en années et mois** lorsque la valeur atteint ou dépasse 12 mois.

Exemples :

- 24 mois → « 2 ans »
- 36 mois → « 3 ans »
- 18 mois → « 1 an et 6 mois »
- 6 mois → « 6 mois » (inchangé)

### 5.7 Date de référence pour la relance

Un paramètre de configuration optionnel permet de choisir la **date de référence** utilisée pour le calcul de la relance périodique :

- **Par défaut** : la date de visite du dernier dossier périodique.
- **Option activable** : la date la plus récente entre la date de visite et la date de commission, utile lorsque le dernier dossier donnant avis est un groupe de visite dont la commission s'est tenue après la visite.

### 5.8 Détection des ERP en retard de visite périodique

Le système identifie les ERP ouverts dont la prochaine visite périodique est **dépassée** (date de prochaine visite antérieure au mois en cours). Ces établissements sont remontés dans les tableaux de bord et les listes de suivi.

---

## 6. Règles de gestion

| Identifiant | Règle |
|-------------|-------|
| **RG-PER-001** | La périodicité est exprimée en **mois** (nombre entier, de 0 à 999). |
| **RG-PER-002** | La clé unique d'une périodicité dans la matrice est le triplet **(catégorie, type d'activité, locaux à sommeil)**. |
| **RG-PER-003** | Une valeur de périodicité à **0** signifie qu'aucune visite périodique n'est requise pour cette combinaison. |
| **RG-PER-004** | Pour un **ERP**, la périodicité par défaut est déterminée par le croisement de sa catégorie, de son type d'activité et de la présence de locaux à sommeil. |
| **RG-PER-005** | Pour un **IGH**, la périodicité par défaut est déterminée par sa classe. La catégorie est fixée à zéro et les locaux à sommeil ne s'appliquent pas. |
| **RG-PER-006** | Certains types d'activité sont considérés comme **obligatoirement avec locaux à sommeil** (par défaut : O et R). Cette liste est configurable. Pour ces types, seule la périodicité « avec locaux à sommeil » est applicable. |
| **RG-PER-007** | La périodicité d'un **site** est la périodicité la plus courte parmi ses établissements ERP enfants ouverts ayant une périodicité non nulle et non vide. |
| **RG-PER-008** | La périodicité d'une **cellule** est héritée de son établissement parent. |
| **RG-PER-009** | L'utilisateur peut **modifier manuellement** la périodicité d'un établissement individuel. Si cette valeur diffère de la matrice de référence, un indicateur visuel le signale. |
| **RG-PER-010** | L'action « Appliquer sur TOUS les établissements » met à jour la périodicité de chaque établissement en se basant sur sa **fiche d'informations la plus récente**. |
| **RG-PER-011** | L'application en masse est **transactionnelle** : en cas d'erreur, aucune donnée n'est modifiée. |
| **RG-PER-012** | La date de **prochaine visite périodique** est calculée en ajoutant la périodicité (en mois) à la date de la dernière visite périodique ayant donné lieu à un avis de commission. |
| **RG-PER-013** | Si la périodicité d'un établissement est à **zéro** ou non renseignée, aucune date de prochaine visite n'est calculée. |
| **RG-PER-014** | Seuls les genres **Établissement** (ERP) et **IGH** portent une périodicité propre. Les sites et cellules utilisent des règles de dérivation (cf. RG-PER-007 et RG-PER-008). |
| **RG-PER-015** | L'affichage de la périodicité peut être configuré en **années et mois** au lieu de mois uniquement. La conversion s'applique à partir de 12 mois. |
| **RG-PER-016** | La date de référence pour le calcul de la relance peut être configurée pour utiliser la **plus récente** entre la date de visite et la date de commission du dernier dossier périodique. |
| **RG-PER-017** | Un ERP ouvert est considéré en **retard de visite périodique** lorsque la date de prochaine visite (dernière visite + périodicité) est antérieure au mois en cours. |
| **RG-PER-018** | L'application en masse vide le **cache de recherche** après exécution réussie. |
| **RG-PER-019** | Si un type d'activité obligatoirement avec locaux à sommeil est sélectionné lors de la création d'un ERP, la valeur « locaux à sommeil » est automatiquement positionnée à **oui**. |
| **RG-PER-020** | Lors de la sauvegarde d'un établissement, un **écran de confirmation** affiche la périodicité saisie et la périodicité conseillée (issue de la matrice). L'utilisateur peut corriger sa saisie en cliquant sur la valeur conseillée. |

---

## 7. Cas particuliers

### 7.1 Types d'activité obligatoirement avec locaux à sommeil

Par défaut, les types O (hôtels) et R (établissements d'enseignement avec internat) sont considérés comme ayant obligatoirement des locaux à sommeil. Dans la matrice, la colonne « sans locaux à sommeil » est masquée pour ces types. Cette liste est configurable par l'administrateur système.

### 7.2 Établissement sans fiche d'informations récente

L'application en masse ne cible que les fiches d'informations les plus récentes de chaque établissement. Si un établissement n'a aucune fiche d'informations, il n'est pas concerné par l'application en masse.

### 7.3 Établissement sans correspondance dans la matrice

Si la combinaison (catégorie, type, locaux à sommeil) d'un établissement n'a pas d'entrée dans la matrice de référence, sa périodicité ne sera pas mise à jour lors de l'application en masse.

### 7.4 Genres non concernés

Les genres « Habitation » et « Cellule » ne portent pas de périodicité propre dans la matrice. Seuls les genres « Établissement » (ERP) et « IGH » sont directement liés à la matrice.

### 7.5 Modification manuelle vs. matrice

La périodicité d'un établissement peut être modifiée manuellement par un préventionniste sur la fiche de l'établissement. Cette valeur peut diverger de la matrice. L'indicateur visuel « périodicité non standard » alerte l'utilisateur de cette divergence. L'action « Appliquer sur TOUS les établissements » écrasera cette valeur manuelle pour la replacer à la valeur de la matrice.

### 7.6 Dossiers pris en compte pour la dernière visite

Le calcul de la date de dernière visite périodique prend en compte les dossiers répondant à **toutes** ces conditions :

- Type de dossier : « visite de commission » ou « groupe de visite ».
- Avis de commission rendu (avis supérieur à zéro).
- Nature du dossier : périodique, et autres natures réglementaires donnant avis.

Seul le dossier le plus récent répondant à ces critères est utilisé.

### 7.7 Affichage en années

Si le paramètre d'affichage en années est activé :

- 12 mois → « 1 an »
- 24 mois → « 2 ans »
- 15 mois → « 1 an et 3 mois »
- Moins de 12 mois → l'affichage reste en mois.
