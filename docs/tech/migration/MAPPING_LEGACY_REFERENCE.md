# Mapping de référence Legacy → Symfony (pour compréhension)

**Objectif :** Document de référence pour comprendre la correspondance entre les champs legacy et Symfony.  
**Note :** Ce mapping n'est PAS utilisé dans le code (tout est en Symfony maintenant).

---

## Champs généraux

| Champ Legacy | Colonne BDD | Propriété Symfony | Type | Description |
|--------------|-------------|-------------------|------|-------------|
| `DATEINSERT` | `DATEINSERT_DOSSIER` | `$dateInsert` | `\DateTimeInterface` | Date de création du dossier |
| `OBJET` | `OBJET_DOSSIER` | `$objet` | `string` | Objet du dossier |
| `OBSERVATION` | `OBSERVATION_DOSSIER` | `$observationDossier` | `string` | Observations |
| `HORSDELAI` | `HORSDELAI_DOSSIER` | `$horsDelai` | `bool` | Hors délai |
| `DATESIGNATURE` | `DATESIGN_DOSSIER` | `$dateSignature` | `\DateTimeInterface` | Date de signature |

---

## Champs réunions (Type 4)

### Nature 31 : Réunion de chantier locaux SDIS

**Legacy :** `['DATEINSERT', 'OBJET', 'DATEREUN', 'DATEREP', 'PREVENTIONNISTE', 'DEMANDEUR', 'OBSERVATION']`

| Legacy | Colonne BDD | Symfony | Type | Description |
|--------|-------------|---------|------|-------------|
| `DATEINSERT` | `DATEINSERT_DOSSIER` | `dateInsert` | `\DateTimeInterface` | Date création |
| `OBJET` | `OBJET_DOSSIER` | `objet` | `string` | Objet |
| `DATEREUN` | `DATEREUN_DOSSIER` | `dateReunion` | `\DateTimeInterface` | Date de la réunion |
| `DATEREP` | `DATEREP_DOSSIER` | `dateReponse` | `\DateTimeInterface` | Date de réponse |
| `PREVENTIONNISTE` | table `dossierpreventionniste` | `preventionnistes` | `Collection` | Préventionnistes liés |
| `DEMANDEUR` | `DEMANDEUR_DOSSIER` | `demandeur` | `string` | Demandeur |
| `OBSERVATION` | `OBSERVATION_DOSSIER` | `observations` | `string` | Observations |

### Nature 32 : Réunion de chantier extérieur SDIS

**Legacy :** `['DATEINSERT', 'OBJET', 'LIEUREUNION', 'DATEREUN', 'DATEREP', 'PREVENTIONNISTE', 'DEMANDEUR', 'OBSERVATION']`

Identique à nature 31 **+ LIEUREUNION** :

| Legacy | Colonne BDD | Symfony | Type | Description |
|--------|-------------|---------|------|-------------|
| `LIEUREUNION` | `LIEUREUNION_DOSSIER` | `lieuReunion` | `string` | Lieu de la réunion |

### Nature 43 : Réunion téléphonique

**Legacy :** `['DATEINSERT', 'OBJET', 'DATEREUN', 'DATEREP', 'PREVENTIONNISTE', 'DEMANDEUR', 'OBSERVATION']`

Identique à nature 31.

---

## Champs études (Type 1) - Pour référence future

| Legacy | Colonne BDD | Symfony | Description |
|--------|-------------|---------|-------------|
| `NUMDOCURBA` | table `dossierdocurba` | `documentsUrbanisme` | Documents urbanisme |
| `NUMCHRONO` | `NUMCHRONO_DOSSIER` | `numeroChronologique` | N° chronologique |
| `DATEMAIRIE` | `DATEMAIRIE_DOSSIER` | `dateMairie` | Date réception mairie |
| `DATESECRETARIAT` | `DATESECRETARIAT_DOSSIER` | `dateSecretariat` | Date réception secrétariat |
| `SERVICEINSTRUC` | `SERVICEINSTRUC_DOSSIER` | `serviceInstructeur` | Service instructeur |
| `COMMISSION` | `ID_COMMISSION` | `commission` | Commission |
| `DESCGEN` | `DESCGEN_DOSSIER` | `descriptifGeneral` | Descriptif général |
| `DESCEFF` | `DESCEFF_DOSSIER` | `descriptifEffectifs` | Descriptif effectifs |
| `DATECOMM` | `DATECOMM_DOSSIER` | `dateCommission` | Date commission |
| `AVIS` | `AVIS_DOSSIER` | `avis` | Avis rapporteur |
| `COORDSSI` | `COORDSSI_DOSSIER` | `coordonneesSsi` | Coordonnées SSI |
| `DATESDIS` | `DATESDIS_DOSSIER` | `dateReponse` | Date réponse SDIS |
| `INCOMPLET` | `INCOMPLET_DOSSIER` | `incomplet` | Dossier incomplet |
| `AVIS_COMMISSION` | `AVIS_DOSSIER_COMMISSION` | `avisCommission` | Avis commission |
| `ABSQUORUM` | `ABSQUORUM_DOSSIER` | `absenceDeQuorum` | Absence quorum |

---

## Champs visites (Type 2) - Pour référence future

| Legacy | Colonne BDD | Symfony | Description |
|--------|-------------|---------|-------------|
| `DATEVISITE` | `DATEVISITE_DOSSIER` | `dateVisite` | Date de visite (≠ dateReunion) |
| `DIFFEREAVIS` | `DIFFEREAVIS_DOSSIER` | `avisDiffere` | Avis différé |
| `DATERVRAT` | `DATERVRAT_DOSSIER` | `dateReceptionRvrat` | Date réception RVRAT |
| `DELAIPRESC` | `DELAIPRESC_DOSSIER` | `dateLimitePrescription` | Délai levée prescriptions |
| `NPSP` | `NPSP_DOSSIER` | `nePeutSePrononcer` | Ne peut se prononcer |

---

## Champs dérogations - Pour référence future

| Legacy | Colonne BDD | Symfony | Description |
|--------|-------------|---------|-------------|
| `REGLEDEROG` | `REGLEDEROG_DOSSIER` | `reglesDerogation` | Règles dérogation |
| `JUSTIFDEROG` | `JUSTIFDEROG_DOSSIER` | `justificationDerogation` | Justification |
| `MESURESCOMPENS` | `MESURESCOMPENS_DOSSIER` | `mesuresCompensatoires` | Mesures compensatoires |
| `MESURESCOMPLE` | `MESURESCOMPLE_DOSSIER` | `mesuresComplementaires` | Mesures complémentaires |

---

## Champs courriers (Type 7)

### Courriers/Courriels complets (natures 49-55, 57-60, 65)

**Legacy complet :** `DATEINSERT, OBJET, NUMCHRONO, DATEMAIRIE, PREVENTIONNISTE, DATEREP, DATEENVTRANSIT, DATESDIS, DEMANDEUR, DATETRANSFERTCOMM, DATERECEPTIONCOMM, OBSERVATION`

| Legacy | Colonne BDD | Symfony | Description |
|--------|-------------|---------|-------------|
| `DATEMAIRIE` | `DATEMAIRIE_DOSSIER` | `dateMairie` | Date réception mairie |
| `DATEREP` | `DATEREP_DOSSIER` | `dateReponse` | Date de réponse |
| `DATEENVTRANSIT` | `DATEENVTRANSIT_DOSSIER` | `dateEnvoiTransit` | Date envoi/transit |
| `DATESDIS` | `DATESDIS_DOSSIER` | `dateReception` | Date réception SDIS |
| `DATETRANSFERTCOMM` | `DATETRANSFERTCOMM_DOSSIER` | `dateTransfertCommission` | Date transfert commission |
| `DATERECEPTIONCOMM` | `DATERECEPTIONCOMM_DOSSIER` | `dateReceptionCommission` | Date réception commission |
| `NUMCHRONO` | - | - | ⚠️ Non implémenté (absent de l'entité) |

**Ordre Symfony :** `dateInsert`, `objet`, `dateMairie`, `dateEnvoiTransit`, `dateReponse`, `dateReception`, `demandeur`, `dateTransfertCommission`, `dateReceptionCommission`, `preventionnistes`, `observations`

### Autres champs courriers

| Legacy | Colonne BDD | Symfony | Description |
|--------|-------------|---------|-------------|
| `DATEPREF` | `DATEPREF_DOSSIER` | `datePrefecture` | Date réception préfecture |

---

## Champs interventions (Type 5) - Pour référence future

| Legacy | Colonne BDD | Symfony | Description |
|--------|-------------|---------|-------------|
| `OPERSDIS` / `OPSDIS` | `OPSDIS_DOSSIER` | `operationSdis` | Opération SDIS |
| `RCCI` | `RCCI_DOSSIER` | `rcci` | RCCI |
| `REX` | `REX_DOSSIER` | `rex` | REX |
| `NUMINTERV` | `NUMINTERV_DOSSIER` | `numeroIntervention` | N° intervention |
| `DATEINTERV` | `DATEINTERV_DOSSIER` | `dateIntervention` | Date intervention |
| `HEUREINTERV` | `HEUREINTERV_DOSSIER` | `heureIntervention` | Heure intervention |
| `DUREEINTERV` | `DUREEINTERV_DOSSIER` | `dureeIntervention` | Durée intervention |

---

## Champs spécifiques

| Legacy | Colonne BDD | Symfony | Description |
|--------|-------------|---------|-------------|
| `CHARGESEC` | `CHARGESEC_DOSSIER` | `chargeSecurite` | Chargé sécurité |
| `LIEUVISITE` | `LIEUVISITE_DOSSIER` | `lieuVisite` | Lieu visite |
| `DATESIGN` | `DATESIGN_DOSSIER` | `dateSignature` | Date signature |

---

## Différences importantes à noter

### 1. DATEREUN vs DATEVISITE
- **DATEREUN** (`dateReunion`) : Utilisé pour les réunions (type 4)
- **DATEVISITE** (`dateVisite`) : Utilisé pour les visites (type 2, 3)
- ⚠️ Ce sont deux champs distincts dans la BDD

### 2. DATEREP vs DATESDIS (deux champs distincts)
- **DATEREP** → `dateReponse` (DATEREP_DOSSIER - date de réponse)
- **DATESDIS** → `dateReception` (DATESDIS_DOSSIER - date réception SDIS)
- ⚠️ Ce sont deux propriétés distinctes dans l'entité !

### 3. OBSERVATION = observations
- **OBSERVATION** → `observations` (OBSERVATION_DOSSIER)
- ⚠️ Propriété = `$observations` (pas `$observationDossier`)

### 4. PREVENTIONNISTE = preventionnistes (collection)
- **PREVENTIONNISTE** → `preventionnistes` (relation ManyToMany)
- Table de jointure : `dossierpreventionniste`

---

**Auteur :** Équipe Prevarisc  
**Date :** 10 décembre 2025  
**Utilisation :** Document de référence uniquement (pas de code de mapping)
