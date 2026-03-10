# Documentation des constantes métier

## Vue d'ensemble

Pour améliorer la lisibilité et la maintenabilité du code, les **magic numbers** (identifiants en base de données utilisés directement dans le code) sont regroupés dans des classes de constantes dédiées dans le répertoire `src/Constant/`.

## Architecture

```
src/Constant/
├── GenreEtablissement.php    # Genres d'établissements (ERP, IGH, etc.)
└── DossierNature.php          # Natures de dossiers
```

## Classes de constantes disponibles

### GenreEtablissement

Constantes pour les genres d'établissements (table `genre`).

**Constantes :**
- `GenreEtablissement::ERP` (2) - Établissement Recevant du Public
- `GenreEtablissement::IGH` (5) - Immeuble de Grande Hauteur
- `GenreEtablissement::HABITATION` (4) - Habitation
- `GenreEtablissement::BUP` (6) - Bâtiment à Usage Principal
- `GenreEtablissement::ALL` - Tableau de tous les genres

**Exemple d'utilisation :**
```php
use App\Constant\GenreEtablissement;

if ($genre->getId() === GenreEtablissement::ERP) {
    // Traitement spécifique ERP
}

// Au lieu de :
if ($genre->getId() === 2) { // ❌ Magic number
    // Traitement spécifique ERP
}
```

### DossierNature

Constantes pour les natures de dossiers (table `dossiernatureliste`).

**Constantes :**
- `DossierNature::ECHEANCIER_TRAVAUX` (46) - Nature écheancier de travaux
- `DossierNature::NATURES_DONNANT_AVIS` - Tableau des natures de dossiers donnant avis

**Exemple d'utilisation :**
```php
use App\Constant\DossierNature;

if ($nature->getId() === DossierNature::ECHEANCIER_TRAVAUX) {
    // Traitement spécifique écheancier
}

if (in_array($nature->getId(), DossierNature::NATURES_DONNANT_AVIS)) {
    // Traitement pour dossiers donnant avis
}
```

## Principes et conventions

### 1. Création d'une nouvelle classe de constantes

**Quand créer une nouvelle classe :**
- Table de référence fixe (rarement modifiée)
- Identifiants utilisés à plusieurs endroits dans le code
- Besoin de clarifier le sens métier d'un identifiant

**Structure type :**
```php
<?php

namespace App\Constant;

/**
 * Description de la classe.
 *
 * Correspond à la table `nom_table` en base de données.
 */
final class MaConstante
{
    /**
     * Description de la constante.
     */
    public const MA_CONSTANTE = 1;

    /**
     * Empêche l'instanciation de la classe.
     */
    private function __construct()
    {
    }
}
```

### 2. Nommage

**Nom de la classe :** Singulier, représente le concept métier (pas le nom de la table)
- ✅ `GenreEtablissement`
- ❌ `Genre` (trop générique)
- ❌ `GenreTable` (référence technique)

**Nom des constantes :** UPPER_SNAKE_CASE, explicite
- ✅ `ECHEANCIER_TRAVAUX`
- ❌ `NATURE_46` (pas explicite)

### 3. Documentation

Chaque classe et constante doit être documentée :
- Description de la classe avec référence à la table BDD
- Description de chaque constante avec sa signification métier
- Valeur entre parenthèses dans le commentaire si utile

### 4. Migration progressive

Les anciennes constantes peuvent être marquées `@deprecated` pour migration progressive :

```php
class MonEntite
{
    /**
     * @deprecated Utiliser MaConstante::MA_VALEUR
     */
    public const ANCIENNE_CONSTANTE = MaConstante::MA_VALEUR;
}
```

## Fichiers impactés

### Refactorisation effectuée

**Classes de constantes créées :**
- `src/Constant/GenreEtablissement.php`
- `src/Constant/DossierNature.php`

**Voters refactorisés :**
- `src/Security/Voter/EtablissementErpVoter.php`
- `src/Security/Voter/EtablissementIghVoter.php`
- `src/Security/Voter/EtablissementHabitationVoter.php`
- `src/Security/Voter/EtablissementBupVoter.php`

**Repositories refactorisés :**
- `src/Repository/DossierRepository.php`

**Entités refactorisées :**
- `src/Entity/Etablissement.php`
- `src/Entity/DossierNatureListe.php` (constantes marquées deprecated)

## Avantages

✅ **Lisibilité** : Le code est auto-documenté, plus besoin de consulter la BDD  
✅ **Maintenabilité** : Modification centralisée si les valeurs changent  
✅ **Type-safe** : Pas de risque de typo dans les valeurs numériques  
✅ **Découvrabilité** : IDE autocompletion sur les constantes disponibles  
✅ **Refactoring** : Recherche/remplacement facilité

## Bonnes pratiques

**À faire :**
- ✅ Utiliser les constantes dans les conditions, requêtes, comparaisons
- ✅ Documenter chaque nouvelle constante ajoutée
- ✅ Grouper les constantes liées dans des tableaux (`ALL`, etc.)
- ✅ Marquer les anciennes constantes `@deprecated` lors de la migration

**À éviter :**
- ❌ Dupliquer les constantes dans plusieurs classes
- ❌ Mélanger constantes métier et constantes techniques
- ❌ Créer des classes de constantes pour des valeurs volatiles
- ❌ Utiliser directement les magic numbers dans le nouveau code

## Évolutions futures

### Tables candidates pour constantes

Autres tables pouvant bénéficier de classes de constantes :
- `dossiertype` → `DossierType`
- `etablissementstatut` → `EtablissementStatut`
- `avis` → `AvisType`
- `fonction` → `FonctionType`

### Service de référence (si nécessaire)

Pour les données vraiment dynamiques, considérer un service avec cache :

```php
class GenreReferenceService
{
    public function getErpId(): int
    {
        return $this->findByLibelle('ERP')->getId();
    }
}
```

**Avantages :** Synchronisé avec la BDD, données dynamiques  
**Inconvénients :** Plus complexe, injection de service nécessaire

---

**Dernière mise à jour :** 12 janvier 2026  
**Responsable :** Équipe migration Prevarisc
