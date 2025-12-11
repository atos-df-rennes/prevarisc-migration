# Migration - Affichage des informations générales des dossiers

**Date :** 5 décembre 2025  
**Version Symfony :** 4.x  
**Version Twig :** 2.x  
**Compatibilité :** Bootstrap 3

---

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Service DossierFieldsService](#service-dossierfieldsservice)
4. [Extension Twig DossierExtension](#extension-twig-dossierextension)
5. [Service DateAffectation](#service-dateaffectation)
6. [Templates](#templates)
7. [Contrôleur](#contrôleur)
8. [Tests](#tests)
9. [Choix techniques](#choix-techniques)
10. [Améliorations futures](#améliorations-futures)

---

## Vue d'ensemble

### Contexte

Migration de la fonctionnalité d'affichage des informations générales des dossiers depuis l'application Zend Framework 1 (legacy) vers Symfony 4.

### Objectifs

- ✅ Conformité 100% avec le comportement du legacy
- ✅ Affichage conditionnel des champs selon la nature du dossier
- ✅ Gestion des documents manquants
- ✅ Respect des principes SOLID et des best practices Symfony
- ✅ Code maintenable et testable

### Périmètre fonctionnel

**Page concernée :** `/dossier/{id}` (lecture seule)

**Fonctionnalités migrées :**
- Affichage des informations générales du dossier
- Filtrage des champs selon la nature (68 natures gérées)
- Affichage des documents manquants avec dates de réception
- Calcul de la visibilité de l'avis de commission
- Lien vers l'ordre du jour de la date de commission
- ✅ **Affichage des informations Plat'AU** (statuts, dates, temps restant)

---

## Architecture

### Diagramme de flux

```
┌─────────────────────────────────────────────────────────────┐
│                         User Request                        │
│                      GET /dossier/{id}                      │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                   DossierController::index()                │
│  - Récupère le dossier                                      │
│  - Calcule afficher_avis                                    │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│         DossierLateralNavigationSubscriber (Event)          │
│  - Récupère id_date_commission via DateAffectation          │
│  - Ajoute les variables à la Request                        │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              Render: dossier/index.html.twig                │
│  - Utilise DossierExtension (filtre should_display_field)   │
│  - Include _documents_manquants.html.twig                   │
└─────────────────────────────────────────────────────────────┘
```

### Composants principaux

| Composant | Rôle | Fichier |
|-----------|------|---------|
| **DossierFieldsService** | Détermine les champs à afficher selon la nature | `src/Service/DossierFieldsService.php` |
| **DateAffectation** | Calcule l'ID de date de commission | `src/Service/DateAffectation.php` |
| **PlatauConsultation** | Service métier pour les consultations Plat'AU | `src/Service/PlatauConsultation.php` |
| **PlatauTempsRestant** | Calcule le temps restant et la couleur | `src/Service/PlatauTempsRestant.php` |
| **PlatauTempsRestantInfo** | Value Object (DTO) pour temps restant + couleur | `src/Dto/PlatauTempsRestantInfo.php` |
| **DossierExtension** | Extension Twig avec filtre custom | `src/Twig/DossierExtension.php` |
| **PlatauExtension** | Extension Twig pour Plat'AU (filtres labels/classes) | `src/Twig/PlatauExtension.php` |
| **DossierController** | Contrôleur principal | `src/Controller/DossierController.php` |
| **Template principal** | Vue d'affichage | `templates/dossier/index.html.twig` |
| **Partial documents** | Sous-template pour documents manquants | `templates/dossier/_documents_manquants.html.twig` |

---

## Service DossierFieldsService

### Responsabilité

Gérer la liste des champs à afficher selon la nature du dossier, conformément à la logique métier du legacy.

### Structure

```php
namespace App\Service;

use App\Entity\Dossier;

class DossierFieldsService
{
    private const LISTE_CHAMPS = [
        '1' => ['type', 'DATEINSERT', 'OBJET', 'NUMDOCURBA', ...],
        '2' => ['type', 'DATEINSERT', 'OBJET', 'NUMDOCURBA', ...],
        // ... 68 natures au total
    ];

    public function shouldDisplayField(Dossier $dossier, string $nomChamp): bool
    {
        // Retourne true si le champ doit être affiché pour ce dossier
    }

    public function getFieldsToDisplay(Dossier $dossier): array
    {
        // Retourne la liste complète des champs pour ce dossier
    }
}
```

### Constante LISTE_CHAMPS

**Source :** `prevarisc/application/controllers/DossierController.php` (propriété `$listeChamps`)

**Natures gérées :**

| Type | Natures | Exemples |
|------|---------|----------|
| **Études** | 1-19, 30, 33, 46, 61-63, 66 | PC, AT, Dérogation, Notice de sécurité, etc. |
| **Visites** | 20-24, 47, 64 | Réception, Périodique, Chantier, Contrôle, etc. |
| **Groupes de visites** | 25-29, 48 | Idem visites mais en groupe |
| **Réunions** | 31-32, 43 | Locaux SDIS, Extérieur SDIS, Téléphonique |
| **Courriers** | 49-54, 57-60, 65 | Lettre, Mise en demeure, Avis écrit motivé, etc. |
| **Interventions** | 37-39 | Incendie, SAP, Intervention diverse |
| **Arrêtés** | 40-42, 44-45 | Ouverture, Fermeture, Mise en demeure, etc. |

### Correspondances de champs

**Attention aux différences de nommage entre legacy et Symfony :**

| Legacy | Symfony | Description |
|--------|---------|-------------|
| `NUMDOCURBA` | `documentsUrbanisme` | Documents d'urbanisme |
| `SERVICEINSTRUC` | `serviceInstructeur` | Service instructeur |
| `MESURESCOMPLE` | `mesuresComplementaires` | Mesures complémentaires |
| `REGLEDEROG` | `reglesDerogation` | Règles de dérogation |
| `JUSTIFDEROG` | `justificationDerogation` | Justification dérogation |
| `MESURESCOMPENS` | `mesuresCompensatoires` | Mesures compensatoires |
| `DATESDIS` | `dateReception` | Date réception SDIS |

### Points d'attention

1. **Ordre des champs :** L'ordre dans le tableau correspond à l'ordre d'affichage historique
2. **Champs spéciaux :** `type`, `DEMANDEUR` présents uniquement dans certaines natures
3. **Champs inutilisés dans l'affichage :** `COORDSSI`, `DESCGEN`, `DESCEFF`, `NUMCHRONO` sont dans la liste mais utilisés uniquement dans les formulaires d'édition

---

## Extension Twig DossierExtension

### Motivation

Simplifier la syntaxe Twig et éviter de passer le service `DossierFieldsService` dans chaque contrôleur.

### Implémentation

```php
namespace App\Twig;

use App\Entity\Dossier;
use App\Service\DossierFields;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DossierExtension extends AbstractExtension
{
    private $dossierFieldsService;

    public function __construct(DossierFields $dossierFieldsService)
    {
        $this->dossierFieldsService = $dossierFieldsService;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('should_display_field', [$this, 'shouldDisplayField']),
        ];
    }

    public function shouldDisplayField(Dossier $dossier, string $fieldName): bool
    {
        return $this->dossierFieldsService->shouldDisplayField($dossier, $fieldName);
    }
}
```

### Enregistrement

**Automatique** via l'autoconfiguration Symfony (`config/services.php`).

### Usage dans les templates

**Avant :**
```twig
{% if dossier_fields_service.shouldDisplayField(dossier, 'OBJET') and dossier.objet %}
    {# ... #}
{% endif %}
```

**Après :**
```twig
{% if dossier|should_display_field('OBJET') and dossier.objet %}
    {# ... #}
{% endif %}
```

### Avantages

- ✅ **Syntaxe plus idiomatique Twig** (pattern filtre)
- ✅ **Contrôleur simplifié** (pas besoin de passer le service)
- ✅ **Encapsulation** du service dans l'extension
- ✅ **Réutilisable** dans tous les templates

---

## Service DateAffectation

### Responsabilité

Calculer l'ID de la date de commission à partir d'un dossier et d'une date de commission d'affectation.

### Signature

```php
public function getIdDateCommission(
    Dossier $dossier,
    ?DateCommission $dateCommissionAffectation
): ?int
```

### Logique métier

```
SI pas de dateCommissionAffectation
    RETOURNER null (cas nominal, pas d'erreur)

SI typeDossier n'est pas ETUDE (1) ET pas GROUPE_VISITE (3)
    LOG: Type de dossier invalide
    RETOURNER null

SI typeEvenement de la dateCommission n'est pas VISITE (2 ou 3)
    LOG: Type d'événement invalide
    RETOURNER null

SI dateCommission est null
    LOG: Date de commission manquante
    RETOURNER null

RETOURNER dateCommission.id
```

### Points clés

1. **Dernier return sans log** : C'est le cas nominal où toutes les données sont valides
2. **Tous les autres return null** : Cas d'erreur nécessitant un log
3. **Early returns** : Simplifie la logique et améliore la lisibilité

### Tests

Fichier : `tests/Service/DateAffectationTest.php`

**Scénarios testés :**
- ✅ Dossier étude avec date de commission valide → retourne l'ID
- ✅ Dossier groupe de visite avec date valide → retourne l'ID
- ✅ Pas de date d'affectation → retourne null (sans log)
- ✅ Type de dossier invalide → retourne null (avec log)
- ✅ Type d'événement invalide → retourne null (avec log)
- ✅ Date de commission manquante → retourne null (avec log)

---

## Services Plat'AU

### Service PlatauConsultation

#### Responsabilité

Service métier de haut niveau gérant la logique d'affichage des consultations Plat'AU. Centralise la logique pour éviter de la dupliquer dans les contrôleurs.

#### Signatures

```php
public function getTempsRestantInfo(?PlatauConsultationEntity $consultation): PlatauTempsRestantInfo

public function getPlatauDataForView(?PlatauConsultationEntity $consultation): ?array
```

#### Logique métier `getTempsRestantInfo()`

```
SI consultation est null
    RETOURNER PlatauTempsRestantInfo::empty()

SI dateReponseAttendue est null
    RETOURNER PlatauTempsRestantInfo::empty()

SI avis est traité (TRAITE, A_RENVOYER ou INCONNU)
    RETOURNER PlatauTempsRestantInfo::empty()

// Cas nominal : calculer le temps restant
tempsRestant = platauTempsRestantService.calculate(dateReponseAttendue)
couleur = platauTempsRestantService.getCouleurTempsRestant(dateReponseAttendue)

RETOURNER new PlatauTempsRestantInfo(tempsRestant, couleur)
```

#### Méthode `getPlatauDataForView()`

Prépare un tableau associatif avec toutes les données Plat'AU pour le template Twig.

**Retour :**
```php
[
    'identifiant' => string,
    'temps_restant' => ?string,              // null si avis envoyé
    'couleur_temps_restant' => ?string,      // null si avis envoyé
    'statut_pec' => string,
    'date_pec' => ?\DateTimeInterface,
    'statut_avis' => string,
    'date_avis' => ?\DateTimeInterface,
]
```

#### Points clés

1. **Séparation des responsabilités** : Le service gère la logique métier, pas le contrôleur
2. **Réutilisabilité** : Utilisable dans d'autres contextes (API, exports, etc.)
3. **Type Safety** : Utilise le DTO `PlatauTempsRestantInfo` pour grouper temps/couleur

### Service PlatauTempsRestant

#### Responsabilité

Service technique de calcul du temps restant et de la couleur associée. Ne gère **aucune** logique métier.

#### Signatures

```php
public function calculate(\DateTimeInterface $limitDate): string

public function getCouleurTempsRestant(\DateTimeInterface $limitDate): string
```

#### Algorithme `calculate()`

```
1. Convertir limitDate en DateTime (début de journée)
2. Créer now en DateTime (début de journée)
3. Calculer diff = now.diff(limitDate)

4. Construire readableDiff:
   - SI diff.y > 0 : ajouter "X an(s)"
   - SI diff.m > 0 : ajouter "X mois"
   - SI diff.d > 0 : ajouter "X jour(s)"
   
5. SI diff.invert == 1 (date passée):
      RETOURNER "- " + readableDiff
   SINON:
      RETOURNER readableDiff
```

**Exemples de retour :**
- `"15 jours"`
- `"2 mois et 5 jours"`
- `"1 an et 3 mois"`
- `"- 10 jours"` (délai dépassé)

#### Algorithme `getCouleurTempsRestant()`

```
SI date dépassée (invert == 1)
    RETOURNER 'default'  // Gris

SI plus de 6 mois restants (y > 0 OU m > 6)
    RETOURNER 'success'  // Vert

SI entre 3 et 6 mois restants (m > 3)
    RETOURNER 'warning'  // Orange

SINON (moins de 3 mois)
    RETOURNER 'danger'   // Rouge
```

**Constantes :**
- `SEUIL_OK = 6` (mois)
- `SEUIL_WARN = 3` (mois)

#### Points clés

1. **Type Safety renforcé** : `\DateTimeInterface` non-nullable en paramètre (pas de `null`)
2. **Calculs en jours calendaires** : Utilise `setTime(0, 0, 0)` pour ignorer les heures
3. **Pas de gestion du null** : Responsabilité du service appelant (`PlatauConsultation`)

### DTO PlatauTempsRestantInfo

#### Motivation

Value Object regroupant le **temps restant** et sa **couleur associée** (Bootstrap label class).

**Problème résolu :**
- ❌ Avant : Deux appels séparés au service, gestion manuelle du null
- ✅ Après : Un seul objet encapsulant les deux notions liées

#### Structure

```php
final class PlatauTempsRestantInfo
{
    private $tempsRestant;  // ?string
    private $couleur;       // ?string
    
    public function __construct($tempsRestant, $couleur)
    public static function empty(): self
    public function getTempsRestant(): ?string
    public function getCouleur(): ?string
    public function hasInfo(): bool
}
```

#### Usage

```php
// Cas nominal
$info = $platauConsultationService->getTempsRestantInfo($consultation);

if ($info->hasInfo()) {
    echo "Il reste " . $info->getTempsRestant();
    echo " (couleur: " . $info->getCouleur() . ")";
}

// Cas sans info (avis envoyé)
$info = PlatauTempsRestantInfo::empty();
$info->hasInfo();  // false
```

#### Avantages

1. **Couplage temps/couleur** : Les deux notions sont intrinsèquement liées
2. **Immutabilité** : `final` class, pas de setters
3. **Factory method** : `empty()` pour éviter `new PlatauTempsRestantInfo(null, null)`
4. **Semantic** : `hasInfo()` plus clair que `$tempsRestant !== null`

### Tests

#### `tests/Service/PlatauConsultationTest.php`

**Scénarios testés :**
- ✅ Consultation null → retourne empty
- ✅ Date réponse attendue null → retourne empty
- ✅ Avis traité → retourne empty
- ✅ Avis à renvoyer → retourne empty
- ✅ Avis en cours → calcule temps restant + couleur
- ✅ `getPlatauDataForView()` avec consultation complète
- ✅ `getPlatauDataForView()` sans temps restant (avis traité)

#### `tests/Service/PlatauTempsRestantTest.php`

**Scénarios testés :**
- ✅ Calcul avec dates futures (jours, mois, années, combinaisons)
- ✅ Calcul avec date passée (préfixe "- ")
- ✅ Couleur pour date passée → `default`
- ✅ Couleur pour > 6 mois → `success`
- ✅ Couleur pour 3-6 mois → `warning`
- ✅ Couleur pour < 3 mois → `danger`

#### `tests/Dto/PlatauTempsRestantInfoTest.php`

**Scénarios testés :**
- ✅ Constructor et getters
- ✅ Factory `empty()`
- ✅ `hasInfo()` retourne false si temps restant null
- ✅ `hasInfo()` retourne true si temps restant présent

---

## Templates

### Template principal : `index.html.twig`

**Structure :**

```twig
{% extends 'layouts/dossier.html.twig' %}

{% block body %}
    {% if platau %}
        <section>
            <h4>Informations sur Plat'AU</h4>
            <ul class='list-unstyled'>
                <li class='row'>
                    <div class='left libelle col-md-3'>Identifiant Plat'AU</div>
                    <div class='right value col-md-9'>{{ platau.identifiant }}</div>
                </li>
                
                {# Statut et date prise en compte #}
                
                {# Temps restant (affiché uniquement si avis non envoyé) #}
                {% if platau.temps_restant is not null %}
                    <li class='row'>
                        <div class='left libelle col-md-3'>Temps restant pour une réponse</div>
                        <div class='right value col-md-9'>
                            <span class='label label-{{ platau.couleur_temps_restant }}'>
                                {{ platau.temps_restant }}
                            </span>
                        </div>
                    </li>
                {% endif %}
                
                {# Statut et date avis #}
            </ul>
        </section>
    {% endif %}

    <section>
        <h4>Type et nature du dossier</h4>
        {# Affichage type et nature #}
    </section>

    <section>
        <h4>Informations sur le dossier</h4>
        <ul class='list-unstyled'>
            {% if dossier|should_display_field('DATEINSERT') %}
                <li class='row'>...</li>
            {% endif %}
            
            {% if dossier|should_display_field('OBJET') and dossier.objet %}
                <li class='row'>...</li>
            {% endif %}
            
            {# ... ~40 champs conditionnels #}
        </ul>
    </section>
{% endblock %}
```

**Pattern de vérification :**

```twig
{% if dossier|should_display_field('NOM_CHAMP') and dossier.propriete %}
    <li class="row">
        <div class="left libelle col-md-3">Libellé</div>
        <div class="right value col-md-9">{{ dossier.propriete }}</div>
    </li>
{% endif %}
```

### Partial : `_documents_manquants.html.twig`

**Responsabilité :** Afficher la liste des documents manquants d'un dossier incomplet.

**Structure :**

```twig
{% if dossier.incomplet and dossier.documentsManquants is not empty %}
    <br><br>
    {% for document_manquant in dossier.documentsManquants %}
        <strong>Incomplet le {{ document_manquant.date|date('d/m/Y') }}</strong>
        <br>
        {{ document_manquant.documentManquant|nl2br }}
        {% if document_manquant.dateReception %}
            <br>
            <span class="text-success">
                Réception document(s) le {{ document_manquant.dateReception|date('d/m/Y') }}
            </span>
        {% endif %}
    {% endfor %}
{% endif %}
```

**Utilisation :**

```twig
{{ include('dossier/_documents_manquants.html.twig') }}
```

### Entité DossierDocumentManquant

**Relation :** `Dossier` → OneToMany → `DossierDocumentManquant`

**Propriétés :**
- `id` : Identifiant
- `numero` : Numéro séquentiel (ordre d'affichage)
- `documentManquant` : Description du document manquant (text)
- `date` : Date où le dossier a été déclaré incomplet
- `dateReception` : Date de réception du document (nullable)
- `dossier` : Relation vers le dossier

---

## Contrôleur

### DossierController::index()

**Route :** `GET /dossier/{id}`

**Code :**

```php
public function index(
    Dossier $dossier, 
    PlatauConsultation $platauConsultationService
): Response {
    $afficherAvis = !$dossier->hasAbsenceDeQuorum()
        && !$dossier->isHorsDelai()
        && !$dossier->hasAvisDiffere()
        && !$dossier->isIncomplet()
        && !$dossier->hasNePeutSePrononcer()
    ;

    $platauData = $platauConsultationService
        ->getPlatauDataForView($dossier->getConsultationPlatau());

    return $this->render('dossier/index.html.twig', [
        'dossier' => $dossier,
        'afficher_avis' => $afficherAvis,
        'platau' => $platauData,
    ]);
}
```

**Variables passées au template :**
- `dossier` : L'entité Dossier
- `afficher_avis` : Boolean indiquant si l'avis de commission doit être affiché
- `platau` : Array|null contenant les données Plat'AU (voir `PlatauConsultation::getPlatauDataForView()`)

**Variables ajoutées par le Subscriber :**
- `id_date_commission` : ID de la date de commission (pour le lien ODJ)
- `nombre_nouvelles_pieces_jointes` : Nombre de nouvelles pièces jointes
- `libelle_verifications_techniques` : Libellé personnalisé
- `libelle_effectifs_degagements` : Libellé personnalisé

### Event Subscriber : DossierLateralNavigationSubscriber

**Événement écouté :** `KernelEvents::CONTROLLER`

**Rôle :** Ajouter des variables communes à toutes les actions du `DossierController`.

**Logique pour `id_date_commission` :**

```php
$dossierAffectation = $this->dossierAffectationRepository
    ->findOneBy(['dossier' => $dossier]);

$dateCommission = null !== $dossierAffectation 
    ? $dossierAffectation->getDateCommission() 
    : null;

$idDateCommission = $this->dateAffectation
    ->getIdDateCommission($dossier, $dateCommission);

$event->getRequest()->attributes
    ->set('id_date_commission', $idDateCommission);
```

---

## Tests

### Tests unitaires

**Fichier :** `tests/Service/DateAffectationTest.php`

**Couverture :**
- ✅ Test avec dossier étude et date de commission valide
- ✅ Test avec dossier groupe de visite
- ✅ Test sans date d'affectation (cas nominal)
- ✅ Test avec type de dossier invalide (visite simple)
- ✅ Test avec type d'événement invalide (commission en salle)
- ✅ Test avec date de commission nulle

**Méthodes testées :**
- `DateAffectation::getIdDateCommission()`

**Mock des entités :**
- `Dossier`
- `DateCommission`
- `CommissionTypeEvenement`
- `DossierType`

### Validation du code

| Outil | Commande | Résultat |
|-------|----------|----------|
| **PHPStan** | `castor symfony:analyse` | ✅ 0 erreur (242 fichiers) |
| **PHP CS Fixer** | `castor symfony:cs` | ✅ Code conforme |
| **Twig CS Fixer** | `castor symfony:cs` | ✅ 104 templates conformes |
| **PHPUnit** | `castor symfony:test` | ✅ 194 tests, 235 assertions |

**Tests Plat'AU ajoutés :**
- 8 tests pour `PlatauConsultationTest`
- 11 tests pour `PlatauTempsRestantTest`
- 4 tests pour `PlatauTempsRestantInfoTest`

---

## Choix techniques

### 1. Service vs Helper Twig

**Choix :** Extension Twig avec filtre custom

**Alternatives considérées :**
- Passer le service dans chaque contrôleur ❌
- Créer un helper global Twig ❌
- Utiliser une fonction Twig au lieu d'un filtre ❌

**Justification :**
- ✅ Syntaxe plus élégante (`dossier|should_display_field('OBJET')`)
- ✅ Réutilisable dans tous les templates sans configuration
- ✅ Encapsulation du service
- ✅ Pattern standard Symfony/Twig

### 2. Partial pour documents manquants

**Choix :** Extraction dans `_documents_manquants.html.twig`

**Justification :**
- ✅ Séparation des responsabilités (SRP)
- ✅ Réutilisabilité potentielle
- ✅ Meilleure lisibilité du template principal
- ✅ Facilite les tests d'intégration

### 3. Classes Bootstrap au lieu de styles inline

**Choix :** Utiliser `class="text-success"` au lieu de `style="color: green"`

**Justification :**
- ✅ Conformité avec Bootstrap 3
- ✅ Meilleure maintenabilité
- ✅ Cohérence avec le reste de l'application
- ✅ Performance (mise en cache CSS)

### 4. Early returns dans DateAffectation

**Choix :** Vérifications séquentielles avec early returns

**Alternatives considérées :**
- If imbriqués ❌
- Exceptions ❌

**Justification :**
- ✅ Réduction de la complexité cyclomatique
- ✅ Meilleure lisibilité
- ✅ Facilite l'ajout de logs contextuels
- ✅ Conformité avec le legacy (même logique)

### 5. Logs conditionnels dans DateAffectation

**Choix :** Log uniquement en cas d'erreur, pas en cas nominal

**Justification :**
- ✅ Évite de polluer les logs avec des cas normaux
- ✅ Facilite le debug (seuls les cas problématiques sont loggés)
- ✅ Performance (moins d'écritures disque)

### 6. Service métier PlatauConsultation au lieu de logique dans le contrôleur

**Choix :** Extraire toute la logique Plat'AU dans un service dédié

**Alternatives considérées :**
- Logique directement dans le contrôleur ❌
- Appels multiples au service PlatauTempsRestant ❌
- Helper Twig ❌

**Justification :**
- ✅ **SRP** : Le contrôleur reste simple (1 ligne de logique Plat'AU)
- ✅ **Testabilité** : 100% de couverture des scénarios métier
- ✅ **Réutilisabilité** : Utilisable dans API, exports, commandes CLI
- ✅ **Type Safety** : DTO `PlatauTempsRestantInfo` pour grouper temps + couleur

**Impact sur le contrôleur :**

```php
// ❌ Avant : 26 lignes de logique métier dans le contrôleur
if (null !== $consultationPlatau) {
    $statutAvis = $consultationPlatau->getStatutAvis();
    $tempsRestant = null;
    $couleurTempsRestant = null;
    
    if (PlatauStatutAvisEnum::TRAITE !== $statutAvis) {
        $tempsRestant = $service->calculate(...);
        if (null !== $tempsRestant && ...) {
            $couleurTempsRestant = $service->getCouleurTempsRestant(...);
        }
    }
    // ... construction du tableau
}

// ✅ Après : 1 ligne
$platauData = $platauConsultationService->getPlatauDataForView($dossier->getConsultationPlatau());
```

### 7. Value Object PlatauTempsRestantInfo

**Choix :** Grouper temps restant et couleur dans un DTO

**Alternatives considérées :**
- Deux propriétés séparées dans un tableau ❌
- Deux appels séparés au service ❌

**Justification :**
- ✅ **Couplage sémantique** : La couleur dépend du temps restant, ils sont liés
- ✅ **Factory method** : `empty()` pour les cas sans info
- ✅ **Type Safety** : `hasInfo()` plus explicite que `$temps !== null`
- ✅ **Immutabilité** : Classe `final`, pas de setters

### 8. Suppression du paramètre nullable dans PlatauTempsRestant::calculate()

**Choix :** Signature `calculate(\DateTimeInterface $limitDate): string` au lieu de `calculate(?\DateTimeInterface $limitDate): ?string`

**Justification :**
- ✅ **Type Safety renforcé** : Impossible de passer null
- ✅ **Séparation des responsabilités** : 
  - `PlatauConsultation` gère les cas métier (null, avis traité)
  - `PlatauTempsRestant` fait **toujours** le calcul
- ✅ **Moins de tests** : Pas besoin de tester le cas null
- ✅ **API plus claire** : Retourne toujours une string, pas de null à gérer

---

## Améliorations futures

### Court terme

Voir le document : `SUGGESTIONS_AMELIORATION_AFFICHAGE_DOSSIER.md`

**Priorités :**
1. ✅ Macros Twig pour réduire la verbosité
2. ✅ Amélioration de l'accessibilité (WCAG 2.1)
3. ✅ Affichage des informations Plat'AU (**Terminé**)
4. ⏳ Groupement des vérifications de champs

### Moyen terme

1. **Tests d'intégration**
   - Tester l'affichage pour chaque nature de dossier
   - Utiliser des fixtures pour les données de test

2. **Performance**
   - Mesurer l'impact du cache Twig
   - Optimiser les requêtes Doctrine (eager loading)

3. **Documentation utilisateur**
   - Guide d'utilisation pour chaque type de dossier
   - Captures d'écran annotées

### Long terme

1. **Migration Bootstrap 3 → 5**
   - Remplacer les glyphicons par Font Awesome ou SVG
   - Adapter les grilles CSS

2. **API REST**
   - Exposer les informations du dossier via API
   - Permettre l'intégration avec d'autres systèmes

3. **Composants Symfony UX**
   - Utiliser Stimulus pour interactions JavaScript
   - Live Components pour mise à jour temps réel

---

## Annexes

### Commandes utiles

```bash
# Lancer les analyses
castor symfony:analyse

# Corriger le style de code
castor symfony:cs

# Lancer les tests
castor symfony:test

# Voir les routes
docker compose exec app bin/console debug:router | grep dossier

# Cache Twig
docker compose exec app bin/console cache:clear
docker compose exec app bin/console cache:warmup

# Vérifier la configuration
docker compose exec app bin/console debug:config framework
docker compose exec app bin/console debug:twig
```

### Ressources

- [Documentation Symfony 4](https://symfony.com/doc/4.4/index.html)
- [Documentation Twig 2](https://twig.symfony.com/doc/2.x/)
- [Bootstrap 3 Documentation](https://getbootstrap.com/docs/3.4/)
- [PHPStan](https://phpstan.org/user-guide/getting-started)
- [PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)

---

**Auteur :** Équipe de développement Prevarisc  
**Date de création :** 5 décembre 2025  
**Dernière mise à jour :** 10 décembre 2025  
**Version du document :** 1.1

---

## Changelog

### Version 1.1 (10 décembre 2025)

**Ajouts :**
- ✅ Section complète sur les services Plat'AU
- ✅ Documentation du service `PlatauConsultation`
- ✅ Documentation du service `PlatauTempsRestant`
- ✅ Documentation du DTO `PlatauTempsRestantInfo`
- ✅ Choix technique : extraction logique métier du contrôleur
- ✅ Choix technique : Value Object pour temps restant + couleur
- ✅ Choix technique : Type Safety avec paramètres non-nullables
- ✅ Tests ajoutés : 23 tests pour les services Plat'AU

**Modifications :**
- Mise à jour du contrôleur `DossierController::index()`
- Mise à jour du template `index.html.twig`
- Mise à jour des statistiques de validation (242 fichiers, 194 tests)

### Version 1.0 (5 décembre 2025)

**Initial :**
- Documentation initiale de la migration
- Services DossierFields et DateAffectation
- Templates et contrôleur
- Tests unitaires
