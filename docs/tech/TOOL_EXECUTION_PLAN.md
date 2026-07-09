# Tool Execution Plan — Rector, PHPStan, cs-fixer, PHPUnit

**Document scope:** Define where and how incremental upgrade tools run across PHP 7.1 → 8.5 / Symfony 4.4 → 8.x journey.

---

## Current State (PHP 7.1 Symfony 4.4)

### Tool Split: Main vs Platau Container

Upgrade tools require **PHP 8.1+** and cannot run in the main container (PHP 7.1). They're isolated in the platau container:

| Tool | Purpose | Version | Location | Manifest |
|------|---------|---------|----------|----------|
| **PHPUnit** | Testing | 7.5.x | **main** (PHP 7.1) | `composer.json` (require-dev) |
| **Rector** | Auto-refactor | 2.0+ | **platau** (PHP 8.1+) | `tools/composer.json` |
| **PHPStan** | Static analysis | 2.1+ | **platau** (PHP 8.1+) | `tools/composer.json` |
| **php-cs-fixer** | Code style | 3.68+ | **platau** (PHP 8.1+) | `tools/composer.json` |

### Why the Split?

- **PHPUnit 7.5.x** is compatible with PHP 7.1 and must run in main to test PHP 7.1 code
- **Rector 2.0+**, **PHPStan 2.1+**, **cs-fixer 3.68+** require PHP 8.1+ and run in platau container
- Castor tasks route `symfony:analyse` and `symfony:cs` to platau container via Docker compose

### Execution Flow

```
castor symfony:test       → PHPUnit 7.5.x in main container (PHP 7.1)
castor symfony:analyse    → PHPStan in platau container (PHP 8.1+)
castor symfony:cs         → cs-fixer in platau container (PHP 8.1+)
castor symfony:validate   → Symfony validation in main container
```

### Validation Loop (Each Milestone)

After each PHP/Symfony upgrade in main container:

```bash
castor symfony:analyse      # PHPStan level 10 (platau: PHP 8.1+)
castor symfony:cs           # Code style check/fix (platau: PHP 8.1+)
castor symfony:test         # All tests passing (main: PHP 7.1→8.5)
castor symfony:validate     # Symfony validation (main)
castor symfony:migrate      # DB migrations if needed (main)
```

---

## Tool Evolution Path

### Rector Configuration

Rector 2.0+ is production-ready for modern PHP and all Symfony versions in the roadmap. Current config in `rector.php` uses prepared sets.

**Current versions:**
```
tools/composer.json: "rector/rector": "^2.0"
```

**Rector version stability:**
- **2.0+** is the target version and stable for all Phases 3–6
- No additional Rector version bumps needed during upgrade phases
- Rule sets evolve with each Rector minor update, but core API remains stable

**Configuration strategy:**
Each PHP/Symfony milestone may activate new rule profiles in `rector.php`:
- `php72`, `php73`, `php74`, `php80`, `php81`, etc.
- `symfony54`, `symfony64`, `symfony7x`, `symfony8x`
- Rules are cumulative (e.g., `php74` includes all previous PHP rules)

---

### PHPStan Configuration

PHPStan 2.1+ provides excellent PHP 8.1+ support and modern Symfony integration. Runs in platau container.

**Current versions:**
```
tools/composer.json: "phpstan/phpstan": "^2.1"
tools/composer.json: "phpstan/phpstan-symfony": "^2.0"
```

**PHPStan version stability:**
- **2.1+** is target and stable for all Phases 3–6
- No PHPStan version bumps needed (2.1→2.2→2.3 are backwards-compatible)
- Symfony extension is already at ^2.0

**Configuration in main container:**
When main container upgrades PHP, update `phpstan.neon.dist`:
```yaml
parameters:
    phpVersion: 70100  # Main container PHP version
    level: 10
```

**Important:** PHPStan analysis runs in platau container (2.1+), but it analyzes code written for main container's PHP version. The `phpVersion` parameter tells PHPStan which PHP version the main container targets.

---

### PHP-CS-Fixer Configuration

cs-fixer 3.68+ is modern and stable. Runs in platau container.

**Current versions:**
```
tools/composer.json: "friendsofphp/php-cs-fixer": "^3.68"
```

**cs-fixer version stability:**
- **3.68+** is target and stable for all Phases 3–6
- No cs-fixer version bumps needed (3.68→3.69→3.70 are backwards-compatible)

**Configuration in .php-cs-fixer.dist.php:**
- Current: Symfony rules with `allow_mixed: true` for PHP 7.1 type hints
- When main container upgrades to PHP 8+, rule set evolves (typed properties, attributes, etc.)
- cs-fixer applies rules for the main container's target PHP version

---

### PHPUnit Version Progression

PHPUnit runs **in main container** and must match main container's PHP version. Versions are tightly coupled.

**Current state:**
```
composer.json require-dev: PHPUnit 9.5.x (main: PHP 7.1)
```

**Required versions by PHP milestone:**

| PHP (main) | Symfony (main) | PHPUnit | Phase | Timeline |
|------------|-----------------|---------|-------|----------|
| 7.1–7.4 | 4.4–5.4 | 7.5.x | Phase 3 early–mid | ~2 weeks |
| 8.0–8.1 | 5.4–6.4 | 9.x | Phase 3–4 mid | ~2 weeks |
| 8.2–8.3 | 6.4–7.x | 10.x | Phase 4 late | ~2 weeks |
| 8.4–8.5 | 8.x | 10.x–11.x | Phase 4 final | ~1 week |

**Upgrade path:**
1. At PHP 8.0 transition, bump composer.json PHPUnit: `"phpunit/phpunit": "^9.0"`
2. At PHP 8.2 transition, bump: `"phpunit/phpunit": "^10.0"`
3. Test suite runs in main container after each PHP bump

---

## Container Transitions

### Phase 3 (PHP 7.2–8.0) — Parallel Container Evolution

**Main container progression:** 7.1 → 7.2 → 7.3 → 7.4 → 8.0
**Platau container:** Stays at PHP 8.1+ (no change)

- PHPUnit tests run in main (evolves with main container)
- Rector/PHPStan/cs-fixer run in platau (stable at 8.1+)
- No container transition needed yet

**Per-milestone tool updates in main:**
- `phpstan.neon.dist`: Update `phpVersion` parameter as main container upgrades
- `.php-cs-fixer.dist.php`: Evolution rules as PHP evolves (no version bump yet)
- `composer.json`: Update PHPUnit when reaching PHP 8.0 (bump to 10.x)

### Phase 3→4 Transition (PHP 8.1 decision point)

**When:** After main container reaches PHP 8.1 (after `php-81` task completes)

**Tool Execution (NO CHANGE):**
- Rector/PHPStan/cs-fixer continue in platau (already at 8.1+)
- PHPUnit continues in main (now at PHP 8.1)
- **No container transition needed**

**Reason:** Tools in platau were always at PHP 8.1+, so they're already compatible with main container's PHP 8.1.

### Phase 4 (PHP 8.2–8.5) — Continued in Current Containers

**Main container progression:** 8.1 → 8.2 → 8.3 → 8.4 → 8.5
**Platau container:** Stays at PHP 8.1+ (optional: can upgrade if new tool features desired)

- PHPUnit continues in main
- Rector/PHPStan/cs-fixer continue in platau
- No container migration needed

---

## Upgrade Checkpoints

### After Each PHP Minor Upgrade (in main container)

1. **Update `phpstan.neon.dist`** with new phpVersion:
   ```yaml
   parameters:
       phpVersion: 70200  # if PHP 7.2
   ```
2. **Update Rector config in `rector.php`** (optional, if new rules needed):
   ```php
   ->withPhpVersion(PhpVersion::PHP_72)
   ->withPhpSets(php72: true)
   ```
3. **Run validation loop:**
   ```bash
   castor symfony:analyse   # PHPStan from platau analyzes main's PHP
   castor symfony:cs        # cs-fixer from platau fixes main's code
   castor symfony:test      # PHPUnit in main runs on new PHP
   castor symfony:validate
   ```

### At PHP 8.0 Threshold (PHPUnit Upgrade)

Upgrade PHPUnit from 7.5.x to 9.x in `composer.json`:
```json
"require-dev": {
    "phpunit/phpunit": "^9.0"
}
```

Then: `composer install` and `castor symfony:test`

### At PHP 8.2 Threshold (PHPUnit Upgrade)

Upgrade PHPUnit from 9.x to 10.x in `composer.json`:
```json
"require-dev": {
    "phpunit/phpunit": "^10.0"
}
```

Then: `composer install` and `castor symfony:test`

### After Each Symfony LTS Upgrade

1. **Verify main container meets minimum PHP requirement** (see UPGRADE_ROADMAP.md)
2. **Run Rector with SF rule set** (in platau):
   ```bash
   ./tools/vendor/bin/rector process --dry-run  # Preview
   ./tools/vendor/bin/rector process             # Apply
   ```
3. **Run full validation:**
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

- [x] Document tool split: PHPUnit in main (7.1), Rector/PHPStan/cs-fixer in platau (8.1+)
- [x] Clarify version inventory: Rector 2.0+, PHPStan 2.1+, cs-fixer 3.68+
- [x] Define container routing (Castor tasks automatically route analyse/cs to platau)
- [x] Document tool version progression during Phases 3–6 (stable at current versions)
- [x] Clarify PHPUnit upgrade path: 9.5→10 at PHP 8.0, 10→11 at PHP 8.2
- [x] Document Rector rule set strategy (per PHP/SF milestone)
- [x] Define upgrade checkpoints and validation loop per milestone
- [x] Confirm no container transition needed (platau already at 8.1+)

---

**Created:** Phase 2 (Upgrade Tooling & Tool Execution Planning)  
**Last updated:** 2024 (Phase 2 corrected)  
**Next:** Execute Phase 3 (PHP 7.2), continue with planned PHP/Symfony upgrades
