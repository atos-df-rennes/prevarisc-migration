# Propositions techniques pour l'affichage des dossiers

Ce document distingue les pistes à réexaminer des mécanismes déjà présents. Il ne constitue pas une feuille de route.

## Éléments déjà présents

- Les macros de consultation `champ_date` et `champ_texte` ainsi que la macro de formulaire `champ_form` sont définies dans `templates/macros/dossier.html.twig`.
- Le template d'affichage du dossier utilise ces macros et le filtre `should_display_field`.
- Les documents manquants sont rendus dans un template partiel.

Les anciens exemples suggérant de créer ces macros ou d'extraire ces éléments ne sont donc plus des tâches à réaliser.

## Pistes non évaluées

- Réexaminer la sémantique HTML et l'accessibilité du rendu avec un audit RGAA, puis cibler les écarts observés.
- Mesurer les performances du rendu avant d'introduire une mise en cache ou de regrouper les calculs de visibilité.
- Évaluer les changements de classes CSS uniquement lorsqu'un besoin de style ou de maintenance est établi.

Ne pas reprendre les anciennes estimations ou priorités sans nouvelle analyse. Toute modification de l'affichage doit préserver le comportement existant et être couverte par les tests pertinents.
