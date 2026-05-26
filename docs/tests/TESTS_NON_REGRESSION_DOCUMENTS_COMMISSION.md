# Tests de non-régression — Documents de commission

> **Objectif :** Vérifier que la migration Zend → Symfony n'a pas altéré la génération des 4 types de documents de commission.
> **Méthode :** Générer chaque document depuis l'interface, ouvrir l'ODT généré et cocher chaque champ.
> **Légende :** [ ] = à tester | ✅ = OK | ❌ = KO (noter la valeur obtenue vs attendue)

---

## Prérequis

Avant de commencer, s'assurer que la commission de test dispose de :

- [x] Au moins 1 membre **par commune** avec le modèle ODT configuré
- [x] Au moins 1 membre **par groupement** (ex : gendarmerie) avec le modèle ODT configuré
- [x] Au moins 2 dossiers affectés à la commission, dans des communes différentes
- [x] Chaque dossier a un établissement avec : adresse complète, catégorie, type, effectifs public ET personnel
- [x] Au moins 1 préventionniste affecté à chaque dossier (avec grade, tél, mail)
- [ ] Au moins 1 avis/dérogation sur le **dossier**
- [ ] Au moins 1 avis/dérogation sur l'**établissement**
- [ ] Au moins 1 dossier avec un permis de construire (numéro doc urbanisme)
- [x] Un établissement avec un **établissement parent** renseigné
- [x] La commission configurée avec `GESTION_HEURES = 1` (heures par dossier)
- [x] Le document CR configuré pour la commission (`DOCUMENT_CR`)

---

## 📄 1. CONVOCATION

> Déclencher la génération de la convocation depuis le calendrier des commissions.

### 1.1 — Membre par commune, commission de type "Salle"

**Pré-condition :** Le membre a `COURRIER_CONVOCATIONSALLE` configuré.

#### Champs globaux (une section par commune)

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 1 | `numMairie` | Numéro de rue de la mairie de la commune | [ ] |
| 2 | `rueMairie` | Rue de la mairie | [ ] |
| 3 | `cpMairie` | Code postal de la mairie | [ ] |
| 4 | `communeMairie` | Nom de la commune | [ ] |
| 5 | `commissionNom` | Nom de la commission (ex : CCDSA Rennes) | [ ] |
| 6 | `date` | Date du jour au format JJ/MM/AAAA | [ ] |
| 7 | `dateCommission` | Date de la commission au format JJ/MM/AAAA | [ ] |
| 8 | `heureDebutComm` | Heure de début HH:MM | [ ] |
| 9 | `heureDeb1erDossier` | Heure du 1er dossier de la commune HH:MM | [ ] |

#### Champs par dossier (segment répété pour chaque dossier de la commune)

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 10 | `nomEtab` | Nom de l'établissement | [ ] |
| 11 | `nomPereEtab` | Nom de l'établissement parent (vide si aucun) | [ ] |
| 12 | `idDossier` | Identifiant numérique du dossier | [ ] |
| 13 | `numWinPrev` | Numéro WinPrev de l'établissement | [ ] |
| 14 | `mailEtab` | Email de l'établissement | [ ] |
| 15 | `communeEtab` | Nom de la commune de l'établissement | [ ] |
| 16 | `etablissementAdresse` | Adresse complète (n° rue CP commune) | [ ] |
| 17 | `categorieEtab` | Catégorie (ex : 1ère catégorie) | [ ] |
| 18 | `typeLettreP` | Type principal (ex : M) | [ ] |
| 19 | `natureDossier` | Nature du dossier (ex : Construction neuve) | [ ] |
| 20 | `objetDossier` | Objet du dossier | [ ] |
| 21 | `avisDossier` | "Favorable", "Défavorable" ou vide | [ ] |
| 22 | `avisDossierCommission` | "Favorable", "Défavorable" ou vide | [ ] |
| 23 | `heureDebDossier` | Heure de début du dossier HH:MM | [ ] |
| 24 | `heureFinDossier` | Heure de fin du dossier HH:MM | [ ] |
| 25 | `numDocUrba` | Numéro(s) de permis (vide si aucun) | [ ] |
| 26 | `preventionnistes` | "Grade Nom Prénom, ..." | [ ] |
| 27 | `listeTelPreventioniste` | Téléphone(s) séparés par virgule | [ ] |
| 28 | `listeMailPreventioniste` | Email(s) séparés par virgule | [ ] |

#### Avis/dérogations dossier (segment répété par avis)

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 29 | `type` | Type d'avis (ex : Dérogation) | [ ] |
| 30 | `titre` | Titre | [ ] |
| 31 | `objet` | Objet | [ ] |
| 32 | `justification` | Justification | [ ] |
| 33 | `articles_concernes` | Articles concernés | [ ] |
| 34 | `mesures_compensatoires_proposees` | Mesures compensatoires | [ ] |
| 35 | `analyse_de_risque` | Analyse de risque | [ ] |
| 36 | `avis` | Libellé de l'avis | [ ] |
| 37 | `identifiantdossierleve` | ID du dossier levé (vide si aucun) | [ ] |
| 38 | `date_avis_derogation` | Date de la commission ou de visite de l'avis | [ ] |

#### Avis/dérogations établissement (mêmes 10 champs, source = établissement)

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 39 | `type` | Type d'avis établissement | [ ] |
| 40 | `titre` | Titre | [ ] |
| 41 | `objet` | Objet | [ ] |
| 42 | `justification` | Justification | [ ] |
| 43 | `articles_concernes` | Articles concernés | [ ] |
| 44 | `mesures_compensatoires_proposees` | Mesures compensatoires | [ ] |
| 45 | `analyse_de_risque` | Analyse de risque | [ ] |
| 46 | `avis` | Libellé de l'avis | [ ] |
| 47 | `identifiantdossierleve` | ID dossier levé | [ ] |
| 48 | `date_avis_derogation` | Date de l'avis établissement | [ ] |

---

### 1.2 — Membre par groupement

**Pré-condition :** Le membre a `ID_GROUPEMENT` renseigné (ex : gendarmerie).

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 49 | `commissionNom` | Nom de la commission | [ ] |
| 50 | `date` | Date du jour JJ/MM/AAAA | [ ] |
| 51 | `communeGrp` | Ville du groupement | [ ] |
| 52 | `numGrp` | Numéro de rue du groupement | [ ] |
| 53 | `rueGrp` | Rue du groupement | [ ] |
| 54 | `cpGrp` | Code postal du groupement | [ ] |
| 55 | `dateCommission` | Date de la commission JJ/MM/AAAA | [ ] |
| 56 | `heureDebutComm` | Heure de début HH:MM | [ ] |
| 57 | `maires` | Noms des maires concernés (liste) | [ ] |

> Les champs par dossier (#10 à #48) s'appliquent également dans ce cas.

---

### 1.3 — Commission de type "Visite" (type 2 ou 3)

**Pré-condition :** `COURRIER_CONVOCATIONVISITE` configuré pour le membre.

| # | Vérification | Résultat |
|---|-------------|----------|
| 58 | Le bon template de visite est utilisé (pas le template salle) | [x]      |
| 59 | Tous les champs #1 à #57 s'affichent correctement | [ ]      |

---

### 1.4 — Cas dégradés

| # | Situation | Comportement attendu | Résultat |
|---|-----------|---------------------|----------|
| 60 | Membre sans document configuré | Message d'avertissement affiché, pas de crash | [x]      |
| 61 | Aucun membre dans la commission | Message "Aucun membre renseigné" | [x]      |
| 62 | Dossier sans établissement | Dossier ignoré (non inclus dans le document) | [x]      |
| 63 | Email établissement vide | Champ `mailEtab` vide, pas de placeholder `{mailEtab}` | [x]      |
| 64 | Aucun permis de construire | Champ `numDocUrba` vide | [ ]      |
| 65 | Aucun préventionniste | Champs `preventionnistes`, `listeTelPreventioniste`, `listeMailPreventioniste` vides | [x]      |
| 66 | Dossier sans avis/dérogation | Segment présent avec champs vides (pas de placeholder) | [ ]      |
| 67 | Pas de membre par groupement | Pas de section groupement dans le document | [ ]      |

---

## 📋 2. O.D.J. (Ordre du Jour)

> Déclencher la génération de l'ODJ depuis le calendrier des commissions.

### 2.1 — Membre par commune

#### Champs globaux (segment `membresComm`, répété par commune)

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 1 | `numMairie` | Numéro adresse mairie | [ ] |
| 2 | `rueMairie` | Rue de la mairie | [ ] |
| 3 | `cpMairie` | Code postal de la mairie | [ ] |
| 4 | `communeMairie` | Ville de la mairie (champ `VILLEADRESSE`) | [ ] |
| 5 | `commissionNom` | Nom de la commission | [ ] |
| 6 | `date` | Date du jour JJ/MM/AAAA | [ ] |
| 7 | `dateCommission` | Date de la commission JJ/MM/AAAA | [ ] |
| 8 | `heureDebutComm` | Heure de début HH:MM | [ ] |

#### Champs par dossier (segment `infosDoss`, répété par dossier)

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 9 | `idDossier` | ID du dossier | [ ] |
| 10 | `avisDossier` | "Favorable", "Défavorable" ou vide | [ ] |
| 11 | `nomEtab` | Nom de l'établissement | [ ] |
| 12 | `numWinPrev` | Numéro WinPrev | [ ] |
| 13 | `nomPereEtab` | Nom établissement parent (vide si aucun) | [ ] |
| 14 | `dateVisite` | Date de visite JJ/MM/AAAA (vide si aucune) | [ ] |
| 15 | `categorieEtab` | Catégorie | [ ] |
| 16 | `typeLettreP` | Type principal | [ ] |
| 17 | `natureDossier` | Nature du dossier | [ ] |
| 18 | `objetDossier` | Objet du dossier | [ ] |
| 19 | `communeConcerne` | Commune de l'établissement | [ ] |
| 20 | `etablissementAdresse` | Adresse complète (n° rue CP commune) | [ ] |
| 21 | `heureDebDossier` | Heure de début HH:MM | [ ] |
| 22 | `heureFinDossier` | Heure de fin HH:MM | [ ] |
| 23 | `numDocUrba` | Numéros permis (vide si aucun) | [ ] |
| 24 | `typeDossierCourt` | "E" (Étude), "V" (Visite/Groupe), "A" (autre) | [ ] |
| 25 | `preventionnistes` | "Grade Nom Prénom, ..." | [ ] |
| 26 | `listeTelPreventioniste` | Téléphone(s) | [ ] |
| 27 | `listeMailPreventioniste` | Email(s) | [ ] |

> Avis/dérogations dossier (#29→#38 de la convocation) et établissement (#39→#48) : mêmes champs, même vérification.

---

### 2.2 — Membre par groupement

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 28 | `commissionNom` | Nom de la commission | [ ] |
| 29 | `date` | Date du jour | [ ] |
| 30 | `communeGrp` | Ville du groupement | [ ] |
| 31 | `numGrp` | Numéro de rue du groupement | [ ] |
| 32 | `rueGrp` | Rue du groupement | [ ] |
| 33 | `cpGrp` | Code postal du groupement | [ ] |
| 34 | `dateCommission` | Date de la commission | [ ] |
| 35 | `heureDebutComm` | Heure de début | [ ] |

---

### 2.3 — Gestion par heure vs par ordre

| # | Configuration | Vérification | Résultat |
|---|--------------|-------------|----------|
| 36 | `GESTION_HEURES = 1` | `heureDebDossier` et `heureFinDossier` affichent les heures | [ ] |
| 37 | `GESTION_HEURES = 0` | `heureDebDossier` et `heureFinDossier` sont vides | [ ] |
| 38 | `GESTION_HEURES = 1` | L'ordre des dossiers respecte l'ordre chronologique | [ ] |
| 39 | `GESTION_HEURES = 0` | L'ordre des dossiers respecte l'ordre de l'ODJ | [ ] |

---

### 2.4 — Cas dégradés

| # | Situation | Comportement attendu | Résultat |
|---|-----------|---------------------|----------|
| 40 | Commune sans contact mairie renseigné | Message d'avertissement affiché | [ ] |
| 41 | Dossier sans date de visite | Champ `dateVisite` vide | [ ] |

---

## 📝 3. P.V. (Procès-Verbal)

> Déclencher la génération du PV depuis le calendrier des commissions.

### 3.1 — Champs standards par dossier (segment `dossierInfos`)

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 1 | `commissionNom` | Nom de la commission | [ ] |
| 2 | `communeDossier` | Commune de l'établissement | [ ] |
| 3 | `objetDossier` | Objet du dossier | [ ] |
| 4 | `natureDossier` | Nature du dossier | [ ] |
| 5 | `dateCommission` | Date au format **"JJ mois AAAA"** (ex : "22 mai 2026") | [ ] |
| 6 | `dateVisite` | Date de visite **"JJ mois AAAA"** ou vide | [ ] |
| 7 | `libelleRue` | Rue de l'établissement | [ ] |
| 8 | `codePostal` | Code postal | [ ] |
| 9 | `libelleCommune` | Commune | [ ] |
| 10 | `effectifTotal` | Somme effectif public + personnel (ex : "215") | [ ] |
| 11 | `typeDossier` | "Étude", "Visite de commission" ou "Groupe de visite" | [ ] |
| 12 | `nomPereEtab` | Nom établissement parent (vide si aucun) | [ ] |
| 13 | `nomEtab` | Nom de l'établissement | [ ] |
| 14 | `categorieEtab` | Catégorie | [ ] |
| 15 | `typeEtab` | Type principal | [ ] |
| 16 | `avisDossier` | "Favorable", "Défavorable" ou vide | [ ] |
| 17 | `avisDossierCommission` | "Favorable", "Défavorable" ou vide | [ ] |
| 18 | `numDocUrba` | Numéros permis | [ ] |

> Avis/dérogations dossier et établissement : mêmes 10 champs que la convocation.

---

### 3.2 — Prescriptions ⚠️ (exclusif au PV)

**Pré-condition :** Le dossier doit avoir des prescriptions réglementaires, d'exploitation ET d'amélioration.

#### Prescriptions réglementaires

| # | Segment ODT | Champ | Valeur attendue | Résultat |
|---|-------------|-------|----------------|----------|
| 19 | `listeReg` (toutes) | `num` | Numéro de la prescription | [ ] |
| 20 | `listeReg` | `libelle` | Libellé (personnalisé ou type si vide) | [ ] |
| 21 | `listeReg` | `textearticle` | Texte des articles associés | [ ] |
| 22 | `listeRegReprises` | `num`, `libelle`, `textearticle` | Uniquement les prescriptions reprises | [ ] |
| 23 | `listeRegActuelles` | `num`, `libelle`, `textearticle` | Uniquement les nouvelles prescriptions | [ ] |

#### Prescriptions exploitation

| # | Segment ODT | Champ | Valeur attendue | Résultat |
|---|-------------|-------|----------------|----------|
| 24 | `listeExploitation` | `num`, `libelle`, `textearticle` | Toutes les prescriptions exploitation | [ ] |
| 25 | `listeExploitationReprises` | idem | Reprises | [ ] |
| 26 | `listeExploitationActuelles` | idem | Nouvelles | [ ] |

#### Prescriptions amélioration

| # | Segment ODT | Champ | Valeur attendue | Résultat |
|---|-------------|-------|----------------|----------|
| 27 | `listeAmelioration` | `num`, `libelle`, `textearticle` | Toutes les prescriptions amélioration | [ ] |
| 28 | `listeAmeliorationReprises` | idem | Reprises | [ ] |
| 29 | `listeAmeliorationActuelles` | idem | Nouvelles | [ ] |

---

### 3.3 — Effectif total

| # | Effectif public | Effectif personnel | Valeur `effectifTotal` attendue | Résultat |
|---|----------------|-------------------|-------------------------------|----------|
| 30 | 200 | 15 | "215" | [ ] |
| 31 | 0 | 0 | "non renseigné" | [ ] |
| 32 | 0 | 10 | "10" | [ ] |

---

### 3.4 — Date de visite

| # | Situation | Valeur `dateVisite` attendue | Résultat |
|---|-----------|------------------------------|----------|
| 33 | `DATEVISITE_DOSSIER` renseignée | Date du dossier (format long) | [ ] |
| 34 | `DATEVISITE_DOSSIER` null, `last_visite` établissement renseigné | Date de la dernière visite établissement | [ ] |
| 35 | Aucune date disponible | Champ vide | [ ] |

---

### 3.5 — Prescriptions : libellé

| # | Situation | Valeur `libelle` attendue | Résultat |
|---|-----------|--------------------------|----------|
| 36 | `LIBELLE_PRESCRIPTION_DOSSIER` renseigné | Libellé personnalisé | [ ] |
| 37 | `LIBELLE_PRESCRIPTION_DOSSIER` vide | `PRESCRIPTIONTYPE_LIBELLE` (libellé du type) | [ ] |

---

### 3.6 — Cas dégradés

| # | Situation | Comportement attendu | Résultat |
|---|-----------|---------------------|----------|
| 38 | Aucun membre sans groupement (`ID_GROUPEMENT IS NULL`) | Pas de document généré, message d'erreur | [ ] |
| 39 | Dossier sans prescription | Segments présents avec champs vides (pas de placeholder) | [ ] |

---

## 📊 4. COMPTE-RENDU

> Déclencher la génération du compte-rendu depuis le calendrier des commissions.
> **Note :** Un seul document est généré pour toute la commission (pas un par membre).

### 4.1 — Champ global

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 1 | `dateGeneration` | Date du jour au format **"JJ mois AAAA"** (ex : "22 mai 2026") | [ ] |

---

### 4.2 — Champs par dossier (segment `dossierInfos`)

| # | Champ ODT | Valeur attendue | Résultat |
|---|-----------|----------------|----------|
| 2 | `commissionNom` | Nom de la commission | [ ] |
| 3 | `communeDossier` | Commune de l'établissement | [ ] |
| 4 | `objetDossier` | Objet du dossier | [ ] |
| 5 | `natureDossier` | Nature du dossier | [ ] |
| 6 | `observationDossier` | Observation saisie sur le dossier ⚠️ | [ ] |
| 7 | `dateCommission` | Date au format **"JJ mois AAAA"** | [ ] |
| 8 | `dateVisite` | Date de visite **"JJ mois AAAA"** ou vide | [ ] |
| 9 | `libelleRue` | Rue de l'établissement | [ ] |
| 10 | `codePostal` | Code postal | [ ] |
| 11 | `numWinPrev` | Numéro WinPrev | [ ] |
| 12 | `libelleCommune` | Commune | [ ] |
| 13 | `effectifTotal` | Somme effectif public + personnel | [ ] |
| 14 | `typeDossier` | "Étude", "Visite de commission" ou "Groupe de visite" | [ ] |
| 15 | `nomPereEtab` | Nom établissement parent (vide si aucun) | [ ] |
| 16 | `nomEtab` | Nom de l'établissement | [ ] |
| 17 | `categorieEtab` | Catégorie | [ ] |
| 18 | `typeEtab` | Type principal | [ ] |
| 19 | `avisDossier` | "Favorable", "Défavorable" ou vide | [ ] |
| 20 | `avisDossierCommission` | "Favorable", "Défavorable" ou vide | [ ] |
| 21 | `numDocUrba` | Numéros permis | [ ] |

> Avis/dérogations dossier et établissement : mêmes 10 champs que la convocation.

---

### 4.3 — Observation dossier ⚠️ (exclusif au compte-rendu)

| # | Situation | Comportement attendu | Résultat |
|---|-----------|---------------------|----------|
| 22 | Observation renseignée | Texte affiché intégralement dans `observationDossier` | [ ] |
| 23 | Observation vide | Champ vide, pas de placeholder | [ ] |

---

### 4.4 — Cas dégradés

| # | Situation | Comportement attendu | Résultat |
|---|-----------|---------------------|----------|
| 24 | `DOCUMENT_CR` non configuré pour la commission | Message "Le fichier de compte rendu pour la commission n'existe pas" | [ ] |
| 25 | Dossier sans adresse établissement | Le dossier est ignoré dans le document | [ ] |

---

## 🔁 5. Tests transversaux (communs aux 4 documents)

### 5.1 — Formulaires personnalisés

**Pré-condition :** Des formulaires personnalisés doivent être configurés sur au moins une des capsules suivantes :
- Descriptif vérifications techniques (dossier)
- Descriptif établissement
- Effectifs et dégagements (dossier)
- Effectifs et dégagements (établissement)

| # | Type de champ | Comportement attendu | Résultat |
|---|---------------|---------------------|----------|
| 1 | Champ texte simple | Valeur affichée telle quelle | [ ] |
| 2 | Case à cocher = cochée | Affiche **"Oui"** | [ ] |
| 3 | Case à cocher = non cochée | Affiche **"Non"** | [ ] |
| 4 | Champ date renseigné | Format "JJ mois AAAA" (ex : "22 mai 2026") | [ ] |
| 5 | Champ date vide | Champ vide | [ ] |
| 6 | Champ Parent sans tableau — enfants renseignés | Valeurs des champs enfants affichées | [ ] |
| 7 | Champ Parent avec tableau — données renseignées | Chaque ligne dans `valeur0`, `valeur1`... | [ ] |
| 8 | Champ Parent avec tableau — tableau vide | Ligne avec `valeur0`, `valeur1`... vides | [ ] |

---

### 5.2 — Avis/dérogations : cas limites

| # | Cas | Comportement attendu | Résultat |
|---|-----|---------------------|----------|
| 9 | 1 seul avis/dérogation sur le dossier | 1 occurrence du segment avec toutes les données | [ ] |
| 10 | N avis/dérogations sur le dossier | N occurrences du segment | [ ] |
| 11 | Aucun avis/dérogation dossier | Segment présent avec tous les champs vides | [ ] |
| 12 | Avis avec date de commission | `date_avis_derogation` = date de commission | [ ] |
| 13 | Avis avec date de visite (pas de commission) | `date_avis_derogation` = date de visite | [ ] |
| 14 | Avis sans aucune date | `date_avis_derogation` vide | [ ] |
| 15 | N avis/dérogations sur l'établissement | N occurrences du segment `avisDerogationsEtablissement` | [ ] |
| 16 | Aucun avis établissement | Segment présent avec tous les champs vides | [ ] |

---

### 5.3 — Établissements liés (parent/enfant)

| # | Cas | Comportement attendu | Résultat |
|---|-----|---------------------|----------|
| 17 | Établissement seul (sans parent) | `nomPereEtab` vide | [ ] |
| 18 | Établissement enfant avec parent | `nomPereEtab` = nom de l'établissement parent | [ ] |
| 19 | Établissements liés avec avis sur les enfants | `avisDerogationsEtablissement` inclut les avis de TOUS les établissements liés | [ ] |

---

### 5.4 — Encodage des caractères

| # | Cas | Comportement attendu | Résultat |
|---|-----|---------------------|----------|
| 20 | Nom avec accents (é, è, à, ô, ü...) | Affiché correctement dans l'ODT | [x]      |
| 21 | Texte avec apostrophe ou guillemets | Affiché sans corruption | [x]      |
| 22 | Texte long (objet, observation) | Non tronqué, entièrement affiché | [x]      |

---

### 5.5 — Sauvegarde en pièce jointe

Après chaque génération, vérifier dans **Pièces jointes > date de commission** :

| # | Document | Nom PJ attendu | Extension | Résultat |
|---|----------|---------------|-----------|----------|
| 23 | Convocation | "Convocation" | .odt | [x]      |
| 24 | O.D.J. | "Ordre du jour" | .odt | [ ]      |
| 25 | P.V. | "P.V." | .odt | [ ]      |
| 26 | Compte-rendu | "Compte rendu" | .odt | [ ]      |
| 27 | Tous | Le fichier ODT est téléchargeable et ouvrable | — | [x]      |

---

## 📌 Récapitulatif des champs exclusifs par document

| Champ | Convoc | ODJ | PV | CR |
|-------|:------:|:---:|:--:|:--:|
| `heureDeb1erDossier` | ✅ | — | — | — |
| `maires` (groupement) | ✅ | — | — | — |
| `mailEtab` | ✅ | — | — | — |
| `typeDossierCourt` (E/V/A) | — | ✅ | — | — |
| `effectifTotal` | — | — | ✅ | ✅ |
| `typeDossier` (libellé long) | — | — | ✅ | ✅ |
| `dateVisite` | — | ✅ | ✅ | ✅ |
| `observationDossier` | — | — | — | ✅ |
| `dateGeneration` | — | — | — | ✅ |
| Prescriptions (listeReg...) | — | — | ✅ | — |
| `libelleRue`/`codePostal` séparés | — | — | ✅ | ✅ |
| `etablissementAdresse` (concaténée) | ✅ | ✅ | — | — |
| `numWinPrev` | ✅ | ✅ | — | ✅ |
| `communeConcerne` | — | ✅ | — | — |

---

*Généré le 22 mai 2026 — Analyse du legacy `CalendrierDesCommissionsController.php` et des vues `generation*.phtml`*
