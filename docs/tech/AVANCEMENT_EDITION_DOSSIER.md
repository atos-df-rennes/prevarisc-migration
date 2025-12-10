# Avancement : Édition des dossiers

**Projet :** Migration Zend v1 → Symfony 4  
**Fonctionnalité :** Édition/Création des dossiers  
**Dernière mise à jour :** 10 décembre 2025 - 17h15

---

## 📊 Vue d'ensemble

### Statistiques

- **Natures implémentées :** 15 / ~70 (21%)
- **Phases terminées :** 2 / 6+
- **Temps estimé restant :** ~5-7 jours de développement

### Types de dossiers

| Type | Libellé | Natures totales | Natures OK | Status |
|------|---------|-----------------|------------|--------|
| **Type 1** | Étude | ~13 | 0 | ⏳ TODO |
| **Type 2** | Visite | ~10 | 0 | ⏳ TODO |
| **Type 3** | Visite avec avis | ~5 | 0 | ⏳ TODO |
| **Type 4** | Réunion | 3 | **3** | ✅ TERMINÉ |
| **Type 5** | Intervention | ~5 | 0 | ⏳ TODO |
| **Type 7** | Courrier/Courriel | 12+ | **12** | ✅ TERMINÉ |

---

## ✅ Phases terminées

### Phase 0 : Infrastructure (10/12/2025)

**Objectif :** Créer la base pour l'édition dynamique

**Réalisations :**
- ✅ Route `/dossier/{id}/modifier` + contrôleur
- ✅ Template `edit.html.twig` avec structure de base
- ✅ FormType `DossierType` avec champs type/nature
- ✅ Service `DossierFields` : configuration champs par nature
- ✅ JavaScript `form.js` : gestion affichage dynamique (vanilla JS)
- ✅ Filtre Twig `should_display_field`
- ✅ Services Plat'AU (calcul temps restant + DTO)

**Commits :**
- `feat(dossier): infrastructure édition Phase 0`

---

### Phase 1 : Réunions - Type 4 (10/12/2025)

**Objectif :** Implémenter le type le plus simple (3 natures, peu de champs)

**Natures implémentées :**

| Nature | Libellé | Champs |
|--------|---------|--------|
| **31** | Réunion locaux SDIS | 7 champs |
| **32** | Réunion extérieur SDIS | 8 champs (+ lieuReunion) |
| **43** | Réunion téléphonique | 7 champs |

**Champs implémentés :**
```
dateInsert, objet, dateReunion, dateReponse, 
preventionnistes (placeholder), demandeur, observations
```

**Corrections importantes :**
- `DATEREUN` → `dateReunion` (≠ `dateVisite` pour les visites)
- `DATEREP` → `dateReponse` (pas `dateReception`)
- `OBSERVATION` → `observations` (propriété correcte)

**Structure template :**
- H4 : Type et nature du dossier
- H4 : Informations sur le dossier
- H4 : Préventionnistes
- H4 : Informations complémentaires

**Commits :**
- `feat(dossier): édition réunions Phase 1`
- `fix(dossier): corrections Phase 1 et standardisation`
- `fix(dossier): correction ordre champs et distinction dateReunion vs dateVisite`
- `fix(dossier): ajout champs manquants et restructuration sections réunions`
- `fix(dossier): corrections systématiques Phase 1 - dateReponse + observations`

---

### Phase 2 : Courriers/Courriels - Type 7 (10/12/2025)

**Objectif :** Implémenter tous les courriers (12 natures, mêmes champs)

**Natures implémentées :**

| Nature | Libellé |
|--------|---------|
| **49** | Rapport d'organisme agréé |
| **50** | Demande visite hors programme |
| **51** | Avis écrit motivé |
| **52** | Lettre |
| **53** | Consultation PLU |
| **54** | Demande de renseignements |
| **55** | Mise en demeure |
| **57** | Demande visite technique |
| **58** | Demande visite inopinée |
| **59** | Demande visite périodique |
| **60** | Demande visite de réception |
| **65** | Autorisation de travaux |

**Champs implémentés (identiques pour toutes) :**
```
dateInsert, objet, dateMairie, dateEnvoiTransit, dateReponse, 
dateReception, demandeur, dateTransfertCommission, 
dateReceptionCommission, preventionnistes, observations
```

**Nouveaux champs FormType :**
- `dateMairie` : Date réception mairie
- `dateEnvoiTransit` : Date envoi/transit
- `dateTransfertCommission` : Date transfert commission
- `dateReceptionCommission` : Date réception commission

**Points d'attention :**
- ⚠️ `DATEREP` = `dateReponse` (date de réponse)
- ⚠️ `DATESDIS` = `dateReception` (date réception SDIS) - **champs distincts !**
- ⚠️ `NUMCHRONO` non implémenté (absent de l'entité Dossier)

**Commits :**
- `feat(dossier): édition courriers/courriels (natures 49-55) - Phase 2`
- `feat(dossier): ajout natures courriers manquantes (59, 57, 58, 50, 60, 65)`

---

## ⏳ Phases à réaliser

### Phase 3 : Visites périodiques - Type 2

**Priorité :** Haute  
**Complexité :** Moyenne  
**Natures cibles :** 21, 28, 29

**Nouveaux champs attendus :**
- `commission` : Select commission
- `dateVisite` : Date de visite (≠ dateReunion)
- `dateCommission` : Date passage commission
- `avisDiffere` : Checkbox avis différé
- `horsDelai` : Checkbox hors délai
- `dateReceptionRvrat` : Date réception RVRAT
- `dateLimitePrescription` : Date échéance levée prescriptions
- `incomplet` : Checkbox dossier incomplet
- `avisCommission` : Select avis commission
- `absenceDeQuorum` : Checkbox absence quorum
- `nePeutSePrononcer` : Checkbox ne peut se prononcer

**Difficulté :**
- Gestion des relations avec commission (EntityType)
- Affichage conditionnel section avis selon checkboxes
- Plusieurs dates à gérer (visite + commission)

---

### Phase 4 : Études simples - Type 1

**Priorité :** Moyenne  
**Complexité :** Moyenne à élevée  
**Natures cibles :** PC, AT (sans dérogations d'abord)

**Nouveaux champs attendus :**
- `documentsUrbanisme` : Collection de documents
- `numeroChronologique` : Numéro chronologique (si implémenté)
- `dateSecretariat` : Date réception secrétariat
- `serviceInstructeur` : Service instructeur
- `descriptifGeneral` : Descriptif général
- `descriptifEffectifs` : Descriptif effectifs
- `coordonneesSsi` : Coordonnées SSI

**Difficulté :**
- Gestion collection documents urbanisme
- Nombreux champs à afficher/masquer
- Logique métier plus complexe

---

### Phase 5 : Interventions - Type 5

**Priorité :** Moyenne  
**Complexité :** Faible à moyenne  
**Natures cibles :** 37, 38, 39

**Nouveaux champs attendus :**
- `operationSdis` : Opération SDIS
- `rcci` : RCCI
- `rex` : REX
- `numeroIntervention` : N° intervention
- `dateIntervention` : Date intervention
- `heureIntervention` : Heure intervention
- `dureeIntervention` : Durée intervention

---

### Phase 6 : Autres types

**À planifier selon priorités métier**

---

## 📚 Documentation créée

### Fichiers techniques

| Fichier | Description | Status |
|---------|-------------|--------|
| `PLAN_MIGRATION_EDITION_DOSSIER.md` | Plan général incrémental | ✅ À jour |
| `AVANCEMENT_EDITION_DOSSIER.md` | Suivi d'avancement (ce fichier) | ✅ Créé |
| `MAPPING_LEGACY_REFERENCE.md` | Mapping complet legacy → Symfony | ✅ À jour |

### Fichiers guides

| Fichier | Description | Status |
|---------|-------------|--------|
| `docs/guides/affichage-dossiers.md` | Guide utilisateur consultation | ✅ À jour (Plat'AU) |
| `tech/migration-affichage-dossiers.md` | Documentation technique consultation | ✅ À jour (Plat'AU) |

---

## 🔑 Points clés pour reprendre demain

### Infrastructure en place

✅ **Architecture JavaScript :**
- Fichier : `public/js/dossier/form.js`
- Principe : Affichage dynamique basé sur configuration JSON
- Configuration : Passée via `data-fields-config` dans le template
- Champs gérés : Liste dans `conditionalFields`

✅ **Service DossierFields :**
- Fichier : `src/Service/DossierFields.php`
- Méthode principale : `getFieldsForNature(int $nature): array`
- Retourne : Liste des champs à afficher pour une nature donnée
- Noms : Toujours en camelCase Symfony (pas de legacy)

✅ **Template de base :**
- Fichier : `templates/dossier/edit.html.twig`
- Structure : Sections H4 (Type/nature, Infos, Préventionnistes, Infos complémentaires)
- Champs : Tous avec ID `field-{nomChamp}` pour JavaScript

### Bonnes pratiques établies

1. **Nommage :** Toujours utiliser les noms Symfony (camelCase), jamais les noms legacy (UPPERCASE)
2. **Mapping :** Documenté dans `MAPPING_LEGACY_REFERENCE.md` (référence uniquement)
3. **Ordre des champs :** Suivre strictement l'ordre du legacy (vérifié dans DossierController.php)
4. **Validation :** PHPStan + CS après chaque phase
5. **Commits :** Un commit par phase ou correction importante

### Pièges à éviter

⚠️ **Confusion DATEREP vs DATESDIS :**
- `DATEREP` → `dateReponse` (date de réponse)
- `DATESDIS` → `dateReception` (date réception SDIS)
- Ce sont **deux propriétés distinctes** !

⚠️ **Confusion dateReunion vs dateVisite :**
- `DATEREUN` → `dateReunion` (pour réunions - type 4)
- `DATEVISITE` → `dateVisite` (pour visites - types 2, 3)
- Ce sont **deux propriétés distinctes** !

⚠️ **Nom de propriété observations :**
- Propriété entité : `$observations` (pas `$observationDossier`)
- Legacy : `OBSERVATION_DOSSIER`
- Symfony : `observations`

### Chaîne de vérification complète

Pour chaque nouvelle nature à implémenter :

1. ✅ Vérifier legacy : `grep "'XX'" prevarisc/application/controllers/DossierController.php`
2. ✅ Vérifier entité : Propriétés et colonnes BDD exactes
3. ✅ Mettre à jour `DossierFields.php` : Bon ordre + bons noms Symfony
4. ✅ Ajouter champs `FormType` si nécessaire
5. ✅ Mettre à jour template : Champs avec IDs
6. ✅ Mettre à jour JavaScript : `conditionalFields`
7. ✅ Valider : PHPStan + CS
8. ✅ Tester visuellement : Changement type/nature
9. ✅ Commit

---

## 📝 Notes importantes

### Préventionnistes

**Status :** Placeholder actuellement  
**Raison :** Nécessite autocomplete multi-sélection  
**À faire :** Phase dédiée ultérieure (après types principaux)

**Affichage actuel :**
```html
<p class='form-control-static text-muted'>
    <em>Fonctionnalité en cours d'implémentation (autocomplete multi-sélection)</em>
</p>
```

### NUMCHRONO

**Status :** Non implémenté  
**Raison :** Champ absent de l'entité Dossier  
**Action :** Ignoré pour l'instant dans toutes les natures

### Technologies utilisées

- **Backend :** Symfony 4, Doctrine ORM
- **Frontend :** JavaScript vanilla + jQuery existant (pas de Node/npm/Webpack)
- **CSS :** Bootstrap 3 (déjà présent)
- **Templates :** Twig 2

---

## 🎯 Objectifs semaine prochaine

1. **Phase 3 (Visites)** : Implémenter natures 21, 28, 29
2. **Phase 4 (Études)** : Démarrer les études simples (PC/AT sans dérogations)
3. **Tests utilisateurs** : Faire tester les 15 natures existantes

---

**Dernière modification :** 10 décembre 2025 - 17h15  
**Auteur :** Équipe Prevarisc  
**Prochaine session :** 11 décembre 2025
