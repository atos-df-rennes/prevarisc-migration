# Plan de migration : Formulaires personnalisés → Symfony Forms + DTOs

**Date :** Juillet 2025
**Auteur :** Copilot — Senior Symfony Developer
**Statut :** Prêt pour revue
**Cible :** PHP 7.1.33 / Symfony 4.4 LTS / Doctrine 2.14

---

## 1. Énoncé du problème

Le système actuel de formulaires personnalisés (`DescriptifSaver`) parse manuellement des clés POST brutes selon une convention héritée du legacy Zend (`champ-{id}`, `valeur-{ts}-{parentId}-{champId}-NULL`). Ce mécanisme est :

- **Fragile** : aucune validation côté serveur, les erreurs de format produisent des valeurs `null` silencieuses
- **Non-testable unitairement** sans reproduire des tableaux POST complexes
- **Incompatible** avec les bonnes pratiques Symfony (CSRF intégré, validation, form themes)
- **Dupliqué** : 4 controllers répètent le même pattern GET/POST/CSRF/flash/redirect

### Approche

Remplacement par un pipeline **DTO → FormType → Service** :

1. **DTOs** capturent la structure typée des données du formulaire
2. **FormTypes** Symfony gèrent le binding, la validation et le CSRF nativement
3. **Services** spécialisés (Hydrator, Builder, Persister) isolent chaque responsabilité
4. Le `DescriptifSaver` et `gestionTableau.js` sont **supprimés** en fin de migration

---

## 2. Architecture cible

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CONTROLLER                                  │
│  1. $data = Descriptif::getForDossier(...)                         │
│  2. $formData = Hydrator::hydrateFromDossier($dossier, $rubriques) │
│  3. $form = Builder::buildForm($factory, $formData, $rubriques)    │
│  4. $form->handleRequest($request)                                 │
│  5. if submitted & valid:                                           │
│       Persister::persistForDossier($dossier, $form->getData(), ..) │
└──────────┬──────────────────────────┬───────────────────────────────┘
           │                          │
           ▼                          ▼
┌──────────────────┐    ┌─────────────────────────────────────────────┐
│   Descriptif     │    │            FORM LAYER                       │
│   (read service) │    │                                             │
│   ─────────────  │    │  DescriptifType (root, named builder)       │
│   getForDossier  │    │    └─ RubriqueType[] (keyed by rubriqueId)  │
│   getForEts      │    │         ├─ display (CheckboxType)           │
│                  │    │         └─ champs[]                         │
│   Returns:       │    │              ├─ ChampValueType (simple)     │
│   {capsule,      │    │              │    → dispatches to:          │
│    rubriques[]}  │    │              │      ChampTextType           │
│                  │    │              │      ChampTextareaType       │
│                  │    │              │      ChampSelectType         │
│                  │    │              │      ChampNumberType         │
│                  │    │              │      ChampCheckboxType       │
│                  │    │              │      ChampDateType           │
│                  │    │              │                              │
│                  │    │              └─ TableauType (PARENT+tableau)│
│                  │    │                   └─ TableauRowType[]       │
│                  │    │                        └─ leaf types[]      │
└──────────────────┘    └─────────────────────────────────────────────┘
           │                          │
           ▼                          ▼
┌──────────────────────────────────────────────────────────────────────┐
│                         DTO LAYER                                    │
│                                                                      │
│  DescriptifFormData                                                  │
│    └─ rubriques: array<int, RubriqueFormData>  (keyed by rubriqueId)│
│                                                                      │
│  RubriqueFormData                                                    │
│    ├─ display: bool                                                  │
│    └─ champs: array<int, ChampValueData>  (keyed by champId)        │
│                                                                      │
│  ChampValueData                                                      │
│    ├─ champId: int                                                   │
│    ├─ valeurId: ?int  (for update tracking)                         │
│    ├─ value: mixed    (str|int|bool|\DateTime|null)                  │
│    ├─ lignes: TableauRowData[]  (only for PARENT+tableau)           │
│    └─ sousChamps: ChampValueData[]  (only for PARENT non-tableau)   │
│                                                                      │
│  TableauRowData                                                      │
│    ├─ cells: array<int, mixed>  (champId → value)                   │
│    └─ valeurIds: array<int, ?int>  (champId → existing valeurId)    │
└──────────────────────────────────────────────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────────────────────────────────┐
│                       SERVICE LAYER                                  │
│                                                                      │
│  DescriptifFormHydrator         (Valeur entities → DTOs)            │
│    ├─ hydrateFromDossier()                                          │
│    └─ hydrateFromEtablissement()                                    │
│                                                                      │
│  DescriptifFormBuilder          (DTOs + Capsule structure → Form)   │
│    └─ buildForm()               uses FormFactory programmatically   │
│                                                                      │
│  DescriptifFormPersister        (DTOs → Valeur entities, replaces   │
│    ├─ persistForDossier()        DescriptifSaver)                   │
│    └─ persistForEtablissement()                                     │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 3. Conception détaillée par couche

### 3.1 DTO Layer — `src/Dto/Descriptif/`

Tous les DTOs sont **PHP 7.1** : pas de propriétés typées, DocBlocks obligatoires, constructeurs explicites.

#### `DescriptifFormData`

```php
namespace App\Dto\Descriptif;

/**
 * Racine du formulaire personnalisé.
 * Contient toutes les rubriques avec leurs champs/valeurs.
 */
class DescriptifFormData
{
    /**
     * Rubriques indexées par rubriqueId.
     *
     * @var array<int, RubriqueFormData>
     */
    private $rubriques;

    /**
     * @param array<int, RubriqueFormData> $rubriques
     */
    public function __construct(array $rubriques = [])
    {
        $this->rubriques = $rubriques;
    }

    /** @return array<int, RubriqueFormData> */
    public function getRubriques(): array { return $this->rubriques; }

    /** @param array<int, RubriqueFormData> $rubriques */
    public function setRubriques(array $rubriques): self
    {
        $this->rubriques = $rubriques;
        return $this;
    }

    public function getRubrique(int $rubriqueId): ?RubriqueFormData
    {
        return $this->rubriques[$rubriqueId] ?? null;
    }

    public function setRubrique(int $rubriqueId, RubriqueFormData $rubrique): self
    {
        $this->rubriques[$rubriqueId] = $rubrique;
        return $this;
    }
}
```

#### `RubriqueFormData`

```php
/**
 * Données d'une rubrique : préférence d'affichage + valeurs des champs.
 */
class RubriqueFormData
{
    /** @var bool */
    private $display;

    /**
     * Champs indexés par champId.
     * Seuls les champs racine (sans parent) sont présents ici.
     *
     * @var array<int, ChampValueData>
     */
    private $champs;

    /**
     * @param array<int, ChampValueData> $champs
     */
    public function __construct(bool $display = true, array $champs = [])
    {
        $this->display = $display;
        $this->champs = $champs;
    }

    public function getDisplay(): bool { return $this->display; }
    public function setDisplay(bool $display): self { $this->display = $display; return $this; }

    /** @return array<int, ChampValueData> */
    public function getChamps(): array { return $this->champs; }

    /** @param array<int, ChampValueData> $champs */
    public function setChamps(array $champs): self { $this->champs = $champs; return $this; }

    public function getChamp(int $champId): ?ChampValueData
    {
        return $this->champs[$champId] ?? null;
    }
}
```

#### `ChampValueData`

```php
/**
 * Valeur d'un champ simple ou conteneur d'un tableau/groupe.
 *
 * - Pour un champ simple (TEXT, TEXTAREA, SELECT, NUMBER, CHECKBOX, DATE) :
 *   `value` contient la valeur typée, `lignes` est vide, `sousChamps` est vide.
 * - Pour un champ PARENT + tableau :
 *   `value` est null, `lignes` contient les TableauRowData.
 * - Pour un champ PARENT + groupe (non-tableau) :
 *   `value` est null, `sousChamps` contient les ChampValueData enfants.
 */
class ChampValueData
{
    /** @var int */
    private $champId;

    /** @var int|null ID de la Valeur existante (null si nouveau) */
    private $valeurId;

    /** @var string|int|bool|\DateTimeInterface|null */
    private $value;

    /** @var TableauRowData[] Lignes du tableau (vide si champ simple) */
    private $lignes;

    /** @var ChampValueData[] Sous-champs d'un groupe non-tableau */
    private $sousChamps;

    /**
     * @param string|int|bool|\DateTimeInterface|null $value
     * @param TableauRowData[]                        $lignes
     * @param ChampValueData[]                        $sousChamps
     */
    public function __construct(
        int $champId,
        $value = null,
        array $lignes = [],
        array $sousChamps = [],
        ?int $valeurId = null
    ) {
        $this->champId = $champId;
        $this->value = $value;
        $this->lignes = $lignes;
        $this->sousChamps = $sousChamps;
        $this->valeurId = $valeurId;
    }

    public function getChampId(): int { return $this->champId; }
    public function getValeurId(): ?int { return $this->valeurId; }
    public function setValeurId(?int $valeurId): self { $this->valeurId = $valeurId; return $this; }

    /** @return string|int|bool|\DateTimeInterface|null */
    public function getValue() { return $this->value; }

    /** @param string|int|bool|\DateTimeInterface|null $value */
    public function setValue($value): self { $this->value = $value; return $this; }

    /** @return TableauRowData[] */
    public function getLignes(): array { return $this->lignes; }

    /** @param TableauRowData[] $lignes */
    public function setLignes(array $lignes): self { $this->lignes = $lignes; return $this; }

    /** @return ChampValueData[] */
    public function getSousChamps(): array { return $this->sousChamps; }

    /** @param ChampValueData[] $sousChamps */
    public function setSousChamps(array $sousChamps): self { $this->sousChamps = $sousChamps; return $this; }
}
```

#### `TableauRowData`

```php
/**
 * Représente une ligne d'un tableau dynamique.
 *
 * `cells` contient les valeurs par champId enfant.
 * `valeurIds` contient les IDs des Valeur existantes (pour le suivi update/delete).
 */
class TableauRowData
{
    /**
     * Valeurs des cellules : champId => valeur typée.
     *
     * @var array<int, string|int|bool|\DateTimeInterface|null>
     */
    private $cells;

    /**
     * IDs des Valeur Doctrine existantes pour chaque cellule.
     * champId => valeurId (null si nouvelle cellule).
     *
     * @var array<int, int|null>
     */
    private $valeurIds;

    /**
     * @param array<int, string|int|bool|\DateTimeInterface|null> $cells
     * @param array<int, int|null>                                $valeurIds
     */
    public function __construct(array $cells = [], array $valeurIds = [])
    {
        $this->cells = $cells;
        $this->valeurIds = $valeurIds;
    }

    /** @return array<int, string|int|bool|\DateTimeInterface|null> */
    public function getCells(): array { return $this->cells; }

    /** @param array<int, string|int|bool|\DateTimeInterface|null> $cells */
    public function setCells(array $cells): self { $this->cells = $cells; return $this; }

    /**
     * @param string|int|bool|\DateTimeInterface|null $value
     */
    public function setCell(int $champId, $value): self
    {
        $this->cells[$champId] = $value;
        return $this;
    }

    /** @return array<int, int|null> */
    public function getValeurIds(): array { return $this->valeurIds; }

    /** @param array<int, int|null> $valeurIds */
    public function setValeurIds(array $valeurIds): self { $this->valeurIds = $valeurIds; return $this; }
}
```

**Décision clé :** `valeurIds` dans `TableauRowData` permet au Persister de savoir quelles Valeur mettre à jour vs créer, sans avoir à re-requêter la base. Le Hydrator remplit ces IDs lors du chargement initial.

**Compatibilité drag & drop :**
`valeurIds` est indexé par `champId` (identifie **quelle cellule** d'une ligne), pas par la position de la ligne. Réordonner les lignes via drag & drop ne casse donc pas ce tracking. L'ordre des lignes est déterminé exclusivement par la **position dans `lignes[]`** au moment de la soumission. Le Persister normalise les `idx` à chaque save (voir section 3.3).

---

### 3.2 FormType Layer — `src/Form/Type/Descriptif/`

#### Types feuille (leaf types)

Chaque type wrape un type Symfony natif et peut ajouter des attributs HTML spécifiques.

| FormType | Type interne | Options spéciales |
|----------|-------------|-------------------|
| `ChampTextType` | `TextType` | `attr.class = 'form-control'` |
| `ChampTextareaType` | `TextareaType` | `attr.class = 'form-control'` |
| `ChampSelectType` | `ChoiceType` | `choices` dynamiques via option `champ_valeurs_liste`, `placeholder = 'Sélectionnez un élément'` |
| `ChampNumberType` | `IntegerType` | `attr.class = 'form-control'`, `attr.min = 0` |
| `ChampCheckboxType` | `CheckboxType` | `required = false`, `false_values = ['0']` |
| `ChampDateType` | `TextType` | `attr.class = 'date form-control'` + `DataTransformer` d/m/Y ↔ \DateTime |

**Exemple — `ChampDateType` :**

```php
namespace App\Form\Type\Descriptif;

use App\Form\DataTransformer\DateToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChampDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new DateToStringTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['class' => 'date form-control'],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
```

**`DateToStringTransformer`** (`src/Form/DataTransformer/DateToStringTransformer.php`) :

```php
use Symfony\Component\Form\DataTransformerInterface;

class DateToStringTransformer implements DataTransformerInterface
{
    /**
     * DateTime → string pour l'affichage.
     *
     * @param \DateTimeInterface|null $value
     *
     * @return string
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        return $value->format('d/m/Y');
    }

    /**
     * String → DateTime pour la soumission.
     *
     * @param string|null $value
     *
     * @return \DateTime|null
     */
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $date = \DateTime::createFromFormat('d/m/Y', $value);

        return $date instanceof \DateTime ? $date : null;
    }
}
```

#### `ChampValueType` — Le dispatcheur dynamique

Ce type ne peut pas connaître le Champ à la construction. Il utilise un `PRE_SET_DATA` listener :

```php
namespace App\Form\Type\Descriptif;

use App\Constant\TypeChamp;
use App\Dto\Descriptif\ChampValueData;
use App\Repository\ChampRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChampValueType extends AbstractType
{
    /** @var ChampRepository */
    private $champRepository;

    public function __construct(ChampRepository $champRepository)
    {
        $this->champRepository = $champRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $champRepository = $this->champRepository;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($champRepository) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!$data instanceof ChampValueData) {
                return;
            }

            $champ = $champRepository->find($data->getChampId());
            if (null === $champ) {
                return;
            }

            $type = $champ->getType();
            $typeId = null !== $type ? $type->getId() : null;

            switch ($typeId) {
                case TypeChamp::TEXT:
                    $form->add('value', ChampTextType::class, ['label' => false]);
                    break;
                case TypeChamp::TEXTAREA:
                    $form->add('value', ChampTextareaType::class, ['label' => false]);
                    break;
                case TypeChamp::SELECT:
                    $choices = [];
                    foreach ($champ->getValeursListe() as $vl) {
                        $choices[$vl->getValeur()] = $vl->getValeur();
                    }
                    $form->add('value', ChampSelectType::class, [
                        'label' => false,
                        'choices' => $choices,
                    ]);
                    break;
                case TypeChamp::NUMBER:
                    $form->add('value', ChampNumberType::class, ['label' => false]);
                    break;
                case TypeChamp::CHECKBOX:
                    $form->add('value', ChampCheckboxType::class, ['label' => false]);
                    break;
                case TypeChamp::DATE:
                    $form->add('value', ChampDateType::class, ['label' => false]);
                    break;
                case TypeChamp::PARENT:
                    if ($champ->getTableau()) {
                        $form->add('lignes', CollectionType::class, [
                            'entry_type' => TableauRowType::class,
                            'entry_options' => [
                                'child_champs' => $champ->getEnfants()->toArray(),
                            ],
                            'allow_add' => true,
                            'allow_delete' => true,
                            'by_reference' => false,
                            'label' => false,
                            'prototype' => true,
                        ]);
                    } else {
                        $form->add('sousChamps', CollectionType::class, [
                            'entry_type' => self::class,
                            'label' => false,
                        ]);
                    }
                    break;
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChampValueData::class,
        ]);
    }
}
```

**Note importante :** Le `ChampRepository` est injecté via le constructeur car les FormTypes sont des services dans Symfony 4.4 (`services.yaml` avec autowiring).

**Optimisation performance :** Pour éviter N requêtes `ChampRepository::find()` (une par champ), on peut pré-charger tous les Champs via `CapsuleRubriqueRepository::findByNomInterneWithFullData()` (qui eager-load toute l'arborescence) et injecter une map dans les options. Cette optimisation est documentée dans la section Risques.

#### `TableauRowType`

```php
namespace App\Form\Type\Descriptif;

use App\Constant\TypeChamp;
use App\Dto\Descriptif\TableauRowData;
use App\Entity\Champ;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TableauRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Champ[] $childChamps */
        $childChamps = $options['child_champs'];

        foreach ($childChamps as $childChamp) {
            $champId = $childChamp->getId();
            $type = $childChamp->getType();
            $typeId = null !== $type ? $type->getId() : null;

            $formType = $this->resolveLeafType($typeId);
            $formOptions = [
                'label' => false,
                'required' => false,
                'property_path' => 'cells['.$champId.']',
            ];

            // Cas spécial : SELECT nécessite les choices
            if (TypeChamp::SELECT === $typeId) {
                $choices = [];
                foreach ($childChamp->getValeursListe() as $vl) {
                    $choices[$vl->getValeur()] = $vl->getValeur();
                }
                $formOptions['choices'] = $choices;
            }

            $builder->add((string) $champId, $formType, $formOptions);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TableauRowData::class,
        ]);
        $resolver->setRequired('child_champs');
        $resolver->setAllowedTypes('child_champs', 'array');
    }

    /**
     * @return string FQCN du FormType feuille
     */
    private function resolveLeafType(?int $typeId): string
    {
        switch ($typeId) {
            case TypeChamp::TEXT: return ChampTextType::class;
            case TypeChamp::TEXTAREA: return ChampTextareaType::class;
            case TypeChamp::SELECT: return ChampSelectType::class;
            case TypeChamp::NUMBER: return ChampNumberType::class;
            case TypeChamp::CHECKBOX: return ChampCheckboxType::class;
            case TypeChamp::DATE: return ChampDateType::class;
            default: return ChampTextType::class;
        }
    }
}
```

#### Construction programmatique (pas de `DescriptifType` / `RubriqueType` statiques)

La structure est dynamique (déterminée par CapsuleRubrique en BDD). Le `DescriptifFormBuilder` construit le formulaire programmatiquement :

```php
// Dans DescriptifFormBuilder::buildForm()
$formBuilder = $formFactory->createNamedBuilder('descriptif', FormType::class, $formData);

foreach ($rubriques as $item) {
    $rubrique = $item['rubrique'];
    $rubriqueId = $rubrique->getId();

    $rubriqueBuilder = $formFactory->createNamedBuilder(
        'rubrique_'.$rubriqueId,
        FormType::class,
        $formData->getRubrique($rubriqueId),
        ['auto_initialize' => false]
    );

    $rubriqueBuilder->add('display', CheckboxType::class, [
        'required' => false,
        'label' => $rubrique->getNom(),
    ]);

    foreach ($item['champs'] as $champData) {
        $champ = $champData['champ'];
        $champId = $champ->getId();

        $rubriqueBuilder->add(
            'champ_'.$champId,
            ChampValueType::class,
            ['label' => $champ->getNom()]
        );
    }

    $formBuilder->add($rubriqueBuilder);
}

return $formBuilder->getForm();
```

**Pourquoi pas des AbstractType classiques ?** Parce que la structure (quelles rubriques, quels champs) est **dynamique** et déterminée par la configuration admin (CapsuleRubrique en base), pas par le code. Le Builder itère cette structure à chaque requête.

---

### 3.3 Service Layer

#### `DescriptifFormHydrator` — `src/Service/Descriptif/DescriptifFormHydrator.php`

Responsabilité : transformer les données du service `Descriptif` (entities Valeur) en DTOs pour pré-remplir le formulaire.

```php
namespace App\Service\Descriptif;

use App\Constant\TypeChamp;
use App\Dto\Descriptif\ChampValueData;
use App\Dto\Descriptif\DescriptifFormData;
use App\Dto\Descriptif\RubriqueFormData;
use App\Dto\Descriptif\TableauRowData;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\Rubrique;
use App\Entity\Valeur;

class DescriptifFormHydrator
{
    /**
     * Construit un DescriptifFormData pré-rempli depuis les données d'un Dossier.
     *
     * @param list<array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}> $rubriques
     */
    public function hydrateFromDossier(Dossier $dossier, array $rubriques): DescriptifFormData
    {
        return $this->hydrate($rubriques);
    }

    /**
     * @param list<array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}> $rubriques
     */
    public function hydrateFromEtablissement(Etablissement $etablissement, array $rubriques): DescriptifFormData
    {
        return $this->hydrate($rubriques);
    }

    /**
     * @param list<array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}> $rubriques
     */
    private function hydrate(array $rubriques): DescriptifFormData
    {
        $rubriqueFormDatas = [];

        foreach ($rubriques as $item) {
            /** @var Rubrique $rubrique */
            $rubrique = $item['rubrique'];
            $rubriqueId = $rubrique->getId();
            if (null === $rubriqueId) {
                continue;
            }

            $champs = [];
            foreach ($item['champs'] as $champData) {
                /** @var Champ $champ */
                $champ = $champData['champ'];
                $champId = $champ->getId();
                if (null === $champId) {
                    continue;
                }
                $champs[$champId] = $this->hydrateChamp($champData);
            }

            $rubriqueFormDatas[$rubriqueId] = new RubriqueFormData(
                $item['display'],
                $champs
            );
        }

        return new DescriptifFormData($rubriqueFormDatas);
    }

    /**
     * Hydrate un champ en ChampValueData.
     *
     * @param array<string, mixed> $champData
     */
    private function hydrateChamp(array $champData): ChampValueData
    {
        /** @var Champ $champ */
        $champ = $champData['champ'];
        /** @var Valeur|null $valeur */
        $valeur = $champData['valeur'] ?? null;

        $type = $champ->getType();
        $typeId = null !== $type ? $type->getId() : null;

        if (TypeChamp::PARENT === $typeId) {
            if ($champ->getTableau()) {
                return $this->hydrateTableau($champ, $champData['lignes'] ?? []);
            }

            return $this->hydrateGroupe($champ, $champData['sous_champs'] ?? []);
        }

        $value = $this->extractValue($valeur, $typeId);
        $valeurId = null !== $valeur ? $valeur->getId() : null;

        return new ChampValueData((int) $champ->getId(), $value, [], [], $valeurId);
    }

    /**
     * Extrait la valeur typée depuis une entité Valeur selon le TypeChamp.
     *
     * @return string|int|bool|\DateTimeInterface|null
     */
    private function extractValue(?Valeur $valeur, ?int $typeId)
    {
        if (null === $valeur) {
            return null;
        }

        switch ($typeId) {
            case TypeChamp::TEXT:
            case TypeChamp::SELECT:
                return $valeur->getStr();
            case TypeChamp::TEXTAREA:
                return $valeur->getLongStr();
            case TypeChamp::NUMBER:
                return $valeur->getNumber();
            case TypeChamp::CHECKBOX:
                return $valeur->getCheckbox();
            case TypeChamp::DATE:
                return $valeur->getDate();
            default:
                return $valeur->getStr();
        }
    }

    /**
     * Hydrate un champ PARENT+tableau.
     *
     * @param list<array<int, Valeur>> $lignes
     */
    private function hydrateTableau(Champ $champ, array $lignes): ChampValueData
    {
        $rows = [];
        foreach ($lignes as $ligne) {
            $cells = [];
            $valeurIds = [];
            foreach ($ligne as $champId => $valeur) {
                $enfantChamp = $valeur->getChamp();
                $enfantTypeId = null;
                if (null !== $enfantChamp) {
                    $enfantType = $enfantChamp->getType();
                    $enfantTypeId = null !== $enfantType ? $enfantType->getId() : null;
                }
                $cells[$champId] = $this->extractValue($valeur, $enfantTypeId);
                $valeurIds[$champId] = $valeur->getId();
            }
            $rows[] = new TableauRowData($cells, $valeurIds);
        }

        return new ChampValueData((int) $champ->getId(), null, $rows);
    }

    /**
     * Hydrate un champ PARENT+groupe (non-tableau).
     *
     * @param list<array{champ: Champ, valeur: Valeur|null}> $sousChamps
     */
    private function hydrateGroupe(Champ $champ, array $sousChamps): ChampValueData
    {
        $sousChampDatas = [];
        foreach ($sousChamps as $sc) {
            $sousChampDatas[] = $this->hydrateChamp($sc);
        }

        return new ChampValueData((int) $champ->getId(), null, [], $sousChampDatas);
    }
}
```

#### `DescriptifFormBuilder` — `src/Service/Descriptif/DescriptifFormBuilder.php`

Responsabilité : construire le `FormInterface` Symfony à partir des DTOs et de la structure de capsule.

```php
namespace App\Service\Descriptif;

use App\Dto\Descriptif\DescriptifFormData;
use App\Entity\Rubrique;
use App\Form\Type\Descriptif\ChampValueType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class DescriptifFormBuilder
{
    /**
     * Construit un formulaire Symfony pour les formulaires personnalisés.
     *
     * @param list<array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}> $rubriques
     */
    public function buildForm(
        FormFactoryInterface $formFactory,
        DescriptifFormData $formData,
        array $rubriques
    ): FormInterface {
        $builder = $formFactory->createNamedBuilder(
            'descriptif',
            FormType::class,
            $formData,
            ['csrf_protection' => true]
        );

        foreach ($rubriques as $item) {
            /** @var Rubrique $rubrique */
            $rubrique = $item['rubrique'];
            $rubriqueId = $rubrique->getId();
            $rubriqueData = $formData->getRubrique((int) $rubriqueId);

            $rubriqueBuilder = $formFactory->createNamedBuilder(
                'rubrique_'.$rubriqueId,
                FormType::class,
                $rubriqueData,
                ['auto_initialize' => false, 'label' => $rubrique->getNom()]
            );

            $rubriqueBuilder->add('display', CheckboxType::class, [
                'required' => false,
                'label' => $rubrique->getNom(),
            ]);

            foreach ($item['champs'] as $champData) {
                $champ = $champData['champ'];
                $champId = $champ->getId();

                $rubriqueBuilder->add(
                    'champ_'.$champId,
                    ChampValueType::class,
                    [
                        'label' => $champ->getNom(),
                        'data' => $rubriqueData
                            ? $rubriqueData->getChamp((int) $champId)
                            : null,
                    ]
                );
            }

            $builder->add($rubriqueBuilder);
        }

        return $builder->getForm();
    }
}
```

#### `DescriptifFormPersister` — `src/Service/Descriptif/DescriptifFormPersister.php`

Responsabilité : mapper `DescriptifFormData` → entities Valeur. **Remplace `DescriptifSaver`.**

**Phases du Persister :**

1. **Display prefs** : itère `RubriqueFormData::display` → upsert `DossierDisplayRubrique` / `EtablissementDisplayRubrique`
2. **Champs simples** : compare `ChampValueData::valeurId` avec le lookup → update ou create. Si valeur vide + valeurId existant → delete.
3. **Tableaux** :
   - Itère `TableauRowData[]` avec la position dans le tableau comme nouvelle valeur d'`idx` (1-based)
   - Pour chaque cellule avec `valeurId` non-null → **update existant** ET **met à jour `idx` = position courante**
   - Pour chaque cellule avec `valeurId` null → create avec `idx` = position courante
   - Détecte les Valeur existantes absentes du DTO → delete
   - **Note drag & drop :** l'affectation `idx = position` normalise l'ordre à chaque save — après un drag & drop, le JS renomme les indices du CollectionType (voir 3.6), le Persister reflète automatiquement le nouvel ordre via les positions
4. **Groupes** : traite `sousChamps` comme des champs simples récursivement
5. **Flush unique** en fin de transaction

```php
namespace App\Service\Descriptif;

use App\Constant\TypeChamp;
use App\Dto\Descriptif\ChampValueData;
use App\Dto\Descriptif\DescriptifFormData;
use App\Dto\Descriptif\TableauRowData;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\DossierDisplayRubrique;
use App\Entity\Etablissement;
use App\Entity\EtablissementDisplayRubrique;
use App\Entity\Rubrique;
use App\Entity\Valeur;
use App\Repository\ChampRepository;
use App\Repository\DossierDisplayRubriqueRepository;
use App\Repository\EtablissementDisplayRubriqueRepository;
use App\Repository\RubriqueRepository;
use App\Repository\ValeurRepository;
use Doctrine\ORM\EntityManagerInterface;

class DescriptifFormPersister
{
    /** @var ValeurRepository */
    private $valeurRepository;

    /** @var ChampRepository */
    private $champRepository;

    /** @var RubriqueRepository */
    private $rubriqueRepository;

    /** @var DossierDisplayRubriqueRepository */
    private $dossierDisplayRubriqueRepository;

    /** @var EtablissementDisplayRubriqueRepository */
    private $etablissementDisplayRubriqueRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    // Constructor avec injection de toutes les dépendances...

    /**
     * @param list<array{rubrique: Rubrique, display: bool, champs: list<mixed>}> $rubriques
     */
    public function persistForDossier(
        Dossier $dossier,
        DescriptifFormData $formData,
        array $rubriques
    ): void {
        $existingValeurs = $this->valeurRepository->findByDossier($dossier);
        $this->doPersist($dossier, $formData, $rubriques, $existingValeurs);
    }

    /**
     * @param list<array{rubrique: Rubrique, display: bool, champs: list<mixed>}> $rubriques
     */
    public function persistForEtablissement(
        Etablissement $etablissement,
        DescriptifFormData $formData,
        array $rubriques
    ): void {
        $existingValeurs = $this->valeurRepository->findByEtablissement($etablissement);
        $this->doPersist($etablissement, $formData, $rubriques, $existingValeurs);
    }

    /**
     * @param Dossier|Etablissement $entity
     * @param list<array{rubrique: Rubrique, display: bool, champs: list<mixed>}> $rubriques
     * @param array<int, Valeur[]>  $existingValeurs
     */
    private function doPersist($entity, DescriptifFormData $formData, array $rubriques, array $existingValeurs): void
    {
        $lookup = $this->buildLookup($existingValeurs);
        /** @var int[] $touchedValeurIds IDs des Valeur tableau soumises */
        $touchedValeurIds = [];

        foreach ($rubriques as $item) {
            /** @var Rubrique $rubrique */
            $rubrique = $item['rubrique'];
            $rubriqueId = (int) $rubrique->getId();
            $rubriqueData = $formData->getRubrique($rubriqueId);

            if (null === $rubriqueData) {
                continue;
            }

            $this->upsertDisplayPref($entity, $rubriqueId, $rubriqueData->getDisplay());

            foreach ($rubriqueData->getChamps() as $champValueData) {
                $this->persistChampValue($entity, $champValueData, $lookup, $existingValeurs, $touchedValeurIds);
            }
        }

        // Supprimer les Valeur de tableau non soumises
        $this->deleteUntouchedTableauValeurs($entity, $existingValeurs, $touchedValeurIds);

        $this->entityManager->flush();
    }

    // Méthodes privées : persistSimple(), persistTableau(), persistGroupe(),
    // applyValueToValeur(), upsertDisplayPref(), buildLookup(),
    // deleteUntouchedTableauValeurs(), computeNextIdx()
    //
    // Même logique que DescriptifSaver mais avec des DTOs typés au lieu de POST brut.
}
```

---

### 3.4 Template Layer

#### Réécriture de `_formulaire_personnalise_edit.html.twig`

Le template actuel utilise des macros Twig pour générer des inputs HTML avec des noms spécifiques. Après migration, il utilisera `form_widget()`, `form_row()`, `form_errors()` de Symfony :

```twig
{# templates/components/_formulaire_personnalise_edit.html.twig (APRÈS migration) #}
{% form_theme form 'form/descriptif_theme.html.twig' %}

{{ form_start(form) }}

<div class="action-buttons pull-right">
    <a class="btn btn-default" href="javascript:history.back()" data-do-not-unload>
        <span class="glyphicon glyphicon-remove" aria-hidden="true"></span> Annuler
    </a>
    <button type="submit" class="btn btn-success" data-do-not-unload>
        <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> Enregistrer
    </button>
</div>

<div class="clearfix"></div>
<h3>{{ capsule ? capsule.nom : 'Formulaire personnalisé' }}</h3>

{% for rubriqueKey, rubriqueForm in form.children %}
    {% if rubriqueKey starts with 'rubrique_' %}
        {% include 'components/_descriptif_rubrique_panel.html.twig' with {
            rubriqueForm: rubriqueForm,
            rubriqueKey: rubriqueKey,
        } %}
    {% endif %}
{% endfor %}

{# Modale d'affichage #}
{% include 'components/_formulaire_personnalise_display_modal.html.twig' %}

{{ form_end(form) }}
```

#### Form Theme Bootstrap 3

Créer `templates/form/descriptif_theme.html.twig` :

```twig
{% use "bootstrap_3_layout.html.twig" %}

{# Thème pour ChampDateType : input + bouton "Aujourd'hui" #}
{% block champ_date_widget %}
    <div class="colDate">
        {{ parent() }}
        <button type="button" class="today btn btn-default">
            <span class="glyphicon glyphicon-calendar" aria-hidden="true"></span>
            Aujourd'hui
        </button>
    </div>
{% endblock %}

{# Thème pour les lignes de tableau #}
{% block tableau_row_widget %}
    <tr>
        {% for cell in form %}
            <td>{{ form_widget(cell) }}</td>
        {% endfor %}
        <td class="colonne-edition">
            <button type="button" class="deleteRow btn btn-danger">
                <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
            </button>
        </td>
    </tr>
{% endblock %}
```

#### Prototype JS pour CollectionType

Le `data-prototype` est automatiquement généré par Symfony pour les `CollectionType` avec `allow_add=true`. Le template rend :

```html
<tbody data-prototype="{{ form_widget(form.lignes.vars.prototype)|e('html_attr') }}"
       data-index="{{ form.lignes|length }}">
    {% for row in form.lignes %}
        {{ form_widget(row) }}
    {% endfor %}
</tbody>
```

---

### 3.5 Controller Updates

**Avant (pattern actuel, 4 controllers identiques) :**

```php
public function editVerificationsTechniques(Dossier $dossier, Request $request): Response
{
    $data = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES);

    if ($request->isMethod('POST')) {
        // CSRF manual, DescriptifSaver::saveForDossier($dossier, $request->request->all())
        // flash + redirect
    }

    return $this->render('...', ['dossier' => ..., 'capsule' => ..., 'rubriques' => ...]);
}
```

**Après :**

```php
public function editVerificationsTechniques(
    Dossier $dossier,
    Request $request,
    DescriptifFormHydrator $hydrator,
    DescriptifFormBuilder $formBuilder,
    DescriptifFormPersister $persister,
    FormFactoryInterface $formFactory
): Response {
    $data = $this->descriptifService->getForDossier(
        $dossier,
        NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES
    );

    $formData = $hydrator->hydrateFromDossier($dossier, $data['rubriques']);
    $form = $formBuilder->buildForm($formFactory, $formData, $data['rubriques']);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            /** @var DescriptifFormData $submittedData */
            $submittedData = $form->getData();
            $persister->persistForDossier($dossier, $submittedData, $data['rubriques']);
            $this->addFlash('success', 'Les vérifications techniques ont bien été mises à jour.');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde', [
                'dossier_id' => $dossier->getId(),
                'exception' => $e->getMessage(),
            ]);
            $this->addFlash('error', 'Erreur lors de la sauvegarde. Veuillez réessayer.');
        }

        return $this->redirectToRoute('dossier_verifications_techniques', ['id' => $dossier->getId()]);
    }

    return $this->render('dossier/edit_verifications_techniques.html.twig', [
        'dossier' => $dossier,
        'capsule' => $data['capsule'],
        'form' => $form->createView(),
    ]);
}
```

**Avantages :**
- CSRF géré nativement par le form (plus de token manuel)
- Validation possible via constraints Symfony
- `$form->getData()` retourne un DTO typé (plus de `$request->request->all()`)

---

### 3.6 JavaScript

#### Suppression : `gestionTableau.js`

Remplacé par le pattern officiel Symfony CollectionType.

#### Nouveau : `public/js/formulaire/descriptif/descriptif-collection.js`

```javascript
$(document).ready(function () {
    // Ajout de ligne de tableau via le prototype Symfony CollectionType
    $(document).on('click', '.addRow', function () {
        const $tbody = $(this).closest('.table-tableau').find('tbody');
        const prototype = $tbody.data('prototype');
        const index = parseInt($tbody.data('index'), 10) || 0;

        const newRow = prototype.replace(/__name__/g, index);
        $tbody.append(newRow);
        $tbody.data('index', index + 1);
    });

    // Suppression de ligne
    $(document).on('click', '.deleteRow', function () {
        $(this).closest('tr').remove();
    });
});
```

**Différence clé :** Plus de manipulation manuelle des noms d'inputs. Symfony gère les noms via `descriptif[rubrique_X][champ_Y][lignes][__name__][champId]`. Le prototype remplace `__name__` par l'index.

#### Compatibilité drag & drop (future implémentation)

Quand le drag & drop sera remis en place, la logique JS devra **renommer les indices** après chaque déplacement de ligne pour que l'ordre de soumission reflète l'ordre affiché :

```javascript
// Fonction à appeler après chaque déplacement de ligne (drag & drop)
function reindexTableauRows($tbody) {
    $tbody.find('tr').each(function (newIndex) {
        $(this).find('input, select, textarea').each(function () {
            const name = $(this).attr('name');
            if (name) {
                // Remplace l'ancien index par le nouveau dans le nom du champ
                // Ex : descriptif[rubrique_1][champ_5][lignes][2][...] → [1][...]
                $(this).attr('name', name.replace(/\[lignes\]\[\d+\]/, '[lignes][' + newIndex + ']'));
            }
        });
    });
    // Met à jour data-index pour que les nouvelles lignes aient un index unique
    $tbody.data('index', $tbody.find('tr').length);
}
```

**Pourquoi ça fonctionne avec le Persister :**
- Le Persister affecte `idx = position dans lignes[]` (1-based) pour **toutes** les lignes à chaque save
- `valeurIds` est indexé par `champId` (pas par position) → le tracking des entités résiste au réordonnancement
- Après drag & drop + renommage JS, `lignes[0]` contient la première ligne affichée avec ses `valeurIds` d'origine → le Persister met à jour les bonnes entités et leur affecte `idx=1`

#### Conservation : `edit.js`

Le fichier `edit.js` est conservé. Adaptation mineure des sélecteurs pour les checkboxes d'affichage (utiliser `data-*` attributes si les noms changent).

---

### 3.7 Tests

#### `DescriptifFormHydratorTest` — Test unitaire

```
- testHydrateChampSimpleTexte()
- testHydrateChampCheckboxVrai()
- testHydrateChampCheckboxFaux()
- testHydrateChampDate()
- testHydrateTableauAvecLignesExistantes()
- testHydrateTableauVide()
- testHydrateGroupeNonTableau()
- testHydrateRubriqueAvecDisplayFalse()
- testHydrateRubriquesVides()
```

#### `DescriptifFormPersisterTest` — Test unitaire (remplace `DescriptifSaverTest`)

```
- testPersistChampSimpleMetAJour()
- testPersistChampSimpleCree()
- testPersistChampSimpleVideSupprime()
- testPersistCheckbox()
- testPersistDate()
- testPersistTableauNouvelleLigne()
- testPersistTableauSupprimeLigneAbsente()
- testPersistDisplayPrefCree()
- testPersistDisplayPrefMetAJour()
- testPersistPourEtablissement()
```

#### `ChampValueTypeTest` — TypeTestCase Symfony

```
- testChampTexteRenduCorrectement()
- testChampSelectAvecChoix()
- testChampCheckboxSoumission()
- testChampDateTransformation()
- testChampParentTableauAjouteCollectionType()
```

#### `DescriptifFormBuilderTest` — KernelTestCase

```
- testBuildFormAvecRubriquesEtChamps()
- testBuildFormVide()
- testFormEstSoumissibleEtValide()
```

---

## 4. Phases d'implémentation

### Phase A — DTOs (1 commit)

| Fichier | Description |
|---------|-------------|
| `src/Dto/Descriptif/DescriptifFormData.php` | DTO racine |
| `src/Dto/Descriptif/RubriqueFormData.php` | DTO rubrique |
| `src/Dto/Descriptif/ChampValueData.php` | DTO champ (valeur ou conteneur) |
| `src/Dto/Descriptif/TableauRowData.php` | DTO ligne de tableau |

**Commit :** `feat(descriptif): ajout des DTOs pour les formulaires personnalisés`

**Validation :** PHPStan 0 erreur, CS 0 erreur

---

### Phase B — FormTypes (2-3 commits)

#### B1 : Types feuille + DataTransformer

| Fichier | Description |
|---------|-------------|
| `src/Form/DataTransformer/DateToStringTransformer.php` | Transformateur d/m/Y ↔ DateTime |
| `src/Form/Type/Descriptif/ChampTextType.php` | Wrapper TextType |
| `src/Form/Type/Descriptif/ChampTextareaType.php` | Wrapper TextareaType |
| `src/Form/Type/Descriptif/ChampSelectType.php` | ChoiceType dynamique |
| `src/Form/Type/Descriptif/ChampNumberType.php` | Wrapper IntegerType |
| `src/Form/Type/Descriptif/ChampCheckboxType.php` | Wrapper CheckboxType |
| `src/Form/Type/Descriptif/ChampDateType.php` | TextType + DateToStringTransformer |

**Commit :** `feat(descriptif): ajout des FormTypes feuille pour les champs`

#### B2 : Types composites

| Fichier | Description |
|---------|-------------|
| `src/Form/Type/Descriptif/TableauRowType.php` | Type pour une ligne de tableau |
| `src/Form/Type/Descriptif/ChampValueType.php` | Dispatcheur dynamique (PRE_SET_DATA) |

**Commit :** `feat(descriptif): ajout des FormTypes composites (tableau, dispatch)`

---

### Phase C — Services (2 commits)

#### C1 : Hydrator

| Fichier | Description |
|---------|-------------|
| `src/Service/Descriptif/DescriptifFormHydrator.php` | Valeur → DTO |

**Commit :** `feat(descriptif): ajout du service DescriptifFormHydrator`

#### C2 : Builder + Persister

| Fichier | Description |
|---------|-------------|
| `src/Service/Descriptif/DescriptifFormBuilder.php` | DTO + structure → FormInterface |
| `src/Service/Descriptif/DescriptifFormPersister.php` | DTO → Valeur entities (remplace DescriptifSaver) |

**Commit :** `feat(descriptif): ajout des services FormBuilder et FormPersister`

---

### Phase D — Controllers (1 commit)

| Fichier | Modification |
|---------|-------------|
| `src/Controller/EtablissementController.php` | `editDescriptifPersonnalise()` + `editEffectifsDegagements()` → nouveau pattern |
| `src/Controller/DossierController.php` | `editVerificationsTechniques()` → nouveau pattern |
| `src/Controller/DossierEffectifsController.php` | `editEffectifsDegagements()` → nouveau pattern |

**Commit :** `refactor(descriptif): migration des controllers vers Symfony Forms`

---

### Phase E — Templates + JS (1-2 commits)

#### E1 : Templates + Form Theme

| Fichier | Description |
|---------|-------------|
| `templates/components/_formulaire_personnalise_edit.html.twig` | Réécriture avec `form_widget()` |
| `templates/form/descriptif_theme.html.twig` | Form theme Bootstrap 3 |
| `templates/components/_descriptif_rubrique_panel.html.twig` | Panel de rubrique (extrait de la macro) |

**Commit :** `feat(descriptif): réécriture des templates avec Symfony form_widget`

#### E2 : JavaScript

| Fichier | Description |
|---------|-------------|
| `public/js/formulaire/descriptif/descriptif-collection.js` | Nouveau JS pour CollectionType |
| `public/js/formulaire/descriptif/edit.js` | Adaptations mineures (sélecteurs) |

**Commit :** `feat(descriptif): nouveau JS pour gestion des tableaux CollectionType`

---

### Phase F — Tests (1-2 commits)

| Fichier | Description |
|---------|-------------|
| `tests/Service/Descriptif/DescriptifFormHydratorTest.php` | Tests unitaires hydration |
| `tests/Service/Descriptif/DescriptifFormPersisterTest.php` | Tests unitaires persistence |
| `tests/Form/Type/Descriptif/ChampValueTypeTest.php` | TypeTestCase |
| `tests/Service/Descriptif/DescriptifFormBuilderTest.php` | Intégration KernelTestCase |

**Commit :** `test(descriptif): tests des services et FormTypes`

---

### Phase G — Cleanup (1 commit)

| Action | Fichier |
|--------|---------|
| Supprimer | `src/Service/DescriptifSaver.php` |
| Supprimer | `tests/Service/DescriptifSaverTest.php` |
| Supprimer | `public/js/formulaire/tableau/gestionTableau.js` |
| Mettre à jour | `docs/tech/suggestions/SUGGESTIONS_MAINTENABILITE_FORMULAIRES.md` |

**Commit :** `refactor(descriptif): suppression de DescriptifSaver et gestionTableau.js`

---

## 5. Décisions techniques clés

| Décision | Justification |
|----------|---------------|
| **DTOs vs Doctrine entities** | Les entités Valeur ont un mapping type-spécifique (str/longStr/number/checkbox/date) incompatible avec les formulaires Symfony. Les DTOs offrent un `value` unifié et un `champId` pour le dispatch. |
| **CollectionType vs JS custom** | Le pattern officiel Symfony (`data-prototype`, `allow_add/delete`) est plus maintenable que le JS legacy qui manipule les noms d'inputs manuellement. |
| **PRE_SET_DATA dans ChampValueType** | Le type de champ est dynamique (stocké en BDD), inconnu à la construction du FormType. Le listener permet d'ajouter le bon sous-type à la volée. |
| **Builder programmatique vs types déclaratifs** | La structure Capsule/Rubrique/Champ est en BDD — impossible de déclarer les champs dans un AbstractType statique. Le Builder itère la structure à chaque requête. |
| **valeurIds dans TableauRowData** | Permet au Persister de distinguer update (valeurId existant) vs create (null) sans re-requêter la base. Rempli par le Hydrator lors du chargement. Index par `champId` (pas par position de ligne) → résiste au réordonnancement drag & drop. |
| **idx normalisé à chaque save** | Le Persister affecte `idx = position dans lignes[]` pour toutes les lignes (existantes + nouvelles) à chaque sauvegarde. Pas d'accumulation de gaps, et l'ordre drag & drop est reflété dès que le JS renomme les indices CollectionType. |
| **Conserver Descriptif service** | Le service de lecture (`getForDossier`, `getForEtablissement`) reste inchangé — il fournit les données pour le Hydrator et le Builder. Pas de modification inutile. |
| **Pas de refactorisation des entities** | Les entities Valeur, Champ, Rubrique ne changent pas. Seul le code de binding/persistence change. Aucune migration BDD. |

---

## 6. Risques et mitigations

| Risque | Impact | Mitigation |
|--------|--------|-----------|
| **Régression tableau (add/delete)** | Critique | Tests E2E manuels obligatoires sur les 4 onglets. Conserver les tests `DescriptifSaverTest` jusqu'à Phase G. |
| **Performance PRE_SET_DATA** | Moyen | Le listener fait un `ChampRepository::find()` par champ. Pour un formulaire dense (100+ champs) = 100 requêtes. **Mitigation** : la capsule est déjà eager-loaded par `findByNomInterneWithFullData()`. Injecter une map `champId → Champ` dans les options du form pour éviter les queries dans le listener. |
| **Noms d'inputs changent** | Moyen | Les noms passent de `champ-{id}` à `descriptif[rubrique_X][champ_Y][value]`. Le `DescriptifCopier` et `DescriptifInspector` ne sont **pas** impactés (ils opèrent sur les entities, pas sur le POST). |
| **JavaScript existant** | Faible | `edit.js` utilise des sélecteurs `[name^="afficher_rubrique-"]`. **Mitigation** : utiliser des `data-*` attributes dans le form theme pour découpler le JS des noms d'inputs. |
| **Compatibilité PHP 7.1** | Faible | Pas de propriétés typées, pas de `void`, pas de `mixed`. Vérifiable via PHPStan niveau 10 (`castor symfony:analyse`). |
| **Migration base de données** | Aucun | Aucune modification de schéma requise. Les entities ne changent pas. |
| **Impact sur DescriptifCopier** | Aucun | Le Copier opère sur les entities Valeur directement, pas via les formulaires. |

---

## 7. Mise à jour documentation

### `docs/tech/suggestions/SUGGESTIONS_MAINTENABILITE_FORMULAIRES.md`

Ajouter en fin de fichier :

```markdown
---

## Décisions implémentées

### ✅ 1. Symfony Forms typés (suggestion #1)
**Implémenté dans :** Phases B-E
- `DescriptifSaver` remplacé par le pipeline DTO → FormType → Persister
- Noms d'inputs gérés par Symfony (plus de parsing manuel)
- CSRF natif, validation possible via constraints

### ✅ 3. DTOs typés (suggestion #3)
**Implémenté dans :** Phase A
- `DescriptifFormData`, `RubriqueFormData`, `ChampValueData`, `TableauRowData`
- Valeurs typées par DTOs, PHPStan niveau 10

### ⏳ 2. Contrôleur générique (suggestion #2)
**Non implémenté** — À planifier séparément. Les 4 actions utilisent désormais le même
pattern (Hydrator → Builder → Form → Persister), ce qui facilite une future factorisation.

### ⏳ 4. Validateurs Symfony (suggestion #4)
**Non implémenté** — Possible en ajoutant des constraints sur les DTOs.
La base est prête grâce aux FormTypes.
```

---

## 8. Estimation d'effort

| Phase | Effort estimé | Dépendances |
|-------|--------------|-------------|
| A — DTOs | 0.5 jour | Aucune |
| B — FormTypes | 1.5 jours | Phase A |
| C — Services | 1.5 jours | Phases A + B |
| D — Controllers | 0.5 jour | Phase C |
| E — Templates + JS | 1.5 jours | Phases B + D |
| F — Tests | 1 jour | Phases A-E |
| G — Cleanup | 0.5 jour | Phase F |
| **Total** | **~7 jours** | |

---

## Annexe : Mapping complet des champs de Valeur

| TypeChamp | ID | Champ Valeur | Type PHP | FormType Symfony |
|-----------|----|-----------  |----------|-----------------|
| TEXT | 1 | `str` | `?string` | `ChampTextType` (→ TextType) |
| TEXTAREA | 2 | `longStr` | `?string` | `ChampTextareaType` (→ TextareaType) |
| SELECT | 3 | `str` | `?string` | `ChampSelectType` (→ ChoiceType) |
| NUMBER | 4 | `number` | `?int` | `ChampNumberType` (→ IntegerType) |
| CHECKBOX | 5 | `checkbox` | `?bool` | `ChampCheckboxType` (→ CheckboxType) |
| PARENT | 6 | — | — | `ChampValueType` → `TableauType` ou `sousChamps` |
| DATE | 7 | `date` | `?\DateTimeInterface` | `ChampDateType` (→ TextType + transformer) |
