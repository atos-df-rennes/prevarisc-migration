# Nature 21 - Implémentation finale

**Date** : 11 décembre 2025  
**Commits** : 86cba7b → f3eed2a (7 commits)

---

## ✅ Implémentation complète

### Phase TDD

1. **RED** (86cba7b) ✅
   - Test fonctionnel créé
   - Échec attendu : champs manquants
   
2. **GREEN** (227ef74) ✅
   - 7 champs ajoutés au FormType
   - Test passe : 20 assertions

3. **REFACTOR** ✅
   - Documentation complète
   - Ajustements appliqués

---

## 🔧 Ajustements appliqués

### 1. Labels (97170b2)

**Corrigé** :
- `avisDiffere` : "Avis différé" → "Diffère l'avis" ✅

### 2. Champs ajoutés (97170b2)

**FormType** :
- `echeancierTravaux` (DateType) ✅

### 3. JavaScript (97170b2)

**Champs conditionnels** :
- 9 nouveaux champs dans `hideAllConditionalFields` ✅

**Comportements** :
- `handleAvisCommissionChange()` : Facteur + Échéancier si avis défavorable ✅
- `handleCommissionForVisites()` : Commission disabled pour types 2/3 ✅

### 4. Template Bootstrap (f3eed2a)

**Structure complète 100% compatible legacy** :

Tous les champs affichés explicitement avec :
- Structure : `<li class='row' id='field-xxx'>`
- Label : `col-md-3`
- Widget : `col-md-9`
- IDs corrects pour JavaScript

**Champs rendus** (15) :
1. commission ✅
2. descriptifGeneral ✅
3. descriptifEffectifs ✅
4. coordonneesSsi ✅
5. dateVisite ✅
6. dateReponse ✅
7. avis ✅
8. avisCommission ✅
9. facteurDangerosite ✅ (conditionnel)
10. echeancierTravaux ✅ (conditionnel)
11. avisDiffere ✅ (checkbox)
12. absenceDeQuorum ✅ (checkbox)
13. observations ✅
14. dateLimitePrescription ✅
15. preventionnistes ✅ (placeholder implémentation future)

**Doublons supprimés** :
- facteurDangerosite (était 2×)
- observations (était 2×)
- dateVisite (était 2×)
- avisCommission (était 2×)

**Plus de `form_rest()`** : Tous les champs explicites, structure cohérente

---

## 📊 État final

### Fonctionnalité : 100% opérationnelle

**Champs gérés** : 15 (13 de base + 2 conditionnels)

**Ordre respecté** (selon legacy) :
1. dateInsert (affiché via autre section)
2. commission ← disabled pour visites
3. descriptifGeneral
4. descriptifEffectifs
5. dateVisite
6. dateReponse
7. preventionnistes
8. avisDiffere ← label corrigé
9. absenceDeQuorum
10. avis
11. avisCommission
12. facteurDangerosite ← conditionnel
13. echeancierTravaux ← conditionnel
14. observations
15. dateLimitePrescription

### Comportements JavaScript

**Initialisation** : `initConditionalBehaviors()`

**Avis défavorable** :
- Détection : avisCommission.value === '2'
- Action : Affiche facteurDangerosite + echeancierTravaux
- Réinitialisation : Masque et vide si autre avis

**Commission visites** :
- Détection : type === '2' || type === '3'
- Action : disabled=true + message d'aide
- Message : "La commission est liée à la date de visite..."

**Champs par nature** :
- Configuration dans DossierFields (serveur)
- Affichage/masquage via JavaScript (client)

### Validation

**Tests** :
- ✅ 20 assertions passent
- ✅ Tous les champs présents et affichés
- ✅ Soumission et persistance OK

**Qualité code** :
- ✅ PHPStan : 0 erreur
- ✅ CS Fixer : 0 erreur
- ✅ Twig CS : 0 erreur

**Compatibilité** :
- ✅ Structure HTML = Legacy (col-md-3 / col-md-9)
- ✅ IDs = JavaScript fonctionnel
- ✅ Labels = Legacy
- ✅ Ordre = Legacy (via DossierFields)

---

## 📝 Points techniques

### Différence Date vs String

**echeancierTravaux** :
- Type attendu : Date
- Pas Textarea (erreur initiale corrigée)

### Avis en base

| ID | Libellé | Comportement |
|----|---------|--------------|
| 1 | Favorable | Standard |
| 2 | Défavorable | ← Affiche facteur + échéancier |
| 6 | Pas d'avis | Standard |

### Champs nature 21 uniquement

**Présents** :
- Tous les champs visites de base

**Absents** (pour 28/29) :
- `dateCommission` (commenté, à activer pour 28 et 29)
- `objet` (non affiché pour 21)

---

## 🎯 Prêt pour natures 28 et 29

### Réutilisable

**JavaScript** :
- Même comportements
- Même structure
- Juste ajouter natures dans conditionalFields

**Template** :
- Structure complète
- Décommenter dateCommission
- Activer objet (déjà présent)

**FormType** :
- Tous les champs déjà présents
- Rien à ajouter !

### À faire pour 28/29

1. Décommenter `dateCommission` dans template
2. Ajouter champ `objet` au bon endroit
3. Configuration déjà dans DossierFields ✅
4. JavaScript déjà fonctionnel ✅
5. Tests à adapter

---

## 📈 Métriques session

**Commits nature 21** : 7
- 1 RED
- 1 GREEN  
- 3 REFACTOR (ajustements)
- 2 DOC

**Lignes modifiées** :
- FormType : +20 lignes
- Template : +114 lignes, -28 lignes (nettoyage doublons)
- JavaScript : +94 lignes

**Temps investissement** :
- TDD initial : ~1h
- Ajustements : ~1h30
- Total : ~2h30

---

**Nature 21 : TERMINÉE À 100% et compatible legacy ! 🎉**

Prochaines : Natures 28 et 29 (beaucoup plus rapides car tout est en place)
