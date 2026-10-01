# Copilot instructions — Prevarisc application

This is the independent Git repository for the current production application. Bug fixes and new features belong here. The Zend application in the sibling `prevarisc/` repository is a frozen, read-only historical reference; it is not delivered to clients.

## Stack and conventions

- PHP 8.5, Symfony 7.4, Doctrine ORM 2.20, Twig 3, PHPUnit 13.
- Prefer typed and `readonly` properties, promoted constructor properties, enums, `match`, nullsafe `?->`, and PHP attributes instead of annotations.
- Do not use Doctrine `findAll()` without pagination.
- Do not add Symfony Messenger, a scheduler, or another feature requiring a long-running worker process; client infrastructure changes are too costly to roll out.
- Preserve existing behavior unless the user approves a behavior change.
- Never modify the frozen Zend reference. For historical comparisons, use the `analyse-legacy` skill when the sibling checkout or GitHub repository is accessible.

## Git and delivery

- Never switch or check out another branch without explicit user permission.
- Do not commit unless requested. When committing is requested, use Conventional Commits (`type(scope): description`) and include `Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>`.
- See `.github/skills-config.yml` for skill run-context policy.

## Validation

- Prefer `castor symfony:validate` from the sibling `prevarisc-infra` repository when it and its Docker environment are available.
- The `validate-application` skill documents the exact checks Castor runs and how to execute the underlying tools from this repository.
- In a local CLI session started here, `/add-dir /path/to/prevarisc-infra` loads the sibling repository's skills and agents, including `run-linters`. This local configuration does not make those files available to a remote delegated task.
- See the `testing.instructions.md` path-specific instruction for test conventions.

## Functional specifications

Pour toute évolution qui modifie ou ajoute un comportement fonctionnel — fonctionnalité, correction de bug ou changement de règle — les spécifications de `docs/specs/` doivent guider l'implémentation et rester cohérentes avec elle.

Avant de modifier le code :

1. Lire `docs/specs/INDEX.md`, puis les sections pertinentes des modules concernés et, si nécessaire, des modules liés.
2. Pour un bug, comparer le comportement signalé avec la règle documentée et établir si le code s'écarte d'une règle validée, si la spécification est erronée, ou si le comportement attendu reste ambigu.
3. Mettre à jour ou rédiger les spécifications concernées avant le code et les tests. Ne pas présenter une règle incertaine ou issue de rétro-documentation comme validée par le métier.
4. Si le besoin métier ne peut pas être déduit sans risque, demander une clarification avant de choisir ou modifier le comportement attendu. Enregistrer explicitement les hypothèses à confirmer plutôt que de les transformer en règles certaines.
5. Implémenter ensuite le changement et adapter les tests pour vérifier les scénarios et règles décrits.

Pour appliquer la démarche détaillée, lire le skill `.agents/skills/specifications-fonctionnelles/SKILL.md`. Pour répondre aux questions métier ou analyser les impacts fonctionnels, utiliser l'agent `functional-specialist` défini dans `.github/agents/functional-specialist.agent.md`.

Les changements purement techniques, sans effet fonctionnel observable, n'imposent pas de modifier les spécifications.
