# Consignes fonctionnelles

Pour toute évolution qui modifie ou ajoute un comportement fonctionnel — fonctionnalité, correction de bug ou changement de règle — les spécifications de `docs/specs/` doivent guider l'implémentation et rester cohérentes avec elle.

Avant de modifier le code :

1. Lire `docs/specs/INDEX.md`, puis les sections pertinentes des modules concernés et, si nécessaire, des modules liés.
2. Pour un bug, comparer le comportement signalé avec la règle documentée et établir si le code s'écarte d'une règle validée, si la spécification est erronée, ou si le comportement attendu reste ambigu.
3. Mettre à jour ou rédiger les spécifications concernées avant le code et les tests. Ne pas présenter une règle incertaine ou issue de rétro-documentation comme validée par le métier.
4. Si le besoin métier ne peut pas être déduit sans risque, demander une clarification avant de choisir ou modifier le comportement attendu. Enregistrer explicitement les hypothèses à confirmer plutôt que de les transformer en règles certaines.
5. Implémenter ensuite le changement et adapter les tests pour vérifier les scénarios et règles décrits.

Pour appliquer la démarche détaillée, lire le skill `.agents/skills/specifications-fonctionnelles/SKILL.md`. Pour répondre aux questions métier ou analyser les impacts fonctionnels, utiliser l'agent `functional-specialist` défini dans `.github/agents/functional-specialist.agent.md`.

Les changements purement techniques, sans effet fonctionnel observable, n'imposent pas de modifier les spécifications.
