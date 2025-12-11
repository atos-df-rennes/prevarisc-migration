# Ajustements Nature 21 - À appliquer aux natures suivantes

**Date** : 11 décembre 2025  
**Contexte** : Review après implémentation nature 21

---

## 🔍 Points à corriger

### 1. Ordre des champs ❌

**Problème** : L'ordre des champs dans le formulaire ne correspond pas au legacy.

**Solution** :
- Vérifier l'ordre exact dans le legacy pour chaque nature
- Respecter cet ordre dans le FormType
- Se baser sur `DossierFields.php` qui respecte l'ordre legacy

**Action** : 
```php
// Référence legacy nature 21 :
// DATEINSERT, COMMISSION, DESCGEN, DESCEFF, DATEVISITE, DATEREP, 
// PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM, AVIS, AVIS_COMMISSION, 
// OBSERVATION, DELAIPRESC

// Ordre Symfony correspondant :
- dateInsert
- commission
- descriptifGeneral
- descriptifEffectifs
- dateVisite
- dateReponse
- preventionnistes
- avisDiffere
- absenceDeQuorum
- avis
- avisCommission
- observations
- dateLimitePrescription
```

---

### 2. Mise en page (label gauche / valeur droite) ❌

**Problème** : Pas le même affichage que legacy avec label à gauche et champ à droite.

**Solution** : Utiliser la structure Bootstrap du legacy dans le template :
```twig
<div class="row form-group">
    <div class="col-md-3">
        {{ form_label(form.commission, 'Commission concernée') }}
    </div>
    <div class="col-md-9">
        {{ form_widget(form.commission) }}
    </div>
</div>
```

**Fichier à modifier** : `templates/dossier/edit.html.twig`

---

### 3. Labels incorrects ❌

**Problèmes identifiés** :
| Champ | Label actuel (Symfony) | Label legacy correct |
|-------|------------------------|----------------------|
| `avisDiffere` | "Avis différé" | "Diffère l'avis" |
| *(à vérifier)* | *(à vérifier)* | *(à vérifier)* |

**Action** : Corriger dans `src/Form/DossierType.php`

```php
->add('avisDiffere', CheckboxType::class, [
    'label' => 'Diffère l\'avis',  // ✅ Correct
    'required' => false,
    'attr' => ['class' => 'form-check-input'],
])
```

---

### 4. Champs conditionnels manquants (avis défavorable) ❌

**Problème** : 2 champs s'affichent uniquement si avis de commission = défavorable :
- **Facteur de criticité**
- **Échéancier de travaux**

**Solution** : 
1. Ajouter les champs au FormType
2. Ajouter la logique JavaScript conditionnelle

**FormType** (`src/Form/DossierType.php`) :
```php
->add('facteurCriticite', IntegerType::class, [
    'label' => 'Facteur de criticité',
    'required' => false,
    'attr' => ['class' => 'form-control'],
])
->add('echeancierTravaux', TextareaType::class, [
    'label' => 'Échéancier de travaux',
    'required' => false,
    'attr' => [
        'rows' => 3,
        'class' => 'form-control',
    ],
])
```

**JavaScript** (`public/js/dossier/form.js`) :
```javascript
// Écouter le changement de l'avis de commission
$('#dossier_avisCommission').on('change', function() {
    const selectedAvisId = $(this).val();
    
    // Vérifier si c'est "défavorable" (ID à déterminer - probablement 2)
    // TODO: Vérifier l'ID de l'avis "défavorable" en base
    if (selectedAvisId === '2') { // À confirmer
        $('#field-facteurCriticite').show();
        $('#field-echeancierTravaux').show();
    } else {
        $('#field-facteurCriticite').hide();
        $('#field-echeancierTravaux').hide();
    }
});

// Déclencher au chargement
$('#dossier_avisCommission').trigger('change');
```

**Note** : Vérifier en base l'ID de l'avis "défavorable" :
```sql
SELECT * FROM avis WHERE LIBELLE_AVIS LIKE '%défavorable%';
```

---

### 5. Commission concernée disabled ❌

**Problème** : Le champ commission devrait être disabled car le changement influe sur la date de visite.

**Solution temporaire** : Ajouter `disabled` en JavaScript jusqu'à implémentation complète de la logique

**JavaScript** (`public/js/dossier/form.js`) :
```javascript
// Pour les visites (types 2 et 3), désactiver commission
if ($('#dossier_type').val() === '2' || $('#dossier_type').val() === '3') {
    $('#dossier_commission').prop('disabled', true)
        .addClass('disabled-field')
        .after('<small class="form-text text-muted">La commission est liée à la date de visite</small>');
}
```

**Note** : À terme, implémenter la vraie logique :
- Changement commission → Ouvrir modal sélection date
- Intégration avec le système de calendrier
- (Voir legacy pour comportement exact)

---

## 📋 Checklist d'implémentation

### Pour chaque nature (28, 29, etc.) :

#### FormType
- [ ] Vérifier l'ordre des champs (référence : DossierFields.php)
- [ ] Corriger les labels (comparer avec legacy)
- [ ] Ajouter champs conditionnels (facteurCriticite, echeancierTravaux)
- [ ] Vérifier tous les champs nécessaires

#### Template (edit.html.twig)
- [ ] Structure Bootstrap : label gauche (col-md-3) / champ droite (col-md-9)
- [ ] Wrapper champs dans des divs avec IDs pour JavaScript
- [ ] Grouper logiquement les champs (Informations, Avis, etc.)

#### JavaScript (form.js)
- [ ] Ajouter champs dans `conditionalFields` pour la nature
- [ ] Logique affichage facteurCriticite/echeancierTravaux (avis défavorable)
- [ ] Désactiver commission pour types 2 et 3
- [ ] Tester affichage/masquage dynamique

#### Tests
- [ ] Mettre à jour test pour vérifier tous les champs
- [ ] Vérifier labels corrects
- [ ] Tester soumission

---

## 🎯 Plan d'action avant Nature 28

### 1. Vérifier l'ID de l'avis "défavorable"
```bash
docker exec prevarisc-infra-app-1 bash -c "cd /var/www/html/prevarisc-migration && php bin/console doctrine:query:sql 'SELECT * FROM avis'"
```

### 2. Analyser structure template legacy
```bash
cd prevarisc/application/views/scripts/dossier/
grep -A 10 "COMMISSION\|DESCGEN" index.phtml
```

### 3. Créer structure template cohérente
- [ ] Créer blocs réutilisables pour groupes de champs
- [ ] Macro Twig pour structure label/champ ?

### 4. Implémenter JavaScript de base
- [ ] Fonction toggleConditionalFields()
- [ ] Initialisation au chargement
- [ ] Événements change

---

## 📝 Notes de migration

### Propriétés à vérifier
- `facteurCriticite` : existe dans l'entité ? → À vérifier
- `echeancierTravaux` : existe dans l'entité ? → À vérifier

### Mapping à clarifier
| Legacy | Symfony | Statut |
|--------|---------|--------|
| `FACTCRIT_DOSSIER` | `facteurCriticite` | ❓ À vérifier |
| `ECHEANCIER_DOSSIER` | `echeancierTravaux` | ❓ À vérifier |

---

## 🔧 Commandes utiles

### Vérifier propriété dans entité
```bash
grep -i "facteur\|echeancier" src/Entity/Dossier.php
```

### Vérifier colonne en base
```bash
docker exec prevarisc-infra-app-1 bash -c "cd /var/www/html/prevarisc-migration && php bin/console doctrine:query:sql 'DESCRIBE dossier'" | grep -i "FACT\|ECHE"
```

### Voir structure formulaire legacy
```bash
cd prevarisc/application/views/scripts/dossier/
grep -B 2 -A 2 "FACTCRIT\|ECHEANCIER" index.phtml
```

---

## ✅ À faire maintenant

1. **Vérifier existence propriétés** facteurCriticite et echeancierTravaux
2. **Récupérer ID avis défavorable** en base
3. **Analyser ordre exact** des champs dans legacy pour nature 21
4. **Créer structure template** propre avant nature 28

---

**Une fois ces points clarifiés, on pourra implémenter proprement les natures 28 et 29 !** 🚀
