# Guide d'implémentation - Édition Visites (Natures 21, 28, 29)

**Date** : 11 décembre 2025  
**Contexte** : Implémentation correcte après review nature 21

---

## 📋 Informations vérifiées

### IDs des avis en base
| ID | Libellé |
|----|---------|
| 1 | Favorable |
| 2 | Défavorable ← **Trigger pour champs conditionnels** |
| 6 | Pas d'avis |

### Propriétés entité
- ✅ `facteurDangerosite` (Integer) - Pas `facteurCriticite` !
- ✅ `echeancierTravaux` (Date) - Pas Textarea !

### Ordre des champs Nature 21 (Legacy)
```
DATEINSERT, COMMISSION, DESCGEN, DESCEFF, DATEVISITE, DATEREP, 
PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM, AVIS, AVIS_COMMISSION, 
OBSERVATION, DELAIPRESC
```

**Correspondance Symfony** :
1. `dateInsert` - Date de création
2. `commission` - Commission concernée
3. `descriptifGeneral` - Descriptif général
4. `descriptifEffectifs` - Descriptif effectif
5. `dateVisite` - Date de visite
6. `dateReponse` - Date de réponse
7. `preventionnistes` - Préventionniste(s)
8. `avisDiffere` - **Diffère l'avis** ← Label à corriger
9. `absenceDeQuorum` - Absence de quorum
10. `avis` - Avis du rapporteur
11. `avisCommission` - Avis de la commission
12. `observations` - Observations
13. `dateLimitePrescription` - Délai levée prescriptions

---

## 🔧 Corrections à appliquer

### 1. FormType - Corriger labels

**Fichier** : `src/Form/DossierType.php`

```php
->add('avisDiffere', CheckboxType::class, [
    'label' => 'Diffère l\'avis',  // ✅ Corrigé (était "Avis différé")
    'required' => false,
    'attr' => ['class' => 'form-check-input'],
])
```

### 2. FormType - Réorganiser l'ordre

**Ordre actuel dans le code** : Incohérent  
**Ordre à respecter** : Celui du legacy (voir ci-dessus)

**Action** : Réorganiser les `->add()` dans DossierType pour respecter l'ordre

### 3. FormType - Ajouter champs conditionnels

**Après `avisCommission`**, ajouter :

```php
->add('facteurDangerosite', IntegerType::class, [
    'label' => 'Facteur de dangerosité',
    'required' => false,
    'attr' => [
        'class' => 'form-control',
        'min' => 0,
    ],
])
->add('echeancierTravaux', DateType::class, [
    'label' => 'Échéancier de travaux',
    'widget' => 'single_text',
    'required' => false,
    'attr' => ['class' => 'form-control'],
])
```

**Note** : Ces champs existent déjà partiellement dans le form, à vérifier leur position.

---

## 🎨 Template - Structure Bootstrap

### Structure recommandée

**Fichier** : `templates/dossier/edit.html.twig`

```twig
{# Macro pour afficher un champ avec label à gauche #}
{% macro field_row(form_field, label, help_text) %}
    <div class="row form-group" id="field-{{ form_field.vars.name }}">
        <div class="col-md-3">
            {{ form_label(form_field, label, {'label_attr': {'class': 'control-label'}}) }}
        </div>
        <div class="col-md-9">
            {{ form_widget(form_field) }}
            {{ form_errors(form_field) }}
            {% if help_text %}
                <small class="form-text text-muted">{{ help_text }}</small>
            {% endif %}
        </div>
    </div>
{% endmacro %}

{# Utilisation #}
{% import _self as macros %}

{# Section Informations générales #}
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Informations générales</h3>
    </div>
    <div class="panel-body">
        {{ macros.field_row(form.commission, 'Commission concernée', 'La commission est liée à la date de visite') }}
        {{ macros.field_row(form.descriptifGeneral, 'Descriptif général') }}
        {{ macros.field_row(form.descriptifEffectifs, 'Descriptif effectif') }}
        {{ macros.field_row(form.dateVisite, 'Date de visite') }}
        {{ macros.field_row(form.dateReponse, 'Date de réponse') }}
    </div>
</div>

{# Section Avis #}
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Avis et décisions</h3>
    </div>
    <div class="panel-body">
        {{ macros.field_row(form.avis, 'Avis du rapporteur') }}
        {{ macros.field_row(form.avisCommission, 'Avis de la commission') }}
        
        {# Champs conditionnels - cachés par défaut, montrés par JS si avis défavorable #}
        <div id="fields-avis-defavorable" style="display: none;">
            {{ macros.field_row(form.facteurDangerosite, 'Facteur de dangerosité') }}
            {{ macros.field_row(form.echeancierTravaux, 'Échéancier de travaux') }}
        </div>
        
        {# Checkboxes #}
        <div class="row form-group">
            <div class="col-md-9 col-md-offset-3">
                <div class="checkbox">
                    <label>
                        {{ form_widget(form.avisDiffere) }}
                        Diffère l'avis
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        {{ form_widget(form.absenceDeQuorum) }}
                        Absence de quorum
                    </label>
                </div>
            </div>
        </div>
        
        {{ macros.field_row(form.observations, 'Observations') }}
        {{ macros.field_row(form.dateLimitePrescription, 'Délai levée prescriptions') }}
    </div>
</div>
```

---

## 💻 JavaScript - Comportements dynamiques

### Fichier : `public/js/dossier/form.js`

#### 1. Champs conditionnels par nature

**Dans `conditionalFields`**, corriger/ajouter :

```javascript
const conditionalFields = {
    // ... autres natures ...
    
    // Nature 21 - Visite périodique
    21: [
        'commission',
        'descriptifGeneral',
        'descriptifEffectifs',
        'dateVisite',
        'dateReponse',
        'preventionnistes',
        'avisDiffere',
        'absenceDeQuorum',
        'avis',
        'avisCommission',
        'facteurDangerosite',      // ← Conditionnel (avis défavorable)
        'echeancierTravaux',        // ← Conditionnel (avis défavorable)
        'observations',
        'dateLimitePrescription'
    ],
    
    // Nature 28 - Visite de contrôle (groupe)
    28: [
        'objet',
        'commission',
        'descriptifGeneral',
        'descriptifEffectifs',
        'dateCommission',
        'dateVisite',
        'avis',
        'coordonneesSsi',
        'dateReponse',
        'preventionnistes',
        'avisDiffere',
        'absenceDeQuorum',
        'avisCommission',
        'facteurDangerosite',      // ← Conditionnel
        'echeancierTravaux',        // ← Conditionnel
        'observations',
        'dateLimitePrescription'
    ],
    
    // Nature 29 - Visite inopinée (groupe)
    29: [
        'objet',
        'commission',
        'descriptifGeneral',
        'descriptifEffectifs',
        'dateCommission',
        'dateVisite',
        'avis',
        'dateReponse',              // Pas coordonneesSsi pour nature 29
        'preventionnistes',
        'avisDiffere',
        'absenceDeQuorum',
        'avisCommission',
        'facteurDangerosite',      // ← Conditionnel
        'echeancierTravaux',        // ← Conditionnel
        'observations',
        'dateLimitePrescription'
    ]
};
```

#### 2. Logique avis défavorable

**Ajouter cette fonction** :

```javascript
/**
 * Gère l'affichage des champs conditionnels selon l'avis de commission
 */
function handleAvisCommissionChange() {
    const $avisCommission = $('#dossier_avisCommission');
    const $fieldsDefavorable = $('#fields-avis-defavorable');
    
    $avisCommission.on('change', function() {
        const selectedAvisId = $(this).val();
        
        // ID 2 = Défavorable
        if (selectedAvisId === '2') {
            $fieldsDefavorable.slideDown();
        } else {
            $fieldsDefavorable.slideUp();
            // Réinitialiser les valeurs si masqué
            $('#dossier_facteurDangerosite').val('');
            $('#dossier_echeancierTravaux').val('');
        }
    });
    
    // Déclencher au chargement pour gérer l'état initial
    $avisCommission.trigger('change');
}

// Appeler au document ready
$(document).ready(function() {
    handleAvisCommissionChange();
    // ... autres fonctions ...
});
```

#### 3. Commission disabled pour visites

**Ajouter cette fonction** :

```javascript
/**
 * Désactive le champ commission pour les visites (types 2 et 3)
 * Car le changement de commission impacte la date de visite
 */
function handleCommissionForVisites() {
    const $type = $('#dossier_type');
    const $commission = $('#dossier_commission');
    
    function toggleCommission() {
        const typeId = $type.val();
        
        // Types 2 et 3 = Visites
        if (typeId === '2' || typeId === '3') {
            $commission.prop('disabled', true)
                .addClass('disabled-field')
                .closest('.form-group')
                .find('.col-md-9')
                .append(
                    '<small class="form-text text-muted commission-help">' +
                    'La commission est liée à la date de visite. ' +
                    'Modifier la date de visite pour changer de commission.' +
                    '</small>'
                );
        } else {
            $commission.prop('disabled', false)
                .removeClass('disabled-field')
                .closest('.form-group')
                .find('.commission-help')
                .remove();
        }
    }
    
    $type.on('change', toggleCommission);
    toggleCommission(); // Initial
}

// Appeler au document ready
$(document).ready(function() {
    handleCommissionForVisites();
    // ... autres fonctions ...
});
```

---

## ✅ Checklist finale

### FormType
- [ ] Corriger label `avisDiffere` → "Diffère l'avis"
- [ ] Réorganiser champs dans l'ordre du legacy
- [ ] Vérifier que `facteurDangerosite` et `echeancierTravaux` sont présents
- [ ] Valider tous les labels

### Template
- [ ] Créer macro `field_row` pour structure cohérente
- [ ] Grouper champs par sections (panels Bootstrap)
- [ ] Wrapper `facteurDangerosite` et `echeancierTravaux` dans div conditionnelle
- [ ] IDs sur les wrappers pour JavaScript

### JavaScript
- [ ] Ajouter/corriger `conditionalFields` pour 21, 28, 29
- [ ] Fonction `handleAvisCommissionChange()`
- [ ] Fonction `handleCommissionForVisites()`
- [ ] Tester affichage dynamique

### Tests
- [ ] Mettre à jour tests pour vérifier ordre des champs
- [ ] Tester champs conditionnels
- [ ] Vérifier labels corrects

---

## 📝 Notes

- `facteurDangerosite` existe déjà dans le form (ligne ~133)
- `echeancierTravaux` : vérifier si existe déjà
- Les deux champs doivent être dans `conditionalFields` pour TOUTES les natures qui les utilisent
- Template : privilégier les macros pour réutilisabilité

---

**Prêt pour une implémentation propre ! 🚀**
