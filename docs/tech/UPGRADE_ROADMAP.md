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

**Status:** ✅ Complete

This phase focused on **core decoupling tasks** only. Cache porting and Plat'AU repatriation are deferred to a later phase.

### Core Tasks (Reduced Scope: ~5–6 Tasks) — ✅ ALL COMPLETE

| Task | Description | Impact | Status |
|------|-------------|--------|--------|
| **p1-remove-fallback** | Delete Strangler fallback in `public/index.php` and `SymfonyRouteFoundSubscriber` | **Symfony is now autonomous** | ✅ Done |
| **p1-retire-zend-auth** | Remove Zend session bridge (`ZendSessionBridge`, `ZendIdentityBuilder`, `LegacySessionAuthenticator`) | Auth is 100% Symfony | ✅ Done |
| **p1-native-login** | Switch to pure Symfony auth — remove `PREVARISC_SYMFONY_LOGIN=0` fallback | No legacy auth dependency | ✅ Done |
| **p1-remove-prevarisc-path** | Remove `PREVARISC_PATH` from config and `.env*` | Clean environment | ✅ Done |
| **p1-clean-compose** | Remove legacy Zend service & auth config from `compose.dev.yaml` (keep platau container) | Dev environment simplified | ✅ Done |
| **p1-verify-standalone** | Final verification: zero runtime refs to legacy, all 642 tests passing, `public/` self-sufficient | **Phase 1 complete** | ✅ Done |

### Deferred Tasks (Separate Future Phase)

The following tasks are **not** in Phase 1—they require deeper refactoring and will be scheduled separately:

| Task | Reason for Deferral |
|------|-------------------|
| **p1-port-etab-cache** | Full porting to Symfony cache requires significant refactoring; deferral keeps Phase 1 lean |
| **p1-port-dossier-cache** | Deferred; redirect legacy route to 404 or stub for now |
| **p1-port-matrice-droits** | Deferred; admin matrix porting is substantial work |
| **p1-port-user-save** | Deferred; user save operations require DB schema alignment |
| **p1-native-search-cache** | Review if already using Symfony cache; if not, defer to future phase |
| **p1-repatriate-platau** | Not feasible without full Plat'AU code migration; keep platau container in compose.dev.yaml |

### Phase 1 Approach to Cache & Legacy Routes

**For cache/admin endpoints that remain in Zend:**
1. Remove from Zend fallback routing (no legacy route forwarding)
2. Return 404 or redirects where appropriate
3. (Later phase:) Implement Symfony equivalents and migrate users

**For Plat'AU:**
- Keep `platau` container in `compose.dev.yaml` (mounted as-is)
- No repatriation now—separate chore task for future

**Estimated duration:** 2–3 weeks (1–2 PRs per week)

**Success criteria:**
- [ ] All 6 core tasks merged
- [ ] Fallback removed
- [ ] All tests passing (642)
- [ ] PHPStan clean (level 10)
- [ ] Code style clean
- [ ] Zero runtime Zend dependencies
- [ ] Team approval to proceed with Phase 2/3

---

## Phase 2 — Upgrade Tooling & Tool Execution Planning

**Status:** ✅ Complete

Configure Rector, PHPStan, and cs-fixer for incremental PHP/Symfony upgrades. Plan when tools transition between containers.

### Tool Execution Context

**Current state (PHP 7.1):**
- Tools (`analyse`, `cs`) run in `platau` container (PHP 8.1+)
- Tests (`test`) run in main container (`php:7.1-apache`)

**During PHP 7.2–8.0 upgrades (Phase 3, early):**
- Tools continue in `platau` container as main container upgrades in-place
- No container change needed during this phase

**At PHP 8.1 threshold (Phase 3, middle):**
- Main container reaches PHP 8.1 (same as platau)
- **No container transition** (Rector/PHPStan/cs-fixer already in platau at 8.1+)
- Continue validation loop as-is
- PHPUnit bumped to 10.x at PHP 8.2 (separate step)

**Phase 2 Deliverables (✅ Complete):**
- ✅ Tool split clarified: PHPUnit 7.5.x in main, Rector/PHPStan/cs-fixer in platau
- ✅ Tool versions pinned: Rector 2.0+, PHPStan 2.1+, cs-fixer 3.68+ (stable during phases)
- ✅ PHPUnit upgrade path: 7.5→9 at PHP 8.0, 9→10 at PHP 8.2
- ✅ Tool execution plan documented: `docs/tech/TOOL_EXECUTION_PLAN.md`
- ✅ Validation on PHP 7.1 baseline: PHPStan clean, 629 tests passing, cs-fixer OK

**Duration:** 1 week

---

## Phase 3 & 4 — PHP & Symfony Upgrades (Entangled, Not Sequential)

**CRITICAL:** PHP and Symfony upgrades are **entangled**—each Symfony version requires a minimum PHP version. The upgrade order is dictated by these constraints, not a simple sequential path.

### Symfony Minimum PHP Requirements

| Symfony Version | Min PHP | Max PHP |
|-----------------|---------|---------|
| 4.4 LTS | 7.1 | 8.x |
| 5.4 LTS | 7.4 | 8.x |
| 6.4 LTS | 8.1 | 8.x |
| 7.x LTS | 8.2 | 8.x |
| 8.x LTS | 8.4 | 8.x |

### Correct Entangled Upgrade Path

```
Starting: PHP 7.1, Symfony 4.4

1. PHP 7.1 → 7.2  (SF 4.4 still compatible)
2. PHP 7.2 → 7.3  (SF 4.4 still compatible)
3. PHP 7.3 → 7.4  (SF 4.4 still compatible)
   
   [DECISION POINT: PHP 7.4 reached → SF 4.4 → 5.4 LTS now viable]
   
4. SF 4.4 → 5.4 LTS  (PHP 7.4+ required, satisfied)
   
5. PHP 7.4 → 8.0  (SF 5.4 still compatible)
6. PHP 8.0 → 8.1  (SF 5.4 still compatible)
   
   [DECISION POINT: PHP 8.1 reached → SF 5.4 → 6.4 LTS now viable]
   [ALSO: Attributes conversion, sensio/framework-extra-bundle removal]
   [ALSO: Move tool execution from platau to main container]
   
7. SF 5.4 → 6.4 LTS  (PHP 8.1+ required, satisfied)
   
8. PHP 8.1 → 8.2  (SF 6.4 still compatible)
   
   [DECISION POINT: PHP 8.2 reached → SF 6.4 → 7.x LTS now viable]
   
9. SF 6.4 → 7.x LTS  (PHP 8.2+ required, satisfied)
   
10. PHP 8.2 → 8.3  (SF 7.x still compatible)
11. PHP 8.3 → 8.4  (SF 7.x still compatible)
    
    [DECISION POINT: PHP 8.4 reached → SF 7.x → 8.x LTS now viable]
    
12. SF 7.x → 8.x LTS  (PHP 8.4+ required, satisfied)
    
13. PHP 8.4 → 8.5  (SF 8.x still compatible) [FINAL]
```

### Phase 3 & 4 Tasks (Integrated List)

| Step | Task | Type | Duration | Notes |
|------|------|------|----------|-------|
| 1 | **php-72** | PHP | 2–3h | No Rector sets needed yet (simple deprecations) |
| 2 | **php-73** | PHP | 2–3h | — |
| 3 | **php-74** | PHP | 2–3h | — |
| 4 | **sf-54** | SF | 3–5h | SF 4.4 → 5.4 LTS (decision point reached) |
| 5 | **odtphp-fork** | Gate | 2–3d | Fork & patch `cybermonde/odtphp` for PHP 8 (blocks php-80) |
| 6 | **deps-php8-audit** | Gate | 2–3d | Audit critical deps (`apereo/phpcas`, `sabre/*`, etc.) for PHP 8 compat (blocks php-80) |
| 7 | **php-80** | PHP | 3–4h | Named arguments, constructor properties, match expressions |
| 8 | **php-81** | PHP | 3–4h | Enums, readonly properties, fibers (if applicable) |
| 9 | **sf-64** | SF | 4–5h | SF 5.4 → 6.4 LTS + Attributes conversion (decision point reached); also move tools to main container |
| 10 | **php-82** | PHP | 3–4h | Disjunctive normal form types, readonly classes |
| 11 | **sf-7x** | SF | 3–5h | SF 6.4 → 7.x LTS (decision point reached) |
| 12 | **php-83** | PHP | 3–4h | Typed class constants |
| 13 | **php-84** | PHP | 3–4h | Property hooks, asymmetric visibility |
| 14 | **sf-8x** | SF | 3–5h | SF 7.x → 8.x LTS (decision point reached) |
| 15 | **php-85** | PHP | 3–4h | Final PHP version |

### Estimated Effort

- **PHP bumps:** 2–3 hrs each × 9 bumps = 18–27 hours
- **SF bumps:** 3–5 hrs each × 4 hops = 12–20 hours
- **Gates (odtphp-fork, deps-php8-audit):** 2–3 days each = 16–24 hours
- **Total:** ~30–50 hours (4–6 weeks with skill automation + 1–2 PRs per week)

### Do NOT:
- ❌ Upgrade Symfony before its minimum PHP version is reached
- ❌ Skip gates (odtphp-fork and deps-php8-audit block PHP 8.0)
- ❌ Use `findAll()` without pagination during upgrades
- ❌ Modify `prevarisc/` (legacy read-only)

## Phase 5 — Infrastructure Modernization (Debian 12, MariaDB LTS, Apache 2.4)

Parallel to Phases 3–4 after Phase 1 completion.

| Task | Description | Target |
|------|-------------|--------|
| **infra-mariadb** | Migrate MySQL 5.6 → MariaDB LTS: dump/restore, `my.cnf`, charset `utf8mb4`, Doctrine platform, `compose.*.yaml` | MariaDB 11.x LTS |
| **infra-apache-debian12** | Upgrade Docker image, Apache 2.4, `php.ini`, opcache, install extensions, Apache configs | PHP 8.5-apache on Debian 12 |
| **infra-compose-topology** | Simplify `compose.*.yaml`, finalize legacy Zend removal (platau container remains for now per Phase 1 adjustments) | Single-app topology |
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

---

# Absolute Rules (Applies to All Phases)

1. **`findAll()` without pagination is forbidden** — always use offset/limit or iterate carefully
2. **`prevarisc/` (legacy) is read-only** — never modify it in automated runs
3. **PHP must remain 7.1-compatible through Phase 1** — no PHP 8 features (typed properties, `match`, etc.)
4. **All work on `develop/` branch via PRs** — release branch created ONLY after all phases complete
5. **Respect Symfony minimum PHP requirements** — never upgrade Symfony before its minimum PHP is reached
6. **Gates block upgrades** — odtphp-fork and deps-php8-audit must complete before PHP 8.0
7. **Keep platau container in compose.dev.yaml** — per Phase 1 adjustments (Plat'AU repatriation deferred)

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
