# Constantes métier

Les constantes centralisées du projet se trouvent dans `src/Constant/`. Elles nomment notamment des identifiants de référence stockés en base, des catégories partagées entre services et des types internes à l'application.

## Classes présentes

- `AboutDocument`
- `Avis` et `AvisCommission`
- `CategorieEtablissement`
- `CommissionTypeEvenement`
- `DataStoreObjectTypes`
- `DossierNature` et `DossierType`
- `Fonction`
- `GenreEtablissement`
- `PlacementEtablissementPj`
- `Platau/HealthcheckErrorSource`
- `StatutEtablissement`
- `TypeActivite`
- `TypeChamp`
- `TypeChangement`
- `TypePrescription`

Cette liste reflète les classes présentes dans `src/Constant/`; elle n'indique pas que toutes les valeurs possibles des référentiels sont déjà centralisées.

## Exemples

```php
use App\Constant\DossierNature;
use App\Constant\GenreEtablissement;

if ($genreId === GenreEtablissement::ERP) {
    // Traitement réservé aux ERP.
}

if (in_array($natureId, DossierNature::NATURES_DONNANT_AVIS, true)) {
    // Traitement des natures concernées.
}
```

`GenreEtablissement` regroupe les identifiants des genres et plusieurs ensembles utilisés par l'application. `DossierNature` regroupe des identifiants et des listes de natures réutilisés dans plusieurs services. Consulter les classes pour leur définition et leurs commentaires à jour.

## Ajouter une constante

- Réutiliser une constante existante plutôt que dupliquer une valeur.
- Garder un nom explicite en `UPPER_SNAKE_CASE`.
- Documenter le référentiel ou le concept concerné lorsque cela clarifie la valeur.
- Ne centraliser que les valeurs stables et réellement réutilisées ; les données configurables restent gérées par leurs mécanismes dédiés.
