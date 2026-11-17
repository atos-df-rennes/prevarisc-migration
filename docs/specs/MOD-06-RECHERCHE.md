# MOD-06 — Recherche et export

## 1. Objet et périmètre

Le module Recherche permet aux utilisateurs de l'application Prevarisc de retrouver des **établissements** et des **dossiers** selon de nombreux critères combinables. Chaque recherche produit une liste paginée de résultats. L'utilisateur peut exporter ces résultats dans un fichier tableur (format ODS).

Le module comporte quatre écrans de recherche :

| Écran | Description | Statut |
|-------|-------------|--------|
| Recherche d'établissements | Écran principal, affiché par défaut | Actif |
| Recherche de dossiers | Recherche multicritère sur les dossiers | Actif |
| Recherche de courriers | Recherche textuelle sur les dossiers de type « courrier » | Présent mais probablement inutilisé |
| Recherche d'utilisateurs | Recherche par nom et fonction | Présent mais probablement inutilisé |

> Les deux derniers écrans (courriers et utilisateurs) sont présents dans le code mais signalés comme potentiellement non utilisés. Cette spécification les mentionne par souci d'exhaustivité.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|---------------------|
| Préventionniste | Utilise la recherche pour retrouver ses établissements et ses dossiers. Est lui-même un critère de filtrage. |
| Chef de groupement | Recherche les établissements et dossiers de son groupement territorial (filtre présélectionné). |
| Administrateur | Accède à l'ensemble des résultats sans restriction de périmètre. |
| Tout utilisateur authentifié | Accède aux formulaires de recherche. Le droit d'export tableur est soumis à un contrôle d'accès spécifique par groupe. |

---

## 3. Recherche d'établissements

### 3.1 Critères de recherche disponibles

#### 3.1.1 Champ de recherche principal

Un champ texte libre permet de rechercher un établissement :

| Saisie | Comportement |
|--------|-------------|
| Texte libre | Recherche approchante sur le **libellé** de l'établissement |
| Identifiant précédé de `#` (ex. `#12345`) | Recherche exacte sur l'**identifiant numérique** de l'établissement |
| Chaîne de type identifiant (ex. `E123/45`, `123-456.AB`) | Détection automatique : recherche approchante sur le **code identifiant** de l'établissement |

#### 3.1.2 Filtres combinables

Tous les filtres suivants sont combinables entre eux (conditions cumulatives en « ET »). Les filtres à sélection multiple appliquent un « OU » entre les valeurs sélectionnées au sein du même critère.

| # | Critère | Type de saisie | Valeurs possibles | Comportement par défaut |
|---|---------|---------------|-------------------|------------------------|
| 1 | **Statut** | Sélection multiple | Liste des statuts du référentiel (ex. ouvert, fermé…) | Tous les statuts |
| 2 | **Présence de local à sommeil** | Sélection multiple | « Avec » / « Sans » | Qu'importe (aucun filtre) |
| 3 | **Avis d'exploitation** | Sélection multiple | « Favorable » / « Défavorable » | Tous les avis |
| 4 | **Commission compétente** | Sélection multiple | Liste des commissions de sécurité du référentiel | Toutes les commissions |
| 5 | **Genre** | Sélection multiple | Liste des genres du référentiel (site, établissement, cellule, IGH, habitation…) | Tous les genres |
| 6 | **Catégorie** | Sélection multiple | Liste des catégories (1ʳᵉ à 5ᵉ) | Toutes les catégories |
| 7 | **Classe** | Sélection multiple | Liste des classes (pour les ERT) | Toutes les classes |
| 8 | **Famille** | Sélection multiple | Liste des familles (pour les habitations) | Toutes les familles |
| 9 | **Type d'activité** | Sélection multiple | Liste des types d'activité, groupée par type (J, L, M, N, O…) | Tous les types |
| 10 | **Groupement territorial** | Sélection multiple | Liste des groupements territoriaux | Présélectionné selon le groupement de rattachement de l'utilisateur connecté |
| 11 | **Préventionniste** | Sélection simple | Liste des préventionnistes actifs (ayant au moins un dossier) | Aucun (pas de filtre) |
| 12 | **Zone de recherche** | Modale hiérarchique | Commune → Voie → Numéro | Département entier |

#### 3.1.3 Affichage conditionnel des critères selon le genre

Certains critères ne sont pertinents que pour certains genres d'établissement. L'interface adapte l'affichage :

| Genre sélectionné | Critères supplémentaires affichés |
|-------------------|----------------------------------|
| Établissement (ERP) | Type d'activité, Catégorie |
| IGH | Type d'activité, Catégorie |
| Habitation | Famille |
| ERT | Classe |
| Site | Aucun critère supplémentaire |
| Aucun genre sélectionné | Tous les critères masqués par défaut |

#### 3.1.4 Zone de recherche géographique

La zone de recherche est définie via une fenêtre modale dédiée avec trois niveaux de précision :

1. **Commune** — Champ texte avec autocomplétion (recherche par nom de commune ou code postal, minimum 2 caractères)
2. **Voie** — Champ texte avec autocomplétion (activé après sélection d'une commune)
3. **Numéro** — Champ texte libre (activé après sélection d'une voie)

Le libellé de la zone sélectionnée est affiché en résumé (ex. « Arras, Rue Gambetta, 12 »). Un bouton de réinitialisation permet de revenir au périmètre « Département ».

La recherche géographique prend en compte l'adresse directe de l'établissement ainsi que, selon le genre :
- L'adresse du **site** parent (pour les établissements enfants d'un site)
- L'adresse de la **cellule** rattachée (pour les établissements ayant des cellules)

### 3.2 Résultats et pagination

#### 3.2.1 Contenu de chaque ligne de résultat

Chaque résultat affiché comporte :

| Information | Détail |
|-------------|--------|
| Type, genre et catégorie | Icône du type (si disponible), libellé du type en texte, 4 premières lettres du genre en majuscules et numéro de catégorie. Le numéro de catégorie est affiché uniquement s'il est renseigné ; son libellé complet est accessible au survol. Les éléments absents et leurs séparateurs sont masqués. |
| Libellé | Nom de l'établissement (lien vers la fiche). Si vide : « Libellé non renseigné » |
| Commune | Affichée selon le genre : commune du site (pour un site), commune de la cellule (pour une cellule), commune de l'adresse directe (sinon) |
| Avis d'exploitation | Favorable / Défavorable / Avis différé / Avis d'exploitation indisponible, avec date de l'avis (date de visite ou date de passage en commission) |
| Échéancier de travaux | Badge affiché si un échéancier de travaux est lié à l'établissement |

#### 3.2.2 Navigation hiérarchique

Les établissements possédant des **sous-établissements** (enfants) sont identifiés visuellement. Un clic sur la ligne charge dynamiquement la liste des enfants, affichée sous forme indentée. Ce chargement est récursif.

#### 3.2.3 Pagination

- **50 résultats par page**
- Navigation par numéros de pages (style « Elastic » : les numéros de page s'adaptent à la page courante)
- Compteur total d'éléments affiché en en-tête de liste

#### 3.2.4 Tri

Les résultats sont triés par **libellé de l'établissement** (ordre alphabétique ascendant), uniquement lorsqu'au moins un critère de filtrage est actif. Sans filtre actif, aucun tri n'est garanti (optimisation de performance).

#### 3.2.5 Établissements supprimés

Les établissements marqués comme **supprimés** sont systématiquement exclus des résultats. De plus, les établissements ayant certains statuts (ex. fermé) sont affichés en semi-transparence.

### 3.3 Export des résultats

#### 3.3.1 Conditions d'accès

- L'utilisateur doit disposer du **droit d'export des établissements** (contrôlé par son groupe)
- Le bouton « Export Calc » n'est visible que si l'utilisateur a ce droit
- L'export est **limité à 10 000 résultats**. Au-delà, un message d'avertissement est affiché et l'export est désactivé

#### 3.3.2 Comportement

- L'export reprend les **mêmes critères de filtrage** que la recherche en cours
- Le bouton d'export est masqué dès que l'utilisateur modifie un critère, jusqu'à ce qu'une nouvelle recherche soit lancée
- Le fichier est téléchargé immédiatement au format tableur (ODS)
- Le nom du fichier suit le format : `Export_Etablissements_AAAA-MM-JJ_HH-MM-SS.ods`

#### 3.3.3 Colonnes du fichier d'export

L'export contient les 24 colonnes suivantes :

| # | Colonne | Description |
|---|---------|-------------|
| 1 | Commune | Commune de l'adresse de l'établissement |
| 2 | Catégorie | Catégorie ERP (1ʳᵉ, 2ᵉ…) |
| 3 | Type | Type réglementaire (J, L, M…) |
| 4 | Activité | Libellé de l'activité |
| 5 | Commission compétente | Nom de la commission |
| 6 | Code/identifiant établissement | Identifiant unique de l'établissement |
| 7 | Libellé établissement | Nom de l'établissement |
| 8 | Statut | Statut courant (ouvert, fermé…) |
| 9 | Avis | Dernier avis d'exploitation (favorable / défavorable) |
| 10 | Date du dernier avis | Date associée au dernier avis (visite, commission ou création) |
| 11 | Date du premier avis favorable | Date de la première visite ayant donné un avis favorable |
| 12 | Date du premier avis défavorable consécutif | Date du premier avis défavorable non suivi d'un avis favorable |
| 13 | Effectif total | Effectif public + effectif personnel |
| 14 | Effectif public | Effectif public déclaré |
| 15 | Effectif personnel | Effectif personnel déclaré |
| 16 | Date de dernière visite | Date de la dernière visite effectuée (passée) |
| 17 | Date de prochaine visite périodique | Calculée à partir de la date de dernière visite et de la périodicité |
| 18 | Date de visite prévue | Date de la prochaine visite planifiée (future) |
| 19 | Adresse | Adresse complète (numéro + voie + complément + code postal) |
| 20 | Groupement territorial compétent | Nom du groupement territorial |
| 21 | Libellé du père/site | Nom de l'établissement parent ou du site |
| 22 | Genre | Genre de l'établissement |
| 23 | Préventionniste | Nom et prénom du préventionniste affecté |
| 24 | Présence de locaux à sommeil | « oui » ou « non » |

> **Dates calculées dans l'export** : la date de prochaine visite périodique est calculée en ajoutant la périodicité (en mois) à la date de dernière visite. Elle n'est renseignée que si la périodicité est définie et non nulle.

---

## 4. Recherche de dossiers

### 4.1 Critères de recherche disponibles

#### 4.1.1 Champ de recherche principal

Un champ texte libre permet de rechercher un dossier :

| Saisie | Comportement |
|--------|-------------|
| Texte libre | Recherche approchante sur l'**objet** du dossier |
| Identifiant précédé de `#` (ex. `#PC123`) | Recherche sur le **numéro de document d'urbanisme** |
| Chaîne de type identifiant (ex. `PC062.123`) | Détection automatique : recherche sur le **numéro de document d'urbanisme** |
| Identifiant Plat'AU (format `XXX-XXX-XXX`) | Recherche exacte sur l'**identifiant de consultation Plat'AU** |

#### 4.1.2 Filtres combinables

| # | Critère | Type de saisie | Valeurs possibles | Comportement par défaut |
|---|---------|---------------|-------------------|------------------------|
| 1 | **Type de dossier** | Sélection multiple | Liste des types (visite, étude, courrier…) | Tous les types |
| 2 | **Commission** | Sélection multiple | Liste des commissions, groupée par type de commission | Toutes les commissions |
| 3 | **Groupement territorial** | Sélection multiple | Liste des groupements territoriaux | Présélectionné selon le groupement de l'utilisateur |
| 4 | **Avis de la commission** | Sélection multiple | « Favorable » / « Défavorable » | Tous les avis |
| 5 | **Avis du rapporteur** | Sélection multiple | « Favorable » / « Défavorable » | Tous les avis |
| 6 | **Provenance** | Sélection simple | « Provenant de Plat'AU » / « Ne provenant pas de Plat'AU » | Toutes les provenances |
| 7 | **Commune** | Sélection simple | Liste de toutes les communes du référentiel | Aucune |
| 8 | **Voie** | Sélection simple | Voies de la commune sélectionnée (chargement dynamique) | Aucune |
| 9 | **Numéro** | Sélection simple | Numéros de la voie sélectionnée (chargement dynamique) | Aucun |
| 10 | **Préventionniste** | Sélection simple | Liste des préventionnistes actifs | Aucun |
| 11 | **Numéro de permis** | Champ texte libre | Numéro de document d'urbanisme | Vide |
| 12 | **Référence courrier** | Champ texte libre | Référence courrier du dossier | Vide |
| 13 | **Date de création** | Plage de dates (du/au) | Format JJ/MM/AAAA | Aucune |
| 14 | **Date de réception** | Plage de dates (du/au) | Format JJ/MM/AAAA | Aucune |
| 15 | **Date de réponse** | Plage de dates (du/au) | Format JJ/MM/AAAA | Aucune |
| 16 | **Date de passage en commission** | Plage de dates (du/au) | Format JJ/MM/AAAA | Aucune |
| 17 | **Date de visite** | Plage de dates (du/au) | Format JJ/MM/AAAA | Aucune |

> **Critère conditionnel :** le filtre « Provenance » (Plat'AU) n'est affiché que si l'intégration Plat'AU est activée dans la configuration de l'application.

Le critère « Référence courrier » effectue une recherche partielle : la chaîne saisie peut apparaître n'importe où dans la référence enregistrée.

#### 4.1.3 Adresse en cascade

La sélection de l'adresse fonctionne en cascade :

1. L'utilisateur sélectionne une **commune** dans la liste déroulante
2. Les **voies** de cette commune sont chargées dynamiquement
3. Après sélection d'une voie, les **numéros** de cette voie sont chargés dynamiquement

#### 4.1.4 Sélection de dates

Les champs de date utilisent un sélecteur de dates avec :
- Format attendu : `JJ/MM/AAAA`
- Noms des mois et jours en français
- La date de début borne automatiquement la date de fin (et inversement)

### 4.2 Résultats et pagination

#### 4.2.1 Contenu du tableau de résultats

Les résultats sont affichés sous forme de tableau à 5 colonnes :

| Colonne | Contenu |
|---------|---------|
| **Établissement** | Libellé de l'établissement lié (lien vers sa fiche), pictogramme du type (si disponible), libellé du type en texte, genre abrégé (4 premières lettres en majuscules) et numéro de catégorie. Le libellé complet de la catégorie est accessible au survol. Les éléments absents et leurs séparateurs sont masqués. |
| **Objet** | Type + nature + objet du dossier + numéro d'urbanisme. Comprend également : date significative (commission, visite ou création), avis (favorable / défavorable / différé / incomplet / répondu / réponse à faire), alertes (réception de travaux, échéancier de travaux), badges Plat'AU, temps restant avant échéance de réponse |
| **Date** | Trois dates empilées : date de création, date de réception, date de réponse |
| **Préventionnistes** | Noms des préventionnistes affectés au dossier |
| **Pièces jointes** | Icône cliquable si des pièces jointes existent. Un clic déploie la liste des fichiers joints (nom + extension) |

#### 4.2.2 Informations complémentaires sur chaque dossier

| Information | Conditions d'affichage |
|-------------|----------------------|
| Badge « Dossier incomplet » | Si le dossier est marqué incomplet |
| Badge « Avis différé » | Si l'avis est différé |
| Badge « Favorable / Défavorable » | Selon l'avis de la commission, avec mention « à l'exploitation » pour certaines natures de visite |
| Badge « Répondu » | Pour les dossiers de type courrier déjà répondus |
| Badge « Réponse à faire » | Pour les dossiers de type courrier sans réponse, avec délai écoulé |
| Badge « Consultation Plat'AU » | Si le dossier provient de Plat'AU, avec son identifiant |
| Badge « En attente de PEC » | Si le dossier Plat'AU n'a pas encore été pris en charge (non marqué incomplet) |
| Badge « Présence d'un dossier de réception de travaux » | Si un dossier lié de nature réception de travaux existe |
| Badge « Fait l'objet d'un échéancier de travaux » | Si un échéancier de travaux est lié |
| Temps restant | Durée restante avant l'échéance de réponse attendue (couleur variable selon l'urgence) |
| Badge « Nouveau dossier ! » | Si le dossier est récent |
| Badge « Nouvelles pièces ! » | Si de nouvelles pièces jointes ont été ajoutées |
| Icône cadenas | Si le dossier est verrouillé |

#### 4.2.3 Navigation hiérarchique

La navigation hiérarchique au clic (dépliage des dossiers liés) est définie dans la spec originale mais n'est pas implémentée : la ligne `slide` est mal positionnée dans le legacy, rendant le dépliage inopérant. Ce comportement n'est pas porté en Symfony.

#### 4.2.4 Pagination

- **50 résultats par page**
- Navigation paginée identique à la recherche d'établissements
- Compteur total affiché

#### 4.2.5 Tri

Les résultats sont triés par **date de création du dossier** (du plus récent au plus ancien), uniquement lorsqu'au moins un critère de filtrage est actif au-delà de l'exclusion des dossiers supprimés.

#### 4.2.6 Dossiers supprimés

Les dossiers marqués comme **supprimés** sont systématiquement exclus des résultats.

### 4.3 Export des résultats

#### 4.3.1 Conditions d'accès

- L'utilisateur doit disposer du **droit d'export des dossiers** (contrôlé par son groupe, distinct du droit d'export des établissements)
- Le bouton « Export Calc » est masqué si l'utilisateur n'a pas ce droit
- L'export est **limité à 10 000 résultats**

#### 4.3.2 Comportement

- Mêmes critères que la recherche en cours
- Le bouton d'export est masqué dès modification d'un critère
- Fichier téléchargé au format ODS
- Nom du fichier : `Export_Dossiers_AAAA-MM-JJ_HH-MM-SS.ods`

#### 4.3.3 Colonnes du fichier d'export

L'export contient 23 colonnes :

| # | Colonne | Description |
|---|---------|-------------|
| 1 | Groupement | Groupement territorial |
| 2 | Commune | Commune de l'établissement lié |
| 3 | Catégorie | Catégorie de l'établissement lié |
| 4 | Type | Type réglementaire de l'établissement lié |
| 5 | Activité | Activité de l'établissement lié |
| 6 | Code/identifiant établissement | Identifiant de l'établissement lié |
| 7 | Libellé établissement | Nom de l'établissement lié |
| 8 | Statut | Statut de l'établissement lié |
| 9 | Genre | Genre de l'établissement lié |
| 10 | Type du dossier | Type de dossier (visite, étude, courrier…) |
| 11 | Nature du dossier | Nature précise du dossier |
| 12 | Date de création du dossier | Date de création |
| 13 | Référence courrier | Référence courrier du dossier |
| 14 | Objet du dossier | Texte libre de l'objet |
| 15 | Numéro document urbanisme | Numéro(s) de permis liés |
| 16 | Date de visite | Date de visite du dossier |
| 17 | Date de la commission en salle | Date du passage en commission |
| 18 | Commission du dossier | Nom de la commission affectée |
| 19 | Avis rapporteur | Avis du rapporteur (favorable / défavorable) |
| 20 | Avis commission | Avis de la commission (favorable / défavorable) |
| 21 | Préventionniste en charge du dossier | Nom et prénom |
| 22 | Pièces jointes ? | « Oui » ou « Non » |
| 23 | Identifiant Plat'AU | Affiché uniquement si le filtre de provenance Plat'AU est actif |

---

## 5. Autocomplétion (recherche en temps réel)

### 5.1 Autocomplétion sur la zone de recherche des établissements

La définition de la zone de recherche géographique repose sur trois niveaux d'autocomplétion :

| Niveau | Champ | Seuil de déclenchement | Source |
|--------|-------|----------------------|--------|
| 1 | Commune | 2 caractères minimum | Référentiel des communes (nom ou code postal) |
| 2 | Voie | 2 caractères minimum | Voies de la commune sélectionnée (code INSEE) |
| 3 | Numéro | Saisie libre | — |

- La sélection d'une commune **active** le champ voie et le champ numéro
- Le clic dans le champ commune **réinitialise** les champs voie et numéro
- Le clic dans le champ voie **réinitialise** le champ numéro

### 5.2 Adressage en cascade pour la recherche de dossiers

Pour la recherche de dossiers, l'adressage fonctionne par **listes déroulantes en cascade** :

| Étape | Action utilisateur | Effet |
|-------|-------------------|-------|
| 1 | Sélection d'une commune | Chargement dynamique des voies de cette commune |
| 2 | Sélection d'une voie | Chargement dynamique des numéros de cette voie |
| 3 | Sélection d'un numéro | Filtre finalisé |

### 5.3 Chargement hiérarchique des sous-éléments

Lors de l'affichage des résultats, un chargement asynchrone permet d'afficher les **sous-établissements** d'un établissement (sites contenant des établissements, établissements contenant des cellules). Ce chargement se fait au clic sur un résultat comportant des enfants.

Le chargement des **dossiers liés** à un dossier parent n'est pas implémenté (ni en legacy, ni en Symfony) : voir §4.2.3.

---

## 6. Règles de gestion

### Règles communes

| Règle | Énoncé |
|-------|--------|
| **RG-RCH-001** | Les éléments supprimés (établissements ou dossiers) ne sont jamais inclus dans les résultats de recherche. |
| **RG-RCH-002** | Les résultats de recherche sont paginés par blocs de **50 éléments** par page. |
| **RG-RCH-003** | Le compteur total d'éléments est affiché au-dessus de la liste de résultats. |
| **RG-RCH-004** | Les résultats de recherche sont **mis en cache**. Deux recherches identiques (mêmes critères, même page) retournent les résultats du cache sans relancer la requête. |
| **RG-RCH-005** | Le nombre maximum de résultats par page est plafonné à **1 000 pour les établissements** et **100 pour les dossiers**. |
| **RG-RCH-006** | L'export tableur est **limité à 10 000 résultats**. Au-delà de ce seuil, le bouton d'export est remplacé par un message d'avertissement. |
| **RG-RCH-007** | Le droit d'accès à l'export tableur est contrôlé séparément pour les établissements et pour les dossiers, en fonction du groupe de l'utilisateur. |
| **RG-RCH-008** | Le bouton d'export est **masqué** dès que l'utilisateur modifie un critère de recherche. Il ne réapparaît qu'après le lancement d'une nouvelle recherche. |

### Règles spécifiques à la recherche d'établissements

| Règle | Énoncé |
|-------|--------|
| **RG-RCH-010** | Lors du premier affichage de la page, si l'utilisateur est rattaché à un **groupement territorial**, celui-ci est présélectionné dans le filtre correspondant. |
| **RG-RCH-011** | La saisie d'un texte commençant par `#` dans le champ principal est interprétée comme une recherche par **identifiant numérique** exact de l'établissement. |
| **RG-RCH-012** | La saisie d'une chaîne ne contenant que des caractères de type identifiant (chiffres, lettres majuscules, `/`, `-`, `.`) est automatiquement interprétée comme une recherche sur le **code identifiant** de l'établissement. |
| **RG-RCH-013** | Les critères **catégorie** et **type d'activité** ne sont affichés que lorsque le genre « Établissement (ERP) » ou « IGH » est sélectionné. |
| **RG-RCH-014** | Le critère **famille** n'est affiché que lorsque le genre « Habitation » est sélectionné. |
| **RG-RCH-015** | Le critère **classe** n'est affiché que lorsque le genre « ERT » est sélectionné. |
| **RG-RCH-016** | La recherche géographique par commune, voie ou numéro s'applique à l'adresse de l'établissement lui-même, mais aussi à l'adresse de son **site parent** (si genre site) et de sa **cellule rattachée** (si genre cellule). |
| **RG-RCH-017** | Les informations d'un établissement affichées (libellé, genre, type, etc.) correspondent à la **version la plus récente** de ses informations historisées. |
| **RG-RCH-018** | Le tri alphabétique par libellé n'est appliqué que si au moins un critère de filtrage est actif (optimisation de performance). |
| **RG-RCH-019** | L'avis d'exploitation affiché est celui du **dossier donnant avis** référencé sur l'établissement. |
| **RG-RCH-020** | Dans l'export, la **date de prochaine visite périodique** est calculée en ajoutant la périodicité (en mois) à la date de dernière visite. Elle n'est renseignée que si la périodicité est définie et non nulle. |
| **RG-RCH-021** | Dans l'export, la **date du premier avis défavorable consécutif** correspond à la date de visite du premier avis défavorable non suivi d'un avis favorable ultérieur. |
| **RG-RCH-022** | Les établissements dont le statut est « fermé » ou « en projet » sont affichés en **semi-transparence** dans la liste de résultats. |

### Règles spécifiques à la recherche de dossiers

| Règle | Énoncé |
|-------|--------|
| **RG-RCH-030** | Lors du premier affichage, le **groupement territorial** de l'utilisateur est présélectionné. |
| **RG-RCH-031** | La saisie de `#` suivi d'un texte dans le champ objet est interprétée comme une recherche sur le **numéro de document d'urbanisme**. |
| **RG-RCH-032** | La saisie d'une chaîne au format `XXX-XXX-XXX` (3 groupes de 3 caractères alphanumériques) est interprétée comme une recherche par **identifiant de consultation Plat'AU**. |
| **RG-RCH-033** | La saisie d'une chaîne composée uniquement de chiffres, lettres majuscules et points est interprétée comme une recherche sur le **numéro de document d'urbanisme**. |
| **RG-RCH-034** | Les informations de l'établissement lié à un dossier sont celles **à la date la plus pertinente** parmi : date d'insertion du dossier, date de visite, date de commission. En cas d'absence, ce sont les informations les plus anciennes. ⚠️ *Écart documenté* : ni le legacy ni Symfony n'implémentent cette règle — les deux utilisent la fiche courante (`etablissementinformationsactuel`). Le legacy fait autorité. |
| **RG-RCH-035** | Le filtre **provenance Plat'AU** n'est disponible que si la fonctionnalité Plat'AU est activée dans la configuration de l'application. |
| **RG-RCH-036** | Les plages de dates sont validées au format `JJ/MM/AAAA`. Une date invalide est ignorée. |
| **RG-RCH-037** | Le tri par date de création décroissante n'est appliqué que si au moins un critère de filtrage personnalisé est actif. |
| **RG-RCH-038** | La colonne « Identifiant Plat'AU » dans l'export n'est remplie que si le filtre de provenance Plat'AU est actif. |
| **RG-RCH-039** | L'affichage du **temps restant** avant l'échéance de réponse utilise un code couleur variable selon l'urgence. |
| **RG-RCH-040** | L'objet du dossier affiché dans la liste est **tronqué à 70 caractères** suivi de « (...) » s'il dépasse cette longueur. |
| **RG-RCH-041** | Les filtres par date de passage en commission et par date de visite s'appliquent comme des plages inclusives aux dates correspondantes du dossier. Lorsqu'une borne est renseignée, les dossiers sans date correspondante sont exclus. Les mêmes critères s'appliquent à la liste et à l'export. |

---

## 7. Cas particuliers et limites

### 7.1 Performance

- Le tri des résultats est **désactivé** sur les requêtes sans critère de filtrage pour préserver les performances sur de gros volumes de données.
- Les résultats sont mis en cache pour éviter des requêtes identiques répétées.
- L'export tableur ne comporte pas de limite de pagination : tous les résultats correspondant aux critères sont exportés (dans la limite de 10 000).

### 7.2 Recherche de courriers (non actif)

Un écran de recherche de courriers existe avec un champ de recherche unique portant sur l'objet du courrier et le nom de l'expéditeur. Les résultats sont limités aux dossiers de type « Courrier ». Cet écran ne dispose pas d'action de contrôleur identifiée et semble **non utilisé en production**.

### 7.3 Recherche d'utilisateurs (non actif)

Un écran de recherche d'utilisateurs existe avec :
- Un champ texte de recherche par nom (recherche approchante sur nom ou prénom)
- Un filtre par fonction (sélection multiple)
- Seuls les utilisateurs **actifs** sont retournés

Cet écran est signalé comme **non utilisé** et ne devrait pas être migré sans vérification préalable.

### 7.4 Valeurs vides et nulles

- Si le libellé d'un établissement est vide, le texte « Libellé non renseigné » est affiché en italique.
- Si aucune date n'est disponible pour un dossier, le texte « Pas de date » est affiché.
- Les dates invalides (`0000-00-00`) sont affichées comme `00/00/0000`.

### 7.5 Avis d'exploitation spécifiques

- Pour les établissements de genre **cellule**, la mention « à l'exploitation » n'est pas ajoutée après l'avis.
- Pour les établissements de genre **site**, aucun avis n'est affiché (les sites n'ont pas d'avis d'exploitation).

### 7.6 Export — mode dégradé

Le dimensionnement automatique des colonnes du fichier tableur est désactivé pour assurer la compatibilité avec certains environnements serveur. Les colonnes du fichier exporté ne sont pas auto-ajustées.

### 7.7 Dates affichées dans les résultats de dossiers

La date affichée sous chaque dossier suit la priorité suivante :

1. **Date de passage en commission** (si disponible)
2. **Date de visite** (si la date de commission n'est pas disponible)
3. **Date de création du dossier** (en dernier recours)

### 7.8 Filtres par date de passage en commission et par date de visite

Les plages de dates filtrent respectivement la date de passage en commission et la date de visite enregistrées sur le dossier. Chaque borne renseignée est inclusive ; les dossiers sans date correspondante ne sont pas retenus lorsque le filtre est actif. Les critères sont combinables avec les autres filtres et produisent les mêmes résultats dans la liste et l'export.
