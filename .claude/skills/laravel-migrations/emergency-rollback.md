# Emergency Rollback Procedure

When data loss occurs in production after a migration, follow this procedure to recover.

## Prerequisites

This procedure assumes:
- Your deployment script backs up the database before migrations (verify with `update-photo-album.sh` or equivalent)
- You have SSH access to production server
- You have database credentials for production

## Step 1: Assess the Damage

SSH into production and check if data loss occurred:

```bash
cd ~/domains/[your-domain]/public_html

php artisan tinker
```

Run diagnostic queries:

```php
// Check if data exists in new structure
>>> \App\Models\Post::count()
// Expected: Should match old photo count

>>> \App\Models\Post::whereNotNull('caption')->count()
// If this is 0 and you had captions before, data was lost

>>> \App\Models\Photo::count()
// Verify photos still exist

// Check migration status
>>> exit
```

```bash
php artisan migrate:status
```

**Decision Point:**
- If data exists in new structure → Migration succeeded, no rollback needed
- If data is missing → Proceed to Step 2

## Step 2: Stop the Application

Prevent users from making changes during recovery:

```bash
# Put application in maintenance mode
php artisan down --message="Performing emergency maintenance" --retry=300
```

This shows users a maintenance page and tells them to retry in 5 minutes.

## Step 3: Locate Database Backup

Find the most recent backup taken before the migration:

```bash
# List backups with timestamps (most recent first)
ls -lth ~/backups/ | head -10
```

Look for a backup file created just before the migration, typically named:
- `backup-YYYY-MM-DD-HHMMSS.sql`
- `database-YYYY-MM-DD.sql`
- `[database-name]-YYYY-MM-DD.sql`

**Verify backup timestamp:**
```bash
# Check backup file timestamp
stat ~/backups/backup-2025-11-26.sql

# Optionally, inspect backup to verify it has data
head -n 50 ~/backups/backup-2025-11-26.sql
```

## Step 4: Create a Safety Backup

Before restoring, backup the current (broken) state:

```bash
# Create safety backup
mysqldump -u [username] -p [database_name] > ~/backups/pre-restore-$(date +%Y%m%d-%H%M%S).sql

# Or for other databases:
# PostgreSQL:
pg_dump -U [username] [database_name] > ~/backups/pre-restore-$(date +%Y%m%d-%H%M%S).sql

# SQLite:
cp database/database.sqlite database/database.sqlite.backup
```

This allows you to investigate what went wrong later.

## Step 5: Restore Database from Backup

### MySQL

```bash
mysql -u [username] -p [database_name] < ~/backups/backup-2025-11-26.sql
```

Enter password when prompted.

### PostgreSQL

```bash
psql -U [username] -d [database_name] -f ~/backups/backup-2025-11-26.sql
```

### SQLite

```bash
cp ~/backups/database.sqlite.backup database/database.sqlite
```

**Verify restoration:**

```bash
php artisan tinker
```

```php
>>> \App\Models\Photo::whereNotNull('caption')->count()
// Should show count > 0 if you had captions

>>> \App\Models\Photo::count()
// Should match expected photo count
```

## Step 6: Roll Back Problematic Migration(s)

After restoring data, roll back the migration(s) that caused the issue:

```bash
# Check which migrations are currently applied
php artisan migrate:status

# Roll back the problematic migration
# Use --step to roll back specific number of migrations
php artisan migrate:rollback --step=1

# Or roll back to specific batch
php artisan migrate:rollback --batch=5
```

**Verify migration status:**

```bash
php artisan migrate:status
# The problematic migration should show "Pending" now
```

**Verify database structure:**

```bash
php artisan tinker
```

```php
>>> Schema::hasTable('posts')
// Should be false if you rolled back posts table creation

>>> Schema::hasColumn('photos', 'caption')
// Should be true if caption column was restored

>>> \App\Models\Photo::whereNotNull('caption')->count()
// Verify data is accessible
```

## Step 7: Deploy Corrected Migration

### 7a: Pull Corrected Code

```bash
# Pull the corrected migration from repository
git fetch origin
git pull origin main

# Verify the corrected migration file exists
ls -l database/migrations/
```

### 7b: Review Migration Before Running

```bash
# Review the corrected migration
cat database/migrations/2025_11_25_120000_convert_photos_to_multi_photo_posts.php
```

Verify it has:
- [ ] Idempotency checks (`hasTable`, `hasColumn`)
- [ ] Data migration BEFORE column drops
- [ ] Full rollback implementation

### 7c: Run Corrected Migration

```bash
php artisan migrate --force
```

The `--force` flag is required in production environment.

**Monitor output carefully** - it should complete without errors.

## Step 8: Verify Data Integrity

```bash
php artisan tinker
```

Run comprehensive verification:

```php
// Verify migration completed
>>> \App\Models\Post::count()
// Should match photo count

>>> \App\Models\Post::whereNotNull('caption')->count()
// Should match number of photos that had captions

>>> \App\Models\Photo::whereNull('post_id')->count()
// Should be 0 (all photos linked to posts)

// Sample data to verify correctness
>>> \App\Models\Post::inRandomOrder()->limit(5)->get(['id', 'caption', 'display_date'])

>>> \App\Models\Photo::with('post')->inRandomOrder()->limit(5)->get()

// Check for orphaned records
>>> \App\Models\Post::whereDoesntHave('photos')->count()
// Should be 0 unless your logic allows empty posts
```

## Step 9: Application-Level Testing

Test critical functionality:

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Check logs
tail -n 100 storage/logs/laravel.log
```

Visit application in browser:
- [ ] Home page loads
- [ ] Individual posts display correctly
- [ ] Photo upload works
- [ ] Edit functionality works
- [ ] No JavaScript console errors

## Step 10: Bring Application Back Online

```bash
# Remove maintenance mode
php artisan up
```

Monitor logs for the next 30 minutes:

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log
```

## Step 11: Post-Mortem Documentation

Document what went wrong:

```bash
# Create incident report
cat > ~/incident-reports/migration-$(date +%Y%m%d).md << 'EOF'
# Migration Incident - [DATE]

## What Happened
- Migration ran with incorrect column drop ordering
- Data loss occurred in [table/columns]
- Discovered at [TIME]

## Impact
- Duration: X minutes in maintenance mode
- Data affected: [describe]
- Users affected: [number or "all"]

## Root Cause
- Migration dropped columns before migrating data
- Incorrect order: Create → Drop → Migrate (should be Create → Migrate → Drop)

## Resolution
- Restored from backup: ~/backups/backup-[DATE].sql
- Rolled back migration
- Deployed corrected migration
- Verified data integrity

## Prevention
- Implemented Laravel Safe Migrations skill
- Added migration testing checklist
- Required code review for data migrations

## Timeline
- [TIME]: Migration deployed
- [TIME]: Data loss discovered
- [TIME]: Maintenance mode enabled
- [TIME]: Database restored
- [TIME]: Migration rolled back
- [TIME]: Corrected migration deployed
- [TIME]: Application back online

EOF
```

## Common Issues During Recovery

### Issue: "Can't find backup file"

**Solution:**
```bash
# Search for any .sql files
find ~ -name "*.sql" -type f -mtime -7

# Check deployment script for backup location
cat update-photo-album.sh | grep backup
```

### Issue: "Migration rollback fails"

**Solution:**
```bash
# Check migration status first
php artisan migrate:status

# If rollback fails, restore database from backup first
mysql -u user -p database < ~/backups/backup.sql

# Then migration status will reflect pre-migration state
```

### Issue: "Database restore fails with foreign key errors"

**Solution:**
```bash
# For MySQL, disable foreign key checks during restore
mysql -u [username] -p [database_name] -e "SET FOREIGN_KEY_CHECKS=0; SOURCE ~/backups/backup.sql; SET FOREIGN_KEY_CHECKS=1;"
```

### Issue: "Application shows 500 errors after restore"

**Solution:**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerate optimized files
php artisan optimize

# Check logs for specific error
tail -n 50 storage/logs/laravel.log
```

### Issue: "Some data is still missing after restore"

**Problem:** Backup might be older than expected

**Solution:**
```bash
# Check backup file timestamp
stat ~/backups/backup-2025-11-26.sql

# Find older backups
ls -lt ~/backups/

# If data created between backup and migration, it's lost
# Restore most recent backup and manually re-enter missing data
```

## Prevention Checklist

After resolving the incident, implement these safeguards:

- [ ] Add pre-migration database backup verification to deployment script
- [ ] Require migration code review before deployment
- [ ] Test migrations with production-like data locally
- [ ] Test rollback locally before deploying
- [ ] Document all data migrations with analysis comments
- [ ] Use the Laravel Safe Migrations skill for future data migrations
- [ ] Add automated tests for critical migrations
- [ ] Set up database backup monitoring/alerts

## Quick Reference Command Sheet

```bash
# Assessment
php artisan migrate:status
php artisan tinker -> Model::count()

# Backup current state
mysqldump -u user -p database > ~/backups/pre-restore-$(date +%Y%m%d-%H%M%S).sql

# Restore from backup
mysql -u user -p database < ~/backups/backup-YYYY-MM-DD.sql

# Rollback migration
php artisan migrate:rollback --step=1

# Deploy fix
git pull origin main
php artisan migrate --force

# Verify
php artisan tinker -> Model::count()
tail -f storage/logs/laravel.log

# Maintenance mode
php artisan down    # Enable
php artisan up      # Disable
```

## When to Contact Database Administrator

Escalate to a DBA if:
- Database restore fails multiple times
- Foreign key constraints prevent restore
- Data corruption is suspected
- Large production database (>1GB) needs restoration
- You're unsure about backup file integrity
- Multiple migrations need coordinated rollback

## Contact Information Template

Keep this information readily accessible:

```
Database Administrator: [Name]
Phone: [Number]
Email: [Email]
Slack/Discord: [Handle]

Hosting Provider Support:
Phone: [Number]
Account ID: [ID]
Emergency Ticket System: [URL]

Backup Storage Location: ~/backups/
Database Name: [name]
Database User: [username]
```
