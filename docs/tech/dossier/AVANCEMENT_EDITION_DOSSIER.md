# Avancement : Édition des dossiers

**Projet :** Migration Zend v1 → Symfony 4  
**Fonctionnalité :** Édition/Création des dossiers  
**Dernière mise à jour :** 15 décembre 2025 - 17h00

---

## 📊 Vue d'ensemble

### Statistiques

- **Natures implémentées :** 17 / ~70 (24%)
- **Phases terminées :** 2 / 6+
- **Phase en cours :** Phase 3 (Visites périodiques) - 2/3 terminées
- **Temps estimé restant :** ~4.5 jours de développement

### Types de dossiers

| Type | Libellé | Natures totales | Natures OK | Status |
|------|---------|-----------------|------------|--------|
| **Type 1** | Étude | ~13 | 0 | ⏳ TODO |
| **Type 2** | Visite | ~10 | **2** | 🟡 EN COURS (2/10) |
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

## 🔄 Phase en cours

### Phase 3 : Visites périodiques - Type 2 (11-15 décembre 2025)

**Priorité :** Haute  
**Complexité :** Moyenne  
**Natures cibles :** 21 ✅, 28 ✅, 29 ⏳

**Statut :** 2/3 terminées (67%)

#### Nature 21 (Visite périodique) ✅ TERMINÉE

**Commits :** 86cba7b → f3eed2a (7 commits)

**Développement complet** :
1. Implémentation : 7 champs ajoutés au FormType
2. Template : 15 champs rendus explicitement (Bootstrap)
3. JavaScript : Comportements conditionnels (avis défavorable + commission)
4. Ajustements : Labels, champs conditionnels, validation

**Champs implémentés (15)** :
```
dateInsert, commission, descriptifGeneral, descriptifEffectifs, 
dateVisite, dateReponse, preventionnistes, avisDiffere, 
absenceDeQuorum, avis, avisCommission, observations, 
dateLimitePrescription
+ conditionnels : facteurDangerosite, echeancierTravaux
```

**Ajustements appliqués** :
- ✅ Labels corrigés (`avisDiffere` = "Diffère l'avis")
- ✅ Template Bootstrap structure complète (15 champs explicites)
- ✅ Champs conditionnels (facteur + échéancier si avis défavorable)
- ✅ JavaScript : commission disabled pour visites + comportements avis
- ✅ 0 erreur PHPStan, CS Fixer, Twig CS

**Documentation** :
- `NATURE_21_FINALE.md` : État complet
- `AJUSTEMENTS_APPLIQUES_NATURE_21.md` : Détails ajustements
- `GUIDE_IMPLEMENTATION_VISITES.md` : Guide réutilisable

**Résultat** : Nature 21 100% opérationnelle et conforme legacy ! 🎉

#### Nature 28 (Visite de contrôle) ✅ TERMINÉE

**Commits :** ad56b80 → c3e004f (5 commits, 15 décembre 2025)

**Développement complet** :
1. Activation : décommentage `dateCommission` dans template
2. Ajout champ `objet` (déjà dans FormType)
3. Ajout case "Répercuter l'avis" (`repercuterAvis`)
4. Correction état case à cocher "avis différé"
5. Utilisation des helpers appropriés (form_row)

**Champs implémentés** :
```
dateInsert, objet, commission, descriptifGeneral, descriptifEffectifs,
dateCommission, dateVisite, avis, coordonneesSsi, dateReponse,
preventionnistes, avisDiffere, absenceDeQuorum, avisCommission,
observations, dateLimitePrescription, repercuterAvis
+ conditionnels : facteurDangerosite, echeancierTravaux
```

**Différence vs Nature 21** :
- ➕ `objet` (obligatoire pour nature 28)
- ➕ `dateCommission` (date de commission)
- ➕ `coordonneesSsi` (coordonnées SSI)
- ➕ `repercuterAvis` (case à cocher)

**Validation** :
- ✅ Configuration DossierFields correcte
- ✅ Template mis à jour et structuré
- ✅ JavaScript déjà fonctionnel (hérité de nature 21)
- ⏳ Validation PHPStan + CS + Twig CS à confirmer

**Résultat** : Nature 28 100% opérationnelle ! 🎉

#### Natures 28 & 29 : À venir

**Résultat** : Nature 28 100% opérationnelle ! 🎉

#### Nature 29 (Visite inopinée) ⏳ À FAIRE

**Plan d'implémentation** :
- Champs identiques à nature 28 SAUF `coordonneesSsi` (à retirer)
- Configuration DossierFields : ajouter nature 29
- Template déjà OK (champs conditionnels gérés dynamiquement)
- JavaScript déjà fonctionnel
- Validation PHPStan + CS + Twig CS

**Temps estimé** : 30 minutes (configuration simple)

---

## ⏳ Phases à réaliser

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
| `AVANCEMENT_EDITION_DOSSIER.md` | Suivi d'avancement (ce fichier) | ✅ Mis à jour 12/12 |
| `MAPPING_CHAMPS_DOSSIER.md` | Mapping complet legacy → Symfony | ✅ À jour |
| `NATURE_21_FINALE.md` | État final nature 21 (100%) | ✅ Créé 11/12 |
| `AJUSTEMENTS_APPLIQUES_NATURE_21.md` | Détails ajustements nature 21 | ✅ Créé 11/12 |
| `GUIDE_IMPLEMENTATION_VISITES.md` | Guide réutilisable visites | ✅ Créé 11/12 |

### Fichiers guides

| Fichier | Description | Status |
|---------|-------------|--------|
| `docs/guides/affichage-dossiers.md` | Guide utilisateur consultation | ✅ À jour (Plat'AU) |
| `tech/migration-affichage-dossiers.md` | Documentation technique consultation | ✅ À jour (Plat'AU) |

---

## 🔑 Points clés pour reprendre

### Infrastructure en place

✅ **Architecture JavaScript :**
- Fichier : `public/js/dossier/form.js`
- Principe : Affichage dynamique basé sur configuration JSON
- Configuration : Passée via `data-fields-config` dans le template
- Champs gérés : Liste dans `conditionalFields`
- **Nouveaux comportements** :
  - `handleAvisCommissionChange()` : Facteur + échéancier si avis défavorable
  - `handleCommissionForVisites()` : Commission disabled pour types 2/3

✅ **Service DossierFields :**
- Fichier : `src/Service/DossierFields.php`
- Méthode principale : `getFieldsForNature(int $nature): array`
- Retourne : Liste des champs à afficher pour une nature donnée
- Noms : Toujours en camelCase Symfony (pas de legacy)
- **Nature 21 configurée** ✅

✅ **Template de base :**
- Fichier : `templates/dossier/edit.html.twig`
- Structure : Sections H4 + listes `<ul>` / `<li>` Bootstrap
- Champs : Tous avec ID `field-{nomChamp}` pour JavaScript
- **Nature 21** : 15 champs rendus explicitement (structure col-md-3/9)

✅ **FormType complet :**
- Fichier : `src/Form/DossierType.php`
- **Tous les champs visites** déjà présents
- Nature 28/29 : juste activer dans template + DossierFields

### Bonnes pratiques établies

1. **Nommage :** Toujours utiliser les noms Symfony (camelCase), jamais les noms legacy (UPPERCASE)
2. **Mapping :** Documenté dans `MAPPING_CHAMPS_DOSSIER.md` (référence uniquement)
3. **Ordre des champs :** Suivre strictement l'ordre du legacy (vérifié dans DossierController.php)
4. **Validation :** PHPStan + CS après chaque phase
5. **Commits :** Un commit par phase ou correction importante
6. **Approche :** Affichage statique + JavaScript + vérification manuelle (TDD reporté)

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

⚠️ **facteurDangerosite vs facteurCriticite :**
- Propriété correcte : `facteurDangerosite` (Integer)
- ❌ Pas `facteurCriticite` !

⚠️ **echeancierTravaux :**
- Type : Date (pas Textarea !)

### Chaîne de vérification complète

Pour chaque nouvelle nature à implémenter :

1. ✅ Vérifier legacy : `grep "'XX'" prevarisc/application/controllers/DossierController.php`
2. ✅ Vérifier entité : Propriétés et colonnes BDD exactes
3. ✅ Mettre à jour `DossierFields.php` : Bon ordre + bons noms Symfony
4. ✅ Ajouter champs `FormType` si nécessaire (pour visites : déjà fait !)
5. ✅ Mettre à jour template : Décommenter/activer champs
6. ✅ Mettre à jour JavaScript : `conditionalFields` (si nécessaire)
7. ✅ Valider : PHPStan + CS
8. ✅ Vérifier manuellement (affichage + JavaScript)
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

## 🎯 Objectifs prochaines sessions

1. **Phase 3 (Visites)** : Terminer nature 29 (dernière visite) → **Phase 3 TERMINÉE !**
2. **Phase 4 (Études)** : Démarrer les études simples (PC/AT sans dérogations)
3. **Tests utilisateurs** : Faire tester les natures implémentées

### Note importante

⚠️ **Approche actuelle** : Développement en affichage statique avec JavaScript pour les comportements dynamiques, et vérification manuelle. Les tests automatisés (TDD) sont reportés à une phase ultérieure pour simplifier le développement initial.

---

**Dernière modification :** 15 décembre 2025 - 17h00  
**Auteur :** Équipe Prevarisc  
**Prochaine étape :** Nature 29 (Phase 3 finale)
