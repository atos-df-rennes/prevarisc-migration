# Symfony 7.4 Upgrade — Follow-up Questions & Resolutions

Post-upgrade clarifications addressing three technical concerns.

## 1. Environment Variable Precedence: Apache vs .env/.env.local

### Question
Does `.env` and `.env.local` override Apache-defined variables, or vice versa?

### Answer
**Apache variables win** (current behavior, no change applied).

### Explanation
- Symfony's `Dotenv::populate()` checks `if (!isset($_SERVER[$key]) && !isset($_ENV[$key])) { ... set from .env ... }`
- Apache `SetEnv` directives populate `$_SERVER` before PHP runs
- Therefore, Apache-defined variables cannot be overridden by `.env` or `.env.local`

### Recommendation for Future
**Migration strategy from Apache to Symfony conventions:**
1. **Application variables** (DATABASE_URL, etc.) → Move to `.env` or `.env.local` (follow Symfony conventions)
2. **Infrastructure variables** (TRUSTED_PROXIES, PHP settings) → Keep in Apache config (operational convenience)
3. **CLI access** → If a CLI command needs an Apache-defined variable, explicitly add it to `.env` instead

### Current Decision
Keep default behavior (Apache wins). Document this convention in your deployment guide to prevent future confusion when developers add variables to Apache but they don't appear in CLI contexts.

---

## 2. Cache Directory Permissions in Deployment (umask)

### Question
Should `umask(0o002)` be fixed in `bin/console` or via a Composer auto-script?

### Answer
**Fixed in `bin/console`** (applied in commit 8f5738c).

### Context
- Deployment model: `.tar.gz` package with bash installation/update scripts
- Deployment user: `prv` (owns application)
- Web server: Apache with user `www-data` (member of `prv` group)
- Original issue (#97): `bin/console cache:clear` creates cache dirs as `0755` (group cannot write) → Apache cannot write to cache → 500 errors

### Solution Applied
```php
// bin/console (cli entry point)
return function (array $context) {
    if (!($context['APP_DEBUG'] ?? false)) {
        umask(0o002);  // Production: group-writable (0775)
    }
    
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    return new Application($kernel);
};
```

### Why This Location
- ✅ Runs exactly when deployment scripts call `bin/console cache:clear`
- ✅ Matches historical fix (Symfony 6.4)
- ✅ Applies to all CLI cache operations (clear, warmup, etc.)
- ❌ Composer post-install not viable: `.tar.gz` packages don't run Composer in deployment
- ⚠️ Does NOT affect web server umask (Apache/PHP-FPM runs as separate process)

### Web Server Cache Writes (Apache/PHP-FPM)
For web server cache writes, ensure:
- Either: PHP-FPM config sets `umask 0002` (recommended for production)
- Or: Apache `.htaccess` or PHP wrapper enforces it
- Or: Initial cache dir is created with correct perms by deployment script

**Current assumption:** Initial `var/cache` directories are pre-created with 0775 during deployment; web server then inherits/maintains those permissions.

### Behavior
- **APP_DEBUG=false** (production): `umask(0o002)` → dirs created as 0775 (group-writable) ✅
- **APP_DEBUG=true** (development): `umask(0o000)` → dirs created as 0777 (world-writable, convenient for dev) ✅

---

## 3. CommissionDocumentGenerationService:1972 — reset() Safety

### Question
Is it safe that Rector removed `?: []` fallback from `reset($prescRow)`?

### Answer
✅ **Yes, safe.** Removal is correct because of a guard condition.

### Explanation
```php
// Line 1967: Guard ensures $prescRow is never empty
if (empty($prescRow)) {
    continue;
}

// Line 1972: Now guaranteed non-empty
$firstAssoc = reset($prescRow);  // Returns array (first element), never false

// Line 1973: Safe to access keys
$this->addChamp($prescription, 'num', (string) ($firstAssoc['NUM_PRESCRIPTION_DOSSIER'] ?? ''));
```

### Why It's Safe
- `reset()` returns `false` only if the array is empty
- Line 1967's guard prevents execution if `$prescRow` is empty
- Therefore, `reset()` always returns an array, never `false`
- The `?: []` was defensive but redundant

### Risk Assessment
- **Low risk** — if this ever breaks (e.g., guard removed), PHPUnit immediately throws `TypeError: Cannot access offset "..." on false`
- Suggests adding a test case or type assertion if this code is heavily refactored in the future

---

## Summary Table

| Item | Status | Decision | Evidence |
|------|--------|----------|----------|
| .env vs Apache precedence | ✅ Working as intended | No change; Apache wins | Symfony dotenv checks `isset($_SERVER)` first |
| umask in bin/console | ✅ Fixed | Restore to bin/console | Commit 8f5738c, matches issue #97 context |
| reset() safety | ✅ Safe | No change needed | Guard at line 1967 prevents false return |

---

## Related Documentation
- **Deployment:** See bash installation scripts for `.tar.gz` cache dir creation (verify 0775 permissions set initially)
- **PHP-FPM:** If using PHP-FPM in production, consider setting `umask = 0022` in `www.conf` for web server consistency
- **Apache:** If caching headers or rewrite rules reference cache dirs, verify Apache's execution context has group write permission
