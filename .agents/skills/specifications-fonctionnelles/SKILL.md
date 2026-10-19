---
name: specifications-fonctionnelles
description: "Maintient docs/specs/ comme référence fonctionnelle lors de toute nouvelle fonctionnalité ou correction de bug. À utiliser avant de modifier le code d'un comportement fonctionnel, pour vérifier les règles existantes, clarifier les ambiguïtés et mettre à jour les spécifications avant l'implémentation."
---

# Spécifications fonctionnelles d'abord

Utiliser ce skill avant toute tâche qui ajoute, corrige ou modifie un comportement fonctionnel de Prevarisc. Les spécifications sont la référence partagée pour l'implémentation, les tests et la validation métier, mais leur contenu n'est pas automatiquement validé par le client : certaines informations proviennent de rétro-documentation et peuvent rester à confirmer.

## Démarche obligatoire

1. **Cadrer le besoin** — Reformuler le comportement demandé et ses acteurs. Pour un bug, relever le comportement constaté, le comportement attendu et les conditions permettant de reproduire l'écart.
2. **Trouver les règles existantes** — Lire `docs/specs/INDEX.md`, puis les sections utiles des modules concernés. Suivre les dépendances fonctionnelles vers d'autres modules lorsque le changement les affecte. Ne pas charger tous les documents sans nécessité.
3. **Qualifier la source de vérité** — Distinguer explicitement :
   - le comportement attendu confirmé par la demande ou une décision métier ;
   - ce que disent les spécifications actuelles ;
   - ce que fait actuellement le code ;
   - les hypothèses et informations à confirmer.
4. **Décider avant de coder** :
   - si une règle attendue et suffisamment établie existe mais que le code s'en écarte, corriger la spécification seulement si elle est incomplète, puis corriger le code ;
   - si la règle documentée est explicitement erronée au regard du besoin confirmé, corriger d'abord la spécification, puis aligner le code et les tests ;
   - si le besoin ou la règle métier sont ambigus et que plusieurs comportements sont raisonnables, consulter d'abord l'agent `functional-specialist` pour vérifier les règles et impacts documentés et cadrer les interprétations possibles ; demander ensuite une clarification avant de décider. L'agent aide à analyser l'ambiguïté mais ne prend pas ni ne valide la décision métier. Ne pas faire passer une hypothèse pour une décision métier.
5. **Mettre à jour les spécifications en premier** — Modifier le ou les documents sous `docs/specs/` avant l'implémentation et les tests. Décrire les acteurs, préconditions, scénario nominal, règles de gestion, permissions et cas limites qui sont pertinents pour le changement. Garder `INDEX.md` cohérent si le périmètre ou le résumé d'un module change.
6. **Implémenter et vérifier** — Adapter ensuite le code et les tests unitaires, d'intégration ou fonctionnels pertinents. Les tests doivent vérifier les règles et scénarios documentés, y compris les cas limites affectés.
7. **Vérifier la cohérence** — Relire le diff des spécifications, du code et des tests ensemble. Signaler toute question métier encore ouverte et ne pas prétendre qu'elle est résolue.

## Rédaction des spécifications

- Respecter le module et la structure déjà en place : objet et périmètre, acteurs, fonctionnalités, règles de gestion (`RG-XXX-NNN` lorsqu'une règle numérotée s'intègre à la liste existante), cas particuliers et limites.
- Écrire des règles observables et vérifiables, avec les conditions et résultats attendus. Éviter les détails d'implémentation qui n'ont pas d'effet fonctionnel.
- Ne pas supprimer ou réécrire une règle existante sans vérifier son impact et expliquer le changement par le besoin confirmé.
- Signaler clairement les points à confirmer dans le texte concerné ; ne pas inventer une validation client ni généraliser une observation du code à l'ensemble du comportement métier.
- Si le changement touche plusieurs modules, mettre à jour chaque module dont les règles ou scénarios changent, sans dupliquer inutilement la même règle.

## Exceptions

Une modification purement technique qui ne change ni le comportement observable, ni les règles, ni les permissions, ni les données fonctionnelles ne requiert pas de changement des spécifications. Expliquer brièvement cette conclusion si le caractère non fonctionnel pourrait prêter à confusion.
