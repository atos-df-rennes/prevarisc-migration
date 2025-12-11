# 🚀 Reprendre ici - Édition Dossiers

**Date :** 11 décembre 2025 - 17h00  
**Session précédente :** 11 décembre 2025 - Après-midi (Phase 3 démarrée)

---

## ✅ Ce qui est fait

### Natures opérationnelles - Affichage ET Édition (16)

**Réunions (3) :**
- 31, 32, 43 ✅

**Courriers (12) :**
- 49, 50, 51, 52, 53, 54, 55, 57, 58, 59, 60, 65 ✅

**Visites (1) :**
- **21 (Périodique) ✅ - Implémentée en TDD aujourd'hui !**

### Session après-midi : Nature 21 en TDD

**Phase RED** ✅
- Test fonctionnel créé (`DossierEditVisitesTest`)
- Test échoue comme prévu
- Commit

**Phase GREEN** ✅  
- 7 champs ajoutés au FormType
- Test passe (20 assertions)
- PHPStan + CS ✅
- Commit

**Phase REFACTOR** ✅
- Documentation complète des ajustements nécessaires
- Guide d'implémentation pour natures suivantes

### Documentation créée

1. **AJUSTEMENTS_NATURE_21.md** : Points à corriger
2. **GUIDE_IMPLEMENTATION_VISITES.md** : Guide complet avec code

---

## ⚠️ Points identifiés à ajuster

### Nature 21 - À corriger avant natures 28/29

1. **Ordre des champs** ❌
   - Actuel : incohérent
   - Attendu : ordre du legacy (voir GUIDE_IMPLEMENTATION_VISITES.md)

2. **Labels incorrects** ❌
   - `avisDiffere` : "Avis différé" → "Diffère l'avis"

3. **Template** ❌
   - Structure : label gauche (col-md-3) / champ droite (col-md-9)
   - Groupement par sections (panels Bootstrap)

4. **Champs conditionnels manquants** ❌
   - `facteurDangerosite` : si avis commission = Défavorable (ID=2)
   - `echeancierTravaux` : si avis commission = Défavorable (ID=2)

5. **JavaScript** ❌
   - Commission disabled pour types 2 et 3 (visites)
   - Affichage conditionnel facteur/échéancier

---

## 🎯 Prochaine étape : Ajustements + Natures 28 & 29

### Plan de travail

#### 1. Appliquer les ajustements à Nature 21

**Voir fichier** : `docs/tech/dossier/GUIDE_IMPLEMENTATION_VISITES.md`

**À faire** :
- [ ] Corriger label `avisDiffere`
- [ ] Réorganiser ordre des champs dans FormType
- [ ] Vérifier facteurDangerosite et echeancierTravaux
- [ ] Créer structure template propre
- [ ] Implémenter JavaScript (avis défavorable + commission)
- [ ] Tester visuellement

#### 2. Implémenter Nature 28 (Visite contrôle)

**Champs spécifiques vs Nature 21** :
- ✅ Tous les champs de nature 21
- ➕ `objet`
- ➕ `dateCommission`
- ➕ `coordonneesSsi`

**Ordre** (legacy) :
```
DATEINSERT, OBJET, COMMISSION, DESCGEN, DESCEFF, DATECOMM, DATEVISITE, 
AVIS, COORDSSI, DATEREP, PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM, 
AVIS_COMMISSION, OBSERVATION, DELAIPRESC
```

**Démarche TDD** :
1. Copier/adapter test nature 21
2. Lancer → RED
3. Ajuster conditionalFields
4. Lancer → GREEN
5. Valider + Commit

#### 3. Implémenter Nature 29 (Visite inopinée)

**Différence vs Nature 28** :
- Nature 28 - `coordonneesSsi` (ce champ n'existe pas pour 29)

**Ordre** (legacy) :
```
DATEINSERT, OBJET, COMMISSION, DESCGEN, DESCEFF, DATECOMM, DATEVISITE, 
AVIS, DATEREP, PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM, AVIS_COMMISSION, 
OBSERVATION, DELAIPRESC
```

**Démarche TDD** : Même que nature 28

---

## 📋 Informations clés vérifiées

### IDs en base
- Avis Favorable : **1**
- Avis Défavorable : **2** ← Trigger champs conditionnels
- Pas d'avis : **6**

### Propriétés entité
- ✅ `facteurDangerosite` (Integer) - PAS `facteurCriticite` !
- ✅ `echeancierTravaux` (Date) - PAS Textarea !

### Ordre Nature 21 (référence)
```
1. dateInsert
2. commission
3. descriptifGeneral
4. descriptifEffectifs
5. dateVisite
6. dateReponse
7. preventionnistes
8. avisDiffere
9. absenceDeQuorum
10. avis
11. avisCommission
12. observations
13. dateLimitePrescription

+ Conditionnels (avis défavorable):
14. facteurDangerosite
15. echeancierTravaux
```

---

## 📚 Fichiers clés

| Fichier | Usage |
|---------|-------|
| `docs/tech/dossier/GUIDE_IMPLEMENTATION_VISITES.md` | **Guide complet avec code** |
| `docs/tech/dossier/AJUSTEMENTS_NATURE_21.md` | Points à corriger |
| `tests/Functional/DossierEditVisitesTest.php` | Tests TDD |
| `src/Form/DossierType.php` | Formulaire (à réorganiser) |
| `templates/dossier/edit.html.twig` | Template (à restructurer) |
| `public/js/dossier/form.js` | JavaScript (à compléter) |

---

## 🔧 Commandes utiles

### Tests
```bash
# Test nature 21
castor symfony:test tests/Functional/DossierEditVisitesTest.php::testEditVisitePeriodique

# Tous tests fonctionnels
castor symfony:test tests/Functional/
```

### Validation
```bash
castor symfony:analyse  # PHPStan
castor symfony:cs       # CS Fixer
```

### Référence legacy
```bash
# Voir champs d'une nature
grep "'21'" prevarisc/application/controllers/DossierController.php

# Voir labels
grep -i "diffère\|criticité" prevarisc/application/views/scripts/dossier/index.phtml
```

---

## 🎯 Objectif session suivante

**Finaliser Phase 3 : Visites périodiques**

1. ✅ **Ajuster Nature 21**
   - Template structure Bootstrap
   - JavaScript comportements
   - Labels corrects
   - Ordre champs

2. ✅ **Implémenter Nature 28** (TDD)
   - Test RED
   - Code GREEN
   - Validation

3. ✅ **Implémenter Nature 29** (TDD)
   - Test RED
   - Code GREEN
   - Validation

**Résultat attendu** :
- 18 natures opérationnelles (16 actuelles + 2 nouvelles)
- Nature 21 100% conforme legacy
- Code propre et testé

---

## 📝 Notes importantes

### Comportements JavaScript requis

1. **Avis défavorable** :
   - Écouter changement `avisCommission`
   - Si ID = 2 → Afficher facteurDangerosite + echeancierTravaux
   - Sinon → Masquer et vider

2. **Commission pour visites** :
   - Si type = 2 ou 3 → Commission disabled
   - Message : "La commission est liée à la date de visite"

3. **Champs conditionnels par nature** :
   - Déjà implémenté dans `conditionalFields`
   - À compléter pour natures 21, 28, 29

### Structure template recommandée

```twig
{# Macro field_row #}
<div class="row form-group" id="field-{{ field.name }}">
    <div class="col-md-3">{{ label }}</div>
    <div class="col-md-9">{{ widget }}</div>
</div>

{# Sections groupées #}
- Panel "Informations générales"
- Panel "Avis et décisions"
  - Champs conditionnels dans div#fields-avis-defavorable
```

---

**Tout est documenté pour reprendre efficacement ! 🚀**

**Prochaine session** : Ajuster nature 21 → Nature 28 → Nature 29 → TERMINÉ Phase 3 ! 💪


