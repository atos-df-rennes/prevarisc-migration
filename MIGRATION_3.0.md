# Prevarisc 2.10 → 3.0 Migration Guide

This guide describes the steps required to upgrade from **Prevarisc 2.10** to **Prevarisc 3.0** (Release Candidate). Release candidates are pre-releases intended for test/acceptance environments only; do not deploy them to production.

**Status:** Release Candidate
**Version Target:** 3.0.0-rc.4
**Release Date:** October 23, 2026

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Infrastructure Requirements](#infrastructure-requirements)
4. [Step-by-Step Migration](#step-by-step-migration)
5. [Configuration Changes](#configuration-changes)
6. [Post-Migration Validation](#post-migration-validation)
7. [Rollback Procedure](#rollback-procedure)
8. [Support & Issues](#support--issues)

---

## Overview

### What's New in 3.0

Prevarisc 3.0 is a **major technical upgrade** of the migration from Zend Framework to Symfony:

| Component | 2.10 | 3.0 |
|-----------|------|-----|
| **PHP** | 7.1+ | 8.5+ |
| **Symfony** | 4.4 LTS | 7.4 LTS |
| **MySQL/MariaDB** | 5.6.51 | 11.8+ (MariaDB) |
| **Doctrine ORM** | 2.6.x | 2.20.x |
| **Bootstrap** | 3.4 | 3.4 (unchanged) |

### Key Changes

- **PHP Version Bump:** 7.1 → 8.5 (major language upgrade with strict typing)
- **Symfony Framework:** 4.4 LTS → 7.4 LTS (3 major versions forward)
- **Database:** MySQL 5.6.51 → MariaDB 11.8+ (new LTS branch)
- **PHP Attributes:** Doctrine annotations replaced with PHP 8 attributes
- **Environment Variables:** DATABASE_URL format change (mysql → mariadb)

### Estimated Timeline

| Phase | Duration | Impact |
|-------|----------|--------|
| **Planning & Backup** | 30 min | None |
| **Infrastructure Upgrade** | 1-2 hours | Downtime (PHP + DB) |
| **Application Deployment** | 15-30 min | Downtime (service restart) |
| **Testing & Validation** | 1-2 hours | Monitoring mode |
| **Total Downtime** | ~2 hours | Planned maintenance window |

---

## Prerequisites

### Pre-Migration Checks

Before starting, verify your current environment:

```bash
# Check current Prevarisc version
grep "version" /path/to/prevarisc-2.10/package.json

# Check PHP version
php -v  # Should be 7.1+

# Check MySQL version
mysql -u root -p -e "SELECT VERSION();"  # Should be 5.6.51+

# Check available disk space
df -h / | tail -1  # Need ~2GB for build, migrations, backups
```

### Required Backups

**Create comprehensive backups before proceeding:**

```bash
# Backup application code
tar -czf /backup/prevarisc-2.10-code-$(date +%Y%m%d).tar.gz \
    /path/to/prevarisc-2.10/

# Backup database
mysqldump -u root -p --all-databases \
    --routines --triggers --events \
    | gzip > /backup/prevarisc-2.10-db-$(date +%Y%m%d).sql.gz

# Backup Apache configuration
tar -czf /backup/apache-config-$(date +%Y%m%d).tar.gz \
    /etc/apache2/

# Backup Prevarisc configuration
tar -czf /backup/prevarisc-2.10-conf-$(date +%Y%m%d).tar.gz \
    /home/prv/current/config/
```

### Access Requirements

- ✅ Root access to production server
- ✅ Write access to `/home/prv/` directory
- ✅ Database root access (for schema upgrades)
- ✅ 2GB+ free disk space
- ✅ Network access to Composer repositories

---

## Infrastructure Requirements

### PHP Upgrade: 7.1 → 8.5

**Action Required:** Upgrade PHP before deploying Prevarisc 3.0

```bash
# Install PHP 8.5 (instructions vary by OS)
# This example assumes Debian 12

apt update
apt install -y \
    php8.5 \
    php8.5-cli \
    php8.5-fpm \
    php8.5-mysql \
    php8.5-gd \
    php8.5-mbstring \
    php8.5-xml \
    php8.5-zip \
    php8.5-ldap \
    php8.5-intl \
    php8.5-curl

# Verify PHP 8.5 installation
php -v  # Should show PHP 8.5.x
php -m | grep -E "mysql|gd|mbstring|xml|zip|ldap|intl"  # All should be present
```

**PHP Module Requirements for 3.0:**

| Extension | Required | Used For |
|-----------|----------|----------|
| `php-mysql` | ✅ Yes | Database connectivity |
| `php-gd` | ✅ Yes | Image manipulation |
| `php-mbstring` | ✅ Yes | String handling (UTF-8) |
| `php-xml` | ✅ Yes | XML/DOM processing |
| `php-zip` | ✅ Yes | Document generation (ODF) |
| `php-ldap` | ✅ Yes | LDAP/Active Directory auth |
| `php-intl` | ✅ Yes | Internationalization |
| `php-curl` | ✅ Yes | HTTP requests |
| `php-simplexml` | ✅ Yes | XML parsing |
| `php-xmlreader` | ✅ Yes | XML streaming |
| `php-xmlwriter` | ✅ Yes | XML generation |
| `php-zlib` | ✅ Yes | Compression |
| `php-ctype` | ✅ Yes | Character type checking |
| `php-dom` | ✅ Yes | DOM manipulation |
| `php-fileinfo` | ✅ Yes | File type detection |
| `php-iconv` | ✅ Yes | Character encoding |
| `php-libxml` | ✅ Yes | XML library |

### Apache Configuration: No Changes Required ✅

**Good News:** Apache configuration remains **100% compatible** between 2.10 and 3.0.

- ✅ VirtualHost configuration: **unchanged**
- ✅ Rewrite rules: **compatible** (same routing engine)
- ✅ Module requirements: **unchanged** (mod_rewrite, mod_ssl, etc.)
- ✅ SSL/TLS configuration: **no changes**
- ✅ Log paths: **unchanged** (still `/var/log/apache2` → `/home/prv/logs`)

**Action Required:** None. Existing Apache configuration continues to work.

### MySQL/MariaDB Upgrade: 5.6 → 11.8 LTS

**Action Required:** Upgrade MySQL to MariaDB 11.8+ before deploying Prevarisc 3.0

> **Important:** Database must be upgraded BEFORE application code deployment

#### Option 1: In-Place Upgrade (Recommended if possible)

```bash
# Backup database first (see Prerequisites section)

# Add MariaDB 11.8 LTS repository (Debian/Ubuntu example)
curl https://mariadb.org/mariadb_release_signing_key.asc | apt-key add -
echo "deb [arch=amd64] https://deb.mariadb.org/11.8 debian main" \
    | tee /etc/apt/sources.list.d/mariadb.list

# Update and upgrade
apt update
apt upgrade -y mariadb-server mariadb-client

# Verify installation
mysql -u root -p -e "SELECT VERSION();"  # Should show MariaDB 11.8.x
```

#### Option 2: Data Migration (If fresh install required)

```bash
# Export data from old MySQL 5.6
mysqldump -u root -p --all-databases > /backup/prevarisc-dump.sql

# Install MariaDB 11.8
# (platform-specific steps)

# Import data into new MariaDB
mysql -u root -p < /backup/prevarisc-dump.sql
```

**Database Configuration:**

After upgrading to MariaDB 11.8, verify these settings:

```bash
mysql -u root -p -e "SHOW VARIABLES LIKE 'character%'; SHOW VARIABLES LIKE 'collation%';"
```

Expected output:
```
character_set_client     = utf8mb4
character_set_connection = utf8mb4
character_set_database   = utf8mb4
character_set_server     = utf8mb4
collation_connection     = utf8mb4_unicode_ci
collation_database       = utf8mb4_unicode_ci
collation_server         = utf8mb4_unicode_ci
```

If not set, update `/etc/mysql/mariadb.conf.d/50-server.cnf`:

```ini
[mysqld]
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci
```

Then restart:
```bash
systemctl restart mariadb
```

---

## Step-by-Step Migration

### Phase 1: Infrastructure Setup (Before Code Deployment)

#### 1.1 Upgrade PHP to 8.5

```bash
# (See PHP Upgrade section above)
systemctl stop php-fpm  # Or your PHP service

# Install PHP 8.5
apt install php8.5-fpm php8.5-cli php8.5-mysql php8.5-gd php8.5-mbstring \
    php8.5-xml php8.5-zip php8.5-ldap php8.5-intl php8.5-curl

# Verify all modules installed
php -m | wc -l  # Should have 30+ modules

# Start PHP 8.5
systemctl start php-fpm
systemctl enable php-fpm
```

#### 1.2 Upgrade MySQL to MariaDB 11.8

```bash
# (See MySQL/MariaDB Upgrade section above)
systemctl stop mysql  # or mariadb

# Backup database
mysqldump -u root -p --all-databases | gzip > /backup/db-backup.sql.gz

# Upgrade MariaDB
# (platform-specific)

# Verify database integrity
mariadb -u root -p -e "REPAIR TABLE ..."  # Check all tables
```

#### 1.3 Update Apache Configuration

**No action required** — existing VirtualHost and configuration remain compatible.

Verify Apache still runs:
```bash
apache2ctl configtest  # Should output "Syntax OK"
systemctl reload apache2
```

### Phase 2: Application Deployment

#### 2.1 Get Prevarisc 3.0 Code

```bash
cd /path/to/prevarisc

# Clone the repository and select the release candidate for testing
git clone https://github.com/atos-df-rennes/prevarisc-migration.git prevarisc-3.0
cd prevarisc-3.0
git checkout v3.0.0-rc.4

# Or if already cloned:
cd /path/to/existing/prevarisc-migration
git fetch --tags origin
git checkout v3.0.0-rc.4
```

#### 2.2 Configure Environment Variables

```bash
cd /path/to/prevarisc-3.0

# Copy example environment file
cp .env.example .env

# Edit .env with your configuration
nano .env
```

**Critical: Update DATABASE_URL format**

Old format (2.10):
```
DATABASE_URL="mysql://user:password@localhost:3306/database?serverVersion=5.6&charset=utf8mb4"
```

New format (3.0):
```
DATABASE_URL="mysql://user:password@localhost:3306/database?serverVersion=11.8.8-MariaDB&charset=utf8mb4"
```

**Example .env configuration:**

```bash
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=<generate-with-symfony-console>

# Database: Update serverVersion to 11.8.8-MariaDB
DATABASE_URL="mysql://prevarisc_user:strong_password@localhost:3306/prevarisc_db?serverVersion=11.8.8-MariaDB&charset=utf8mb4"

# Optional: LDAP configuration (if using)
LDAP_HOST=your-ldap-server
LDAP_DN=cn=admin,dc=example,dc=com
```

#### 2.3 Install Dependencies

```bash
# Install Composer dependencies
composer install --no-dev --classmap-authoritative

# Optimize configuration
composer dump-env prod
```

#### 2.4 Run Database Migrations

> **Important:** Database migrations should run with zero downtime if possible using a read-only period.

```bash
# Run Doctrine migrations (defined in migrations/ directory)
php bin/console doctrine:migrations:migrate

# Verify migrations completed
php bin/console doctrine:migrations:status

# Expected output:
# Executed Migrations
# ------------------
# All (X) migrations successfully executed
```

#### 2.5 Clear and Optimize Cache

```bash
# Clear Symfony cache
php bin/console cache:clear --no-warmup

# Build cache
php bin/console cache:warmup

# Generate PHP autoloader
composer dump-autoload --optimize --classmap-authoritative
```

#### 2.6 Set Permissions

```bash
# Set ownership
chown -R www-data:www-data /path/to/prevarisc-3.0

# Set directory permissions
find /path/to/prevarisc-3.0 -type d -exec chmod 0755 {} \;

# Set file permissions
find /path/to/prevarisc-3.0 -type f -exec chmod 0644 {} \;

# Ensure vendor/bin is executable
chmod -R 755 /path/to/prevarisc-3.0/vendor/bin

# Ensure var/ is writable (logs, cache)
chmod -R 755 /path/to/prevarisc-3.0/var
```

#### 2.7 Update Symlinks (If Using /home/prv/current)

```bash
# Update application symlink
cd /home/prv
ln -sfn /path/to/prevarisc-3.0 current

# Or if migrating incrementally:
# Backup old symlink
mv /home/prv/current /home/prv/prevarisc-2.10

# Point to new version
ln -s /path/to/prevarisc-3.0 /home/prv/current
```

#### 2.8 Restart Services

```bash
# Reload Apache (graceful reload, no downtime for existing connections)
systemctl reload apache2

# Verify Apache status
systemctl status apache2

# Check error logs
tail -20 /var/log/apache2/error.log
```

### Phase 3: Validation & Rollback

See [Post-Migration Validation](#post-migration-validation) section below.

---

## Configuration Changes

### Database URL Format

**This is the primary configuration change between 2.10 and 3.0.**

#### Change: serverVersion parameter

The `serverVersion` parameter in `DATABASE_URL` must reflect your database engine:

**Before (2.10 with MySQL 5.6):**
```
DATABASE_URL="mysql://user:pass@localhost/db?serverVersion=5.6&charset=utf8mb4"
```

**After (3.0 with MariaDB 11.8):**
```
DATABASE_URL="mysql://user:pass@localhost/db?serverVersion=11.8.8-MariaDB&charset=utf8mb4"
```

**Why this matters:**
- Doctrine uses `serverVersion` to generate SQL compatible with your database
- MariaDB has different behaviors than MySQL (e.g., JSON, JSON_ARRAYAGG)
- Incorrect version can cause migration failures or runtime errors

#### Valid serverVersion Values for MariaDB

```
11.8.8-MariaDB    ← Recommended (11.8 LTS)
11.7.0-MariaDB
11.6.0-MariaDB
11.5.0-MariaDB
11.4.0-MariaDB
11.3.0-MariaDB
11.2.0-MariaDB
11.1.0-MariaDB
11.0.0-MariaDB
10.6.0-MariaDB    ← Previous LTS
```

### Apache Configuration

**✅ No changes required** — Apache configuration remains identical between 2.10 and 3.0.

**Verified Compatible:**
- ✅ VirtualHost directives
- ✅ Rewrite rules (mod_rewrite)
- ✅ SSL/TLS configuration
- ✅ Authentication methods
- ✅ Compression (mod_deflate)
- ✅ Header manipulation
- ✅ Module loading

**Example VirtualHost (unchanged from 2.10):**

```apache
<VirtualHost *:80>
    ServerName prevarisc.local
    DocumentRoot /home/prv/current/public
    
    <Directory /home/prv/current/public>
        DirectoryIndex index.php
        AllowOverride all
        Require all granted
        
        <IfModule rewrite_module>
            Options +FollowSymlinks
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !^.+\.php$
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [L]
        </IfModule>
    </Directory>
    
    # Logs
    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
</VirtualHost>
```

### PHP Configuration

**Recommended PHP FPM Settings for 3.0:**

Update `/etc/php/8.5/fpm/php.ini`:

```ini
[PHP]
; Memory limit for Prevarisc (increased for PHP 8.5)
memory_limit = 512M

; Maximum file upload size
upload_max_filesize = 100M
post_max_size = 100M

; Session settings
session.save_path = /var/lib/php/sessions
session.gc_maxlifetime = 3600

[Date]
; Set timezone to match your deployment
date.timezone = "Europe/Paris"

[Opcache]
; Opcache should be enabled in production
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.revalidate_freq = 2
```

**Restart PHP-FPM:**
```bash
systemctl restart php8.5-fpm
```

---

## Post-Migration Validation

### Checklist: Verify Successful Migration

#### ✅ Application Access

```bash
# Test HTTP access
curl -I http://prevarisc.local/

# Test HTTPS (if configured)
curl -I https://prevarisc.local/ 2>/dev/null

# Expected: HTTP 200 OK or 302 Redirect (if authentication required)
```

#### ✅ Database Connectivity

```bash
# Log in to application and verify database operations
# or test via command line:

php bin/console doctrine:query:sql "SELECT VERSION();"

# Expected: Should show MariaDB 11.8.x
```

#### ✅ PHP Version

```bash
# Verify running PHP 8.5
php -v

# Verify all required modules
php -m | grep -E "mysql|gd|mbstring|xml|zip|ldap|intl"

# Should list all extensions
```

#### ✅ File Permissions

```bash
# Check ownership
ls -la /home/prv/current/ | head

# Expected: www-data:www-data ownership

# Check var/ is writable
touch /home/prv/current/var/test && rm /home/prv/current/var/test

# Should succeed without permission errors
```

#### ✅ Application Logs

```bash
# Check for PHP errors
tail -20 /home/prv/current/var/log/*.log

# Should have no CRITICAL or ERROR level messages

# Check Apache error log
tail -20 /var/log/apache2/error.log

# Should have no PHP errors or warnings
```

#### ✅ Doctrine Migrations

```bash
# Verify all migrations executed
php bin/console doctrine:migrations:status

# Expected: "All (N) migrations successfully executed"
```

#### ✅ Cache & Opcache

```bash
# Check cache directory
ls -la /home/prv/current/var/cache/prod/

# Should have generated cache files

# Verify Opcache
php -r "phpinfo();" | grep -A 10 "Opcache"
```

#### ✅ Feature Testing

1. **Login:** Verify authentication works (local or LDAP)
2. **Search:** Test main search functionality
3. **Dashboard:** Verify dashboard displays correctly
4. **File Upload:** Test document upload functionality
5. **Export:** Test data export feature
6. **Report Generation:** Test ODF document generation

### Rollback Procedure

If migration fails and you need to rollback to 2.10:

#### Step 1: Stop Application

```bash
systemctl reload apache2
# Point symlink back to 2.10
ln -sfn /home/prv/prevarisc-2.10 /home/prv/current
systemctl reload apache2
```

#### Step 2: Restore Database (If Schema Changed)

```bash
# ONLY if database migrations were problematic
# Warning: This will lose any data entered during 3.0 testing

mysql -u root -p < /backup/prevarisc-2.10-db-$(date +%Y%m%d).sql.gz
```

#### Step 3: Revert PHP (If Needed)

```bash
# If PHP 8.5 caused issues and you need 7.1:
systemctl stop php8.5-fpm
systemctl start php7.1-fpm  # If still installed
```

#### Step 4: Verify Rollback

```bash
php -v  # Should show 7.1
curl http://localhost/  # Should work with 2.10
```

---

## Support & Issues

### Common Issues

#### Issue: DATABASE_URL Connection Fails

**Symptom:** `SQLSTATE[HY000]: General error: 1030`

**Solution:**
1. Verify MariaDB is running: `systemctl status mariadb`
2. Check DATABASE_URL format in `.env`
3. Verify `serverVersion=11.8.8-MariaDB` is set
4. Test connection: `mysql -u user -p -h localhost database`

#### Issue: PHP Module Missing

**Symptom:** `Call to undefined function gd_info()` or similar

**Solution:**
```bash
# Install missing PHP extension
apt install php8.5-gd  # Example for GD

# Verify extension loaded
php -m | grep gd

# Restart PHP-FPM
systemctl restart php8.5-fpm
```

#### Issue: Permission Denied on var/ Directory

**Symptom:** `[Exception] Unable to create the "var/cache/..." directory`

**Solution:**
```bash
# Fix permissions
chown -R www-data:www-data /home/prv/current/var
chmod -R 755 /home/prv/current/var
```

### Getting Help

- **Prevarisc Documentation:** https://github.com/atos-df-rennes/prevarisc-migration/docs/
- **GitHub Issues:** https://github.com/atos-df-rennes/prevarisc-migration/issues
- **Symfony Documentation:** https://symfony.com/doc/7.4/

### Reporting Issues

When reporting migration issues, include:

```bash
# Diagnostic information
php -v
mysql -u root -p -e "SELECT VERSION();"
composer --version
grep DATABASE_URL /path/to/.env | head -1
tail -50 /home/prv/current/var/log/*.log
tail -50 /var/log/apache2/error.log
```

---

## Timeline & Checklist

### Pre-Migration (1-2 days before)

- [ ] Notify users of maintenance window
- [ ] Schedule infrastructure team for support
- [ ] Verify backups are complete
- [ ] Test migration steps on staging environment
- [ ] Document current PHP/MySQL/Apache versions

### Migration Day (Maintenance Window ~2 hours)

- [ ] Start downtime
- [ ] Backup application code and database
- [ ] Upgrade PHP to 8.5
- [ ] Upgrade MySQL to MariaDB 11.8
- [ ] Deploy Prevarisc 3.0 code
- [ ] Configure .env with correct DATABASE_URL
- [ ] Run database migrations
- [ ] Update symlinks
- [ ] Clear cache and verify
- [ ] Smoke test application
- [ ] End downtime notification

### Post-Migration (24-48 hours)

- [ ] Monitor error logs continuously
- [ ] Test all major features
- [ ] Verify backup and recovery procedures
- [ ] Document any customizations
- [ ] Performance baseline testing
- [ ] Confirm user access and functionality

---

## Appendix: Version Matrix

| Component | 2.10 | 3.0 | Change | Impact |
|-----------|------|-----|--------|--------|
| PHP | 7.1 | 8.5 | Major upgrade | Full compatibility break |
| Symfony | 4.4 LTS | 7.4 LTS | 3 versions forward | Significant API changes |
| MySQL | 5.6.51 | 11.8 (MariaDB) | Database engine change | Schema compatibility maintained |
| Doctrine | 2.6.x | 2.20.x | Major upgrade | Annotation → Attributes |
| Bootstrap | 3.4 | 3.4 | No change | Frontend compatible |
| Apache | 2.4.x | 2.4.x | No change | Configuration compatible |

---

**Last Updated:** October 23, 2026
**Status:** Release Candidate
**Maintainer:** Atos - Rennes Digital Factory

For updates and clarifications, see: https://github.com/atos-df-rennes/prevarisc-migration/
