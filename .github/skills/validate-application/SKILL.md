---
name: validate-application
description: Run and understand the PHPStan, code-style, and PHPUnit checks used to validate the Prevarisc application
---

# Validate the application

## Preferred host workflow: Castor

When the sibling `prevarisc-infra` checkout and its Docker environment are available, run this from the infrastructure repository root:

```bash
castor symfony:validate
```

The task executes these checks sequentially and stops at the first failure:

1. PHPStan: `tools/vendor/bin/phpstan --memory-limit=-1`
2. Rector check: `tools/vendor/bin/rector --dry-run`
3. PHP-CS-Fixer check: `tools/vendor/bin/php-cs-fixer fix --dry-run`
4. Twig-CS-Fixer check: `tools/vendor/bin/twig-cs-fixer check`
5. PHPUnit: `php vendor/bin/phpunit --testdox --exclude-group functional`

Castor runs them inside the `app` container with `/var/www/html/prevarisc-migration` as the working directory. The relevant task definitions are in `prevarisc-infra/.castor/symfony.php`.

For individual checks, Castor also provides:

```bash
castor symfony:analyse
castor symfony:test
castor symfony:cs
```

`castor symfony:cs` applies PHP-CS-Fixer and Twig-CS-Fixer changes; it is not a check-only command. Use `castor symfony:validate` for the non-mutating style checks.

## Project-local workflow

If working in a context without the sibling infra checkout or Docker, run the relevant installed tools from this repository root:

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
