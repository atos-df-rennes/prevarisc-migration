---
name: validate-application
description: Run and understand the PHPStan, code-style, and PHPUnit checks used to validate the Prevarisc application
---

# Validate the application

## Required local CLI workflow: Castor

Local CLI sessions have the `prevarisc-infra` checkout available. Always use its Castor tasks for validation, including from local subagents. Run full validation from the infrastructure repository root:

```bash
castor symfony:validate
```

The task executes these checks sequentially and stops at the first failure:

1. Git conflict markers: `symfony:conflicts` (Swiss Knife, excluding dependencies and generated files)
2. PHPStan: `tools/vendor/bin/phpstan --memory-limit=-1`
3. Rector check: `tools/vendor/bin/rector --dry-run`
4. PHP-CS-Fixer check: `tools/vendor/bin/php-cs-fixer fix --dry-run`
5. Twig-CS-Fixer check: `tools/vendor/bin/twig-cs-fixer check`
6. PHPUnit: `php vendor/bin/phpunit --testdox --exclude-group functional`

Castor runs them inside the `app` container with `/var/www/html/prevarisc-migration` as the working directory. The relevant task definitions are in `prevarisc-infra/.castor/symfony.php`.

For individual checks, Castor also provides:

```bash
castor symfony:analyse
castor symfony:test
castor symfony:cs
castor symfony:conflicts
```

`castor symfony:cs` applies PHP-CS-Fixer and Twig-CS-Fixer changes; it is not a check-only command. Use `castor symfony:validate` for the non-mutating style checks.

`castor symfony:dependencies` checks both Composer projects using Swiss Knife installed in `tools/`; `--target=app` or `--target=tools` restricts the scope. It remains outside validation because it depends on network/cache state. `castor symfony:duplicates` diagnoses PHP clones in `src/` with a default threshold of 150 tokens; it also remains outside validation and returns a nonzero exit code for detected clones or execution errors.

Do not invoke the underlying validation tools directly in local sessions. If access permissions, Docker, or another environment issue prevents Castor validation, report the blocker rather than switching to direct commands. A local fallback requires explicit user authorization.

## Remote/cloud delegated fallback

In remote/cloud delegated sessions, use Castor when the infrastructure checkout and Docker environment are available. Only when that environment is unavailable, run the relevant installed tools from this repository root:

```bash
tools/vendor/bin/phpstan --memory-limit=-1
tools/vendor/bin/rector --dry-run
tools/vendor/bin/php-cs-fixer fix --dry-run
tools/vendor/bin/twig-cs-fixer check
php vendor/bin/phpunit --testdox --exclude-group functional
```

Run only checks whose dependencies are installed and report clearly when a check cannot run. Do not substitute the style-fixing command for the check-only workflow.

## Tests

The default PHPUnit command excludes the `functional` group. Browser-driven end-to-end tests live under `cypress/`; `tests/Functional/` contains PHPUnit HTTP-level functional tests.
