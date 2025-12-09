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
| **DossierExtension** | Extension Twig avec filtre custom | `src/Twig/DossierExtension.php` |
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

## Templates

### Template principal : `index.html.twig`

**Structure :**

```twig
{% extends 'layouts/dossier.html.twig' %}

{% block body %}
    <section>
        <h4>Informations sur Plat'AU</h4>
        {# @todo: intégration Plat'AU #}
    </section>

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
public function index(Dossier $dossier): Response
{
    $afficherAvis = !$dossier->hasAbsenceDeQuorum()
        && !$dossier->isHorsDelai()
        && !$dossier->hasAvisDiffere()
        && !$dossier->isIncomplet()
        && !$dossier->hasNePeutSePrononcer()
    ;

    return $this->render('dossier/index.html.twig', [
        'dossier' => $dossier,
        'afficher_avis' => $afficherAvis,
    ]);
}
```

**Variables passées au template :**
- `dossier` : L'entité Dossier
- `afficher_avis` : Boolean indiquant si l'avis de commission doit être affiché

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
| **PHPStan** | `castor symfony:analyse` | ✅ 0 erreur (231 fichiers) |
| **PHP CS Fixer** | `castor symfony:cs` | ✅ Code conforme |
| **Twig CS Fixer** | `castor symfony:cs` | ✅ 103 templates conformes |
| **PHPUnit** | `castor symfony:test` | ✅ 123 tests, 149 assertions |

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

---

## Améliorations futures

### Court terme

Voir le document : `SUGGESTIONS_AMELIORATION_AFFICHAGE_DOSSIER.md`

**Priorités :**
1. ✅ Macros Twig pour réduire la verbosité
2. ✅ Amélioration de l'accessibilité (WCAG 2.1)
3. ⏳ Groupement des vérifications de champs

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
**Dernière mise à jour :** 5 décembre 2025  
**Version du document :** 1.0
