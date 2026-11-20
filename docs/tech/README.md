# Documentation technique

Cette documentation décrit des mécanismes techniques vérifiés dans le code actuel. Elle ne remplace ni les spécifications fonctionnelles de `docs/specs/`, ni les fichiers de configuration et le code source.

## Stack de référence

| Composant | Version ou source |
|---|---|
| PHP | `^8.5.0` (`composer.json`) |
| Symfony | `7.4.*` (`composer.json`) |
| Doctrine ORM | `^2.20` (`composer.json`) |
| Twig | `^3.0` (`composer.json`) |
| PHPUnit | `^13.0` (`composer.json`, dépendance de développement) |
| Bootstrap | 3.4.1, fichiers statiques sous `public/` |
| jQuery | 1.10.2, fichier statique sous `public/`, avec jQuery Migrate 1.2.1 |
| FullCalendar | 1.6.4, fichier statique sous `public/` |
| Tom Select | 2.5.2, fichier statique sous `public/` |
| Cypress | `^15.21.1` (`package.json`, tests navigateur) |

Les versions des dépendances PHP sont des contraintes Composer, pas une affirmation sur le dernier patch installé. Pour les bibliothèques front-end livrées comme fichiers statiques, vérifier leur en-tête et leur inclusion dans `templates/base.html.twig`.

## Documents

### Architecture et configuration

- [Édition des dossiers](dossier/edition-dossiers.md) — principaux composants du formulaire et événements JavaScript.
- [Modules JavaScript du formulaire dossier](dossier/architecture-js-modulaire.md) — responsabilités des modules et échanges d'événements.
- [Configuration du stockage des fichiers](configuration-datastore.md) — sélection du DataStore et organisation des fichiers.
- [Constantes métier](constantes-metier.md) — classes de constantes sous `src/Constant/`.
- [Notifications toast](toast-notifications.md) — API JavaScript et limites actuelles.
- [Démo des notifications toast](toast-demo.html) — page de démonstration autonome.

### Exploitation

- [Suppression définitive des utilisateurs d'un groupe](suppression-utilisateurs.md) — commande console, garde-fous et journal d'audit.

### Intégration continue

- [Intégration continue](integration-continue.md) — contrôles GitHub Actions, environnement et caches.
- [Tests PHP](tests-php.md) — base dédiée, Foundry, isolation transactionnelle et feuille de route.
- [Recette de la passerelle Plat'AU](recette-passerelle-platau.md) — première commande de lecture et critères proposés pour les tranches suivantes.

### Formulaires personnalisés et documents

- [Formulaires personnalisés](dossier/formulaires-personnalises.md) — lecture, construction et persistance des formulaires configurables.
- [Nommage des variables de fusion ODT](generation-documents/caracteres-speciaux-odt.md) — formatage actuellement appliqué aux noms de champs.

### Propositions

- [Propositions pour l'affichage des dossiers](suggestions/ameliorations-affichage-dossiers.md)
- [Propositions pour l'édition des dossiers](suggestions/ameliorations-edition-dossiers.md)

Les propositions ne décrivent pas des fonctionnalités disponibles et ne constituent pas un engagement de réalisation. Vérifier leur pertinence dans le code et avec les contraintes actuelles avant de les reprendre.

## Conventions de maintenance

- Les noms des documents (hors `README.md`) sont en minuscules, avec des mots séparés par des tirets.
- Le code et la configuration actuels font foi pour décrire le comportement implémenté. Corriger la documentation si elle diverge.
- Distinguer les faits vérifiés des limites connues, hypothèses et propositions.
- Éviter les numéros de lignes, les comptages de lignes et les dates de péremption dans la documentation de référence.
- Toute mise à jour de stack doit être recoupée avec les manifests et les en-têtes des bibliothèques statiques.
