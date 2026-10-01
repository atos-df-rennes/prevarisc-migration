# Édition des dossiers

Ce document donne les principaux points d'entrée techniques du formulaire dossier. Les comportements métier attendus relèvent de `docs/specs/`.

## Composants principaux

| Rôle | Emplacement |
|---|---|
| Contrôleur dossier | `src/Controller/Dossier/DossierController.php` |
| Services spécialisés dossier | `src/Service/Dossier/` |
| Formulaire Symfony | `src/Form/` |
| Templates de création et d'édition | `templates/dossier/new.html.twig`, `templates/dossier/edit.html.twig` |
| Orchestrateur JavaScript | `public/js/dossier/form.js` |
| Modules JavaScript | `public/js/dossier/modules/` |

Le formulaire personnalisé (rubriques et champs configurés en base) suit un pipeline distinct de chargement, hydratation, construction du formulaire et persistance. Voir [Formulaires personnalisés](formulaires-personnalises.md).

## Modules JavaScript

Le formulaire charge `form.js` comme module ES. Celui-ci initialise Tom Select puis les modules Type/Nature, avis, documents d'urbanisme et boutons de date. Voir [Modules JavaScript du formulaire dossier](architecture-js-modulaire.md) pour les responsabilités et événements.

Événements personnalisés documentés dans le code :

| Événement | Émis par | Détail |
|---|---|---|
| `dossier:typeChanged` | `modules/type-nature.js` | `typeId` |
| `dossier:natureChanged` | `modules/type-nature.js` | `natureId`, `typeName` |
| `dossier:fieldsUpdated` | `modules/type-nature.js` | `visibleFields` |
| `dossier:updateAvisVisibility` | `gestion-incomplet.js` | Aucun |

Ces événements sont des détails d'implémentation front-end : vérifier les modules avant de s'y coupler.

## Bibliothèques front-end

Les bibliothèques principales sont livrées en fichiers statiques sous `public/`, et chargées par les templates. Leurs versions de référence et le mode de gestion des dépendances sont recensés dans [l'index technique](../README.md).
