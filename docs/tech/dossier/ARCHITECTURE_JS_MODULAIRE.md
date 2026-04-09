# Architecture JavaScript modulaire - Formulaire Dossier

**Date :** 28 janvier 2026  
**Version :** 1.0  
**Refactoring de :** `form.js` (770 lignes monolithique → 5 modules ~180 lignes/module)

---

## 📁 Structure des modules

```
public/js/dossier/
├── form.js                         (188 lignes) - Orchestrateur principal
├── modules/
│   ├── type-nature.js              (332 lignes) - Gestion Type/Nature
│   ├── avis.js                     (273 lignes) - Avis & Dérogations
│   ├── documents-urbanisme.js      (64 lignes)  - Documents urbanisme
│   └── today-buttons.js            (48 lignes)  - Boutons "Aujourd'hui"
├── gestion-incomplet.js            (150 lignes) - Documents manquants
├── commission.js                   (170 lignes) - Commissions
├── calendar-init.js                (200 lignes) - FullCalendar
├── platau.js                       (224 lignes) - Plat'AU
└── add-collection-widget.js        (83 lignes)  - CollectionType

TOTAL : 905 lignes modules + reste ~827 lignes = 1732 lignes (vs 1597 avant)
```

---

## 🎯 Responsabilités des modules

### 1. **form.js** (Orchestrateur - 188 lignes)

**Responsabilité :** Initialisation et coordination des modules

**Fonctions :**
- `initForm()` : Point d'entrée principal
- `initTomSelectFields()` : Configuration TomSelect (transverse)

**Initialisation :**
```javascript
initTypeNatureModule(fieldsConfig);
initAvisModule(fieldsConfig);
initDocumentsUrbanismeModule();
initTodayButtonsModule();
```

---

### 2. **type-nature.js** (332 lignes)

**Responsabilité :** Gestion Type/Nature et affichage conditionnel des champs

**Fonctions exportées :**
- `initTypeNatureModule(config)` : Initialisation avec configuration

**Événements émis :**
- `dossier:typeChanged` → `{typeId: number}`
- `dossier:natureChanged` → `{natureId: number, typeName: string}`
- `dossier:fieldsUpdated` → `{visibleFields: string[]}`

**Fonctions internes :**
- `onTypeChange()` : Changement de type
- `onNatureChange()` : Changement de nature
- `loadNaturesForType()` : Chargement AJAX des natures
- `updateNatureSelect()` : Mise à jour du select natures
- `updateFieldsVisibility()` : Affichage/masquage conditionnel
- `hideAllConditionalFields()` : Masquer tous les champs
- `showField()` / `hideField()` : Afficher/masquer un champ
- `findFieldRow()` : Trouver la ligne d'un champ
- `applyAnalyseDeRisqueDisplay()` : Affichage analyse de risque (types 1/2/3)

---

### 3. **avis.js** (273 lignes)

**Responsabilité :** Gestion affichage/masquage avis & dérogations

**Fonctions exportées :**
- `initAvisModule(config)` : Initialisation avec configuration

**Événements écoutés :**
- `dossier:updateAvisVisibility` → Déclenché par `gestion-incomplet.js`
- `dossier:natureChanged` → Réinitialise labels avis

**Fonctions internes :**
- `initAvisLabelsOnLoad()` : Labels avis selon type/nature
- `applyHideAvisLogicOnLoad()` : Logique masquage avis (incomplet/horsdelai/absquorum)
- `initHideAvisCheckboxListeners()` : Listeners checkboxes masquage
- `applyFacteurEcheancierDisplayOnLoad()` : Affichage facteur/échéancier si avis défavorable
- `findFieldRow()` : Trouver la ligne d'un champ

**Règles métier (legacy) :**
1. Incomplet → masquer AVIS, AVIS_COMMISSION, HORSDELAI
2. Hors délai → masquer AVIS, AVIS_COMMISSION, dateCommission
3. Absence quorum/NPSP → masquer AVIS_COMMISSION, dateCommission
4. Standard → afficher selon type ET nature

---

### 4. **documents-urbanisme.js** (64 lignes)

**Responsabilité :** Préfixage automatique des documents d'urbanisme

**Fonctions exportées :**
- `initDocumentsUrbanismeModule()` : Initialisation

**Événements écoutés :**
- `collection:item-added` → Déclenché par `add-collection-widget.js`

**Fonctions internes :**
- `initDocumentsUrbanismeHandlers()` : Listeners événements collection
- `getDocumentUrbanismePrefix(natureId)` : Retourne préfixe (PC/AT/PA/DP/CU)

**Mapping préfixes :**
- Nature 1 → `PC` (Permis de construire)
- Nature 2 → `AT` (Autorisation de travaux)
- Nature 14 → `PA` (Permis d'aménager)
- Nature 30 → `DP` (Déclaration préalable)
- Nature 62 → `CU` (Certificat d'urbanisme)

---

### 5. **today-buttons.js** (48 lignes)

**Responsabilité :** Gestion boutons "Aujourd'hui" pour champs date

**Fonctions exportées :**
- `initTodayButtonsModule()` : Initialisation

**Événements écoutés :**
- `dossier:fieldsUpdated` → Réinitialise listeners si nouveaux champs

**Fonctions internes :**
- `initTodayButtons()` : Attache listeners sur `.btn-today`

---

## 🔄 Communication inter-modules (Événements custom)

| Événement | Émetteur | Récepteur(s) | Payload | Raison |
|-----------|----------|--------------|---------|--------|
| `dossier:typeChanged` | type-nature.js | - | `{typeId: number}` | Type changé |
| `dossier:natureChanged` | type-nature.js | avis.js | `{natureId: number, typeName: string}` | Nature changée → labels avis |
| `dossier:fieldsUpdated` | type-nature.js | today-buttons.js | `{visibleFields: string[]}` | Champs affichés → réinit listeners |
| `dossier:updateAvisVisibility` | gestion-incomplet.js | avis.js | - | État incomplet changé |
| `collection:item-added` | add-collection-widget.js | documents-urbanisme.js | `{list, element}` | Document ajouté |

---

## ✅ Avantages du refactoring

**Avant (monolithique) :**
- ❌ 770 lignes dans 1 fichier
- ❌ Debugging difficile (chercher dans 770 lignes)
- ❌ Risque de conflits Git
- ❌ Pas de réutilisabilité
- ❌ Tests unitaires impossibles

**Après (modulaire) :**
- ✅ 5 modules < 350 lignes chacun
- ✅ Debugging ciblé (problème avis → avis.js uniquement)
- ✅ Moins de conflits Git (fichiers séparés)
- ✅ Réutilisabilité (import modules ailleurs)
- ✅ Testabilité (mock événements custom)
- ✅ Principe SRP (Single Responsibility Principle)
- ✅ Séparation des préoccupations claire

---

## 🛠️ Utilisation

### Chargement dans Twig

```twig
{% block javascripts %}
    {{ parent() }}
    
    {# Module ES6 pour le formulaire dossier #}
    <script type="module" src="{{ asset('js/dossier/form.js') }}"></script>
    
    {# Autres scripts (sans modules ES6) #}
    <script src="{{ asset('js/dossier/gestion-incomplet.js') }}"></script>
    <script src="{{ asset('js/dossier/commission.js') }}"></script>
{% endblock %}
```

**Note :** `type="module"` est **requis** pour les imports ES6.

### Import dans un autre module

```javascript
import { initTypeNatureModule } from './modules/type-nature.js';
import { initAvisModule } from './modules/avis.js';

// Utiliser les modules
initTypeNatureModule(config);
initAvisModule(config);
```

### Écouter événements custom

```javascript
document.addEventListener('dossier:natureChanged', function(e) {
    console.log('Nature changée:', e.detail.natureId);
});
```

---

## 🔍 Debugging

### Accès aux fonctions depuis la console

```javascript
// form.js (orchestrateur)
window.DossierForm // Non exposé (modules isolés)

// type-nature.js
window.DossierTypeNature.updateFieldsVisibility();
window.DossierTypeNature.fieldsConfig();
```

### Tester événements manuellement

```javascript
// Simuler changement de nature
document.dispatchEvent(new CustomEvent('dossier:natureChanged', {
    detail: { natureId: 1, typeName: 'Étude' }
}));
```

---

## ⚠️ Contraintes navigateurs

**ES6 Modules supportés :**
- Chrome 61+ (2017)
- Firefox 60+ (2018)
- Safari 11+ (2017)
- Edge 16+ (2017)

**Compatibilité projet :**
- ✅ Bootstrap 3 (navigateurs modernes)
- ✅ jQuery 3.x (navigateurs modernes)
- ✅ TomSelect (navigateurs modernes)

**Fallback si besoin :** Utiliser IIFE classique avec `window.DossierModules = {}`

---

## 📝 Historique

**Version 1.0** (28 janvier 2026)
- Refactoring form.js monolithique → 4 modules + orchestrateur
- Ajout événements custom pour communication inter-modules
- Séparation responsabilités : Type/Nature, Avis, Documents, Today Buttons
- Conservation 100% iso-fonctionnalité legacy

---

## 📚 Fichiers liés

- `docs/tech/dossier/PLAN_MIGRATION_EDITION_DOSSIER.md` → Plan migration complet
- `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md` → Mapping champs
- `.github/edition-dossier.md` → Spécifications édition
- `REPRENDRE_ICI.md` → État avancement

---

**Auteur :** Copilot CLI  
**Dernière mise à jour :** 28 janvier 2026
