# Système de tests automatisés de comparaison Legacy vs Symfony

**Date :** 10 décembre 2025  
**Contexte :** Migration affichage dossiers (Suggestion #12)  
**Status :** ⚠️  Infrastructure créée, nécessite configuration authentification tests

---

## 🎯 Objectif

Garantir que l'affichage Symfony est **100% conforme ISO** au legacy pour tous les champs et toutes les natures de dossiers.

**Approche :** Tests automatisés qui comparent :
- ✅ Présence/absence des champs attendus
- ✅ Labels affichés
- ✅ Valeurs affichées (format dates, etc.)
- ✅ Conformité avec la configuration `DossierFields`

---

## 📁 Fichiers créés

### Test principal
**Fichier :** `tests/Functional/DossierAffichageComparisonTest.php`

**Contenu :**
- Classe `DossierAffichageComparisonTest` extends `WebTestCase`
- Tests par nature (31, 32, 52, etc.)
- Méthode de comparaison automatisée
- Rapport détaillé des écarts

### Tests implémentés

| Méthode | Nature | Type | Description |
|---------|--------|------|-------------|
| `testAffichageReunionLocauxSdis()` | 31 | Réunion | Réunion locaux SDIS |
| `testAffichageReunionExterieurSdis()` | 32 | Réunion | Réunion extérieur SDIS |
| `testAffichageCourrierLettre()` | 52 | Courrier | Lettre |

---

## 🔧 Fonctionnement

### 1. Principe

Pour chaque nature de dossier :

```php
public function testAffichageReunionLocauxSdis(): void
{
    $dossier = $this->getDossierByNature(31);
    
    $this->compareAffichageWithLegacy($dossier, [
        'dateInsert' => 'Date de création',
        'objet' => 'Objet',
        'dateReunion' => 'Date de réunion',
        'dateReponse' => 'Date de réponse',
        'demandeur' => 'Demandeur',
        'observations' => 'Observations',
    ]);
}
```

### 2. Vérifications automatiques

La méthode `compareAffichageWithLegacy()` vérifie :

#### a) Si valeur NULL/vide
- ❌ **Erreur** : Si le champ est affiché alors qu'il devrait être masqué
- ⚠️ **Avertissement** : Si le champ est affiché mais vide

#### b) Si valeur renseignée
- ✅ **Champ affiché** : Vérifie la présence dans le DOM
- ✅ **Label correct** : Compare avec le label attendu
- ✅ **Valeur correcte** : Compare avec la valeur formatée attendue

### 3. Rapport généré

```
================================================================
📊 RAPPORT DE COMPARAISON - Dossier #51921 (Nature 31)
================================================================

✅ Champs attendus : 6
❌ Erreurs critiques : 0
⚠️  Avertissements : 0

✅ PARFAIT ! Tous les champs sont conformes au legacy.
================================================================
```

Ou en cas d'erreurs :

```
================================================================
📊 RAPPORT DE COMPARAISON - Dossier #51921 (Nature 31)
================================================================

✅ Champs attendus : 6
❌ Erreurs critiques : 2
⚠️  Avertissements : 1

--- ERREURS CRITIQUES ---
❌ Champ "dateReunion" (Date de réunion) : NON affiché alors qu'il contient une valeur
❌ Champ "objet" : valeur incorrecte (attendu: "Test réunion", obtenu: "")

--- AVERTISSEMENTS ---
⚠️  Champ "observations" (Observations) : affiché alors que la valeur est vide/null

================================================================
```

---

## 🚧 Status actuel : Bloqué par l'authentification

### Problème

Les tests nécessitent un utilisateur authentifié pour accéder aux pages `/dossier/{id}`.

**Erreur actuelle :**
```
HTTP/1.1 302 Found
Location: /session/login
```

### Solution à implémenter

#### Option 1 : Désactiver l'authentification en environnement test

**Fichier :** `config/packages/test/security.yaml`

```yaml
security:
    firewalls:
        main:
            pattern: ^/
            security: false
```

⚠️ **Attention :** Cela désactive toute sécurité en mode test

#### Option 2 : Créer un utilisateur de test

```php
protected function setUp(): void
{
    parent::setUp();
    self::bootKernel();
    
    $this->entityManager = self::$container->get('doctrine')->getManager();
    
    // Créer un utilisateur de test
    $this->loginAsUser();
}

private function loginAsUser(): void
{
    $user = $this->entityManager
        ->getRepository(Utilisateur::class)
        ->findOneBy(['login' => 'test_user']);
        
    if (null === $user) {
        $this->markTestSkipped('Utilisateur de test non trouvé');
    }
    
    $client = static::createClient();
    $client->loginUser($user);
}
```

#### Option 3 : Utiliser HTTP Basic Auth (si activé)

```php
$client = static::createClient([], [
    'PHP_AUTH_USER' => 'test_user',
    'PHP_AUTH_PW'   => 'test_password',
]);
```

---

## 🎯 Utilisation future

### Étape 1 : Configurer l'authentification

Choisir et implémenter une des options ci-dessus.

### Étape 2 : Exécuter les tests

```bash
# Tous les tests de comparaison
castor symfony:test tests/Functional/DossierAffichageComparisonTest.php

# Un test spécifique
castor symfony:test tests/Functional/DossierAffichageComparisonTest.php --filter testAffichageReunionLocauxSdis

# Mode verbose avec rapport détaillé
castor symfony:test tests/Functional/DossierAffichageComparisonTest.php --testdox
```

### Étape 3 : Ajouter de nouveaux tests

Pour chaque nature migrée, ajouter un test :

```php
/**
 * Test de comparaison pour visite périodique (nature 21)
 */
public function testAffichageVisitePeriodique(): void
{
    $dossier = $this->getDossierByNature(21);
    
    if (null === $dossier) {
        $this->markTestSkipped('Aucun dossier de nature 21 en base de données');
    }
    
    $this->compareAffichageWithLegacy($dossier, [
        'dateInsert' => 'Date de création',
        'objet' => 'Objet',
        'dateVisite' => 'Date de visite',
        'dateCommission' => 'Date commission',
        // ... autres champs
    ]);
}
```

---

## 📊 Métriques de qualité

### Tests actuels

- ✅ **3 natures** testées (31, 32, 52)
- ✅ **~20 champs** vérifiés au total
- ⚠️ **Tests skippés** (authentification manquante)

### Objectif final

- 🎯 **15+ natures** testées (toutes les natures implémentées)
- 🎯 **100+ champs** vérifiés
- 🎯 **100% de conformité** avec le legacy

---

## 🔍 Points de contrôle

### Structure HTML attendue

Pour que les tests fonctionnent, le HTML doit suivre cette structure :

```html
<div id="field-{nomPropriete}">
    <div class="left libelle">{Label affiché}</div>
    <div class="right form">
        <div class="form-control-static">{Valeur affichée}</div>
    </div>
</div>
```

**Exemple :**

```html
<div id="field-dateReunion">
    <div class="left libelle">Date de réunion</div>
    <div class="right form">
        <div class="form-control-static">15/12/2025</div>
    </div>
</div>
```

### Format des valeurs

Les valeurs sont automatiquement formatées :

| Type | Format attendu | Exemple |
|------|----------------|---------|
| `DateTime` | `d/m/Y` | `15/12/2025` |
| `boolean` | `Oui` / `Non` | `Oui` |
| `string` | Texte brut | `Réunion de chantier` |
| Objet avec `__toString()` | Résultat de `__toString()` | `Commission sécurité` |

---

## 🎓 Avantages du système

### 1. Détection automatique des régressions

```
❌ Champ "dateReunion" : label incorrect 
   (attendu: "Date de réunion", obtenu: "Date réunion")
```

### 2. Validation de la configuration DossierFields

Les champs testés correspondent exactement à ceux définis dans `DossierFields::getFieldsForNature()`.

### 3. Documentation vivante

Les tests servent de documentation :
- Quels champs sont affichés par nature
- Quels labels sont utilisés
- Quel format de données est attendu

### 4. Gain de temps en recette

- ✅ Tests automatisés en quelques secondes
- ✅ Rapport détaillé des écarts
- ✅ Pas de tests manuels fastidieux

---

## 📝 TODO

### Court terme

- [ ] Configurer l'authentification pour les tests
- [ ] Exécuter les 3 tests existants
- [ ] Valider le rapport de comparaison

### Moyen terme

- [ ] Ajouter tests pour toutes les natures implémentées (15+)
- [ ] Intégrer dans la CI/CD
- [ ] Créer des fixtures de test si besoin

### Long terme

- [ ] Tests E2E avec Panther (JavaScript compris)
- [ ] Tests de comparaison pour l'édition (pas seulement affichage)
- [ ] Snapshots HTML pour régression visuelle

---

## 🔗 Références

- **Suggestion #12** : `docs/tech/suggestions/SUGGESTIONS_AMELIORATION_EDITION_DOSSIER.md`
- **Mapping legacy** : `docs/tech/MAPPING_LEGACY_REFERENCE.md`
- **Service DossierFields** : `src/Service/DossierFields.php`
- **Template affichage** : `templates/dossier/index.html.twig`

---

**Auteur :** Équipe Prevarisc  
**Date de création :** 10 décembre 2025  
**Status :** Infrastructure créée, en attente configuration authentification  
**Priorité :** 🟢 Haute (qualité logicielle)
