# Guide : Ajout de nouveaux champs au formulaire de dossier

**Dernière mise à jour :** 16 décembre 2025

---

## 📋 Procédure complète

Lorsque vous ajoutez un nouveau champ au formulaire d'édition de dossier, suivez ces étapes dans l'ordre :

### 1. Entité et base de données

✅ **Si le champ n'existe pas encore :**
- Ajouter la propriété dans `src/Entity/Dossier.php`
- Créer une migration : `bin/console make:migration`
- Exécuter la migration : `bin/console doctrine:migrations:migrate`

### 2. FormType

📝 **Fichier :** `src/Form/DossierType.php`

Ajouter le champ dans la méthode `buildForm()` :

```php
$builder
    ->add('monNouveauChamp', TextType::class, [
        'label' => 'Mon nouveau champ',
        'required' => false,
        'attr' => ['class' => 'form-control'],
    ]);
```

**Types de champs courants :**
- `TextType` : Input texte simple
- `TextareaType` : Zone de texte
- `DateType` : Sélecteur de date
- `ChoiceType` : Select/Radio
- `EntityType` : Select avec entités (ex: Commission)
- `CollectionType` : Collection d'éléments (ex: documentsUrbanisme)

### 3. Configuration des natures (DossierFields)

📝 **Fichier :** `src/Service/DossierFields.php`

Ajouter le champ dans les natures concernées :

```php
// Nature X : Mon type de dossier
'X' => [
    'dateInsert',
    'objet',
    'monNouveauChamp',  // ⬅️ Ajouter ici
    'observations',
],
```

**⚠️ Important :** Le nom doit correspondre exactement au nom de la propriété dans l'entité (camelCase).

### 4. Template Twig

📝 **Fichier :** `templates/dossier/edit.html.twig`

#### A. Affichage du champ

Ajouter le champ dans la section appropriée :

```twig
<li class='row' id='field-monNouveauChamp'>
    <div class='left libelle col-md-3'>{{ form_label(form.monNouveauChamp) }}</div>
    <div class='right form col-md-9'>
        {{ form_widget(form.monNouveauChamp) }}
        {{ form_errors(form.monNouveauChamp) }}
    </div>
</li>
```

**⚠️ Important :** L'attribut `id='field-monNouveauChamp'` est **obligatoire** pour que le JavaScript puisse gérer l'affichage conditionnel.

#### B. Cas spécial : Collection

Pour les champs de type `CollectionType` (comme `documentsUrbanisme`) :

1. **Afficher le champ manuellement** dans le template (comme ci-dessus)
2. **Marquer le champ comme affiché** pour éviter qu'il soit re-rendu par `form_rest()` :

```twig
{# Rendu du champ collection ici #}
<li class='row' id='field-documentsUrbanisme'>
    <div class='left libelle col-md-3'>
        <label>Numéros documents d'urbanisme</label>
    </div>
    <div class='right form col-md-9'>
        <ul id="dossier_documentsUrbanisme"
            class="row list-unstyled remove-collection-widget"
            data-prototype="{{ form_widget(form.documentsUrbanisme.vars.prototype)|e }}"
            data-widget-tags="{{ '<li></li>'|e }}"
            data-widget-counter="{{ form.documentsUrbanisme|length }}">
            {% for document_urbanisme in form.documentsUrbanisme %}
                <li>
                    {{ form_errors(document_urbanisme) }}
                    {{ form_widget(document_urbanisme, {attr: {class: 'collection-item-wrapper'}}) }}
                </li>
            {% endfor %}
        </ul>
        
        <div class="text-center mt-2">
            <button type="button"
                    class="btn btn-primary add-collection-widget"
                    data-list-selector="#dossier_documentsUrbanisme">
                <span class="glyphicon glyphicon-plus"></span> Ajouter un numéro
            </button>
        </div>
        {{ form_errors(form.documentsUrbanisme) }}
    </div>
</li>

{# Marquer le champ comme affiché pour éviter qu'il soit re-rendu #}
{% do form.documentsUrbanisme.setRendered %}
```

**⚠️ Crucial :** La ligne `{% do form.documentsUrbanisme.setRendered %}` est **obligatoire** pour les champs Collection affichés manuellement, sinon `form_rest()` les affichera à nouveau en fin de formulaire.

### 5. JavaScript - Affichage conditionnel

📝 **Fichier :** `public/js/dossier/form.js`

Ajouter le champ dans la liste `conditionalFields` de la fonction `hideAllConditionalFields()` :

```javascript
function hideAllConditionalFields() {
    const conditionalFields = [
        'dateInsert',
        'objet',
        'monNouveauChamp',  // ⬅️ Ajouter ici
        'observations',
        // ... autres champs
    ];

    conditionalFields.forEach(function(fieldName) {
        hideField(fieldName);
    });
}
```

**⚠️ Important :** 
- Le nom doit correspondre au nom du champ dans le FormType (sans le préfixe `dossier_`)
- **Tous les champs conditionnels** (affichés selon la nature) doivent être dans cette liste
- Si un champ n'est pas dans cette liste, il sera **toujours affiché**, quelle que soit la nature

**Comment ça fonctionne :**
1. Au chargement, `hideAllConditionalFields()` masque tous les champs
2. `updateFieldsVisibility()` affiche uniquement les champs configurés dans `DossierFields` pour la nature sélectionnée
3. Si un champ n'est pas dans `conditionalFields`, il ne sera jamais masqué → **toujours visible**

### 6. Validation (optionnel)

📝 **Fichier :** `src/Entity/Dossier.php`

Ajouter les contraintes de validation si nécessaire :

```php
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Column(type="string", length=255, nullable=true)
 * @Assert\NotBlank(message="Ce champ est obligatoire")
 * @Assert\Length(max=255, maxMessage="Maximum {{ limit }} caractères")
 */
private $monNouveauChamp;
```

---

## 📝 Checklist complète

Avant de commit, vérifier que :

- [ ] ✅ Entité mise à jour (si nouveau champ)
- [ ] ✅ Migration créée et exécutée (si nouveau champ)
- [ ] ✅ Champ ajouté dans `DossierType`
- [ ] ✅ Champ ajouté dans `DossierFields` pour les natures concernées
- [ ] ✅ Champ affiché dans le template avec `id='field-nomChamp'`
- [ ] ✅ Pour les Collections : `{% do form.nomChamp.setRendered %}` ajouté
- [ ] ✅ Champ ajouté dans `conditionalFields` du JavaScript
- [ ] ✅ Validation ajoutée (si nécessaire)
- [ ] ✅ Tests manuels : le champ s'affiche/se masque correctement selon la nature
- [ ] ✅ PHPStan : `castor symfony:analyse`
- [ ] ✅ Coding Style : `castor symfony:cs`
- [ ] ✅ Tests PHPUnit : `castor symfony:test`

---

## ⚠️ Erreurs courantes à éviter

### 1. Champ toujours affiché (non conditionnel)

**Symptôme :** Le champ s'affiche pour toutes les natures, même celles qui ne devraient pas l'avoir.

**Cause :** Le champ n'est pas dans la liste `conditionalFields` du JavaScript.

**Solution :** Ajouter le champ dans `public/js/dossier/form.js` → fonction `hideAllConditionalFields()`.

### 2. Champ Collection affiché deux fois

**Symptôme :** Le champ Collection apparaît deux fois dans le formulaire.

**Cause :** Le champ Collection est affiché manuellement mais `{% do form.nomChamp.setRendered %}` n'a pas été ajouté.

**Solution :** Ajouter `{% do form.nomChamp.setRendered %}` après l'affichage manuel du champ.

### 3. JavaScript ne trouve pas le champ

**Symptôme :** Le champ ne se masque/affiche pas dynamiquement.

**Cause :** L'attribut `id='field-nomChamp'` manque sur la balise `<li>`.

**Solution :** Ajouter `id='field-nomChamp'` sur la balise `<li class='row'>` du champ.

### 4. Nom de champ incorrect

**Symptôme :** Erreur JavaScript ou champ non trouvé.

**Cause :** Incohérence entre les noms dans DossierFields, FormType et JavaScript.

**Solution :** Utiliser **exactement le même nom** (camelCase) partout :
- Entité : `private $monNouveauChamp;`
- DossierFields : `'monNouveauChamp'`
- FormType : `->add('monNouveauChamp')`
- JavaScript : `'monNouveauChamp'`
- Template : `id='field-monNouveauChamp'`

---

## 🔍 Exemple complet : Champ `serviceInstructeur`

### 1. Entité (déjà existant)
```php
/**
 * @ORM\ManyToOne(targetEntity="App\Entity\ServiceInstructeur")
 * @ORM\JoinColumn(name="SERVICEINSTRUCTEUR_DOSSIER", referencedColumnName="ID_SERVICE", nullable=true)
 */
private $serviceInstructeur;
```

### 2. FormType
```php
->add('serviceInstructeur', EntityType::class, [
    'class' => ServiceInstructeur::class,
    'choice_label' => 'libelle',
    'label' => 'Service instructeur',
    'required' => false,
    'placeholder' => 'Sélectionner un service',
    'attr' => ['class' => 'form-control'],
])
```

### 3. DossierFields
```php
// Nature 1 : PC
'1' => [
    'dateInsert',
    'objet',
    'serviceInstructeur',  // ⬅️
    'commission',
    // ...
],
```

### 4. Template
```twig
<li class='row' id='field-serviceInstructeur'>
    <div class='left libelle col-md-3'>{{ form_label(form.serviceInstructeur) }}</div>
    <div class='right form col-md-9'>
        {{ form_widget(form.serviceInstructeur) }}
        {{ form_errors(form.serviceInstructeur) }}
    </div>
</li>
```

### 5. JavaScript
```javascript
const conditionalFields = [
    'dateInsert',
    'objet',
    'serviceInstructeur',  // ⬅️
    'commission',
    // ...
];
```

---

## 📚 Ressources

- **Symfony Forms :** https://symfony.com/doc/4.4/forms.html
- **Twig :** https://twig.symfony.com/doc/2.x/
- **Fichiers de référence :**
  - `src/Form/DossierType.php`
  - `src/Service/DossierFields.php`
  - `templates/dossier/edit.html.twig`
  - `public/js/dossier/form.js`

---

**Note :** Ce guide est mis à jour au fur et à mesure de l'ajout de nouveaux types de champs.
