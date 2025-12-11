# 🚀 Reprendre ici - Édition Dossiers

**Date :** 11 décembre 2025 - 16h00  
**Session précédente :** 11 décembre 2025 - Matin

---

## ✅ Ce qui est fait

### Natures opérationnelles - Affichage ET Édition (15)

**Réunions (3) :**
- 31, 32, 43 ✅

**Courriers (12) :**
- 49, 50, 51, 52, 53, 54, 55, 57, 58, 59, 60, 65 ✅

### Améliorations session du matin

**Entité Dossier enrichie** :
- ✅ `descriptifGeneral` (DESCGEN_DOSSIER)
- ✅ `descriptifEffectifs` (DESCEFF_DOSSIER)  
- ✅ `coordonneesSsi` (COORDSSI_DOSSIER)

**DossierFields corrigé** :
- ✅ `observationDossier` → `observations`
- ✅ Natures 21, 28, 29 : `dateReception` → `dateReponse`

**Tests fonctionnels améliorés** :
- ✅ Support `has*()` et `is*()` pour booléens
- ✅ Labels corrects (Commission concernée, etc.)
- ✅ Nouveau test comparaison Legacy vs Symfony

---

## 🎯 Phase 3 : Visites périodiques - ÉDITION

### Approche TDD validée

**Stratégie** :
1. ✍️ **Écrire test fonctionnel** pour l'édition → Échec (RED)
2. 🔧 **Implémenter** le code (FormType, template, JS) → Succès (GREEN)
3. ♻️ **Refactorer** et valider (PHPStan, CS)
4. 📝 **Commit**

### Natures à implémenter (3)

| Nature | Libellé | Type | Priorité |
|--------|---------|------|----------|
| **21** | Périodique | Visite de commission | 1️⃣ |
| **28** | Contrôle | Groupe de visite | 2️⃣ |
| **29** | Inopinée | Groupe de visite | 3️⃣ |

### Champs attendus (basés sur le legacy)

#### Nature 21 - Visite périodique
```
DATEINSERT, COMMISSION, DESCGEN, DESCEFF, DATEVISITE, DATEREP, 
PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM, AVIS, AVIS_COMMISSION, 
OBSERVATION, DELAIPRESC
```

**Champs Symfony** :
- `dateInsert`, `commission`, `descriptifGeneral`, `descriptifEffectifs`
- `dateVisite`, `dateReponse`, `preventionnistes`
- `avisDiffere`, `absenceDeQuorum`
- `avis`, `avisCommission`, `observations`
- `dateLimitePrescription`

#### Nature 28 - Visite de contrôle (groupe)
```
DATEINSERT, OBJET, COMMISSION, DESCGEN, DESCEFF, DATECOMM, DATEVISITE, 
AVIS, COORDSSI, DATEREP, PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM, 
AVIS_COMMISSION, OBSERVATION, DELAIPRESC
```

**Champs Symfony** :
- Nature 21 + `objet`, `dateCommission`, `coordonneesSsi`

#### Nature 29 - Visite inopinée (groupe)
```
DATEINSERT, OBJET, COMMISSION, DESCGEN, DESCEFF, DATECOMM, DATEVISITE, 
AVIS, DATEREP, PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM, AVIS_COMMISSION, 
OBSERVATION, DELAIPRESC
```

**Champs Symfony** :
- Nature 28 - `coordonneesSsi`

---

## 📋 Plan d'action Nature 21

### 1. Test fonctionnel (RED) ✍️

**Créer** : `tests/Functional/DossierEditVisitesTest.php`

Test doit vérifier :
- [ ] GET `/dossier/{id}/modifier` affiche le formulaire
- [ ] Tous les champs attendus sont présents
- [ ] Champs pré-remplis avec valeurs actuelles
- [ ] POST sauvegarde correctement
- [ ] Redirection après sauvegarde
- [ ] Données persistées en base

**Lancer** : Test doit échouer (champs manquants)

### 2. Implémentation (GREEN) 🔧

#### DossierType.php
Ajouter champs pour nature 21 :
```php
- descriptifGeneral (TextareaType)
- descriptifEffectifs (TextareaType)
- avisDiffere (CheckboxType)
- absenceDeQuorum (CheckboxType)
```

#### edit.html.twig
Ajouter blocs d'affichage conditionnel

#### form.js
Ajouter dans `conditionalFields` :
```javascript
21: ['commission', 'descriptifGeneral', 'descriptifEffectifs', 
     'dateVisite', 'preventionnistes', 'avisDiffere', 
     'absenceDeQuorum', 'avis', 'avisCommission', 
     'observations', 'dateLimitePrescription']
```

**Lancer** : Test doit passer ✅

### 3. Validation ✅

```bash
castor symfony:analyse
castor symfony:cs
castor symfony:test
```

### 4. Commit 📝

```bash
git add -A
git commit -m "feat(dossier): édition visite périodique (nature 21) - Phase 3

TDD:
- Test fonctionnel d'édition pour nature 21
- Ajout champs descriptifGeneral, descriptifEffectifs
- Ajout checkboxes avisDiffere, absenceDeQuorum
- Configuration JavaScript conditionnelle
- Tests passent ✅"
```

---

## 🔑 Commandes utiles

### Tests
```bash
# Test spécifique
castor symfony:test tests/Functional/DossierEditVisitesTest.php --filter testEditVisitePeriodique

# Tous les tests
castor symfony:test
```

### Validation
```bash
castor symfony:analyse  # PHPStan
castor symfony:cs       # Rector + CS Fixer
```

### Debug
```bash
# Voir les champs d'une nature
grep "'21'" src/Service/DossierFields.php
```

---

## 📚 Fichiers clés

| Fichier | Usage |
|---------|-------|
| `tests/Functional/DossierEditVisitesTest.php` | **Tests TDD** (à créer) |
| `src/Form/DossierType.php` | Définition formulaire |
| `templates/dossier/edit.html.twig` | Template édition |
| `public/js/dossier/form.js` | JavaScript conditionnel |
| `src/Service/DossierFields.php` | Config champs (affichage) |
| `src/Entity/Dossier.php` | Entité (propriétés OK ✅) |

---

## ⚠️ Rappels importants

### Nommage Symfony (camelCase)
✅ **Utiliser** : `dateVisite`, `descriptifGeneral`, `avisDiffere`  
❌ **Ne PAS utiliser** : `DATEVISITE`, `DESCGEN`, `DIFFEREAVIS`

### Booléens
- Convention : `has*()` et `is*()` dans l'entité
- Affichage : Seulement si `true`
- Édition : Checkbox toujours présente

### DATEREP vs DATESDIS
- `DATEREP` → `dateReponse` ✅ (pour visites)
- `DATESDIS` → `dateReception` (pour études)

---

## 🎯 Objectif de la session

**Implémenter l'édition des 3 natures de visites périodiques en TDD**

**Résultat attendu** :
- 18 natures opérationnelles (15 actuelles + 3 nouvelles)
- Tests d'édition passent pour 21, 28, 29
- Code validé (PHPStan + CS)

**Ordre** : Nature 21 → 28 → 29

---

Prêt pour le TDD ! 🚀 On commence par **écrire le test** pour la nature 21 !

