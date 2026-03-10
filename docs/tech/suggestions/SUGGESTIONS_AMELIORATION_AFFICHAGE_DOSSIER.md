# Suggestions d'amélioration pour l'affichage des dossiers

Ce document liste les suggestions d'amélioration pour le template `templates/dossier/index.html.twig` qui n'ont pas encore été implémentées.

**Date :** 5 décembre 2025

## État actuel

✅ **Implémenté :**
- Point 4 : Extraction des sections complexes dans des partials (`_documents_manquants.html.twig`)
- Point 7 : Extension Twig pour `shouldDisplayField` (filtre `should_display_field`)
- Point 8 : Remplacement des styles inline par des classes CSS Bootstrap

⏳ **À implémenter :**
- Points 1, 2, 3, 5, 6 (détaillés ci-dessous)

---

## 1. Créer un Twig macro pour les champs répétitifs

### Problème
Le template contient beaucoup de répétitions de la structure `<li class="row">` avec le même pattern :
- Vérification de visibilité du champ
- Structure HTML identique (libellé + valeur)

### Solution proposée

**Créer un fichier :** `templates/dossier/_macros.html.twig`

```twig
{% macro display_field(label, value, condition = true) %}
    {% if condition %}
        <li class="row">
            <div class="left libelle col-md-3">{{ label }}</div>
            <div class="right value col-md-9">{{ value|raw }}</div>
        </li>
    {% endif %}
{% endmacro %}

{% macro display_date_field(label, date, condition = true) %}
    {% if condition and date %}
        <li class="row">
            <div class="left libelle col-md-3">{{ label }}</div>
            <div class="right value col-md-9">{{ date|date('d/m/Y') }}</div>
        </li>
    {% endif %}
{% endmacro %}

{% macro display_text_field(label, text, condition = true) %}
    {% if condition and text %}
        <li class="row">
            <div class="left libelle col-md-3">{{ label }}</div>
            <div class="right value col-md-9">{{ text|nl2br }}</div>
        </li>
    {% endif %}
{% endmacro %}
```

### Utilisation dans le template

```twig
{% import 'dossier/_macros.html.twig' as macros %}

{# Au lieu de #}
{% if dossier|should_display_field('OBJET') and dossier.objet %}
    <li class='row'>
        <div class='left libelle col-md-3'>Objet</div>
        <div class='right value col-md-9'>
            {{ dossier.objet|nl2br }}
        </div>
    </li>
{% endif %}

{# Écrire simplement #}
{{ macros.display_text_field(
    'Objet',
    dossier.objet,
    dossier|should_display_field('OBJET')
) }}
```

### Avantages
- ✅ **Réduction de code** : ~60% de lignes en moins pour chaque champ
- ✅ **Cohérence** : Structure HTML identique garantie
- ✅ **Maintenabilité** : Modification centralisée de la structure
- ✅ **Lisibilité** : Code plus déclaratif et expressif

### Estimation
- **Complexité** : Moyenne
- **Temps** : 2-3 heures
- **Impact** : Réduction de ~200 lignes dans le template principal

---

## 2. Grouper les vérifications de champs par catégorie

### Problème
L'appel `dossier|should_display_field('CHAMP')` est répété 40+ fois dans le template, ce qui peut impacter les performances (même si Twig met en cache).

### Solution proposée

**En début de template, créer un dictionnaire :**

```twig
{% set fields_visible = {
    'dateInsert': dossier|should_display_field('DATEINSERT'),
    'objet': dossier|should_display_field('OBJET'),
    'demandeur': dossier|should_display_field('DEMANDEUR'),
    'lieuReunion': dossier|should_display_field('LIEUREUNION'),
    'docUrba': dossier|should_display_field('NUMDOCURBA'),
    'dateMairie': dossier|should_display_field('DATEMAIRIE'),
    'dateSecretariat': dossier|should_display_field('DATESECRETARIAT'),
    'dateEnvoiTransit': dossier|should_display_field('DATEENVTRANSIT'),
    'dateSdis': dossier|should_display_field('DATESDIS'),
    'serviceInstructeur': dossier|should_display_field('SERVICEINSTRUC'),
    'commission': dossier|should_display_field('COMMISSION'),
    'dateVisite': dossier|should_display_field('DATEVISITE'),
    'dateRvrat': dossier|should_display_field('DATERVRAT'),
    'dateCommission': dossier|should_display_field('DATECOMM'),
    'reglesDerogation': dossier|should_display_field('REGLEDEROG'),
    'justificationDerogation': dossier|should_display_field('JUSTIFDEROG'),
    'mesuresCompensatoires': dossier|should_display_field('MESURESCOMPENS'),
    'mesuresComplementaires': dossier|should_display_field('MESURESCOMPLE'),
    'incomplet': dossier|should_display_field('INCOMPLET'),
    'horsDelai': dossier|should_display_field('HORSDELAI'),
    'differeAvis': dossier|should_display_field('DIFFEREAVIS'),
    'npsp': dossier|should_display_field('NPSP'),
    'absenceQuorum': dossier|should_display_field('ABSQUORUM'),
    'avis': dossier|should_display_field('AVIS'),
    'delaiPrescription': dossier|should_display_field('DELAIPRESC'),
    'avisCommission': dossier|should_display_field('AVIS_COMMISSION'),
    'observations': dossier|should_display_field('OBSERVATION'),
    'datePrefecture': dossier|should_display_field('DATEPREF'),
    'dateReponse': dossier|should_display_field('DATEREP'),
    'dateTransfertCommission': dossier|should_display_field('DATETRANSFERTCOMM'),
    'dateReceptionCommission': dossier|should_display_field('DATERECEPTIONCOMM'),
    'dateReunion': dossier|should_display_field('DATEREUN'),
    'preventionnistes': dossier|should_display_field('PREVENTIONNISTE'),
    'operationSdis': dossier|should_display_field('OPERSDIS'),
    'rcci': dossier|should_display_field('RCCI'),
    'rex': dossier|should_display_field('REX'),
    'chargeSecurite': dossier|should_display_field('CHARGESEC'),
    'numeroIntervention': dossier|should_display_field('NUMINTERV'),
    'dateIntervention': dossier|should_display_field('DATEINTERV'),
    'dureeIntervention': dossier|should_display_field('DUREEINTERV'),
    'dateSignature': dossier|should_display_field('DATESIGN'),
} %}
```

**Usage dans le template :**

```twig
{% if fields_visible.objet and dossier.objet %}
    <li class='row'>
        <div class='left libelle col-md-3'>Objet</div>
        <div class='right value col-md-9'>
            {{ dossier.objet|nl2br }}
        </div>
    </li>
{% endif %}
```

### Avantages
- ✅ **Performance** : Appel du service une seule fois par champ
- ✅ **Lisibilité** : Noms de variables plus parlants en camelCase
- ✅ **Debug** : Plus facile de voir tous les champs disponibles
- ✅ **Cache Twig** : Meilleure optimisation possible

### Inconvénients
- ⚠️ **Verbosité initiale** : Ajout de ~40 lignes en début de template
- ⚠️ **Maintenance** : Il faut penser à ajouter les nouveaux champs

### Estimation
- **Complexité** : Faible
- **Temps** : 1 heure
- **Impact** : Amélioration potentielle des performances (à mesurer)

---

## 3. Améliorer l'accessibilité

### Problème
Le template actuel utilise des structures HTML génériques (`<ul>`, `<li>`) qui ne sont pas sémantiquement appropriées pour afficher des paires clé-valeur.

### Solution proposée

**Remplacer les listes par des listes de définitions :**

```twig
{# Au lieu de #}
<ul class='list-unstyled'>
    <li class='row'>
        <div class='left libelle col-md-3'>Date de création du dossier</div>
        <div class='right value col-md-9'>
            {{ dossier.dateInsert|date('d/m/Y') }}
        </div>
    </li>
</ul>

{# Utiliser #}
<dl class="dossier-info">
    <div class="row">
        <dt class="col-md-3">Date de création du dossier</dt>
        <dd class="col-md-9">{{ dossier.dateInsert|date('d/m/Y') }}</dd>
    </div>
</dl>
```

**Ajouter des attributs ARIA pour les icônes :**

```twig
{# Au lieu de #}
<span class='glyphicon glyphicon-calendar'></span>

{# Utiliser #}
<span class='glyphicon glyphicon-calendar' aria-label="Afficher l'ordre du jour" role="img"></span>
```

**Améliorer les indicateurs visuels :**

```twig
{# Pour les états importants #}
<strong class="text-danger" role="alert">Incomplet</strong>
<strong class="text-success" role="status">Complet</strong>
```

### Avantages
- ✅ **Accessibilité** : Meilleure navigation pour les lecteurs d'écran
- ✅ **Sémantique** : HTML plus correct et significatif
- ✅ **SEO** : Meilleur référencement (si applicable)
- ✅ **Standards** : Conformité WCAG 2.1

### Estimation
- **Complexité** : Moyenne
- **Temps** : 3-4 heures (test avec lecteurs d'écran recommandé)
- **Impact** : Amélioration significative de l'accessibilité

---

## 5. Optimiser les vérifications null

### Problème
De nombreuses vérifications `{% if dossier.champ %}` sont suivies d'un simple affichage de la valeur.

### Solution proposée

**Utiliser l'opérateur de coalescence nulle et les filtres Twig :**

```twig
{# Au lieu de #}
{% if dossier.objet %}
    <li class='row'>
        <div class='left libelle col-md-3'>Objet</div>
        <div class='right value col-md-9'>
            {{ dossier.objet|nl2br }}
        </div>
    </li>
{% endif %}

{# Utiliser (avec la macro du point 1) #}
{{ macros.display_text_field('Objet', dossier.objet ?? '') }}
```

**Pour les valeurs par défaut :**

```twig
{{ dossier.demandeur|default('Non renseigné') }}
```

**Pour les transformations conditionnelles :**

```twig
{{ dossier.incomplet ? 'Incomplet' : 'Complet' }}
```

### Avantages
- ✅ **Concision** : Code plus court et direct
- ✅ **Expressivité** : Intent plus clair
- ✅ **Moins d'imbrication** : Meilleure lisibilité

### Note
Cette suggestion fonctionne bien en combinaison avec le point 1 (macros).

### Estimation
- **Complexité** : Faible
- **Temps** : 1-2 heures
- **Impact** : Amélioration de la lisibilité

---

## 6. Ajouter des classes CSS sémantiques

### Problème
Les classes CSS actuelles sont orientées structure (`row`, `col-md-3`) plutôt que sémantique.

### Solution proposée

**Ajouter des classes BEM (Block Element Modifier) :**

```twig
<li class="row dossier-field dossier-field--objet">
    <div class="dossier-field__label col-md-3">Objet</div>
    <div class="dossier-field__value col-md-9">{{ dossier.objet|nl2br }}</div>
</li>

<li class="row dossier-field dossier-field--date dossier-field--date-creation">
    <div class="dossier-field__label col-md-3">Date de création du dossier</div>
    <div class="dossier-field__value col-md-9">{{ dossier.dateInsert|date('d/m/Y') }}</div>
</li>

<li class="row dossier-field dossier-field--status" data-status="{{ dossier.incomplet ? 'incomplet' : 'complet' }}">
    <div class="dossier-field__label col-md-3">État du dossier</div>
    <div class="dossier-field__value col-md-9">
        <strong class="dossier-status dossier-status--{{ dossier.incomplet ? 'incomplete' : 'complete' }}">
            {{ dossier.incomplet ? 'Incomplet' : 'Complet' }}
        </strong>
    </div>
</li>
```

**Créer un fichier CSS dédié :** `assets/css/dossier.css`

```css
.dossier-field {
    margin-bottom: 15px;
}

.dossier-field__label {
    font-weight: 600;
    color: #333;
}

.dossier-field__value {
    color: #555;
}

.dossier-field--date .dossier-field__value {
    font-family: monospace;
}

.dossier-status--incomplete {
    color: #d9534f;
}

.dossier-status--complete {
    color: #5cb85c;
}

/* Facilite le ciblage JavaScript si nécessaire */
.dossier-field--objet,
.dossier-field--demandeur,
.dossier-field--commission {
    /* Styles spécifiques si nécessaire */
}
```

### Avantages
- ✅ **Maintenabilité CSS** : Classes réutilisables et cohérentes
- ✅ **JavaScript** : Facilite le ciblage des éléments (ex: `document.querySelector('.dossier-field--objet')`)
- ✅ **Thématisation** : Plus facile de personnaliser l'apparence
- ✅ **Debug** : Classes plus explicites dans l'inspecteur

### Estimation
- **Complexité** : Moyenne
- **Temps** : 2-3 heures
- **Impact** : Amélioration de la maintenabilité CSS

---

## Priorités recommandées

### 🔴 Haute priorité
1. **Point 1** - Macros Twig : Impact immédiat sur la maintenabilité
2. **Point 3** - Accessibilité : Amélioration de l'expérience utilisateur

### 🟡 Moyenne priorité
3. **Point 2** - Groupement des vérifications : Optimisation des performances
4. **Point 6** - Classes CSS sémantiques : Amélioration de la structure

### 🟢 Basse priorité
5. **Point 5** - Optimisation vérifications null : Amélioration cosmétique

---

## Plan d'implémentation suggéré

### Phase 1 : Macros et structure (Points 1 + 6)
1. Créer le fichier de macros
2. Ajouter les classes CSS sémantiques
3. Refactoriser le template en utilisant les macros
4. Tester l'affichage

**Estimation :** 4-5 heures

### Phase 2 : Accessibilité (Point 3)
1. Remplacer les `<ul>/<li>` par `<dl>/<dt>/<dd>`
2. Ajouter les attributs ARIA
3. Tester avec un lecteur d'écran
4. Ajuster si nécessaire

**Estimation :** 3-4 heures

### Phase 3 : Optimisations (Points 2 + 5)
1. Créer le dictionnaire de vérifications
2. Refactoriser le template pour utiliser le dictionnaire
3. Optimiser les vérifications null
4. Mesurer l'impact performance

**Estimation :** 2-3 heures

**Total estimé :** 9-12 heures

---

## Tests à effectuer après implémentation

- [ ] Affichage correct de tous les types de dossiers (Études, Visites, Groupes de visites, etc.)
- [ ] Vérification de l'affichage conditionnel des champs selon la nature
- [ ] Test d'accessibilité avec NVDA ou JAWS
- [ ] Validation HTML (W3C Validator)
- [ ] Test de régression visuelle (comparaison avant/après)
- [ ] Vérification des performances (temps de rendu)
- [ ] Test sur différents navigateurs
- [ ] Test responsive (mobile, tablette)

---

## Notes complémentaires

### Compatibilité
- Toutes les suggestions sont compatibles avec Symfony 4 et Twig 2
- Bootstrap 3 est conservé (pas de migration vers Bootstrap 4/5 suggérée)
- Pas de dépendances JavaScript supplémentaires requises

### Migration progressive
Les suggestions peuvent être implémentées indépendamment les unes des autres, permettant une migration progressive sans risque de casser l'affichage existant.

### Documentation
Après implémentation, mettre à jour :
- La documentation développeur
- Les guidelines de contribution
- Les exemples de templates

---

**Auteur :** GitHub Copilot CLI  
**Date de création :** 5 décembre 2025  
**Dernière mise à jour :** 5 décembre 2025
