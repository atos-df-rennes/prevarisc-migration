# Édition des dossiers - Documentation technique

**Date de création :** 10 décembre 2025  
**Dernière mise à jour :** 17 décembre 2025  
**Version Symfony :** 4.4 LTS  
**PHP :** 7.1.33

---

## 📊 État d'avancement

### Natures opérationnelles (36/68) - 53%

**Réunions (3/3)** : 31, 32, 43  
**Courriers (12/12)** : 49, 50, 51, 52, 53, 54, 55, 57, 58, 59, 60, 65  
**Visites (3/3)** : 21 (Périodique), 28 (Visite de contrôle), 29 (Visite inopinée)  
**Études (19/25)** : 1-19 (PC, AT, Dérogation, CTS, PA, PD, etc.)

### Champs ajoutés au FormType

- `chargeSecurite` (Nature 6)
- `datePrefecture` (Nature 8)
- Tous les autres champs étaient déjà présents

### Corrections appliquées

1. **Nature 7** : dateReponse → dateReception (DATESDIS)
2. **Nature 11** : Retrait descriptifGeneral/descriptifEffectifs (non utilisés dans formulaire principal)
3. **Nature 15** : facteurDangerosite/echeancierTravaux limités aux visites uniquement
4. **Préventionnistes inactifs** : Affichage avec indication "(inactif)"

---

## 📁 Architecture

### Fichiers principaux

```
src/
├── Controller/
│   └── DossierController.php (edit, update)
├── Form/
│   ├── DossierType.php (formulaire principal)
│   └── DataTransformer/
│       └── DateCommissionTransformer.php
├── Service/
│   ├── DossierFields.php (configuration champs par nature)
│   └── DossierService.php (logique métier)
├── Repository/
│   └── UtilisateurRepository.php
│       ├── createPreventionnistesQueryBuilder()
│       └── createPreventionnistesWithExistingQueryBuilder()
└── Entity/
    ├── Dossier.php
    └── Utilisateur.php (avec getDisplayLabel())

templates/dossier/
└── edit.html.twig

public/js/dossier/
└── form.js (affichage conditionnel des champs)
```

### Service DossierFields

**Rôle** : Définir les champs visibles par nature

```php
class DossierFields
{
    public const FIELDS_BY_NATURE = [
        '1' => ['dateInsert', 'objet', 'documentsUrbanisme', ...],
        '21' => ['dateInsert', 'dateVisite', 'avis', ...],
        // ... 68 natures
    ];
}
```

---

## 🎨 Affichage conditionnel (JavaScript)

### Champs masqués par défaut

Tous les champs du formulaire sont générés, puis masqués/affichés selon la nature via `form.js`.

### Logiques spéciales

**1. Avis commission**
- Labels dynamiques selon type/nature (ex: "à l'exploitation", "à l'étude")
- Désactivé si avis non favorable pour les visites

**2. facteurDangerosite / echeancierTravaux**
- Uniquement pour visites (type 2/3, natures 20-29)
- Affichés si avis commission = Défavorable (ID 2)

**3. Commission**
- Désactivée pour visites si dateVisite non renseignée

**4. analyseDeRisque**
- Affiché pour types 1, 2, 3 (études, visites, groupes)

---

## 🔧 Guide : Ajout d'un nouveau champ

### 1. Entité et base de données

```php
// src/Entity/Dossier.php
/**
 * @var ?string
 * @ORM\Column(name="NOUVEAUCHAMP_DOSSIER", type="text", nullable=true)
 */
private $nouveauChamp;

public function getNouveauChamp(): ?string
{
    return $this->nouveauChamp;
}

public function setNouveauChamp(?string $nouveauChamp): self
{
    $this->nouveauChamp = $nouveauChamp;
    return $this;
}
```

### 2. FormType

```php
// src/Form/DossierType.php
->add('nouveauChamp', TextType::class, [
    'label' => 'Label du champ',
    'required' => false,
    'attr' => ['class' => 'form-control'],
])
```

### 3. Configuration par nature

```php
// src/Service/DossierFields.php
'21' => ['dateInsert', 'nouveauChamp', 'objet', ...],
```

### 4. JavaScript (si conditionnel)

```javascript
// public/js/dossier/form.js
const conditionalFields = [
    'nouveauChamp',
    // ...
];
```

### 5. Template (si nécessaire)

```twig
{# templates/dossier/edit.html.twig #}
{{ form_row(form.nouveauChamp) }}
```

### 6. Test

```php
// tests/Functional/DossierEditNatureXTest.php
$this->assertStringContainsString('Label du champ', $content);
```

---

## 📋 Mapping champs Legacy → Symfony

### Champs principaux

| Legacy | Colonne BDD | Symfony | Type |
|--------|-------------|---------|------|
| `DATEINSERT` | `DATEINSERT_DOSSIER` | `dateInsert` | datetime |
| `OBJET` | `OBJET_DOSSIER` | `objet` | string |
| `DATEMAIRIE` | `DATEMAIRIE_DOSSIER` | `dateMairie` | datetime |
| `DATESECRETARIAT` | `DATESECRETARIAT_DOSSIER` | `dateSecretariat` | datetime |
| `DATECOMM` | `DATECOMM_DOSSIER` | `dateCommission` | string (format dd/mm/yyyy) |
| `DATEREP` | `DATEREP_DOSSIER` | `dateReponse` | datetime |
| `DATESDIS` | `DATESDIS_DOSSIER` | `dateReception` | datetime |
| `DATEPREF` | `DATEPREF_DOSSIER` | `datePrefecture` | datetime |
| `PREVENTIONNISTE` | - | `preventionnistes` | Collection |
| `OBSERVATION` | `OBSERVATION_DOSSIER` | `observations` | text |

### Champs spéciaux visites

| Legacy | Symfony | Type |
|--------|---------|------|
| `DATEVISITE` | `dateVisite` | datetime |
| `FACTEUR` | `facteurDangerosite` | text |
| `ECHEANCIER` | `echeancierTravaux` | text |
| `COORDSSI` | `coordonneesSsi` | text |

### Champs spéciaux études

| Legacy | Symfony | Type |
|--------|---------|------|
| `NUMDOCURBA` | `documentsUrbanisme` | text |
| `SERVICEINST` | `serviceInstructeur` | string |
| `CHARGESEC` | `chargeSecurite` | text |

### Champs non implémentés

- `NUMCHRONO` : Absent de l'entité Dossier
- `DESCGEN` / `DESCEFF` : Uniquement dans onglet séparé `descriptifs.phtml`

---

## 🧪 Tests

### Tests fonctionnels

Un test par nature pour vérifier l'affichage des champs :

```bash
tests/Functional/DossierEditNature{1-68}Test.php
```

**Structure type :**
```php
public function testFormDisplaysCorrectFieldsForNatureX(): void
{
    $dossier = $this->findDossierByNature(X);
    $client->request('GET', '/dossier/{id}/modifier');
    
    $this->assertResponseIsSuccessful();
    $this->assertStringContainsString('Label attendu', $content);
}
```

### Commandes de validation

```bash
castor symfony:analyse  # PHPStan niveau 10
castor symfony:cs       # PHP-CS-Fixer + Rector + Twig-CS
castor symfony:test     # PHPUnit
```

---

## 🔍 Particularités techniques

### DateCommission (format dd/mm/yyyy)

Stockée en string, pas en datetime. Utilise un DataTransformer :

```php
// src/Form/DataTransformer/DateCommissionTransformer.php
$builder->get('dateCommission')->addModelTransformer(new DateCommissionTransformer());
```

### Préventionnistes inactifs

Affichés avec `(inactif)` en édition mais non sélectionnables en création :

```php
// src/Repository/UtilisateurRepository.php
public function createPreventionnistesWithExistingQueryBuilder(iterable $existingUsers = []): QueryBuilder
{
    // Inclut actifs OU déjà sélectionnés
}

// src/Entity/Utilisateur.php
public function getDisplayLabel(): string
{
    return $label . ($this->actif ? '' : ' (inactif)');
}
```

### Confusion DATEREP / DATESDIS

⚠️ Bien distinguer :
- `DATEREP` → `dateReponse` (Date de réponse)
- `DATESDIS` → `dateReception` (Date réception SDIS)

---

## 📚 Références

- **Legacy** : `/prevarisc/application/views/scripts/dossier/index.phtml`
- **Controller legacy** : `/prevarisc/application/controllers/DossierController.php`
- **Mapping complet** : `/docs/tech/migration/MAPPING_LEGACY_REFERENCE.md`
- **Affichage (lecture)** : `/docs/tech/dossier/migration-affichage-dossiers.md`

---

## 🎯 Prochaines étapes

1. Terminer les études (natures 20-25)
2. Implémenter la sauvegarde (méthode update)
3. Valider avec données réelles
4. Ajouter les validations métier
