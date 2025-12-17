# 🚀 Reprendre ici - Édition Dossiers

**Date :** 17 décembre 2025 - 09h00  
**Session précédente :** 16 décembre 2025 - Après-midi (Nature 3 Dérogation TERMINÉE ✅)

---

## ✅ Ce qui est fait

### Natures opérationnelles - Affichage ET Édition (22/68)

**Réunions (3) :** 31, 32, 43  
**Courriers (12) :** 49, 50, 51, 52, 53, 54, 55, 57, 58, 59, 60, 65  
**Visites (3) :** 21 (Périodique), 28 (Visite de contrôle), 29 (Visite inopinée)  
**Études (5) :** 1 (PC), 2 (AT), 3 (Dérogation), 4 (Cahier charges SSI), 5 (Cahier charges type T)

### Détails implémentation par nature

**Nature 21 (Périodique)** : Template Bootstrap 15 champs, JS avis défavorable/commission disabled, champs conditionnels (facteurDangerosite, echeancierTravaux)  
**Nature 28 (Visite contrôle)** : Champs nature 21 + objet + dateCommission + coordonneesSsi + repercuterAvis  
**Nature 29 (Visite inopinée)** : Identique nature 28 sans coordonneesSsi  
**Nature 1 (PC)** : Études avec documents urbanisme, dates, commissions  
**Nature 2 (AT)** : Identique nature 1  
**Nature 3 (Dérogation)** : Base PC/AT + 4 champs spécifiques (reglesDerogation, justificationDerogation, mesuresCompensatoires, mesuresComplementaires)  
**Nature 4 (Cahier charges SSI)** : Base études sans documentsUrbanisme ni serviceInstructeur  
**Nature 5 (Cahier charges type T)** : Base nature 4 sans le champ type

Documentation complète : voir `docs/tech/dossier/`

---

## 📝 Notes importantes

⚠️ **Approche actuelle** : Pas de TDD, affichage statique + JavaScript + vérification manuelle. Tests automatisés seront ajoutés ultérieurement.

**JavaScript requis** :
- Avis défavorable (ID=2) → Affiche facteurDangerosite + echeancierTravaux
- Commission disabled pour types visites (2/3) + message
- Champs conditionnels par nature (implémenté dans conditionalFields)

**IDs référence base** : Avis Favorable=1, Défavorable=2, Pas d'avis=6

---

## 📚 Fichiers clés

| Fichier | Usage |
|---------|-------|
| `src/Form/DossierType.php` | Formulaire |
| `templates/dossier/edit.html.twig` | Template Bootstrap 3 |
| `public/js/dossier/form.js` | JavaScript comportements |
| `src/Service/DossierFields.php` | Configuration champs par nature |
| `docs/tech/dossier/GUIDE_IMPLEMENTATION_VISITES.md` | Guide complet visites |
| `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md` | Mapping champs |

---

## 🔧 Commandes utiles

```bash
# Validation (obligatoire avant commit)
castor symfony:analyse  # PHPStan niveau 10
castor symfony:cs       # CS Fixer
castor symfony:test     # Tests PHPUnit

# Référence legacy
grep "'4'" prevarisc/application/controllers/DossierController.php
grep -r "cahier.*charges" prevarisc/application/views/scripts/dossier/
```

---

## 🎯 Objectif prochaine session

**Implémenter Nature 6 : Salon type T**

1. Analyser configuration legacy
2. Identifier champs spécifiques (si différents des natures précédentes)
3. Ajouter au FormType si nécessaire
4. Configurer DossierFields.php (déjà fait)
5. Tester affichage
6. Valider PHPStan + CS + Twig CS

**Résultat attendu** : 23 natures opérationnelles (22 actuelles + nature 6)
