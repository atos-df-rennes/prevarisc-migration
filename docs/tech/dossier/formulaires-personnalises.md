# Formulaires personnalisés

Les formulaires personnalisés chargent des rubriques et champs configurés en base de données, puis enregistrent les valeurs pour un dossier ou un établissement. Cette page décrit les points d'entrée techniques actuels ; les règles métier attendues relèvent de `docs/specs/`.

## Capsules

Les identifiants internes sont définis dans `App\Constant\NomInterneCapsule` :

- `DESCRIPTIF_ETABLISSEMENT`
- `DESCRIPTIF_VERIFICATIONS_TECHNIQUES`
- `EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT`
- `EFFECTIFS_DEGAGEMENTS_DOSSIER`

## Lecture et modèle de données

`App\Service\Descriptif` charge la capsule et ses rubriques/champs, les valeurs de l'entité cible et les préférences de rubrique. Il assemble une structure contenant `capsule` et une liste de rubriques. Chaque rubrique comprend son entité, l'état résolu `display`, l'état `opened` et ses champs racines avec les valeurs associées.

Les préférences d'affichage et d'ouverture de l'utilisateur sont résolues avec les valeurs par défaut de la rubrique. La configuration des champs et les valeurs sont préchargées par leurs repositories ; ne pas supposer un nombre fixe de requêtes sans les vérifier dans le code.

Une rubrique peut contenir :

- des champs simples, associés à une valeur ;
- des champs `PARENT` de type groupe, qui contiennent des sous-champs ;
- des champs `PARENT` configurés comme tableau, dont les valeurs sont regroupées par ligne (`idx`).

Les types de champs actuellement définis dans `App\Constant\TypeChamp` sont `TEXT`, `TEXTAREA`, `SELECT`, `NUMBER`, `CHECKBOX`, `PARENT` et `DATE`.

## Construction et soumission du formulaire

Le traitement d'édition suit ce pipeline :

1. `Descriptif` charge les données pour un `Dossier` ou un `Etablissement`.
2. `DescriptifFormHydrator` les transforme en DTOs (`DescriptifFormData`, `RubriqueFormData`, `ChampValueData`, `TableauRowData`).
3. `DescriptifFormBuilder` construit un formulaire Symfony dynamique nommé `descriptif`, avec CSRF activé.
4. `DescriptifFormPersister` met à jour les valeurs et les préférences après soumission valide.

Les DTOs `DescriptifFormData` et `RubriqueFormData` implémentent `ArrayAccess` pour les chemins de propriétés dynamiques indexés par identifiant. `RubriqueFormData` stocke les états d'affichage et d'ouverture dans `RubriqueEtat`.

Le persister prend en charge les valeurs des dossiers et des établissements. Pour les tableaux, il recalcule les `idx` en fonction de l'ordre soumis, et retire les valeurs des lignes supprimées dans le périmètre des champs traités.

## Rendu et JavaScript

| Rôle | Fichier |
|---|---|
| Partiel de rendu partagé | `templates/components/_formulaire_personnalise_edit.html.twig` |
| Thème de formulaire | `templates/form/descriptif_theme.html.twig` |
| Ajout, suppression et réordonnancement des lignes de tableau | `public/js/formulaire/descriptif/edit.js` |
| Synchronisation des préférences de rubrique, scroll et protection de navigation | `public/js/formulaire/descriptif/edit.js` |
| Bouton « Aujourd'hui » et état de chargement de l'enregistrement | `public/js/formulaire-personnalise-edit.js` |

Le réordonnancement des lignes s'appuie sur le fichier statique SortableJS chargé par les templates concernés. Après un déplacement, le JavaScript renumérote les indices de collection soumis ; le persister recalcule ensuite les `idx` enregistrés.

## Tests

Les tests de service couvrent notamment le chargement/hydratation et la persistance :

- `tests/Service/DescriptifTest.php`
- `tests/Service/Descriptif/DescriptifFormHydratorTest.php`
- `tests/Service/Descriptif/DescriptifFormPersisterTest.php`

Lors d'une évolution, vérifier aussi le rendu des templates et les flux de dossier et d'établissement concernés.
