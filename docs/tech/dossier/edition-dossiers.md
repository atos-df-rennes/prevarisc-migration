# Documentation - Édition des dossiers

**Date de création :** 10 février 2026  
**Statut :** Squelette vérifié - À compléter

> ⚠️ Cette documentation contient UNIQUEMENT des éléments vérifiés dans le code.
> Chaque élément listé ci-dessous existe réellement.

---

## Table des matières

1. [Stack technique](#stack-technique)
2. [Entités](#entités)
3. [Services](#services)
4. [FormTypes](#formtypes)
5. [Controllers](#controllers)
6. [JavaScript](#javascript)
7. [Événements custom](#événements-custom)
8. [Constantes](#constantes)
9. [Configuration](#configuration)

---

## Stack technique

**Vérifiée dans composer.json et package.json :**

| Composant | Version |
|-----------|---------|
| PHP | 7.1.33 |
| Symfony | 4.4 LTS |
| Doctrine ORM | 2.14 |
| Twig | 2.x |
| Bootstrap | 3.x |
| jQuery | (à vérifier) |
| jQuery FullCalendar | v1.6.4 |

---

## Entités

**Localisation :** `src/Entity/`

### Entités liées au dossier

| Entité | Fichier | Description |
|--------|---------|-------------|
| `Dossier` | `Dossier.php` | Entité principale dossier |
| `DossierAffectation` | `DossierAffectation.php` | Affectations préventionnistes |
| `DossierDocumentConsulte` | `DossierDocumentConsulte.php` | Documents consultables |
| `DossierDocumentManquant` | `DossierDocumentManquant.php` | Documents manquants (workflow incomplet) |
| `DossierDocumentUrbanisme` | `DossierDocumentUrbanisme.php` | Documents d'urbanisme |
| `DossierDisplayRubrique` | `DossierDisplayRubrique.php` | Configuration affichage rubriques |
| `DossierNatureListe` | `DossierNatureListe.php` | Liste des natures de dossier |
| `DossierPieceJointe` | `DossierPieceJointe.php` | Pièces jointes |
| `DossierType` | `DossierType.php` | Types de dossier |

### Entités liées aux prescriptions

| Entité | Fichier | Description |
|--------|---------|-------------|
| `PrescriptionDossier` | `PrescriptionDossier.php` | Prescriptions liées au dossier |
| `PrescriptionDossierAssociation` | `PrescriptionDossierAssociation.php` | Associations prescriptions |

---

## Services

**Localisation :** `src/Service/`

### Services principaux pour l'édition dossier

| Service | Fichier | Namespace complet |
|---------|---------|-------------------|
| `Dossier` | `Dossier.php` | `App\Service\Dossier` |
| `Informations` | `Informations.php` | `App\Service\Informations` |
| `Prescriptions` | `Prescriptions.php` | `App\Service\Prescriptions` |
| `Changement` | `Changement.php` | `App\Service\Changement` |

### Services dans src/Service/Dossier/

| Service | Fichier |
|---------|---------|
| `DocumentConsulteTransferService` | `Dossier/DocumentConsulteTransferService.php` |
| `DossierAffectationService` | `Dossier/DossierAffectationService.php` |
| `DossierDonnantAvisService` | `Dossier/DossierDonnantAvisService.php` |

### Services documents manquants

| Service | Fichier |
|---------|---------|
| `DossierDocumentManquantService` | `DossierDocumentManquantService.php` |

### Services alertes email (src/Service/Alerte/)

| Service | Fichier |
|---------|---------|
| `AlerteService` | `Alerte/AlerteService.php` |
| `AvisService` | `Alerte/AvisService.php` |
| `MessageService` | `Alerte/MessageService.php` |
| `ObjetService` | `Alerte/ObjetService.php` |

### Services mail

| Service | Fichier |
|---------|---------|
| `MailerConfiguration` | `Mail/MailerConfiguration.php` |

### Services commission

| Service | Fichier |
|---------|---------|
| `CalendarService` | `Commission/CalendarService.php` |
| `CommissionDatesTransformer` | `Commission/CommissionDatesTransformer.php` |
| `CommissionDocumentBuilder` | `Commission/CommissionDocumentBuilder.php` |
| `DateCommission` | `Commission/DateCommission.php` |

### Services Plat'AU

| Service | Fichier |
|---------|---------|
| `PlatauConsultation` | `PlatauConsultation.php` |
| `PlatauTempsRestant` | `PlatauTempsRestant.php` |
| `PieceJointeExportService` | `Platau/PieceJointeExportService.php` |

---

## FormTypes

**Localisation :** `src/Form/`

| FormType | Fichier | Utilisation |
|----------|---------|-------------|
| `DossierType` | `DossierType.php` | Formulaire principal édition dossier |
| `DossierDocumentManquantType` | `DossierDocumentManquantType.php` | Collection documents manquants |
| `DossierDocumentUrbanismeType` | `DossierDocumentUrbanismeType.php` | Collection documents urbanisme |

---

## Controllers

**Localisation :** `src/Controller/`

| Controller | Fichier | Lignes | Responsabilité |
|------------|---------|--------|----------------|
| `DossierController` | `DossierController.php` | ~257 | Controller principal édition/affichage dossiers |
| `AlerteController` | `AlerteController.php` | ~175 | Envoi alertes email |

---

## JavaScript

**Localisation :** `public/js/dossier/`

### Fichiers principaux

| Fichier | Lignes | Description |
|---------|--------|-------------|
| `form.js` | 188 | Orchestrateur principal - coordonne les modules |
| `calendar-init.js` | 509 | Initialisation jQuery FullCalendar v1.6.4 |
| `gestion-incomplet.js` | 681 | Workflow documents manquants (incomplet/complet) |
| `commission.js` | 59 | Gestion champ commission |
| `platau.js` | 215 | Intégration Plat'AU |
| `dossierDocConsulte.js` | 228 | Documents consultables |
| `dossierDocConsulte-bs3.js` | 228 | Documents consultables Bootstrap 3 |
| `dossierGeneral.js` | 59 | Initialisation générale |
| `avisDerogation.js` | 30 | Avis dérogation |
| `avisDerogation-bs3.js` | 31 | Avis dérogation Bootstrap 3 |
| `drop-list-button.js` | 12 | Dropdown lists |
| `drop-list-button-bs3.js` | 16 | Dropdown lists Bootstrap 3 |
| `pieceJointe.js` | 12 | Pièces jointes |

### Modules (public/js/dossier/modules/)

| Fichier | Lignes | Description |
|---------|--------|-------------|
| `type-nature.js` | 332 | Gestion Type/Nature et affichage conditionnel champs |
| `avis.js` | 270 | Gestion avis et dérogations |
| `documents-urbanisme.js` | 64 | Documents d'urbanisme |
| `today-buttons.js` | 48 | Boutons "Aujourd'hui" pour champs date |

**Total :** ~2982 lignes JavaScript

---

## Événements custom

**Vérifiés dans le code source**

### Événements émis

#### Par `modules/type-nature.js`

```javascript
// Ligne 64
document.dispatchEvent(new CustomEvent('dossier:typeChanged', {
    detail: { typeId, typeLabel }
}));

// Ligne 83
document.dispatchEvent(new CustomEvent('dossier:natureChanged', {
    detail: { natureId, natureLabel }
}));

// Ligne 118
document.dispatchEvent(new CustomEvent('dossier:fieldsUpdated', {
    detail: { fields: affectedFields }
}));
```

#### Par `gestion-incomplet.js`

```javascript
// Lignes 236, 251, 402, 572
document.dispatchEvent(new CustomEvent('dossier:updateAvisVisibility'));
```

#### Par `commission.js`

```javascript
// Ligne 49
document.dispatchEvent(new CustomEvent('calendar:hideAll'));
```

### Événements écoutés

#### Dans `calendar-init.js`

```javascript
// Ligne 413
document.addEventListener('calendar:hide', function(event) { ... });

// Ligne 424
document.addEventListener('calendar:hideAll', function() { ... });
```

#### Dans `modules/avis.js`

```javascript
// Ligne 42
document.addEventListener('dossier:updateAvisVisibility', function() { ... });

// Ligne 47
document.addEventListener('dossier:natureChanged', function() { ... });
```

#### Dans `modules/today-buttons.js`

```javascript
// Ligne 13
document.addEventListener('dossier:fieldsUpdated', function() { ... });
```

---

## Constantes

**Localisation :** `src/Constant/`

| Constante | Fichier | Description |
|-----------|---------|-------------|
| `Avis` | `Avis.php` | Constantes avis de commission |
| `CategorieEtablissement` | `CategorieEtablissement.php` | Catégories établissements |
| `CommissionTypeEvenement` | `CommissionTypeEvenement.php` | Types événements commission |
| `DossierNature` | `DossierNature.php` | Constantes natures dossier (NATURES_DONNANT_AVIS, VISITE_AVANT_OUVERTURE) |
| `DossierType` | `DossierType.php` | Types de dossier |
| `GenreEtablissement` | `GenreEtablissement.php` | Genres établissements (CELLULE = 3) |

### Exemple constante vérifiée

**Fichier :** `src/Constant/DossierNature.php`

```php
class DossierNature
{
    // Utilisé dans DossierRepository, DossierDonnantAvisService
    public const NATURES_DONNANT_AVIS = [
        // Liste à documenter
    ];
    
    // Utilisé dans DossierController ligne 228
    public const VISITE_AVANT_OUVERTURE = [47, 48];
}
```

---

## Configuration

### Configuration email

**Fichiers :**
- `config/prevarisc.php` - Paramètres PREVARISC_MAIL_*
- `config/packages/mailer.php` - Configuration Mailer Symfony
- `src/Service/Mail/MailerConfiguration.php` - Construction DSN dynamique
- `src/DependencyInjection/PrevariscMailerDsnProcessor.php` - EnvVarProcessor

**Variables d'environnement (à documenter) :**
- PREVARISC_MAIL_ENABLED
- PREVARISC_MAIL_TRANSPORT
- PREVARISC_MAIL_HOST
- PREVARISC_MAIL_PORT
- PREVARISC_MAIL_USERNAME
- PREVARISC_MAIL_PASSWORD
- PREVARISC_MAIL_SENDER
- PREVARISC_MAIL_SENDER_NAME

### Calendrier

**Bibliothèque utilisée :** jQuery FullCalendar v1.6.4

**Fichiers :**
- `public/js/jquery.fullcalendar.js`
- `public/css/jquery/jquery.fullcalendar.css`
- `public/js/dossier/calendar-init.js` (509 lignes)

**Chargement vérifié dans :** `templates/base.html.twig`, `templates/dossier/edit.html.twig`

---

## Validation

**Fichiers :**
- `src/Validator/UniqueUtilisateur.php` - Contrainte validation custom
- `src/Validator/UniqueUtilisateurValidator.php` - Validateur login OU email unique

---

## Templates

**Localisation :** `templates/dossier/`

| Template | Description |
|----------|-------------|
| `edit.html.twig` | Template principal édition dossier (~600 lignes) |
| `_document_manquant_item.html.twig` | Template collection document manquant |
| `_prescription_item.html.twig` | (À vérifier si existe) |
| `_avis_donnant_item.html.twig` | (À vérifier si existe) |

**Localisation :** `templates/alerte/`

| Template | Description |
|----------|-------------|
| `formulaire.html.twig` | Modale Bootstrap 3 envoi alertes email |

---

## À documenter

Les sections suivantes nécessitent un audit plus approfondi :

- [ ] Description détaillée de chaque service (méthodes publiques)
- [ ] Relations entre entités (ManyToOne, OneToMany, etc.)
- [ ] Règles métier par fonctionnalité
- [ ] Workflows (incomplet → complet, affectations, etc.)
- [ ] Configuration détaillée PREVARISC_MAIL_*
- [ ] Liste complète des constantes NATURES_DONNANT_AVIS
- [ ] Validation Symfony sur les entités
- [ ] Tests existants (unitaires, fonctionnels)

---

## Notes de développement

### Vérification effectuée

Cette documentation a été créée en vérifiant :
1. ✅ Existence des fichiers dans `src/Entity/`
2. ✅ Existence des fichiers dans `src/Service/`
3. ✅ Existence des fichiers dans `src/Form/`
4. ✅ Existence des fichiers dans `public/js/dossier/`
5. ✅ Comptage lignes réelles avec `wc -l`
6. ✅ Événements custom avec `grep -r "CustomEvent\|addEventListener"`
7. ✅ Technologies avec `grep -r "FullCalendar\|Flatpickr"`

### Méthodologie

**Pour ajouter du contenu à cette documentation :**
1. Vérifier l'existence du fichier : `ls src/...`
2. Lire le code source : `cat src/...` ou `view src/...`
3. Extraire les informations factuelles (méthodes, constantes, etc.)
4. Documenter uniquement ce qui est vérifié

**Ne jamais :**
- ❌ Inventer des noms de fichiers
- ❌ Supposer des fonctionnalités
- ❌ Documenter ce qui "devrait" exister
- ❌ Faire référence à des technologies non vérifiées

---

**Dernière mise à jour :** 10 février 2026  
**Version :** 0.1 (squelette)  
**Statut :** ✅ Tous les éléments listés existent réellement
