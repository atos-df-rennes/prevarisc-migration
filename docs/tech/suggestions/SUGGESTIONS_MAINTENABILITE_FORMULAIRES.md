# Suggestions de maintenabilité — Formulaires personnalisés (Phase 2)

**Date :** Janvier 2026  
**Contexte :** Migration Phase 2 — édition des formulaires personnalisés (4 onglets)  
**Statut :** À valider post-merge avant implémentation

Ces suggestions n'ont **pas été implémentées** lors de la migration afin de prioriser l'iso-fonctionnalité et de respecter le périmètre de la Phase 2. Elles sont listées ici pour décision du relecteur.

---

## 1. Remplacer le parsing manuel des noms d'inputs par des Symfony Forms typés

**Problème actuel :**  
`DescriptifSaver` parse manuellement les clés POST (`champ-{id}`, `valeur-{ts}-{parentId}-{champId}-NULL`). Cette convention vient du legacy Zend et est fragile à maintenir.

**Solution proposée :**  
Utiliser des `AbstractType` Symfony avec des noms d'inputs normalisés. Le service `DescriptifSaver` deviendrait un simple orchestrateur de formulaires Symfony.

**Impact :**  
- Suppression de `DescriptifSaver::doSave()` et de toute la logique de parsing de clés  
- Mise à jour de `gestionTableau.js` pour utiliser les nouveaux noms d'inputs  
- Nécessite une refonte du JavaScript existant

---

## 2. Factoriser les 4 actions d'édition en un contrôleur générique

**Problème actuel :**  
Les 4 actions (`editDescriptifPersonnalise`, `editEffectifsDegagements` dans EtablissementController et DossierEffectifsController/DossierController) partagent ~80% de code identique (pattern GET/POST, CSRF, flash, redirect).

**Solution proposée :**  
Créer un `DescriptifEditController` générique paramétré par la capsule et le type d'entité :

```php
private function handleDescriptifEdit(
    object $entity,
    string $capsuleNom,
    string $csrfName,
    string $redirectRoute,
    string $template,
    Request $request
): Response { ... }
```

**Impact :**  
- Réduction de ~120 lignes de code dupliqué  
- Les 4 actions deviennent des délégations d'une seule méthode

---

## 3. Introduire des DTOs typés pour les valeurs de formulaire

**Problème actuel :**  
`DescriptifSaver` manipule des `mixed` issus du POST, avec des casts manuels dans `applyValueToValeur()`. Aucune validation de type avant la persistance.

**Solution proposée :**  
Créer des DTOs par type de champ :
```php
class ChampTextValue { public string $value; }
class ChampDateValue { public \DateTime $date; }
class ChampTableauRowValue { public int $parentId; public array $children; }
```

**Impact :**  
- Meilleure détection d'erreurs en amont  
- PHPStan peut vérifier les types sans `@var` assertions  
- Complexité accrue à court terme

---

## 4. Ajouter des validateurs Symfony pour les contraintes métier

**Problème actuel :**  
Aucune validation des valeurs soumises (format de date, plage de nombres, longueur de texte). Les erreurs de format résultent en valeurs `null` silencieuses en base.

**Solution proposée :**  
Ajouter des contraintes Symfony (`@Assert\Date`, `@Assert\Length`, `@Assert\Range`) au niveau de l'entité `Valeur`, ou créer un `DescriptifValidator` service.

**Impact :**  
- Feedback utilisateur en cas de valeur invalide  
- Nécessite une refonte de la gestion d'erreurs dans les templates d'édition

---

## 5. Lazy loading des valeurs de tableau en cas de dégradation de performance

**Problème actuel :**  
`ValeurRepository::findByDossier()` charge toutes les valeurs en une seule requête JOIN. Pour un formulaire très dense (100+ valeurs), cela peut être lourd.

**Solution proposée :**  
Mesurer les performances en production. Si dégradation constatée, charger les valeurs de tableau séparément avec une requête paginée par rubrique.

**Impact :**  
- Optimisation à faire uniquement si problème mesuré  
- Ne pas optimiser prématurément

---

## 6. Unifier `DossierDisplayRubrique` et `EtablissementDisplayRubrique` en entité polymorphe

**Problème actuel :**  
Deux entités quasi-identiques (`DossierDisplayRubrique` et `EtablissementDisplayRubrique`) avec la même structure. `DescriptifSaver` contient deux blocs de code dupliqués pour gérer les deux.

**Solution proposée :**  
Une entité unique `DisplayRubriquePreference` avec un champ polymorphe (ManyToOne nullable vers `Dossier` + nullable vers `Etablissement`, avec contrainte CHECK), ou via héritage Doctrine (`SINGLE_TABLE`).

**Impact :**  
- Migration de base de données requise  
- Suppression de `DossierDisplayRubriqueRepository` et `EtablissementDisplayRubriqueRepository`  
- À faire uniquement si d'autres entités similaires apparaissent (risque de sur-ingénierie)

---

## Priorité suggérée

| Suggestion | Valeur | Complexité | Priorité |
|------------|--------|------------|----------|
| 2. Contrôleur générique | Haute | Faible | ⭐ À faire en premier |
| 4. Validateurs | Haute | Moyenne | ⭐ Recommandé |
| 1. Symfony Forms | Haute | Haute | À planifier |
| 3. DTOs typés | Moyenne | Moyenne | Optionnel |
| 5. Lazy loading | Faible | Faible | Si problème mesuré |
| 6. Entité polymorphe | Faible | Haute | Non recommandé |
