# Formulaires personnalisés — Architecture et fonctionnement

## Table des matières

- [1. Vue d'ensemble](#1-vue-densemble)
- [2. Structure des entités](#2-structure-des-entités)
- [3. Service Descriptif (lecture)](#3-service-descriptif-lecture)
- [4. Pipeline d'édition](#4-pipeline-dédition)
  - [4.1 DTOs](#41-dtos)
  - [4.2 Hydrator](#42-hydrator-descriptifformhydrator)
  - [4.3 Builder](#43-builder-descriptifformbuilder)
  - [4.4 FormTypes feuille](#44-formtypes-feuille)
  - [4.5 Persister](#45-persister-descriptifformpersister)
- [5. Templates et thème Twig](#5-templates-et-thème-twig)
  - [5.1 Template principal](#51-template-principal)
  - [5.2 Thème Twig](#52-thème-twig-descriptif_themehtml-twig)
- [6. Couche JavaScript](#6-couche-javascript)
  - [6.1 descriptif-collection.js](#61-descriptif-collectionjs)
  - [6.2 edit.js](#62-editjs)
- [7. Ajouter un nouveau type de champ](#7-ajouter-un-nouveau-type-de-champ)
- [8. Points d'attention et décisions d'architecture](#8-points-dattention-et-décisions-darchitecture)

---

## 1. Vue d'ensemble

Les formulaires personnalisés permettent de saisir des informations métier structurées en **rubriques** et **champs** configurables en base de données. Ils couvrent quatre contextes, chacun identifié par une constante `NomInterneCapsule` :

| Capsule (`NomInterneCapsule`) | Contexte |
|---|---|
| `descriptifVerificationsTechniques` | Vérifications techniques d'un dossier |
| `effectifsDegagementsDossier` | Effectifs/dégagements d'un dossier |
| `descriptifEtablissement` | Descriptif d'un établissement |
| `effectifsDegagementsEtablissement` | Effectifs/dégagements d'un établissement |

### Structure de données en base

```
CapsuleRubrique (nomInterne)
  └── Rubrique (idx, defaultDisplay)
        └── Champ (idx, type TypeChamp, parent?)
              ├── ChampValeurListe[]  (options SELECT)
              ├── Champ[] enfants     (sous-champs PARENT)
              └── Valeur[]            (données saisies)
```

### Pipeline de traitement

```
[DB]  CapsuleRubriqueRepository::findByNomInterneWithFullData()
        ↓  1 requête eager (rubriques + champs + enfants + valeursListe)
[DB]  ValeurRepository::findByDossier/findByEtablissement()
        ↓  1 requête, indexée par champId
[Service]  Descriptif::getForDossier/getForEtablissement()
        ↓  array{capsule, rubriques: list<{rubrique, display, champs}>}
[Hydrator] DescriptifFormHydrator
        ↓  DescriptifFormData (DTOs)
[Builder]  DescriptifFormBuilder
        ↓  FormInterface Symfony (nommé 'descriptif')
[Template] _formulaire_personnalise_edit.html.twig
        ↓  POST HTTP
[Persister] DescriptifFormPersister
        ↓  flush() → Valeur entities en base
```

---

## 2. Structure des entités

### `CapsuleRubrique`

Point d'entrée. Identifié par `nomInterne` (constante `NomInterneCapsule`).

```php
$capsule->getNomInterne(); // ex: 'descriptifVerificationsTechniques'
$capsule->getRubriques();  // Collection<Rubrique>, triées par idx ASC, id ASC
```

### `Rubrique`

Groupe logique de champs affiché comme un panneau Bootstrap accordéon.

| Propriété | Type | Rôle |
|---|---|---|
| `id` | `int` | Clé primaire |
| `nom` | `string` | Libellé du panneau |
| `idx` | `int` | Ordre d'affichage |
| `defaultDisplay` | `?bool` | Visibilité par défaut (si null → `true`) |
| `ouverte` | `bool` | Non utilisé côté édition |

```php
$rubrique->getRootChamps(); // Collection<Champ> filtrée : parent === null
```

> **Important :** `getRootChamps()` filtre en mémoire la collection `champs` (tous les champs de la rubrique y compris les enfants partagent la même FK `ID_RUBRIQUE`). Seuls les champs sans parent sont retournés.

### `Champ`

Champ de saisie individuel. Peut être racine ou enfant d'un `Champ` parent.

| Propriété | Type | Rôle |
|---|---|---|
| `id` | `int` | Clé primaire |
| `nom` | `string` | Libellé du champ |
| `idx` | `int` | Ordre dans la rubrique |
| `type` | `TypeChamp` | Détermine le widget de saisie |
| `tableau` | `?bool` | Si `true` + `type=PARENT` → tableau de lignes |
| `parent` | `?Champ` | Champ parent (null pour les racines) |
| `enfants` | `Collection<Champ>` | Sous-champs (triés idx ASC, id ASC) |
| `valeursListe` | `Collection<ChampValeurListe>` | Options pour type SELECT |

### `TypeChamp` (constantes)

```php
// src/Constant/TypeChamp.php
TypeChamp::TEXT     = 1  // input text
TypeChamp::TEXTAREA = 2  // textarea
TypeChamp::SELECT   = 3  // liste déroulante
TypeChamp::NUMBER   = 4  // input number
TypeChamp::CHECKBOX = 5  // case à cocher
TypeChamp::PARENT   = 6  // groupe ou tableau de sous-champs
TypeChamp::DATE     = 7  // sélecteur de date
```

### `Valeur`

Une saisie pour un champ donné. Utilise des colonnes polymorphes selon le type.

| Colonne DB | Propriété PHP | Type | Utilisée pour |
|---|---|---|---|
| `VALEUR_STR` | `str` | `?string` | TEXT, SELECT |
| `VALEUR_LONG_STR` | `longStr` | `?string` | TEXTAREA |
| `VALEUR_INT` | `number` | `?int` | NUMBER |
| `VALEUR_CHECKBOX` | `checkbox` | `?bool` | CHECKBOX |
| `VALEUR_DATE` | `date` | `?\DateTimeInterface` | DATE |
| `idx` | `idx` | `?int` | Rang de la ligne (tableaux uniquement) |

Pour un champ simple, `idx` est `null`. Pour un tableau, chaque ligne reçoit un `idx` 1-based.

---

## 3. Service Descriptif (lecture)

**Classe :** `App\Service\Descriptif`

### Rôle

Assemble en une seule structure les rubriques, champs configurés en BDD et les valeurs saisies pour une entité donnée. Deux requêtes SQL max (capsule + valeurs), pas de N+1.

### Méthodes publiques

```php
// Pour un dossier
$data = $descriptif->getForDossier($dossier, NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES);

// Pour un établissement
$data = $descriptif->getForEtablissement($etablissement, NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT);
```

**Retour :**
```php
array{
    capsule: CapsuleRubrique|null,
    rubriques: list<array{
        rubrique: Rubrique,
        display: bool,         // pref utilisateur ou defaultDisplay
        champs: list<array<string, mixed>>
    }>
}
```

### Structure d'un champ dans `rubriques[n]['champs']`

| Cas | Clés présentes |
|---|---|
| Champ simple | `champ`, `valeur` |
| PARENT + tableau | `champ`, `valeur=null`, `lignes: list<array<champId, Valeur>>` |
| PARENT + groupe | `champ`, `valeur=null`, `sous_champs: list<{champ, valeur}>` |

### Tri déterministe

Les rubriques et champs sont triés par `idx ASC, id ASC` (tie-breaker sur l'id) à la fois dans les annotations `@ORM\OrderBy` de Doctrine **et** dans les QueryBuilder des repositories. Cela garantit un ordre stable même en cas d'égalité d'`idx`.

### Préférences d'affichage

```
DossierDisplayRubrique        → (dossier, rubrique, userDisplay)
EtablissementDisplayRubrique  → (etablissement, rubrique, userDisplay)
```

L'index `[rubriqueId => bool]` est construit depuis ces entités, avec fallback sur `Rubrique::defaultDisplay` (lui-même avec fallback `true`).

---

## 4. Pipeline d'édition

### 4.1 DTOs

Les DTOs servent de pont entre les entités Doctrine et les FormTypes Symfony. Ils sont **sérialisables** par le PropertyAccessor de Symfony grâce à `ArrayAccess`.

#### Hiérarchie

```
DescriptifFormData          (root DTO, ArrayAccess)
  └── RubriqueFormData      (par rubrique, ArrayAccess)
        ├── display: bool
        └── ChampValueData  (par champ racine)
              ├── value: mixed        (champs simples)
              ├── lignes: TableauRowData[]   (PARENT+tableau)
              └── sousChamps: ChampValueData[]  (PARENT+groupe)

TableauRowData
  ├── cells: array<champId, mixed>
  └── valeurIds: array<champId, int|null>
```

#### Pourquoi `ArrayAccess` ?

Le FormBuilder génère des champs avec des `property_path` de la forme `[rubrique_480]` ou `[champ_123]`. Symfony utilise le `PropertyAccessor` pour lire/écrire ces chemins sur le DTO. Le PropertyAccessor sait interpréter la notation bracket `[key]` **uniquement si l'objet implémente `ArrayAccess`**. Sans cela, il chercherait une méthode `getRubrique480()` qui n'existe pas.

```php
// DescriptifFormData parse automatiquement "rubrique_{id}" → entier
public function offsetGet($offset) {
    return $this->getRubrique($this->parseRubriqueId($offset)); // substr($offset, 9)
}

// RubriqueFormData parse "champ_{id}" → entier
public function offsetGet($offset) {
    return $this->getChamp($this->parseChampId($offset)); // substr($offset, 6)
}
```

#### `ChampValueData`

```php
new ChampValueData(
    champId: 123,
    value: 'Mon texte',    // null pour PARENT
    lignes: [],            // TableauRowData[] pour PARENT+tableau
    sousChamps: [],        // ChampValueData[] pour PARENT+groupe
    valeurId: 456          // ID Valeur existante, null si nouvelle
);
```

#### `TableauRowData`

```php
new TableauRowData(
    cells:    [champId1 => 'val1', champId2 => 42],   // valeurs par colonne
    valeurIds:[champId1 => 456,    champId2 => 457]    // IDs Valeur existantes
);
```

---

### 4.2 Hydrator (`DescriptifFormHydrator`)

**Rôle :** Transforme la structure retournée par `Descriptif::getForDossier()` en DTOs.

#### `extractValue(?Valeur $valeur, ?int $typeId)`

| TypeChamp | Source dans Valeur | Type PHP retourné |
|---|---|---|
| TEXT, SELECT | `getStr()` | `?string` |
| TEXTAREA | `getLongStr()` | `?string` |
| NUMBER | `getNumber()` | `?int` |
| CHECKBOX | `getCheckbox()` | `?bool` |
| DATE | `getDate()` | `?\DateTimeInterface` |
| défaut | `getStr()` | `?string` |

#### `hydrateTableau(Champ $champ, array $lignes)`

```
$lignes = list<array<champId, Valeur>>   ← retourné par Descriptif::buildTableauLignes()
```

Pour chaque ligne : crée un `TableauRowData` avec `cells[champId]` = valeur extraite selon le type de l'enfant, et `valeurIds[champId]` = `$valeur->getId()`.

---

### 4.3 Builder (`DescriptifFormBuilder`)

**Rôle :** Construit le `FormInterface` Symfony à partir du `DescriptifFormData` et de la liste de rubriques.

```php
// Formulaire racine nommé 'descriptif' — les champs HTML seront 'descriptif[rubrique_X][champ_Y]'
$builder = $formFactory->createNamedBuilder('descriptif', FormType::class, $formData);

// Pour chaque rubrique :
$rubriqueBuilder = $formFactory->createNamedBuilder(
    'rubrique_' . $rubriqueId,
    FormType::class,
    null,
    [
        'property_path' => '[rubrique_' . $rubriqueId . ']',  // bracket → ArrayAccess
        'data_class'    => RubriqueFormData::class,
    ]
);
$rubriqueBuilder->add('display', CheckboxType::class, ['required' => false]);
// puis pour chaque champ :
$rubriqueBuilder->add('champ_' . $champId, ChampValueType::class, [
    'property_path' => '[champ_' . $champId . ']',
]);
```

---

### 4.4 FormTypes feuille

#### `ChampValueType` — dispatcheur dynamique

Utilise un listener `PRE_SET_DATA` car le type du champ est en base de données (inconnu à la construction) :

```php
// PRE_SET_DATA : lit le champId dans le DTO, charge le Champ, lit son typeId
$champ = $champRepository->find($data->getChampId());
switch ($typeId) {
    case TypeChamp::TEXT:     $form->add('value', ChampTextType::class, ...);     break;
    case TypeChamp::TEXTAREA: $form->add('value', ChampTextareaType::class, ...); break;
    case TypeChamp::SELECT:   $form->add('value', ChampSelectType::class, ['choices' => ...]); break;
    case TypeChamp::NUMBER:   $form->add('value', ChampNumberType::class, ...);   break;
    case TypeChamp::CHECKBOX: $form->add('value', ChampCheckboxType::class, ...); break;
    case TypeChamp::DATE:     $form->add('value', ChampDateType::class, ...);     break;
    case TypeChamp::PARENT:
        if ($champ->getTableau()) {
            $form->add('lignes', CollectionType::class, [
                'entry_type'    => TableauRowType::class,
                'entry_options' => ['child_champs' => $champ->getEnfants()->toArray()],
                'allow_add'     => true, 'allow_delete' => true,
                'prototype'     => true,
            ]);
        } else {
            $form->add('sousChamps', CollectionType::class, [
                'entry_type' => ChampValueType::class,  // récursif
                'allow_add'  => false,
            ]);
        }
        break;
}
```

#### `TableauRowType`

Représente une ligne d'un tableau. Chaque colonne est un champ lié via `property_path` :

```php
$builder->add((string) $champId, $formType, [
    'property_path' => 'cells[' . $champId . ']',  // bracket sur TableauRowData::$cells
]);
```

Le `data_class` est `TableauRowData`. La liste des colonnes est passée via l'option `child_champs`.

#### `ChampDateType`

Hérite de `DateType` avec `widget: 'single_text'` et `required: false`. Symfony sérialise automatiquement `\DateTime` ↔ chaîne ISO `Y-m-d` via le comportement standard du `DateType` en `single_text`.

#### `ChampCheckboxType`

Hérite de `CheckboxType` avec :
- `required: false`
- `false_values: ['0', '', null]` — permet de soumettre une valeur falsy sans que Symfony lève une erreur de transformation

Le rendu utilise un bloc Twig personnalisé (voir §5.2) pour éviter le wrapper Bootstrap 3 `<div class="checkbox"><label>`.

---

### 4.5 Persister (`DescriptifFormPersister`)

**Rôle :** Transforme les DTOs soumis en entités `Valeur` (create / update / delete), puis appelle `flush()`.

#### Stratégie idx (tableaux)

L'`idx` est recalculé à chaque sauvegarde en fonction de la **position dans `$lignes[]`** (1-based). Cela normalise l'ordre après un drag & drop, même si le JS a renommé les indices CollectionType à `0, 1, 2, ...`.

```php
foreach ($lignes as $position => $row) {
    $idx = $position + 1;  // idx 1-based
    $this->persistTableauRow($entity, $row, $idx, $lookup, $touchedValeurIds);
}
```

#### Lookup interne

```php
// Clé 'null' pour champs simples, 'i{idx}' pour les lignes de tableau
$lookup[$champId]['null']    = $valeur;  // champ simple
$lookup[$champId]['i3']      = $valeur;  // ligne idx=3
```

#### Fallback drag & drop

Lors d'un drag & drop, une ligne existante peut avoir un `idx` différent de sa nouvelle position. Le persister cherche d'abord par `i{idx}`, puis par `valeurId` via `findValeurById()` si la clé n'existe pas :

```php
$existing = $lookup[$champId]['i' . $idx] ?? null;
if (null === $existing) {
    $existing = $this->findValeurById($lookup[$champId] ?? [], $valeurId);
}
```

#### Suppression des lignes retirées

```php
// Toutes les Valeur avec idx !== null non présentes dans $touchedValeurIds → supprimées
$this->deleteUntouchedTableauValeurs($entity, $existingValeurs, $touchedValeurIds);
```

#### `isEmptyValue`

| Valeur | Résultat |
|---|---|
| `null` | vide → pas de Valeur en base |
| `false` (checkbox décochée) | vide → pas de Valeur en base |
| `""` (chaîne vide) | vide → pas de Valeur en base |
| tout autre | non-vide → créer/mettre à jour |

#### `upsertDisplayPref`

Crée ou met à jour `DossierDisplayRubrique` / `EtablissementDisplayRubrique`. La `Rubrique` est passée directement depuis la boucle (déjà chargée en mémoire), évitant un re-fetch Doctrine.

---

## 5. Templates et thème Twig

### 5.1 Template principal

**Fichier :** `templates/components/_formulaire_personnalise_edit.html.twig`

**Variables attendues :**
- `form` — `FormView` construit par `DescriptifFormBuilder`
- `capsule` — `CapsuleRubrique|null` (pour le titre)

**Inclure dans un template parent :**
```twig
{% include 'components/_formulaire_personnalise_edit.html.twig' with {form: form, capsule: capsule} %}
```

**Structure de rendu :**

```
form_start(form)
  └── [pour chaque rubrique_X dans form]
        └── <div id="afficher_rubrique-{id}" class="panel ...">
              ├── form_row(display, row_attr={style:display:none}, data-display-checkbox="{id}")
              └── [pour chaque champ_Y]
                    ├── PARENT+tableau → <table> + addRow button
                    ├── PARENT+groupe  → <table> (ligne unique)
                    └── simple         → form_widget(champForm.value)
form_end(form)
```

**Champ `display` :** rendu en `display:none` avec `data-display-checkbox="{rubriqueId}"`. Il est bien soumis dans le formulaire mais invisible. Sa valeur est synchronisée par la checkbox miroir du modal TOC via `edit.js`.

**Modal TOC :** chaque checkbox du modal porte `data-controls-rubrique="{rubriqueId}"`. Elle contrôle à la fois la visibilité du panneau (`#afficher_rubrique-{id}`) et la valeur du champ `display` réel via `edit.js`.

**Bouton Aujourd'hui :** géré par event delegation sur `.btn-today[data-target]` (inline `<script type="module">`). Fonctionne aussi pour les lignes ajoutées dynamiquement.

**Protection beforeunload :** tout clic sur un élément sans `data-do-not-unload` active l'alerte de navigation. Les boutons Annuler et Enregistrer ont `data-do-not-unload` pour bypasser.

---

### 5.2 Thème Twig (`descriptif_theme.html.twig`)

```twig
{% use 'bootstrap_3_layout.html.twig' %}
```

#### `champ_date_widget`

Wrap l'input dans un `input-group` Bootstrap 3 avec le bouton "Aujourd'hui" :

```twig
{% block champ_date_widget %}
    <div class="input-group">
        {{ block('form_widget_simple') }}
        <div class="input-group-btn">
            <button type="button" class="btn btn-default btn-today" data-target="{{ id }}">
                ...
            </button>
        </div>
    </div>
{% endblock %}
```

> L'attribut `data-target="{{ id }}"` référence l'`id` HTML de l'input, utilisé par le JS pour y injecter la date du jour. Le style `width: auto` sur les `<td>` est nécessaire dans les tableaux car Bootstrap 3 force `width: 1%` sur les cellules contenant un `.input-group`.

#### `champ_checkbox_widget`

Rendu brut sans le wrapper `<div class="checkbox"><label>` de Bootstrap 3, car le contexte (cellule `<td>` ou ligne de groupe) fournit toujours son propre label ou en-tête :

```twig
{%- block champ_checkbox_widget -%}
    <input type="checkbox" {{ block('widget_attributes') }}
           {% if value is defined %} value="{{ value }}"{% endif %}
           {% if checked %} checked="checked"{% endif %} />
{%- endblock -%}
```

#### `tableau_row_widget`

Rendu d'une ligne `TableauRowType` comme `<tr>` avec une `<td>` par cellule et une colonne d'édition (boutons déplacement + suppression) :

```twig
{% block tableau_row_widget %}
    <tr>
        {% for cell in form %}
            <td>{{ form_widget(cell) }}</td>
        {% endfor %}
        <td class="colonne-edition">
            <button type="button" class="tdMove btn btn-info">...</button>
            <button type="button" class="deleteRow btn btn-danger">...</button>
        </td>
    </tr>
{% endblock %}
```

---

## 6. Couche JavaScript

### 6.1 `descriptif-collection.js`

Gère l'ajout/suppression de lignes et le drag & drop dans les tableaux dynamiques. Basé sur le mécanisme standard Symfony `CollectionType` (`data-prototype`, `data-index`).

#### Ajout de ligne

```js
$(document).on('click', '.addRow', function () {
    const $tbody = $(this).closest('.table-wrapper').find('tbody[data-prototype]');
    const index = parseInt($tbody.data('index'), 10) || 0;
    const newRow = $tbody.data('prototype').replace(/__name__/g, index);
    $tbody.append(newRow);
    $tbody.data('index', index + 1);
});
```

#### Suppression

```js
$(document).on('click', '.deleteRow', function () {
    $(this).closest('tr').remove();
});
```

#### `reindexTableauRows($tbody)` — après drag & drop

Renomme les attributs `name` et `id` de tous les champs pour que leur index CollectionType reflète l'ordre visuel :

```js
window.reindexTableauRows = function ($tbody) {
    $tbody.find('tr').each(function (newIndex) {
        $(this).find('input, select, textarea').each(function () {
            // name : 'descriptif[rubrique_X][champ_Y][lignes][oldIdx][...]'
            //      → 'descriptif[rubrique_X][champ_Y][lignes][newIndex][...]'
            $(this).attr('name', name.replace(/\[lignes\]\[\d+\]/, '[lignes][' + newIndex + ']'));
            $(this).attr('id',   id.replace(/_lignes_\d+_/, '_lignes_' + newIndex + '_'));
        });
    });
    $tbody.data('index', $tbody.find('tr').length);
};
```

#### Drag & drop (SortableJS)

```js
document.querySelectorAll('tbody[data-prototype]').forEach(function (tbody) {
    Sortable.create(tbody, {
        handle: '.tdMove',
        animation: 100,
        onEnd: function () { window.reindexTableauRows($(tbody)); },
    });
});
```

---

### 6.2 `edit.js`

#### Synchronisation modal TOC ↔ panneaux de rubrique

```
[modal TOC] input[data-controls-rubrique="480"]
    ↕  change
[panneau]   #afficher_rubrique-480   (toggleClass 'hide')
    ↕
[champ caché] input[data-display-checkbox="480"]  (prop 'checked')
```

```js
$('[data-controls-rubrique]').on('change', function() {
    const rubriqueId = $(this).data('controls-rubrique');
    const isChecked = this.checked;
    $('#afficher_rubrique-' + rubriqueId).toggleClass('hide', !isChecked);
    $('[data-display-checkbox="' + rubriqueId + '"]').prop('checked', isChecked);
});
```

#### Scroll shadow sur colonnes d'édition

Ajoute/retire la classe `with-shadow` sur `.colonne-edition` selon la position de scroll horizontal du `.table-responsive`.

#### Protection beforeunload

Bloque la navigation non intentionnelle (alerte navigateur) sauf si le clic cible un élément `data-do-not-unload`.

---

## 7. Ajouter un nouveau type de champ

Procédure complète en 6 étapes :

**1. Ajouter la constante dans `TypeChamp`**
```php
// src/Constant/TypeChamp.php
public const MULTISELECT = 8;
```

**2. Créer `ChampMultiselectType` dans `src/Form/Type/Descriptif/`**
```php
class ChampMultiselectType extends AbstractType
{
    public function getParent(): string { return ChoiceType::class; }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['multiple' => true, 'required' => false]);
    }
}
```

**3. Enregistrer dans `ChampValueType::buildForm()` (listener PRE_SET_DATA)**
```php
case TypeChamp::MULTISELECT:
    $form->add('value', ChampMultiselectType::class, ['choices' => $choices]);
    break;
```

**4. Enregistrer dans `DescriptifFormHydrator::extractValue()`**
```php
case TypeChamp::MULTISELECT:
    return $valeur->getStr(); // adapter selon le stockage choisi
```

**5. Enregistrer dans `DescriptifFormPersister::applyValueToValeur()`**
```php
case TypeChamp::MULTISELECT:
    $valeur->setStr(is_string($value) ? $value : null);
    break;
```

**6. (Optionnel) Ajouter un bloc Twig dans `descriptif_theme.html.twig`**
```twig
{% block champ_multiselect_widget %}
    {# rendu personnalisé si nécessaire #}
    {{ block('choice_widget') }}
{% endblock %}
```

> Ne pas oublier d'ajouter également le cas dans `TableauRowType::resolveLeafType()` si le nouveau type doit pouvoir être utilisé en colonne de tableau.

---

## 8. Points d'attention et décisions d'architecture

### ArrayAccess obligatoire sur les DTOs

Symfony `PropertyAccessor` interprète `[rubrique_480]` comme un accès tableau via `ArrayAccess::offsetGet('rubrique_480')`. Sans cette interface, il chercherait une méthode `getRubrique_480()` (ou similaire) qui n'existe pas, causant une `PropertyAccessException`. La notation bracket est nécessaire ici car les IDs de rubriques/champs sont dynamiques.

### idx normalisé à chaque sauvegarde

L'index `idx` est recalculé en 1-based à chaque `flush()`, indépendamment des idx précédents. Cela garantit la cohérence après :
- un drag & drop (JS renomme les indices `0, 1, 2...` mais pas les idx base)
- une suppression de ligne intermédiaire (trous dans la séquence)
- une insertion de ligne en début de tableau

### `false` = vide pour les checkboxes

Une checkbox décochée n'a pas de `Valeur` en base. `isEmptyValue(false)` retourne `true`. À la lecture, l'absence de `Valeur` est interprétée comme `false` par le formulaire (valeur par défaut du `CheckboxType`).

### Tie-breaker `id ASC` sur les tris

En cas d'égalité d'`idx` entre deux champs ou deux rubriques (configuration incorrecte ou migration), le tri secondaire sur `id ASC` garantit un ordre stable et reproductible.

### Lookup avec préfixe `i` pour les tableaux

Les clés du lookup pour les lignes de tableau sont préfixées par `'i'` (`'i1'`, `'i2'`...) pour éviter une collision avec la clé `'null'` utilisée pour les champs simples, et parce que PHP traite les clés `array` qui ressemblent à des entiers comme des `int`, ce qui casse la comparaison stricte.

### Pas de re-fetch de `Rubrique` dans le Persister

`upsertDisplayPref` reçoit l'objet `Rubrique` directement depuis la boucle de `doPersist`, qui itère sur le tableau `$rubriques` déjà en mémoire (chargé par `findByNomInterneWithFullData`). Évite un `find()` superflu.

### `width: auto` dans les `<td>` contenant `.input-group`

Bootstrap 3 applique `width: 1%` aux `<td>` contenant un `.input-group` via une règle CSS destinée aux tables `.input-group`. Dans un tableau de formulaire personnalisé, cela compresse excessivement les colonnes. Un style inline `width: auto` sur les `<td>` de champ date corrige ce comportement.
