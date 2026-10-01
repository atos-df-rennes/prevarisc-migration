# Modules JavaScript du formulaire dossier

Le formulaire d'édition et de création charge `public/js/dossier/form.js` comme module ES depuis `templates/dossier/edit.html.twig` et `templates/dossier/new.html.twig`. L'orchestrateur initialise Tom Select et les modules de formulaire.

## Modules

| Fichier | Responsabilité |
|---|---|
| `form.js` | Orchestration, lecture de `data-fields-config` et initialisation des contrôles Tom Select |
| `modules/type-nature.js` | Changements de type/nature et visibilité des champs configurés |
| `modules/avis.js` | Visibilité et libellés des champs d'avis et de dérogation |
| `modules/documents-urbanisme.js` | Préfixage des documents d'urbanisme lors de l'ajout d'un élément de collection |
| `modules/today-buttons.js` | Boutons de remplissage de date, réinitialisés après mise à jour des champs |
| `gestion-incomplet.js` | État incomplet et visibilité des champs d'avis |
| `commission.js` | Interactions liées à la commission et au calendrier |
| `calendar-init.js` | Initialisation du calendrier FullCalendar |
| `platau.js` | Interactions Plat'AU |

## Événements inter-modules

| Événement | Émetteur | Données |
|---|---|---|
| `dossier:typeChanged` | `modules/type-nature.js` | `{ typeId }` |
| `dossier:natureChanged` | `modules/type-nature.js` | `{ natureId, typeName }` |
| `dossier:fieldsUpdated` | `modules/type-nature.js` | `{ visibleFields }` |
| `dossier:updateAvisVisibility` | `gestion-incomplet.js` | Aucune |

Les événements utilisent le `document` comme bus (`CustomEvent`). Les données et les usages peuvent évoluer avec les modules ; vérifier leur implémentation avant d'ajouter un consommateur.

## Débogage

Les modules n'exposent pas d'API publique globale pour la console. Examiner les événements et l'état du DOM dans les outils développeur du navigateur ; les données de configuration des champs sont fournies par l'attribut `data-fields-config`.
