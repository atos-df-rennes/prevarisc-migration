# Upgrade Roadmap — Version 3.0.0-rc.1

## Overview

The functional migration from Zend 1.12 to Symfony 4.4 was completed in v2.10.0. The Symfony 4.4 / PHP 7.1 application (`prevarisc-migration/`) is now the main entry point. However, it remains coupled to the legacy Zend application via a **Strangler Pattern**: `public/index.php` requires the legacy `prevarisc/public/index.php` in-process when no Symfony route matches (404 without `X-Symfony-Route-Found` header).

**Version 3.0.0-rc.1 objectives:**
1. **Decouple from Zend** — Make Symfony completely autonomous (zero runtime code/files/styles dependencies on legacy)
2. **Modernize incrementally** — PHP 7.1 → 8.5, Symfony 4.4 → 8.x (LTS hops), MySQL 5.6 → MariaDB, Debian 12, Apache 2.4

## Critical Constraint: Sequencing

**The Zend legacy is loaded in the same PHP process via `require`.** This means:
- **It is impossible to upgrade PHP beyond 7.1 while the fallback legacy exists.**
- **Phase 1 (removing Zend) is a hard blocker for Phases 3 (PHP) and 4 (Symfony).**

PHP and Symfony upgrades are **entangled** — each Symfony version requires a minimum PHP version:

```
Phase 1: Remove Zend Legacy (Symfony autonomous)
  ↓
PHP 7.2 → 7.3 → 7.4
  ↓
Symfony 4.4 → 5.4 LTS (requires PHP 7.4+)
  ↓
Fork odtphp + Audit dependencies for PHP 8
  ↓
PHP 8.0 → 8.1
  ↓
Symfony 5.4 → 6.4 LTS (requires PHP 8.1+, annotations → attributes)
  ↓
PHP 8.2 → 8.3
  ↓
Symfony 6.4 → 7.x (requires PHP 8.2+)
  ↓
PHP 8.4 → 8.5
  ↓
Symfony 7.x → 8.x (requires PHP 8.4+)
  ↓
Infrastructure modernization (MariaDB, Apache 2.4, Debian 12) — parallel possible after Phase 1
```

## Key Decisions (User-Approved)

- **Scope:** Full end-to-end roadmap
- **Granularity:** Incremental — one task per PHP minor version / Symfony LTS hop
- **Symfony target:** 8.x via LTS path: 4.4 → 5.4 → 6.4 → 7.x → 8.x
- **Database:** MariaDB (latest LTS, Debian 12 default)
- **PHP 8 compatibility:** Fork and patch `cybermonde/odtphp`
- **Documentation:** Commit roadmap to repo (`docs/tech/UPGRADE_ROADMAP.md`)

## Validation Loop (Each Task)

Every task must complete successfully:

```bash
castor symfony:analyse      # PHPStan level 10
castor symfony:cs          # Code style
castor symfony:test        # All tests passing
castor symfony:validate    # Symfony validation
castor symfony:migrate     # (if database schema changes)
```

Then: commit to PR, merge to `develop/` branch.

## Absolute Rules

1. **`findAll()` without pagination is forbidden** — always use offset/limit or iterate carefully
2. **`prevarisc/` (legacy) is read-only** — never modify it in automated runs
3. **PHP must remain 7.1-compatible until Phase 1 completes** — no PHP 8 features (typed properties, `match`, etc.)
4. **No branch creation during Phase 0–1** — work via PRs on `develop/`; release branch created after all phases complete

---

# Phases

## Phase 0 — Preparation & Guardrails

| Task | Description |
|------|-------------|
| **p0-baseline** | Establish green baseline on PHP 7.1 (validate + coverage snapshot), confirm all 642 tests passing |
| **p0-style-fixes** | Fix remaining code style issues with cs-fixer |
| **p0-roadmap-doc** | Create this file: `docs/tech/UPGRADE_ROADMAP.md` |

**Status:** ✅ Complete

---

## Phase 1 — Remove Zend Legacy (Make Symfony Autonomous) — *Hard blocker for Phase 3 & 4*

Each task below removes a piece of Zend dependency, ending with full autonomy.

| Task | Description | Impact |
|------|-------------|--------|
| **p1-port-etab-cache** | Port `/legacy/etablissement/vider-cache` to native Symfony cache | Zend no longer needed for establishment cache clearing |
| **p1-port-dossier-cache** | Port `/legacy/dossier/vider-cache` to Symfony | Zend no longer needed for dossier cache clearing |
| **p1-port-matrice-droits** | Port `/legacy/admin/groupes/matrice-des-droits` to Symfony | Admin matrix access via Symfony |
| **p1-port-user-save** | Port `/legacy/admin/utilisateurs/sauvegarder` to Symfony | User save operations via Symfony |
| **p1-native-search-cache** | Replace legacy search cache mechanism with native Symfony cache | Eliminate cache search fallback to legacy |
| **p1-remove-fallback** | Delete Strangler fallback in `public/index.php` and `SymfonyRouteFoundSubscriber` | **Symfony is now autonomous** |
| **p1-retire-zend-auth** | Remove Zend session bridge (`ZendSessionBridge`, `ZendIdentityBuilder`, `LegacySessionAuthenticator`) | Auth is 100% Symfony |
| **p1-native-login** | Switch to pure Symfony auth — remove `PREVARISC_SYMFONY_LOGIN=0` fallback | No legacy auth dependency |
| **p1-repatriate-platau** | Move Plat'AU healthcheck/client into main app | Remove dependency on `prevarisc-passerelle-platau` sidecar |
| **p1-remove-prevarisc-path** | Remove `PREVARISC_PATH` from config and `.env*` | Clean environment |
| **p1-clean-compose** | Remove legacy services/mounts from `compose.dev.yaml` | Dev environment simplified |
| **p1-verify-standalone** | Final verification: zero runtime refs to legacy, all 642 tests passing | **Phase 1 complete** |

**Estimated duration:** 2–3 weeks (1–2 PRs per week)

**Success criteria:**
- [ ] All 11 porting tasks merged
- [ ] Fallback removed
- [ ] All tests passing (642)
- [ ] PHPStan clean (level 10)
- [ ] Code style clean
- [ ] Team approval to proceed with Phase 3

---

## Phase 2 — Upgrade Tooling

Configure Rector, PHPStan, and cs-fixer for incremental PHP/Symfony upgrades.

| Task | Description |
|------|-------------|
| **p2-upgrade-tooling** | Set up Rector rulesets (PHP + Symfony), configure `platform.php` in `composer.json`, align PHPUnit bridge, versioning strategy for tooling |

**Estimated duration:** 1 week

---

## Phase 3 — Incremental PHP Upgrades (7.1 → 8.5)

One task per PHP minor version. Each task:
1. Bump `platform.php` in `composer.json`
2. Update Docker image
3. Run Rector with updated `withPhpVersion` and `withPhpSets`
4. Fix PHPStan and cs-fixer issues
5. Run full test suite
6. Commit and merge PR

| Version | Task | Notes |
|---------|------|-------|
| 7.2 | **php-72** | Zend deprecated features removal |
| 7.3 | **php-73** | — |
| 7.4 | **php-74** | — |
| — | **odtphp-fork** | Fork & patch `cybermonde/odtphp` for PHP 8 compatibility. Gate for PHP 8.0. |
| — | **deps-php8-audit** | Audit critical deps (`apereo/phpcas`, `sabre/*`, `phpoffice/phpspreadsheet`, `imagine/imagine`, `phpdocumentor/reflection-docblock`, PHPUnit). Gate for PHP 8.0. |
| 8.0 | **php-80** | Named arguments, constructor property promotion, match expressions |
| 8.1 | **php-81** | Enums, readonly properties, fibers (if applicable) |
| 8.2 | **php-82** | Disjunctive normal form types, readonly classes |
| 8.3 | **php-83** | Typed class constants |
| 8.4 | **php-84** | Property hooks, asymmetric visibility |
| 8.5 | **php-85** | Final upgrade |

**Estimated duration:** 3–4 weeks (1 PR per week)

**Blockers:**
- `odtphp-fork` must complete before PHP 8.0
- `deps-php8-audit` must complete before PHP 8.0

---

## Phase 4 — Symfony LTS Hops (4.4 → 8.x)

One task per LTS hop. Each task:
1. Update constraint (e.g., `symfony/*: "5.4.*"`)
2. Apply Flex recipes
3. Fix deprecations (based on `vendor/symfony/deprecation-contracts`)
4. Run Rector with Symfony sets
5. Migrate code (`isMasterRequest` → `isMainRequest`, annotations → attributes if 8.0+, etc.)
6. Run full test suite
7. Commit and merge PR

| Version | Task | Requires | Notes |
|---------|------|----------|-------|
| 5.4 | **sf-54** | PHP 7.4+ | 3–4 weeks of deprecations from 5.0–5.3 |
| 6.4 | **sf-64** | PHP 8.1+ | Annotations → Attributes, security overhaul, Doctrine ORM 3.0 compat |
| 7.x | **sf-7x** | PHP 8.2+ | Type system refinements |
| 8.x | **sf-8x** | PHP 8.4+ | Modern Symfony for PHP 8.4+ |

**Estimated duration:** 3–4 weeks per hop

**Attributes conversion note:** Symfony 6.4+ requires PHP 8 attributes for routing, Doctrine mapping, and validation. This will be done as part of **sf-64**, integrated with PHP 8.1.

---

## Phase 5 — Infrastructure Modernization (Debian 12, MariaDB LTS, Apache 2.4)

Parallel to Phases 3–4 after Phase 1 completion.

| Task | Description | Target |
|------|-------------|--------|
| **infra-mariadb** | Migrate MySQL 5.6 → MariaDB LTS: dump/restore, `my.cnf`, charset `utf8mb4`, Doctrine platform, `compose.*.yaml` | MariaDB 11.x LTS |
| **infra-apache-debian12** | Upgrade Docker image, Apache 2.4, `php.ini`, opcache, install extensions, Apache configs | PHP 8.5-apache on Debian 12 |
| **infra-compose-topology** | Simplify `compose.*.yaml`, finalize legacy/Plat'AU removal | Single-app topology |
| **infra-docs** | Update `docs/exploitation.md`, `docs/documentation_installation.md` | Latest prod setup |

**Estimated duration:** 1–2 weeks (can overlap with PHP 8.x work)

---

## Phase 6 — Release

Final steps before tagging `v3.0.0-rc.1`.

| Task | Description |
|------|-------------|
| **release-rc1** | Bump version in `composer.json` → 3.0.0-rc.1, update CHANGELOG, run full validation suite, create release branch `release/3.0`, tag `v3.0.0-rc.1` |

**Estimated duration:** 1 hour

---

# Version Gates & Sequencing Rules

```
Phase 1 complete (Zend removed)
  ↓
PHP 7.2, 7.3, 7.4 (no gate)
  ↓
odtphp-fork + deps-php8-audit (GATES for PHP 8.0)
  ↓
PHP 8.0, 8.1
  ↓
Symfony 5.4 → 6.4 (GATE: PHP 8.1+ required)
  ↓
PHP 8.2, 8.3
  ↓
Symfony 6.4 → 7.x (GATE: PHP 8.2+ required)
  ↓
PHP 8.4, 8.5
  ↓
Symfony 7.x → 8.x (GATE: PHP 8.4+ required)
```

## Do NOT:
- ❌ Upgrade Symfony before its minimum PHP version is reached
- ❌ Use `findAll()` without pagination
- ❌ Commit secrets to repo
- ❌ Modify `prevarisc/` (legacy read-only)
- ❌ Create `release/3.0` branch until ALL phases (0–6) are complete

---

# Timeline Estimate

| Phase | Duration | Start After | Dependencies |
|-------|----------|-------------|--------------|
| **P0** | 2–4 hours | Now | None |
| **P1** | 2–3 weeks | P0 complete | None |
| **P2** | 1 week | P1 complete | P1 complete |
| **P3** | 3–4 weeks | P2 complete | P2 complete, P1 complete |
| **P4** | 12–15 weeks | P3 reaches required PHP | See gates above |
| **P5** | 1–2 weeks | P1 complete | P1 complete (can overlap P3–4) |
| **P6** | 1 hour | All phases complete | All phases |

**Total:** ~3–4 months of incremental work (1–2 PRs per week)

---

# Useful Commands Reference

```bash
# Validate current state (no changes)
castor symfony:analyse       # PHPStan level 10
castor symfony:test          # Run all tests
castor symfony:cs            # Check code style
castor symfony:validate      # Symfony validation
castor symfony:migrate       # Run pending migrations (if any)

# View version gates
/roadmap

# Dry-run a version upgrade (Phase 3+)
/upgrade target:php-72 dryRun:true

# Execute a version upgrade (Phase 3+)
/upgrade target:php-72 dryRun:false
```

---

# Notes & Risks

### Auth CAS
`apereo/phpcas ~1.4` is currently used. **Must audit PHP 8 compatibility early** in `deps-php8-audit` task (Phase 3). If incompatible, may need alternative or custom implementation.

### PHPUnit Path: 7.5 → 10/11
PHPUnit versions follow PHP/Symfony versions. The path will be:
- PHP 7.4: PHPUnit 9.x
- PHP 8.0–8.1: PHPUnit 10.x  
- PHP 8.2+: PHPUnit 11.x

Tests may break during upgrades; advance by PHP minor versions and align with Symfony versions.

### odtphp Fork
- Host fork in a public GitHub repository under the org
- Use semantic versioning for tags
- Point to fork via Composer VCS repository
- Cover ODT generation with tests (`DossierRapportOdtService`) before/after fork

### Doctrine ORM 3.0
Doctrine ORM 3.0+ is required for Symfony 6.4+. This requires PHP 8.1+. Migrations to typed properties and attributes will be bundled into the `sf-64` task.

### Plat'AU Repatriation
When repatriating Plat'AU healthcheck/client into the main app (p1-repatriate-platau):
- Copy healthcheck endpoint logic
- Embed Plat'AU client library (or via Composer if published)
- Keep `platau` container in `compose.dev.yaml` for testing integration
- Document any configuration changes

---

# File & Commit Conventions

All commits follow conventional format:

```
feat(scope): description

Longer explanation if needed.

Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>
```

Example scopes:
- `feat(phase1): remove Zend legacy fallback`
- `feat(php): upgrade to PHP 8.0`
- `feat(symfony): upgrade to Symfony 5.4`
- `chore(style): fix cs-fixer issues`
- `docs: update upgrade roadmap`

---

**Last updated:** 2024 (v3.0.0-rc.1 planning)

For latest status, run:
```bash
cd /home/dev/prevarisc-infra/prevarisc-migration
castor migration:progress
```
