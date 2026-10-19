# Copilot instructions — Prevarisc application

This is the independent Git repository for the current production application. Bug fixes and new features belong here. The Zend application in the sibling `prevarisc/` repository is a frozen, read-only historical reference; it is not delivered to clients.

## Stack and conventions

- PHP 8.5, Symfony 7.4, Doctrine ORM 2.20, Twig 3, PHPUnit 13.
- Prefer typed and `readonly` properties, promoted constructor properties, enums, `match`, nullsafe `?->`, and PHP attributes instead of annotations.
- Do not use Doctrine `findAll()` without pagination.
- Do not add Symfony Messenger, a scheduler, or another feature requiring a long-running worker process; client infrastructure changes are too costly to roll out.
- Preserve existing behavior unless the user approves a behavior change.
- For bug fixes, keep the implementation limited to the identified root cause and agreed scope. Present optional validation, UX, persistence, or business-rule changes separately and do not include them without approval.
- Prefer existing Symfony, PHP, and browser capabilities over custom helpers or substantial JavaScript/CSS. Avoid mixing custom behavior with a library's behavior unless the requirement calls for it; choose the simplest solution that meets the request.
- Never modify the frozen Zend reference. For historical comparisons, use the `analyse-legacy` skill when the sibling checkout or GitHub repository is accessible.

## Scope and refactoring

- Critically assess the scope of any request before implementation, especially for refactorings or wide-reaching tasks.
- If a requested change or refactoring touches too many files, responsibilities, or behaviors at once, warn the user upfront that the scope is overly broad and that review, testing, and validation will be difficult to conduct.
- Proactively propose a reduced, incremental scope focused on a single cohesive part (e.g., an isolated class, method, or single responsibility) before executing, rather than attempting a large-scale change in a single session.
- Before implementing a change with multiple materially different viable designs, present the options, their tradeoffs, and the behavior each preserves. Ask the user to decide any unresolved business-rule or API choice, and wait for approval before implementing that choice. Do not add this step for straightforward changes where the requirements determine the design.

## Git and delivery

- Never switch or check out another branch without explicit user permission.
- Do not commit unless requested. When committing is requested, use Conventional Commits (`type(scope): description`) and include `Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>`.
- See `.github/skills-config.yml` for skill run-context policy.

## Validation

- In local CLI sessions, the `prevarisc-infra` checkout is available: always run validation through its Castor tasks from the infrastructure repository root. Use `castor symfony:validate` for full validation or the appropriate targeted Castor task. Do not invoke the underlying validation tools directly.
- If local Castor validation is blocked by access permissions, Docker, or another environment issue, report the blocker; do not substitute direct tool commands unless the user explicitly authorizes it.
- Direct validation commands are a fallback only for remote/cloud delegated sessions without the infrastructure checkout or Docker environment. The `validate-application` skill documents this fallback and the checks Castor runs. Local subagents follow the same Castor requirement as the main local session.
- In a local CLI session started here, `/add-dir /path/to/prevarisc-infra` loads the sibling repository's skills and agents, including `run-linters`. This local configuration does not make those files available to a remote delegated task.
- Test application-owned behavior at the lowest appropriate layer. Avoid heavyweight test-only database/query instrumentation or tests of dependency-owned document rendering unless the application behavior specifically requires them; use Cypress for browser UI behavior.
- See the `testing.instructions.md` path-specific instruction for test conventions.

## Functional specifications

For any change that modifies or introduces functional behavior — feature, bug fix, or business rule update — the specifications in `docs/specs/` must guide the implementation and remain consistent with it.

Before modifying code:

1. Read `docs/specs/INDEX.md`, then the relevant sections of the affected modules and, if necessary, related modules.
2. For a bug, compare the reported behavior with the documented rule and determine whether the code deviates from an approved rule, the specification is incorrect, or the expected behavior remains ambiguous.
3. Update or draft the affected specifications before writing code and tests. Do not present an uncertain rule or one inferred from reverse-engineering as validated by business domain experts.
4. If business requirements cannot be determined safely, request clarification before choosing or altering the expected behavior. Explicitly record assumptions to confirm rather than treating them as established rules.
5. Then implement the change and update tests to verify the described scenarios and rules.

To follow the detailed workflow, refer to the skill `.agents/skills/specifications-fonctionnelles/SKILL.md`. To answer domain questions or assess functional impact, use the `functional-specialist` agent defined in `.github/agents/functional-specialist.agent.md`.

Purely technical changes with no observable functional effect do not require updating the specifications.
