# Suggestions d'amélioration - Édition des dossiers

**Date :** 10 décembre 2025  
**Contexte :** Migration de l'édition des dossiers depuis Zend v1 vers Symfony 4

---

## Table des matières

1. [Architecture et structure](#architecture-et-structure)
2. [Expérience utilisateur (UX)](#expérience-utilisateur-ux)
3. [Validation et feedback](#validation-et-feedback)
4. [Performance](#performance)
5. [Accessibilité](#accessibilité)
6. [Sécurité](#sécurité)
7. [Maintenabilité](#maintenabilité)
8. [Fonctionnalités avancées](#fonctionnalités-avancées)

---

## Architecture et structure

### 1. Découper le formulaire monolithique en étapes (Wizard)

**Problème actuel :**
- Formulaire unique très long (40+ champs selon la nature)
- Difficile à naviguer
- Cognitive overload pour l'utilisateur

**Solution proposée :**
Wizard multi-étapes avec navigation progressive

```
Étape 1: Informations de base
  ├─ Type, Nature, Objet
  └─ Établissement

Étape 2: Informations administratives
  ├─ Dates (réception, etc.)
  ├─ Service instructeur
  └─ Commission

Étape 3: Détails techniques
  ├─ Descriptif
  ├─ Textes applicables
  └─ Vérifications techniques

Étape 4: Avis et prescriptions
  ├─ Avis rapporteur/commission
  ├─ Prescriptions
  └─ Dérogations (si applicable)

Étape 5: Validation et récapitulatif
```

**Avantages :**
- ✅ Meilleure UX (moins intimidant)
- ✅ Validation progressive
- ✅ Sauvegarde intermédiaire possible
- ✅ Permet d'afficher uniquement les champs pertinents à chaque étape

**Outils Symfony :**
- Symfony Workflow Component
- Craue FormFlowBundle
- Custom avec Stimulus

**Priorité :** 🟡 Moyenne (amélioration UX significative mais non critique)

---

### 2. Formulaires modulaires par type de dossier

**Problème actuel :**
- Un seul `DossierType` avec logique conditionnelle complexe
- Difficile à maintenir et tester

**Solution proposée :**
Créer des FormTypes spécialisés

```php
// Base
abstract class AbstractDossierType extends AbstractType

// Spécialisés
class EtudeDossierType extends AbstractDossierType
class VisiteDossierType extends AbstractDossierType
class ReunionDossierType extends AbstractDossierType
class CourrierDossierType extends AbstractDossierType
// etc.
```

**Factory pour créer le bon type :**
```php
class DossierFormTypeFactory
{
    public function createFormType(int $typeId): string
    {
        return match($typeId) {
            1 => EtudeDossierType::class,
            2 => VisiteDossierType::class,
            4 => ReunionDossierType::class,
            // ...
        };
    }
}
```

**Avantages :**
- ✅ Séparation des responsabilités (SRP)
- ✅ Tests plus faciles (un FormType = un test)
- ✅ Réduction de la complexité cyclomatique
- ✅ Meilleure lisibilité

**Priorité :** 🟢 Haute (améliore maintenabilité)

---

## Expérience utilisateur (UX)

### 3. Sauvegarde automatique en brouillon (Draft)

**Problème actuel :**
- Perte de données si l'utilisateur quitte sans sauvegarder
- Pas de gestion des sessions longues

**Solution proposée :**
Auto-save toutes les 30 secondes avec indication visuelle

```javascript
// Stimulus controller
export default class extends Controller {
    static values = { 
        autosaveUrl: String,
        interval: { type: Number, default: 30000 } // 30s
    }
    
    connect() {
        this.autosaveTimer = setInterval(() => {
            this.save(true); // draft mode
        }, this.intervalValue);
    }
    
    async save(isDraft = false) {
        const formData = new FormData(this.element);
        formData.append('draft', isDraft);
        
        // Afficher indicateur "Enregistrement..."
        const response = await fetch(this.autosaveUrlValue, {
            method: 'POST',
            body: formData
        });
        
        // Afficher "✓ Enregistré à 14:35"
    }
}
```

**Base de données :**
```sql
ALTER TABLE dossierdonnees 
ADD COLUMN is_draft BOOLEAN DEFAULT FALSE,
ADD COLUMN draft_saved_at DATETIME;
```

**Avantages :**
- ✅ Aucune perte de données
- ✅ Utilisateur peut reprendre plus tard
- ✅ Rassure l'utilisateur (feedback visuel)

**Priorité :** 🟡 Moyenne (confort mais pas critique)

---

### 4. Prévisualisation avant sauvegarde

**Problème actuel :**
- Aucun aperçu avant validation finale
- Risque d'erreurs de saisie découvertes après coup

**Solution proposée :**
Modal de prévisualisation avec récapitulatif formaté

```twig
{# Modal de prévisualisation #}
<div class="modal fade" id="preview-modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Prévisualisation du dossier</h4>
            </div>
            <div class="modal-body">
                {# Affichage lecture seule formaté #}
                {% include 'dossier/_preview.html.twig' %}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default">Modifier</button>
                <button type="button" class="btn btn-success">Confirmer et sauvegarder</button>
            </div>
        </div>
    </div>
</div>
```

**Avantages :**
- ✅ Détection visuelle des erreurs
- ✅ Confirmation explicite avant sauvegarde
- ✅ Améliore la qualité des données

**Priorité :** 🔵 Faible (nice to have)

---

### 5. Assistance à la saisie contextuelle

**Problème actuel :**
- Utilisateur doit connaître tous les codes/références
- Beaucoup de champs libres sujets à erreurs

**Solution proposée :**

**a) Suggestions intelligentes**
```javascript
// Exemple: Service instructeur
$("#serviceInstructeur").autocomplete({
    source: function(request, response) {
        // Suggérer services basés sur:
        // - Historique de l'utilisateur
        // - Services fréquents pour ce type de dossier
        // - Services de la commune
    }
});
```

**b) Templates de texte**
```twig
{# Descriptif avec templates #}
<div class="form-group">
    <label>Descriptif</label>
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-sm btn-default" 
                data-action="click->dossier#insertTemplate" 
                data-template="pc-neuf">
            Template PC neuf
        </button>
        <button type="button" class="btn btn-sm btn-default" 
                data-action="click->dossier#insertTemplate" 
                data-template="at-amenagement">
            Template AT aménagement
        </button>
    </div>
    <textarea name="descriptif" rows="10"></textarea>
</div>
```

**c) Aide contextuelle inline**
```twig
<div class="form-group">
    <label>
        Numéro document urbanisme
        <i class="glyphicon glyphicon-question-sign" 
           data-toggle="tooltip" 
           title="Format: PC 000 000 00 00000. Exemple: PC 001 234 56 78901"></i>
    </label>
    <input type="text" pattern="PC \d{3} \d{3} \d{2} \d{5}">
</div>
```

**Avantages :**
- ✅ Réduction des erreurs de saisie
- ✅ Gain de temps
- ✅ Formation implicite des nouveaux utilisateurs

**Priorité :** 🟡 Moyenne (gain productivité significatif)

---

### 6. Validation en temps réel (inline)

**Problème actuel :**
- Erreurs découvertes uniquement à la soumission
- Utilisateur doit corriger et re-soumettre

**Solution proposée :**
Validation AJAX après chaque champ perdu le focus

```javascript
export default class extends Controller {
    static targets = ['field']
    
    async validateField(event) {
        const field = event.target;
        const value = field.value;
        const fieldName = field.name;
        
        // Appel AJAX validation
        const response = await fetch('/api/dossier/validate-field', {
            method: 'POST',
            body: JSON.stringify({ field: fieldName, value: value })
        });
        
        const result = await response.json();
        
        if (!result.valid) {
            // Afficher erreur inline
            this.showFieldError(field, result.message);
        } else {
            // Afficher succès (icône verte)
            this.showFieldSuccess(field);
        }
    }
}
```

**Endpoint API :**
```php
#[Route('/api/dossier/validate-field', methods: ['POST'])]
public function validateField(Request $request, ValidatorInterface $validator): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    // Valider le champ individuellement
    $constraint = $this->getConstraintForField($data['field']);
    $violations = $validator->validate($data['value'], $constraint);
    
    return new JsonResponse([
        'valid' => 0 === count($violations),
        'message' => count($violations) > 0 ? $violations[0]->getMessage() : null,
    ]);
}
```

**Avantages :**
- ✅ Feedback immédiat
- ✅ Réduction des erreurs à la soumission
- ✅ Meilleure UX

**Priorité :** 🟢 Haute (amélioration UX significative)

---

## Performance

### 7. Lazy loading des sections complexes

**Problème actuel :**
- Chargement de tous les sous-formulaires d'un coup
- Temps de chargement initial long

**Solution proposée :**
Charger les sections uniquement quand l'utilisateur clique dessus

```twig
{# Section prescriptions #}
<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">
            <a data-toggle="collapse" href="#prescriptions"
               data-controller="lazy-load"
               data-lazy-load-url-value="{{ path('dossier_prescriptions_load', {id: dossier.id}) }}"
               data-lazy-load-loaded-value="false">
                Prescriptions <span class="badge">{{ dossier.prescriptions|length }}</span>
            </a>
        </h4>
    </div>
    <div id="prescriptions" class="panel-collapse collapse">
        <div class="panel-body">
            <div class="spinner">Chargement...</div>
        </div>
    </div>
</div>
```

```javascript
// lazy_load_controller.js
export default class extends Controller {
    static values = { url: String, loaded: Boolean }
    
    async load(event) {
        if (this.loadedValue) return;
        
        const response = await fetch(this.urlValue);
        const html = await response.text();
        
        this.element.querySelector('.panel-body').innerHTML = html;
        this.loadedValue = true;
    }
}
```

**Avantages :**
- ✅ Temps de chargement initial réduit
- ✅ Moins de mémoire utilisée
- ✅ Meilleure perception de performance

**Priorité :** 🟡 Moyenne (si problèmes de performance constatés)

---

### 8. Cache des listes de référence (types, natures, etc.)

**Problème actuel :**
- Requêtes BDD à chaque chargement de formulaire
- Données rarement modifiées

**Solution proposée :**
```php
class DossierFormDataProvider
{
    public function __construct(
        private CacheInterface $cache,
        private DossierTypeRepository $dossierTypeRepository,
        private DossierNatureRepository $dossierNatureRepository
    ) {}
    
    public function getTypes(): array
    {
        return $this->cache->get('dossier_types', function(ItemInterface $item) {
            $item->expiresAfter(3600); // 1 heure
            return $this->dossierTypeRepository->findAll();
        });
    }
    
    public function getNaturesForType(int $typeId): array
    {
        return $this->cache->get("dossier_natures_type_{$typeId}", function(ItemInterface $item) use ($typeId) {
            $item->expiresAfter(3600);
            return $this->dossierNatureRepository->findByType($typeId);
        });
    }
}
```

**Invalidation du cache :**
```php
// EventSubscriber
class DossierTypeChangedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DossierTypeUpdatedEvent::class => 'onTypeUpdated',
        ];
    }
    
    public function onTypeUpdated(DossierTypeUpdatedEvent $event): void
    {
        $this->cache->delete('dossier_types');
        $this->cache->delete("dossier_natures_type_{$event->getTypeId()}");
    }
}
```

**Avantages :**
- ✅ Réduction des requêtes BDD
- ✅ Amélioration du temps de réponse
- ✅ Scalabilité

**Priorité :** 🟢 Haute (impact performance direct)

---

## Accessibilité

### 9. ARIA labels et navigation clavier

**Problème actuel :**
- Pas d'attributs ARIA
- Navigation clavier limitée

**Solution proposée :**

```twig
{# Exemple: Select avec ARIA #}
<div class="form-group">
    <label for="dossier-type" id="dossier-type-label">
        Type de dossier
        <span class="required" aria-label="Champ requis">*</span>
    </label>
    <select id="dossier-type" 
            name="type" 
            aria-labelledby="dossier-type-label"
            aria-required="true"
            aria-invalid="{{ form.type.vars.errors|length > 0 ? 'true' : 'false' }}"
            aria-describedby="dossier-type-error">
        <option value="">Sélectionner un type</option>
        {% for type in types %}
            <option value="{{ type.id }}">{{ type.libelle }}</option>
        {% endfor %}
    </select>
    {% if form.type.vars.errors|length > 0 %}
        <span id="dossier-type-error" class="help-block" role="alert">
            {{ form.type.vars.errors[0].message }}
        </span>
    {% endif %}
</div>

{# Raccourcis clavier #}
<div class="form-actions" role="toolbar" aria-label="Actions du formulaire">
    <button type="button" 
            class="btn btn-default" 
            data-action="click->dossier#cancel"
            accesskey="c">
        <u>C</u>annuler
    </button>
    <button type="submit" 
            class="btn btn-success"
            accesskey="s">
        <u>S</u>auvegarder
    </button>
</div>
```

**Navigation clavier :**
- Tab/Shift+Tab : Navigation entre champs
- Enter : Soumission formulaire
- Escape : Annulation
- Ctrl+S : Sauvegarde (avec prévention du comportement par défaut)

**Avantages :**
- ✅ Accessibilité WCAG 2.1 AA
- ✅ Utilisable par lecteurs d'écran
- ✅ Gain de productivité (raccourcis clavier)

**Priorité :** 🟢 Haute (obligation légale)

---

## Sécurité

### 10. CSRF Token par formulaire

**Déjà géré par Symfony**, mais à vérifier :

```php
class DossierType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'dossier_form',
        ]);
    }
}
```

**Priorité :** ✅ Déjà géré (vérification uniquement)

---

### 11. Rate limiting sur sauvegarde

**Problème potentiel :**
- Soumissions multiples (double-clic)
- Attaques par spam

**Solution proposée :**
```php
use Symfony\Component\RateLimiter\RateLimiterFactory;

class DossierController extends AbstractController
{
    #[Route('/dossier/{id}/save', methods: ['POST'])]
    public function save(
        Request $request, 
        Dossier $dossier,
        RateLimiterFactory $dossierSaveLimiter
    ): Response {
        $limiter = $dossierSaveLimiter->create($request->getClientIp());
        
        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException('Trop de sauvegardes en peu de temps');
        }
        
        // ... logique de sauvegarde
    }
}
```

**Configuration :**
```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        dossier_save:
            policy: 'sliding_window'
            limit: 10
            interval: '1 minute'
```

**Avantages :**
- ✅ Protection contre le spam
- ✅ Prévention des doublons
- ✅ Stabilité du système

**Priorité :** 🟡 Moyenne (sécurité préventive)

---

## Maintenabilité

### 12. Tests fonctionnels automatisés (E2E)

**Problème actuel :**
- Tests manuels chronophages
- Risque de régression

**Solution proposée :**
Utiliser Symfony Panther (Headless Chrome)

```php
use Symfony\Component\Panther\PantherTestCase;

class DossierEditTest extends PantherTestCase
{
    public function testCreateReunion(): void
    {
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/dossier/nouveau');
        
        // Sélectionner type
        $client->waitFor('#dossier_type');
        $crawler->filter('#dossier_type')->selectOption('4'); // Réunion
        
        // Attendre chargement AJAX des natures
        $client->waitFor('#dossier_nature option[value="31"]');
        $crawler->filter('#dossier_nature')->selectOption('31');
        
        // Remplir champs
        $crawler->filter('#dossier_objet')->sendKeys('Réunion de chantier test');
        $crawler->filter('#dossier_dateVisite')->sendKeys('15/12/2025');
        
        // Soumettre
        $crawler->filter('button[type="submit"]')->click();
        
        // Vérifier redirection
        $this->assertSelectorTextContains('h1', 'Dossier créé avec succès');
    }
}
```

**Avantages :**
- ✅ Tests proche du comportement utilisateur
- ✅ Détection des régressions JavaScript
- ✅ CI/CD automatisé

**Priorité :** 🟢 Haute (qualité logicielle)

---

## Fonctionnalités avancées

### 13. Historique des modifications (Audit log)

**Problème actuel :**
- Aucune traçabilité des modifications
- Impossible de savoir qui a modifié quoi et quand

**Solution proposée :**
Doctrine Extensions (Gedmo Loggable)

```php
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Loggable
 */
class Dossier
{
    /**
     * @Gedmo\Versioned
     */
    private $objet;
    
    /**
     * @Gedmo\Versioned
     */
    private $numeroDocumentUrbanisme;
}
```

**Affichage de l'historique :**
```twig
{# templates/dossier/_history.html.twig #}
<div class="panel panel-default">
    <div class="panel-heading">Historique des modifications</div>
    <div class="panel-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Champ modifié</th>
                    <th>Ancienne valeur</th>
                    <th>Nouvelle valeur</th>
                </tr>
            </thead>
            <tbody>
                {% for log in logs %}
                    <tr>
                        <td>{{ log.loggedAt|date('d/m/Y H:i') }}</td>
                        <td>{{ log.username }}</td>
                        <td>{{ log.field }}</td>
                        <td><del>{{ log.oldValue }}</del></td>
                        <td><strong>{{ log.newValue }}</strong></td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
</div>
```

**Avantages :**
- ✅ Traçabilité complète
- ✅ Détection des erreurs
- ✅ Conformité RGPD (journalisation)

**Priorité :** 🟡 Moyenne (utile mais non critique)

---

### 14. Export/Import de dossiers (JSON/XML)

**Use case :**
- Duplication de dossiers similaires
- Migration de données
- Échange avec systèmes tiers

**Solution proposée :**
```php
class DossierExportService
{
    public function exportToJson(Dossier $dossier): string
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        return $serializer->serialize($dossier, 'json', [
            'groups' => ['export'],
            'ignored_attributes' => ['id', 'createdAt', 'updatedAt'],
        ]);
    }
    
    public function importFromJson(string $json): Dossier
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        return $serializer->deserialize($json, Dossier::class, 'json');
    }
}
```

**Avantages :**
- ✅ Gain de temps (duplication)
- ✅ Interopérabilité
- ✅ Backup/restore

**Priorité :** 🔵 Faible (fonctionnalité avancée)

---

## Récapitulatif des priorités

| # | Suggestion | Priorité | Impact | Effort |
|---|-----------|----------|--------|--------|
| 2 | Formulaires modulaires par type | 🟢 Haute | Maintenabilité | Moyen |
| 6 | Validation en temps réel | 🟢 Haute | UX | Faible |
| 8 | Cache des listes de référence | 🟢 Haute | Performance | Faible |
| 9 | ARIA labels et navigation clavier | 🟢 Haute | Accessibilité | Moyen |
| 12 | Tests fonctionnels E2E | 🟢 Haute | Qualité | Élevé |
| 1 | Wizard multi-étapes | 🟡 Moyenne | UX | Élevé |
| 3 | Sauvegarde automatique (draft) | 🟡 Moyenne | UX | Moyen |
| 5 | Assistance à la saisie | 🟡 Moyenne | UX | Moyen |
| 7 | Lazy loading des sections | 🟡 Moyenne | Performance | Faible |
| 11 | Rate limiting | 🟡 Moyenne | Sécurité | Faible |
| 13 | Historique des modifications | 🟡 Moyenne | Traçabilité | Moyen |
| 4 | Prévisualisation | 🔵 Faible | UX | Faible |
| 14 | Export/Import JSON | 🔵 Faible | Fonctionnalité | Moyen |
| 10 | CSRF Token | ✅ Déjà géré | Sécurité | - |

---

**Auteur :** Équipe de développement Prevarisc  
**Date :** 10 décembre 2025  
**Version :** 1.0
