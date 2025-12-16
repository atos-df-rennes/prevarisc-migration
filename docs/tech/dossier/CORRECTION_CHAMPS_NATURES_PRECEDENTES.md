# Gestion des champs conditionnels - Dossiers

> **Date** : 16/12/2024  
> **Contexte** : Migration Zend v1 → Symfony v4  
> **Fichiers legacy** : `prevarisc/application/views/scripts/dossier/index.phtml` (lignes 960-1283)

## Vue d'ensemble

Le legacy utilise **massivement JavaScript** (~2500 lignes) pour gérer l'affichage dynamique des champs selon :
- Le **type** de dossier (Étude, Visite, Groupe de visite)
- La **nature** du dossier (PC, AT, Périodique, etc.)
- L'**avis de la commission** (Favorable, Défavorable, etc.)
- L'**état** du dossier (Complet, Incomplet, Hors délai, etc.)

## Champs avec comportement JavaScript spécial

### 1. ANOMALIE (Analyse de risque) ✅ MIGRÉ

**Legacy** : lignes 1005, 1010
```javascript
if (type == 2 || type == 3) {
    $("#ANOMALIE").show();  // Visite ou Groupe de visite
} else if (type == 1) {
    $("#ANOMALIE").show();  // Étude
}
```

**Règle** : 
- ❌ **Jamais** dans `$listeChamps`
- ✅ Affiché pour **types 1, 2, 3** (indépendamment de la nature)
- ✅ En visualisation : affiché si valeur existe

**Migration** : `form.js` fonction `applyAnalyseDeRisqueDisplay()`

---

### 2. FACTDANGE (Facteur de criticité) ⚠️ PARTIELLEMENT MIGRÉ

**Legacy** : lignes 1002, 1008, 1256-1273
```javascript
// Au chargement (lignes 1000-1011)
if (type == 2 || type == 3) {
    if ($("#AVIS_DOSSIER_COMMISSION").val() == 2) {  // Avis Défavorable
        $("#FACTDANGE").show();
    }
} else if (type == 1 && $("#selectNature").val() == 19) {  // Nature 19 = Levée de réserves
    if ($("#AVIS_DOSSIER_COMMISSION").val() == 2) {
        $("#FACTDANGE").show();
    }
}

// Au changement d'avis (lignes 1256-1273)
$("#AVIS_DOSSIER_COMMISSION").live('change', function() {
    // Même logique dynamique
});
```

**Règles** :
- ❌ **Jamais** dans `$listeChamps`
- ✅ **Type 2 ou 3** + Avis Défavorable (ID=2) → affiché
- ✅ **Type 1** + Nature 19 + Avis Défavorable → affiché
- ✅ Réagit au changement d'avis (dynamique)
- ✅ En visualisation : affiché si valeur existe OU (isSignalement ET Avis Défavorable)

**Migration actuelle** : `form.js` fonction `applyFacteurEcheancierDisplayOnLoad()` (statique uniquement)

**TODO** : Ajouter comportement dynamique au changement d'avis + condition nature 19 pour type 1

---

### 3. ECHEANCIERTRAV (Échéancier de travaux) ⚠️ PARTIELLEMENT MIGRÉ

**Legacy** : lignes 1003, 1261, 1276-1283
```javascript
// Au chargement/changement avis (lignes 1000-1011, 1256-1273)
if (type == 2 || type == 3) {
    if ($("#AVIS_DOSSIER_COMMISSION").val() == 2) {  // Avis Défavorable
        $("#ECHEANCIERTRAV").show();
    }
}

// Gestion checkbox (lignes 1276-1283)
$("#echeanciertrav_check").live('click', function() {
    if ($(this).is(':checked')) {
        $("#ECHEANCIERTRAV_DOSSIER").show();  // Champ date
    } else {
        $("#ECHEANCIERTRAV_DOSSIER").val('').hide();
    }
});
```

**Règles** :
- ❌ **Jamais** dans `$listeChamps`
- ✅ **Type 2 ou 3** + Avis Défavorable (ID=2) → affiché
- ✅ Contient une **checkbox** + champ date (affichage en cascade)
- ✅ Réagit au changement d'avis (dynamique)
- ✅ En visualisation : affiché si valeur existe

**Migration actuelle** : `form.js` fonction `applyFacteurEcheancierDisplayOnLoad()` (statique uniquement)

**TODO** : Ajouter comportement dynamique au changement d'avis + gestion checkbox

---

## Champs conditionnels "normaux" (gérés par $listeChamps) ✅

Ces champs fonctionnent déjà correctement dans la migration :

- **SERVICEINSTRUC** (Service instructeur) : Dans `afficherChamps`, géré par JS dans `conditionalFields`
  - Note : Le legacy a un comportement UX (radio buttons + autocomplete) non encore migré, mais l'affichage conditionnel fonctionne
- **COMMISSION** : Dans `afficherChamps`, géré par JS
- **OBSERVATION** : Dans `afficherChamps`, géré par JS
- **DATECOMM**, **DATEMAIRIE**, etc. : Tous les champs de date conditionnels

---

## Actions prioritaires

### 🔴 Urgent - Compléter la migration

1. **FACTDANGE** : Ajouter comportement dynamique
   - Listener sur changement d'avis commission
   - Condition nature 19 pour type 1

2. **ECHEANCIERTRAV** : Ajouter comportement dynamique
   - Listener sur changement d'avis commission
   - Gestion checkbox (afficher/masquer champ date)

### 🟡 Moyen terme - Analyser autres champs

Vérifier si d'autres champs ont des comportements JavaScript non documentés :
- Parcourir systématiquement le JS legacy (lignes 960-1283)
- Identifier tous les `.show()` / `.hide()` conditionnels
- Documenter et migrer

### 🟢 Long terme - Refactoring

Envisager une approche plus maintenable :
- Centraliser la logique de visibilité des champs
- Réduire la dépendance au JavaScript pour les règles métier simples
- Tests automatisés pour les comportements complexes

---

## Référence rapide - Types et Natures

| Type | ID | Libellé |
|------|-----|---------|
| Étude | 1 | - |
| Visite | 2 | - |
| Groupe de visite | 3 | - |

| Nature clé | ID | Libellé | Particularité |
|------------|-----|---------|---------------|
| Levée de réserves | 19 | - | FACTDANGE si Avis Défavorable (type 1) |

| Avis | ID | Libellé |
|------|-----|---------|
| Favorable | 1 | - |
| Défavorable | 2 | Déclenche FACTDANGE + ECHEANCIERTRAV |

---

## Fichiers clés

**Legacy** :
- `prevarisc/application/views/scripts/dossier/index.phtml` : Template + JS (lignes 1-2500+)
- `prevarisc/application/controllers/DossierController.php` : Liste champs par nature (lignes 42-165)

**Migration** :
- `prevarisc-migration/src/Service/DossierFields.php` : Liste champs par nature
- `prevarisc-migration/templates/dossier/edit.html.twig` : Template édition
- `prevarisc-migration/templates/dossier/index.html.twig` : Template visualisation
- `prevarisc-migration/public/js/dossier/form.js` : Logique JS conditionnelle




