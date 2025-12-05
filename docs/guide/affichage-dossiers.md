# Guide utilisateur - Consultation des informations générales d'un dossier

**Version :** 1.0  
**Date :** 5 décembre 2025

---

## Table des matières

1. [Introduction](#introduction)
2. [Accéder aux informations d'un dossier](#accéder-aux-informations-dun-dossier)
3. [Comprendre l'affichage](#comprendre-laffichage)
4. [Informations affichées selon le type de dossier](#informations-affichées-selon-le-type-de-dossier)
5. [Documents manquants](#documents-manquants)
6. [Avis et commission](#avis-et-commission)
7. [Questions fréquentes](#questions-fréquentes)

---

## Introduction

### À quoi sert cette page ?

La page d'affichage des informations générales d'un dossier vous permet de **consulter en lecture seule** toutes les informations importantes concernant un dossier (étude, visite, groupe de visite, etc.).

### Que puis-je faire sur cette page ?

- ✅ **Consulter** toutes les informations du dossier
- ✅ **Voir les documents manquants** et leur statut
- ✅ **Accéder à l'ordre du jour** de la commission (si applicable)
- ✅ **Modifier le dossier** via le bouton "Modifier le dossier" (si le dossier n'est pas verrouillé)

---

## Accéder aux informations d'un dossier

### Depuis la liste des dossiers

1. Dans le menu principal, cliquez sur **Dossiers**
2. Recherchez ou filtrez le dossier qui vous intéresse
3. Cliquez sur le numéro ou le libellé du dossier

### Depuis un établissement

1. Accédez à la fiche d'un établissement
2. Dans l'onglet **Dossiers**, cliquez sur le dossier souhaité

### Depuis une commission

1. Accédez au calendrier des commissions
2. Cliquez sur une date de commission
3. Dans l'ordre du jour, cliquez sur un dossier

---

## Comprendre l'affichage

### En-tête de page

```
┌────────────────────────────────────────────────────────┐
│  Informations générales                [Modifier]      │
└────────────────────────────────────────────────────────┘
```

- **Titre** : "Informations générales"
- **Bouton "Modifier"** : Visible uniquement si le dossier n'est pas verrouillé
  - Un dossier est verrouillé après passage en commission avec avis

### Structure de la page

La page est organisée en **3 sections principales** :

#### 1. Informations sur Plat'AU

> **Note :** Cette section sera complétée lors de l'intégration avec la plateforme Plat'AU.

#### 2. Type et nature du dossier

Affiche :
- **Type** : Étude / Visite / Groupe de visite / Courrier / etc.
- **Nature** : Détail du type (ex: "Permis de construire", "Visite périodique")

**Exemple :**
```
Type: Étude
Nature: Permis de construire (PC)
```

#### 3. Informations sur le dossier

Section principale contenant tous les champs du dossier.

**Les champs affichés dépendent de la nature du dossier.**

---

## Informations affichées selon le type de dossier

### Champs communs à tous les dossiers

| Champ | Description | Exemple |
|-------|-------------|---------|
| **Date de création** | Date et auteur de création | 15/11/2024 par : DUPONT Jean |
| **Préventionniste(s)** | Personnes en charge | MARTIN Sophie |
| **Observations** | Remarques diverses | Notes complémentaires |

### Dossiers de type "Étude"

**Natures concernées :** PC, AT, Dérogation, Notice de sécurité, etc.

#### Informations principales

| Champ | Description | Affiché pour |
|-------|-------------|--------------|
| **Objet** | Description de la demande | Toutes natures |
| **Numéro(s) document(s) d'urbanisme** | PC, DP, AT, etc. | PC, AT, Notice, PA |
| **Date de réception mairie** | Date d'arrivée en mairie | La plupart |
| **Date de réception secrétariat SDIS** | Date d'arrivée au SDIS | La plupart |
| **Service instructeur** | Service en charge | PC, AT, Notice |
| **Commission concernée** | Commission compétente | La plupart |
| **Demandeur** | Nom du demandeur | Toutes natures |

#### Informations spécifiques dérogations

| Champ | Description |
|-------|-------------|
| **Règles auxquelles il est demandé de déroger** | Articles concernés |
| **Justification de la dérogation** | Arguments justifiant la demande |
| **Mesures compensatoires proposées** | Mesures alternatives |
| **Mesures complémentaires à respecter** | Prescriptions additionnelles |

#### État du dossier

| Champ | Valeurs possibles |
|-------|-------------------|
| **État du dossier** | Complet / Incomplet |
| **Hors délai** | Oui (si applicable) |

> Voir section [Documents manquants](#documents-manquants) pour plus de détails.

#### Avis

| Champ | Description |
|-------|-------------|
| **Avis du rapporteur** | Favorable / Défavorable / Favorable avec prescriptions / etc. |
| **Avis de la commission** | Avis final après commission |
| **Diffère l'avis** | Coché si l'avis est reporté |
| **Ne peut se prononcer** | Coché si impossible de se prononcer |
| **Absence de quorum** | Coché si pas assez de membres présents |

> Voir section [Avis et commission](#avis-et-commission) pour les règles d'affichage.

### Dossiers de type "Visite"

**Natures concernées :** Réception, Périodique, Chantier, Contrôle, etc.

#### Informations spécifiques

| Champ | Description |
|-------|-------------|
| **Date de visite** | Date prévue ou réalisée de la visite |
| **Diffère l'avis** | Visite reportée |
| **Date de réception du RVRAT** | Date de réception du rapport |
| **Date avant laquelle les prescriptions doivent être levées** | Échéance |

**Particularité visite périodique :**
- Pas d'objet affiché (calculé automatiquement)
- Focus sur la date de visite

### Dossiers de type "Groupe de visite"

**Similaire aux visites**, avec en plus :
- **Date de commission en salle** : Date de validation du groupe
- **Avis** : Synthèse des avis individuels

### Dossiers de type "Réunion"

| Champ | Description |
|-------|-------------|
| **Date réunion** | Date de la réunion |
| **Lieu réunion** | Localisation (si réunion extérieure) |
| **Demandeur** | Personne ayant demandé la réunion |

### Dossiers de type "Courrier/Courriel"

| Champ | Description |
|-------|-------------|
| **Date d'envoi/transit** | Date d'envoi du courrier |
| **Date de réception SDIS** | Date de réception |
| **Date de transfert à la commission compétente** | Si transfert |
| **Date de réception à la commission compétente** | Date d'arrivée |
| **Date réception préfecture** | Pour courriers préfecture |

### Dossiers de type "Intervention"

**Natures :** Incendie, SAP, Intervention diverse

| Champ | Description |
|-------|-------------|
| **Opération (intervention) SDIS** | Oui / Non |
| **RCCI** | Compte-rendu intervention |
| **REX** | Retour d'expérience |
| **Numéro d'intervention** | Référence SDIS |
| **Date et heure d'intervention** | Horodatage |
| **Durée intervention** | En heures/minutes |

### Dossiers de type "Arrêté"

**Natures :** Ouverture, Fermeture, Mise en demeure, etc.

| Champ | Description |
|-------|-------------|
| **Date de signature** | Date de signature de l'arrêté |
| **Objet** | Objet de l'arrêté |

---

## Documents manquants

### Qu'est-ce qu'un dossier incomplet ?

Un dossier est **incomplet** lorsqu'il manque des documents nécessaires à son instruction.

### Affichage des documents manquants

**Localisation :** Sous le champ "État du dossier"

**Format d'affichage :**

```
État du dossier: Incomplet

Incomplet le 15/11/2024
- Plans de masse
- Notice de sécurité
- Attestation de solidité

Incomplet le 20/11/2024
- Schéma d'évacuation
Réception document(s) le 25/11/2024
```

### Interprétation

| Élément | Signification |
|---------|---------------|
| **Date en gras** | Date où le dossier a été déclaré incomplet |
| **Liste de documents** | Documents manquants à cette date |
| **"Réception document(s) le..."** (vert) | Documents reçus, dossier complet |
| **Plusieurs entrées** | Historique des incomplets successifs |

### Exemple concret

```
État du dossier: Complet

Incomplet le 10/11/2024
- Plans de niveau
- Notice descriptive
Réception document(s) le 15/11/2024

Incomplet le 20/11/2024
- Attestation de solidité
Réception document(s) le 25/11/2024
```

**Lecture :**
1. Le dossier a été incomplet du 10/11 au 15/11 (plans + notice manquants)
2. Documents reçus le 15/11
3. Nouveau document manquant le 20/11 (attestation)
4. Document reçu le 25/11
5. **Dossier actuellement complet**

---

## Avis et commission

### Quand l'avis de commission est-il affiché ?

L'avis de la commission est affiché **uniquement si toutes ces conditions sont remplies** :

✅ Pas d'absence de quorum  
✅ Pas de "hors délai"  
✅ Pas d'"avis différé"  
✅ Dossier complet  
✅ Pas de "ne peut se prononcer"

### Types d'avis

| Avis | Signification |
|------|---------------|
| **Favorable** | Accord sans condition |
| **Favorable avec prescriptions** | Accord avec conditions |
| **Favorable avec réserves** | Accord conditionné à des vérifications |
| **Défavorable** | Refus |
| **Défavorable avec avis** | Refus motivé |

### Lien vers l'ordre du jour

Si une **date de commission** est affichée, vous pouvez cliquer sur l'icône calendrier 📅 pour accéder à l'ordre du jour complet de la commission.

**Exemple :**

```
Date de commission en salle: 15/12/2024 📅
```

---

## Questions fréquentes

### Pourquoi certains champs ne s'affichent-ils pas ?

**Réponse :** L'affichage des champs dépend de la **nature** du dossier.

Par exemple :
- Un dossier "Visite périodique" n'a pas d'objet
- Un dossier "Réunion" n'a pas de commission
- Un dossier "Courrier" n'a pas de date de visite

C'est un comportement **normal et attendu**.

### Comment modifier les informations du dossier ?

**Étapes :**
1. Cliquez sur le bouton **"Modifier le dossier"** en haut à droite
2. Modifiez les champs nécessaires
3. Cliquez sur **"Enregistrer"**

> ⚠️ **Attention :** Le bouton "Modifier" n'est pas visible si le dossier est verrouillé.

### Pourquoi le bouton "Modifier" n'est-il pas visible ?

**Raisons possibles :**

1. **Dossier verrouillé** : Après passage en commission avec avis
   - Solution : Contacter un administrateur pour déverrouiller

2. **Droits insuffisants** : Votre profil ne permet pas la modification
   - Solution : Contacter votre responsable

### Que signifie "Hors délai" ?

Un dossier est marqué **"Hors délai"** lorsque le délai réglementaire d'instruction est dépassé.

**Conséquences :**
- L'avis de commission ne s'affiche pas
- Le dossier est signalé dans les rapports
- Nécessite un traitement prioritaire

### Que signifie "Avis différé" ?

Un avis est **différé** lorsque la commission décide de reporter sa décision, généralement pour :
- Demander des informations complémentaires
- Attendre une visite de contrôle
- Consulter un expert

**Conséquences :**
- L'avis de commission ne s'affiche pas
- Le dossier reviendra en commission ultérieurement

### Que signifie "Ne peut se prononcer" ?

La commission **ne peut se prononcer** lorsque :
- Les éléments du dossier sont insuffisants
- Il manque des pièces essentielles
- Le dossier nécessite une expertise externe

**Différence avec "Incomplet" :**
- **Incomplet** : Documents administratifs manquants (pièces jointes)
- **Ne peut se prononcer** : Impossibilité technique d'émettre un avis

### Que signifie "Absence de quorum" ?

Le **quorum** est le nombre minimum de membres requis pour que la commission puisse délibérer.

En cas d'**absence de quorum** :
- La commission ne peut pas émettre d'avis
- Le dossier est reporté à une prochaine commission
- L'avis de commission ne s'affiche pas

### Comment savoir quand le dossier passera en commission ?

**Méthode 1 :** Regarder le champ **"Date de commission en salle"**
- Si renseignée : le dossier est planifié
- Si vide : le dossier n'est pas encore programmé

**Méthode 2 :** Accéder au **calendrier des commissions**
1. Menu > Commissions
2. Consulter les dates à venir
3. Vérifier l'ordre du jour

### Comment imprimer les informations du dossier ?

**Option 1 :** Utiliser la fonction d'impression du navigateur
1. Ctrl+P (ou Cmd+P sur Mac)
2. Sélectionner l'imprimante ou "Enregistrer en PDF"

**Option 2 :** Générer un document officiel
1. Accéder aux modèles de documents
2. Sélectionner "Fiche dossier" ou "Rapport d'instruction"
3. Télécharger le PDF généré

### Où puis-je voir les pièces jointes du dossier ?

Les pièces jointes **ne sont pas affichées sur cette page**.

Pour consulter les pièces jointes :
1. Dans le menu latéral du dossier, cliquez sur **"Pièces jointes"**
2. Vous verrez la liste complète des documents attachés au dossier

### Comment ajouter des documents manquants ?

**Vous ne pouvez pas ajouter de documents depuis cette page.**

Pour compléter un dossier incomplet :
1. Cliquez sur **"Modifier le dossier"**
2. Dans l'onglet **"Documents manquants"**, indiquez la date de réception
3. Allez dans **"Pièces jointes"** pour uploader les documents
4. Enregistrez

### Qui peut voir cette page ?

**Tous les utilisateurs** ayant accès à l'application peuvent consulter les informations générales d'un dossier.

Les restrictions d'accès se font au niveau :
- De l'établissement (si profil restreint géographiquement)
- Des actions (modification, suppression)

---

## Assistance

### Besoin d'aide ?

**Contacter :**
- Votre **responsable de service**
- Le **support technique** : support@prevarisc.fr
- Consulter la **documentation complète** dans l'aide en ligne

### Signaler un problème

Si vous constatez :
- Un affichage incorrect
- Des informations manquantes
- Un bug

**Procédure :**
1. Noter le **numéro du dossier**
2. Faire une **capture d'écran**
3. Contacter le support avec ces éléments

---

**Document réalisé par :** Équipe Prevarisc  
**Version :** 1.0  
**Dernière mise à jour :** 5 décembre 2025

