# Plan de migration incrémental - Édition des dossiers

**Date :** 10 décembre 2025  
**Objectif :** Migration de la fonctionnalité d'édition/création de dossiers depuis Zend v1 vers Symfony 4  
**Dernière mise à jour :** 10 décembre 2025 - 17h15

---

## 📊 État d'avancement

### ✅ Phases terminées

| Phase | Description | Natures | Commit | Date |
|-------|-------------|---------|--------|------|
| **Phase 0** | Infrastructure de base | - | `feat(dossier): infrastructure édition Phase 0` | 10/12/2025 |
| **Phase 1** | Réunions (Type 4) | 31, 32, 43 | `fix(dossier): corrections systématiques Phase 1` | 10/12/2025 |
| **Phase 2** | Courriers/Courriels (Type 7) | 49-55, 57-60, 65 | `feat(dossier): ajout natures courriers manquantes` | 10/12/2025 |

**Total : 15 natures opérationnelles** (3 réunions + 12 courriers)

### ⏳ Phases à réaliser

| Phase | Description | Natures estimées | Priorité |
|-------|-------------|------------------|----------|
| **Phase 3** | Visites périodiques | 21, 28-29 | Haute |
| **Phase 4** | Études simples (PC/AT) | 1-13 | Moyenne |
| **Phase 5** | Interventions | 37-39 | Moyenne |
| **Phase 6** | Autres types | Variables | Basse |

---

## Principes directeurs

### Contraintes

1. **Conformité 100% avec le legacy** : Comportement fonctionnel identique
2. **Approche incrémentale** : Commit après chaque incrément pour faciliter la revue
3. **Du simple au complexe** : Commencer par les types/natures les plus simples
4. **Réutilisation** : Récupérer les morceaux fonctionnels de la page de consultation
5. **Tests systématiques** : PHPStan + CS + Tests après chaque incrément

### Architecture générale

```
Legacy: 1 page unique avec affichage dynamique JavaScript
         └─> index.phtml + JavaScript embarqué

Symfony: Même approche avec JavaScript vanilla/jQuery
         ├─> DossierController::edit()
         ├─> templates/dossier/edit.html.twig
         ├─> DossierType (FormType)
         └─> public/js/dossier/form.js (JavaScript vanilla/jQuery)
```

**Note :** Pas d'utilisation de Node/Webpack/Stimulus pour éviter les dépendances supplémentaires côté client.

---

## Analyse du legacy

### Routes principales

| Route | Action | Description |
|-------|--------|-------------|
| `/dossier/add` | `addAction()` | Création nouveau dossier (forward vers index) |
| `/dossier/index?id={id}` | `indexAction()` | Édition dossier existant |
| `/dossier/savenew` | `savenewAction()` | Sauvegarde nouveau dossier |
| `/dossier/save` | `saveAction()` | Sauvegarde dossier existant |

### Structure du formulaire legacy

**Fichier principal :** `application/views/scripts/dossier/index.phtml`

**Sections principales :**
1. **Informations générales** (inline dans index.phtml)
   - Type de dossier
   - Nature
   - Objet
   - Service instructeur
   - Commission concernée
   - Préventionnistes
   - Dates (réception, etc.)
   
2. **Sections séparées** (includes)
   - Descriptif (`descriptif.phtml`)
   - Avis et dérogations (`avis-et-derogations-edit.phtml`)
   - Documents manquants (`formdocmanquant.phtml`)
   - Textes applicables (`edit-textes-applicables.phtml`)
   - Vérifications techniques (`edit-verifications-techniques.phtml`)
   - Effectifs et dégagements (`effectifs-degagements-dossier-edit.phtml`)
   - Prescriptions (`prescription-edit.phtml`)

### JavaScript dynamique

**Comportement principal :**
- Affichage/masquage des champs selon le type/nature sélectionné
- Utilise `$listeChamps` (PHP) → converti en JSON pour JavaScript
- Events `change` sur les selects `type` et `nature`

**Fichier :** Embarqué dans `index.phtml` (pas de fichier séparé)

---

## Plan incrémental

### Phase 0 : Préparation (Infrastructure)

**Objectif :** Mettre en place l'infrastructure de base

**Tâches :**
- [x] Créer `DossierType` (FormType Symfony) avec champs de base - ✅ Existant, amélioré
- [x] Créer `DossierController::edit()` et `DossierController::create()` - ✅ Existants
- [x] Créer template `templates/dossier/edit.html.twig` minimal - ✅ Existant, amélioré
- [x] Créer `public/js/dossier/form.js` (JavaScript vanilla/jQuery) - ✅ Créé
- [x] Adapter `DossierFieldsService` pour retourner JSON pour JavaScript - ✅ Méthodes ajoutées

**Commit :** `feat(dossier): infrastructure de base pour l'édition (Phase 0)`

**Validation :**
- ✅ Route `/dossier/{id}/modifier` accessible
- ✅ Formulaire s'affiche avec champs de base
- ✅ PHPStan 0 erreur
- ✅ CS conforme
- ✅ JavaScript créé (affichage dynamique préparé)

**Améliorations Phase 0 :**
- Option `is_edit` dans DossierType pour désactiver type/nature en édition
- Méthodes `getAllFieldsByNature()` et `getFieldsForNature()` dans DossierFields
- JavaScript `form.js` avec gestion dynamique des champs (structure prête)
- Template mis à jour avec inclusion du JS

---

### Phase 1 : Réunion (Type 4, Nature 31-32-43)

**Choix :** Type le plus simple, peu de champs, pas de logique complexe

**Natures :**
- 31 : Réunion de chantier dans les locaux du SDIS
- 32 : Réunion de chantier à l'extérieur du SDIS
- 43 : Réunion téléphonique

**Champs implémentés :**
```php
// Communs (déjà présents)
- type (select, disabled en édition) ✅
- dateInsert (date création) ✅
- objet (textarea) ✅
- observationDossier (textarea) ✅
- dateSignature (date) ✅
- horsDelai (checkbox) ✅

// Spécifiques réunions
- dateVisite (date réunion) [31, 32, 43] ✅
- lieuVisite (text lieu réunion) [32 uniquement] ✅
- demandeur (text) [31, 32, 43] ✅
- dateReception (date réception SDIS) [31, 32, 43] ✅
```

**JavaScript implémenté :**
- ✅ Mapping des champs legacy → Symfony
- ✅ Affichage dynamique selon la nature sélectionnée
- ✅ Configuration chargée depuis le serveur (JSON)
- ⏳ Préventionnistes (autocomplete multi) - Phase future

**Commit :** `feat(dossier): édition réunions (natures 31-32-43) - Phase 1`

**Validation :**
- ✅ PHPStan : 0 erreur
- ✅ CS : conforme
- ✅ Champs ajoutés au FormType
- ✅ Template mis à jour
- ✅ JavaScript mapping legacy ↔ Symfony
- ✅ Configuration JSON passée au frontend

**Notes :**
- Les préventionnistes nécessitent un composant autocomplete (Phase future)
- Le formulaire est fonctionnel mais l'affichage dynamique sera testé visuellement

---

### Phase 2 : Courriers simples (Type 7, Natures 49-54)

**Choix :** Peu de champs, pas de logique métier complexe

**Natures :**
- 49 : Courrier entrant
- 50 : Courrier sortant
- 51 : Courriel entrant
- 52 : Courriel sortant
- 53 : Lettre (courrier) à destination du maire
- 54 : Lettre (courrier) à destination du préfet

**Champs à implémenter :**
```php
// Nouveaux champs
- DATEREP (date réception SDIS)
- DATEPREF (date réception préfecture) [54 uniquement]
- DATETRANSFERTCOMM (date transfert commission)
- DATEENVOI (date envoi/transit)
```

**JavaScript :**
- Affichage conditionnel de `DATEPREF` si nature = 54
- Toujours simple, pas de dépendances complexes

**Commit :** `feat(dossier): édition courriers simples (natures 49-54)`

---

### Phase 3 : Visites périodiques simples (Type 2, Nature 21)

**Choix :** Visite la plus courante, introduction de la notion de commission

**Nature :**
- 21 : Visite périodique

**Champs à implémenter :**
```php
// Nouveaux champs
- COMMISSIONDOSSIER (select commission)
- DATEVISITE (date visite)
- DATECOMM (date commission en salle)
- DIFFAVIS (checkbox)
- HORSDELAI (checkbox)
- DATEINSERTCTRL (date réception RVRAT)
- DATEECHEANCE (date échéance levée prescriptions)
- INCOMPLET (checkbox)
- AVIS_DOSSIER (select avis commission)
- ABSQUORUM (checkbox)
- NEPEUTSEPRONONCER (checkbox)
```

**JavaScript :**
- Affichage/masquage section avis selon checkboxes
- Gestion des dates de visite/commission

**Commit :** `feat(dossier): édition visites périodiques (nature 21)`

---

### Phase 4 : Études simples (Type 1, Natures AT/PC)

**Choix :** Études sans dérogations

**Natures :**
- 1 : Permis de construire (PC)
- 2 : Autorisation de travaux (AT)

**Champs à implémenter :**
```php
// Nouveaux champs
- NUMDOCURBA (text numéro PC/AT)
- DATEREP (date réception mairie)
- DATESECRETARIAT (date réception secrétariat SDIS)
- SERVICEINSTRUC (text service instructeur)
- COMMISSIONDOSSIER (select commission)
- DATECOMM (date commission)
- INCOMPLET (checkbox)
- AVIS_DOSSIER_RAPPORTEUR (select avis rapporteur)
- AVIS_DOSSIER (select avis commission)
```

**JavaScript :**
- Logique d'affichage selon type/nature
- Gestion des avis (rapporteur vs commission)

**Commit :** `feat(dossier): édition études simples PC/AT (natures 1-2)`

---

### Phase 5 : Dérogations (Type 1, Natures avec dérogations)

**Choix :** Introduction des champs de dérogations

**Natures :**
- Toutes les études avec possibilité de dérogations

**Champs à implémenter :**
```php
// Nouveaux champs de dérogations
- REGLEDEROG (textarea règles dérogation)
- JUSTIFDEROG (textarea justification)
- MESURESCOMPENS (textarea mesures compensatoires)
- MESURESCOMPLE (textarea mesures complémentaires)
```

**JavaScript :**
- Affichage conditionnel de la section dérogations

**Commit :** `feat(dossier): gestion des dérogations`

---

### Phase 6 : Groupes de visites (Type 3)

**Choix :** Logique plus complexe (gestion de groupe)

**Natures :**
- 25 à 29 : Groupes de visites

**Nouveautés :**
- Liaison avec plusieurs visites
- Avis de groupe

**Commit :** `feat(dossier): édition groupes de visites (type 3)`

---

### Phase 7 : Interventions (Type 5)

**Champs spécifiques interventions :**
```php
- OPSDIS (checkbox opération SDIS)
- RCCI (checkbox)
- REX (checkbox)
- NUMINTERV (text numéro intervention)
- DATEINTERV (datetime)
- HEUREINTERV (time)
- DUREEINTERV (time durée)
```

**Commit :** `feat(dossier): édition interventions (type 5)`

---

### Phase 8 : Arrêtés (Type 6)

**Commit :** `feat(dossier): édition arrêtés (type 6)`

---

### Phase 9 : Sous-formulaires complexes

**Fonctionnalités avancées :**
- Documents manquants (modal/inline)
- Textes applicables
- Vérifications techniques
- Effectifs et dégagements
- Prescriptions

**Commit par sous-formulaire :**
- `feat(dossier): gestion documents manquants`
- `feat(dossier): gestion textes applicables`
- `feat(dossier): gestion vérifications techniques`
- `feat(dossier): gestion effectifs/dégagements`
- `feat(dossier): gestion prescriptions`

---

### Phase 10 : Intégration Plat'AU

**Actions de renvoi :**
- Bouton "Renvoyer PEC"
- Bouton "Renvoyer Avis"

**Commit :** `feat(dossier): actions Plat'AU sur formulaire édition`

---

## Points d'attention

### Réutilisation de code

**À récupérer depuis la consultation :**
- ✅ `DossierFieldsService::shouldDisplayField()` (déjà existant)
- ✅ `DossierExtension` (filtre Twig)
- ✅ Layout `layouts/dossier.html.twig`
- ✅ Navigation latérale (Subscriber)

**À adapter :**
- Service pour JSON (JavaScript) : `DossierFieldsService::getFieldsAsJson()`

### JavaScript moderne

**Remplacer jQuery par :**
- **Stimulus** pour les contrôleurs
- **Fetch API** pour AJAX
- **FormData** pour sérialisation

**Approche :**
```javascript
// assets/controllers/dossier_form_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['type', 'nature', 'fieldContainer']
    
    connect() {
        // Charger les champs selon type/nature
    }
    
    onTypeChange(event) {
        // Mettre à jour les natures disponibles
        // Afficher/masquer les champs
    }
    
    onNatureChange(event) {
        // Afficher/masquer les champs
    }
}
```

### FormType Symfony

**Structure recommandée :**
```php
class DossierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', EntityType::class, [
                'class' => DossierType::class,
                'disabled' => $options['is_edit'], // Désactivé en édition
            ])
            ->add('objet', TextareaType::class)
            // ... autres champs
        ;
        
        // Event listener pour champs dynamiques
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                // Ajouter les champs selon le type/nature
            }
        );
    }
}
```

### Validation

**Contraintes Symfony :**
```php
class Dossier
{
    /**
     * @Assert\NotBlank(groups={"reunion"})
     */
    private $dateVisite;
    
    /**
     * @Assert\NotBlank(groups={"etude"})
     */
    private $numeroDocumentUrbanisme;
}
```

**Validation par groupe selon type/nature.**

---

## Suggestions d'améliorations

**À documenter dans :** `docs/tech/suggestions/SUGGESTIONS_AMELIORATION_EDITION_DOSSIER.md`

**Idées :**
1. Découper le formulaire en étapes (wizard)
2. Validation en temps réel (AJAX)
3. Sauvegarde automatique (draft)
4. Historique des modifications
5. Prévisualisation avant sauvegarde
6. Upload de documents directement dans le formulaire
7. Assistance à la saisie (IA)

---

## Critères de succès

**Pour chaque incrément :**
- ✅ Conformité 100% avec le legacy (comportement fonctionnel)
- ✅ PHPStan 0 erreur
- ✅ CS conforme (PHP-CS-Fixer + Twig-CS-Fixer)
- ✅ Tests unitaires/fonctionnels passent
- ✅ Commit propre avec message descriptif
- ✅ Documentation à jour

**Global :**
- ✅ Toutes les 68 natures gérées
- ✅ Création + Édition + Sauvegarde fonctionnelles
- ✅ JavaScript moderne (Stimulus, pas de jQuery inline)
- ✅ Accessibilité WCAG 2.1 AA
- ✅ Performance (temps de chargement < 2s)

---

## Prochaine étape

**Commencer par Phase 0 : Infrastructure de base**

Accord pour démarrer ?
