---
name: laravel-safe-migrations
description: Create production-safe Laravel migrations that preserve existing data when modifying schema. Ensures correct ordering (Create → Migrate → Drop), idempotency, and full rollback support for column migrations and table restructuring.
---

# Laravel Safe Data Migrations

## When to Use This Skill

Use this skill for Laravel migrations that touch existing production data:
- Moving data between columns or tables
- Dropping columns that contain data
- Restructuring database relationships
- Transforming existing data formats

**Critical Rule**: If a migration modifies existing data, use this skill to prevent data loss.

## Core Principles

### 1. Migration Order is Critical

**✅ CORRECT ORDER:**
1. Create new tables/columns
2. **Migrate/copy data** (while old columns still exist)
3. Drop old columns/tables (data already preserved)

**❌ WRONG ORDER (causes data loss):**
1. Create new tables/columns
2. Drop old columns ← **DATA LOST!**
3. Try to migrate data ← **Too late!**

### 2. Idempotency

Migrations must be safe to run multiple times using existence checks:
- `Schema::hasTable('posts')` before creating
- `Schema::hasColumn('photos', 'post_id')` before adding
- Check if data migration already completed

### 3. Database Compatibility

- Use Laravel query builder over raw SQL
- Avoid database-specific syntax (MySQL `SET FOREIGN_KEY_CHECKS`, `INNER JOIN` in `UPDATE`)
- Test on same database engine as production

## 5-Step Implementation Process

### Step 1: Analyze the Change

Document what data exists, where it needs to move, and what will be dropped. See [analysis-template.md](analysis-template.md) for structure.

### Step 2: Plan Migration Steps

Write the order as comments before implementing:
```php
public function up(): void
{
    // Step 1: Create new table (if not exists)
    // Step 2: Migrate data BEFORE dropping columns
    // Step 3: Add new columns
    // Step 4: Link relationships
    // Step 5: Drop old columns (data already safe)
}
```

### Step 3: Implement with Safety Checks

Use conditional checks throughout. See [examples.md](examples.md) for complete patterns.

**Key safety checks:**
- `if (!Schema::hasTable('posts'))` before creating
- `if (Schema::hasColumn('photos', 'caption'))` before migrating
- `if (Schema::hasColumn('photos', 'caption'))` before dropping

### Step 4: Implement Rollback

The `down()` method must fully reverse changes. See [rollback-patterns.md](rollback-patterns.md).

### Step 5: Test Thoroughly

Run the complete test sequence:
```bash
php artisan migrate:fresh --seed  # Test fresh install
php artisan migrate:rollback      # Test rollback
php artisan migrate               # Test re-migration
php artisan migrate               # Test idempotency
```

See [testing-checklist.md](testing-checklist.md) for full verification steps.

## Red Flags (Migration Smells)

⚠️ **Warning signs that require this skill:**

1. **Multiple migrations for related changes** - Combine into one with correct ordering
2. **Dropping columns without data checks** - Migrate data first
3. **Database-specific SQL** - Use Laravel query builder
4. **Empty `down()` method** - Implement full rollback
5. **No existence checks** - Add idempotency checks

## Quick Reference

- **Common Patterns**: [examples.md](examples.md)
- **Testing Checklist**: [testing-checklist.md](testing-checklist.md)
- **Rollback Guide**: [rollback-patterns.md](rollback-patterns.md)
- **Emergency Recovery**: [emergency-rollback.md](emergency-rollback.md)

## Key Takeaways

1. **Order matters**: Create → Migrate → Drop (never reverse)
2. **Check everything**: Use `hasTable()`, `hasColumn()` for idempotency
3. **Test rollbacks**: Don't deploy until verified
4. **One migration**: Keep related changes together
5. **Document clearly**: Include analysis comments in migration file

---

*Created after a production incident where incorrect ordering caused data loss.*
