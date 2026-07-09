# Tool Execution Plan — Rector, PHPStan, cs-fixer

**Document scope:** Define when and where incremental upgrade tools run across PHP 7.1 → 8.5 / Symfony 4.4 → 8.x journey.

---

## Current State (PHP 7.1 / Symfony 4.4)

### Tools & Versions

| Tool | Purpose | Version | Container | Config |
|------|---------|---------|-----------|--------|
| **Rector** | Auto-refactor code for PHP/SF versions | `0.18.x` (PHP 7.1 compatible) | **platau** (PHP 8.1+) | `rector.php` |
| **PHPStan** | Static analysis (level 10) | `1.10.x` (PHP 7.1 compatible) | **platau** (PHP 8.1+) | `phpstan.neon.dist` |
| **php-cs-fixer** | Code style enforcement (Symfony rules) | `3.13.x` (PHP 7.1 compatible) | **platau** (PHP 8.1+) | `.php-cs-fixer.dist.php` |
| **PHPUnit** | Testing | `9.5.x` (PHP 7.1 compatible) | main | `phpunit.xml.dist` |

### Execution Flow

```
Phase 3 (PHP 7.2–8.0): All tools in platau container (PHP 8.1+)
├─ castor symfony:analyse   → PHPStan in platau
├─ castor symfony:cs        → cs-fixer in platau
├─ castor symfony:test      → PHPUnit in main
└─ castor symfony:validate  → Symfony in main
```

### Validation Loop (Each Milestone)

After each PHP/Symfony upgrade:

```bash
castor symfony:analyse      # PHPStan level 10
castor symfony:cs           # Code style check/fix
castor symfony:test         # All tests passing
castor symfony:validate     # Symfony validation
castor symfony:migrate      # DB migrations (if needed)
```

---

## Tool Evolution Path

### Rector Configuration Strategy

Rector auto-refactors code but must be configured with **only rules applicable to the current or next target version**. Applying all rules at once introduces unsupported syntax.

**Configuration approach:**
- Each PHP/Symfony milestone gets a dedicated Rector profile (environment variable or branch)
- When upgrading to PHP 7.2, activate `php72` rule set
- When upgrading to Symfony 5.4, activate `sf54` rule set
- Rules are cumulative (e.g., `php74` includes all previous PHP rules)

**Current config (PHP 7.1):**
```php
->withPhpVersion(PhpVersion::PHP_71)
->withPhpSets(php71: true)
```

**Rector version progression:**
| PHP Target | Rector Version | Notes |
|------------|----------------|-------|
| 7.1–7.4 | 0.18.x | Stable for PHP 7.1 |
| 8.0–8.1 | 0.19.x–0.20.x | PHP 8 support |
| 8.2+ | 1.0.x | Named arguments, readonly support |

---

### PHPStan Configuration Strategy

PHPStan level 10 is already set. Version progression is mostly backwards-compatible, but config may need updates for new PHP syntax.

**Current config (PHP 7.1):**
```yaml
parameters:
    phpVersion: 70100
    level: 10
```

**PHPStan version progression:**
| PHP Target | PHPStan Version | Notes |
|------------|-----------------|-------|
| 7.1–7.4 | 1.10.x | Compatible |
| 8.0–8.2 | 1.11.x–1.12.x | PHP 8 syntax support |
| 8.3+ | 2.x | Full PHP 8.3+ support (attributes, enums) |

---

### PHP-CS-Fixer Configuration Strategy

cs-fixer rules are backwards-compatible across PHP versions. No version bump needed until deprecated rules appear.

**Current config (PHP 7.1):**
```php
'@Symfony' => true,
'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, ...],
```

**cs-fixer version progression:**
| PHP Target | cs-fixer Version | Notes |
|------------|------------------|-------|
| 7.1–7.4 | 3.13.x | Compatible |
| 8.0–8.2 | 3.14.x–3.48.x | PHP 8 syntax support |
| 8.3+ | 3.49.x+ | Full PHP 8.3+ support |

---

### PHPUnit Version Progression

PHPUnit version is tightly coupled to PHP version. Must upgrade in lockstep with PHP to avoid test syntax errors.

**Required versions by PHP milestone:**

| PHP | Symfony | PHPUnit | Timeline |
|-----|---------|---------|----------|
| 7.1–7.3 | 4.4 | 9.5.x | Phase 3 early |
| 7.4 | 4.4–5.4 | 9.5.x | Phase 3 mid |
| 8.0–8.1 | 5.4–6.4 | 10.x | Phase 3–4 mid |
| 8.2–8.3 | 6.4–7.x | 11.x | Phase 4 late |
| 8.4–8.5 | 8.x | 11.x | Phase 4 final |

---

## Container Transitions

### Phase 3 (PHP 7.2–8.0) — All in platau

**Current state:** Rector, PHPStan, cs-fixer run in `platau` container (which has PHP 8.1+)  
**Main container PHP progression:** 7.1 → 7.2 → 7.3 → 7.4 → 8.0

Tools remain in `platau` container as main container upgrades. Castor tasks automatically route to platau.

**No change needed.**

### Phase 3–4 Transition (PHP 8.1 decision point)

**When:** Main container reaches PHP 8.1 (after `php-81` task completes)

**Tool Execution Transitions from platau → main Container:**
- Main container is now PHP 8.1-capable (same as platau)
- Castor tasks for `analyse` and `cs` can now run in main (no longer need platau)
- Update Castor commands to route `symfony:analyse` and `symfony:cs` to main
- PHPUnit already runs in main (no change)

**Update Dockerfile & Castor:**
- Main container Dockerfile → PHP 8.1-apache with Rector, PHPStan, cs-fixer
- Castor tasks → remove platau routing for analyse/cs
- Keep main container versions in sync with platau versions during Phase 3

### Phase 4 (PHP 8.2–8.5) — Continued in main

**Main container PHP progression:** 8.1 → 8.2 → 8.3 → 8.4 → 8.5

Tools remain in main container. Continue version updates as needed.

---

## Upgrade Checkpoints

### After Each PHP Minor Upgrade

1. **Update tool versions** (if listed above)
2. **Update `rector.php`**:
   ```php
   ->withPhpVersion(PhpVersion::PHP_72)  // example
   ->withPhpSets(php72: true)
   ```
3. **Update `phpstan.neon.dist`**:
   ```yaml
   parameters:
       phpVersion: 70200  # example
   ```
4. **Run validation loop:**
   ```bash
   castor symfony:analyse
   castor symfony:cs
   castor symfony:test
   castor symfony:validate
   ```

### After Each Symfony LTS Upgrade

1. **Run Rector with SF rule set:**
   ```bash
   ./vendor/bin/rector process --dry-run  # Preview
   ./vendor/bin/rector process             # Apply
   ```
2. **Run full validation:**
   ```bash
   castor symfony:analyse
   castor symfony:cs
   castor symfony:test
   castor symfony:validate
   ```

---

## Risk Mitigation

### Rector Misconfiguration
- **Risk:** Applying wrong rule set causes syntax errors
- **Mitigation:** Always dry-run Rector first (`--dry-run` flag)
- **Mitigation:** Commit before running Rector; revert if broken

### PHPUnit Breakage During Upgrade
- **Risk:** Tests fail due to PHPUnit API changes
- **Mitigation:** Upgrade PHPUnit atomically with PHP
- **Mitigation:** Review test output carefully; fix assertion syntax

### Tool Version Mismatch
- **Risk:** Tool version incompatible with target PHP
- **Mitigation:** Refer to table above for correct versions
- **Mitigation:** Test `composer require` before committing

---

## Commands Reference

```bash
# View current tool versions
composer show rector/rector phpstan/phpstan friendsofphp/php-cs-fixer phpunit/phpunit

# Update Rector config for next PHP version (example: PHP 7.3 → 7.4)
# Edit rector.php:
#   ->withPhpVersion(PhpVersion::PHP_74)
#   ->withPhpSets(php74: true)

# Dry-run Rector (preview changes before applying)
cd prevarisc-migration
./vendor/bin/rector process --dry-run

# Apply Rector changes
./vendor/bin/rector process

# Run full validation suite
castor symfony:analyse
castor symfony:cs
castor symfony:test
castor symfony:validate

# Upgrade composer package
composer require --dev rector/rector:^0.19.0  # example
```

---

## Phase 2 Deliverables

- [x] Document current tool configuration (rector.php, phpstan.neon.dist, .php-cs-fixer.dist.php)
- [x] Define tool version progression across all milestones
- [x] Clarify when/where tools run (main container throughout)
- [x] Document Rector rule set strategy
- [x] Define PHPUnit version requirements per PHP milestone
- [x] Create upgrade checkpoints and validation loop
- [x] Document risk mitigation for tool failures

---

**Created:** Phase 2 (Upgrade Tooling & Tool Execution Planning)  
**Last updated:** 2024 (Phase 2)  
**Next:** Execute Phase 2 validation on PHP 7.1 baseline, then proceed to Phase 3 (PHP 7.2)
