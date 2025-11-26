# Migration Testing Checklist

## Local Testing Sequence

Run these tests in order before deploying to production:

### Test 1: Fresh Migration

Simulates first-time deployment on a fresh database.

```bash
php artisan migrate:fresh --seed
```

**Verify:**
- [ ] Migration completes without errors
- [ ] All tables created successfully
- [ ] Data exists in new structure
- [ ] Relationships are properly linked
- [ ] No data loss occurred

**Check data integrity:**
```bash
php artisan tinker
>>> \App\Models\Post::count()          // Should match old photo count
>>> \App\Models\Post::whereNotNull('caption')->count()  // Verify data migrated
>>> \App\Models\Photo::whereNull('post_id')->count()    // Should be 0
```

### Test 2: Rollback

Verifies the `down()` method works correctly.

```bash
php artisan migrate:rollback
```

**Verify:**
- [ ] Rollback completes without errors
- [ ] Old structure restored (columns back)
- [ ] Data restored to original location
- [ ] No data loss during rollback
- [ ] Database state matches pre-migration

**Check restored data:**
```bash
php artisan tinker
>>> \App\Models\Photo::whereNotNull('caption')->count()  // Data back in photos
>>> Schema::hasTable('posts')  // Should be false
```

### Test 3: Re-Migration

Tests that migration works on existing data.

```bash
php artisan migrate
```

**Verify:**
- [ ] Migration completes without errors
- [ ] Data migrated correctly again
- [ ] No duplicate entries created
- [ ] All relationships intact

### Test 4: Idempotency

Tests that migration can run multiple times safely.

```bash
php artisan migrate  # Run again
```

**Verify:**
- [ ] No errors occur
- [ ] No duplicate data
- [ ] All existence checks working
- [ ] Database state unchanged

## Production Testing Checklist

Before deploying to production, verify:

### Code Quality

- [ ] **Analysis**: Migration has detailed comment block explaining the change
- [ ] **Ordering**: Data migration happens BEFORE column drops
- [ ] **Idempotency**: All operations check for existence first
  - [ ] `Schema::hasTable()` before creating tables
  - [ ] `Schema::hasColumn()` before adding columns
  - [ ] `Schema::hasColumn()` before data migration
  - [ ] `Schema::hasColumn()` before dropping columns
- [ ] **Compatibility**: No database-specific SQL syntax
  - [ ] No `SET FOREIGN_KEY_CHECKS`
  - [ ] No `INNER JOIN` in `UPDATE` statements
  - [ ] Uses Laravel query builder or cross-database SQL
- [ ] **Rollback**: `down()` method fully implemented and tested
- [ ] **Foreign Keys**: Properly dropped before dropping columns

### Data Safety

- [ ] **Data Preservation**: All data from dropped columns is migrated
- [ ] **Data Validation**: Migration includes integrity checks
- [ ] **URL Compatibility**: IDs preserved if needed for URLs
- [ ] **NULL Handling**: COALESCE or equivalent for nullable fields
- [ ] **Data Types**: Target columns can hold source data

### Testing Coverage

- [ ] **Fresh Install**: Tested with `migrate:fresh`
- [ ] **Rollback**: Tested with `migrate:rollback`
- [ ] **Re-migration**: Tested second `migrate` after rollback
- [ ] **Idempotency**: Tested running migration twice
- [ ] **With Real Data**: Tested with production-like data volume
- [ ] **Database Engine**: Tested on same DB as production (MySQL/PostgreSQL)

### Deployment Preparation

- [ ] **Backup Verified**: Production backup system tested and working
- [ ] **Backup Timing**: Deployment script backs up DB before migration
- [ ] **Rollback Plan**: Emergency rollback procedure documented
- [ ] **Monitoring**: Plan to check data integrity after deployment
- [ ] **Review**: Another developer reviewed the migration

## Database Engine Compatibility Tests

### MySQL/PostgreSQL Differences

If production uses MySQL but local uses SQLite:

```bash
# Test on MySQL locally if possible
DB_CONNECTION=mysql php artisan migrate:fresh --seed
DB_CONNECTION=mysql php artisan migrate:rollback
DB_CONNECTION=mysql php artisan migrate
```

**Common SQLite limitations to avoid:**
- `INNER JOIN` in `UPDATE` statements (use subqueries instead)
- `SET FOREIGN_KEY_CHECKS` (MySQL-specific)
- `ALTER TABLE` limitations (SQLite can't modify columns easily)

## Post-Deployment Verification

After deploying to production:

### Immediate Checks (Within 5 minutes)

```bash
# SSH into production
cd ~/domains/[your-domain]/public_html

# Check migration status
php artisan migrate:status

# Verify data integrity
php artisan tinker
>>> \App\Models\Post::count()
>>> \App\Models\Post::whereNotNull('caption')->count()
>>> \App\Models\Photo::whereNull('post_id')->count()  // Should be 0
```

### Application Testing (Within 15 minutes)

- [ ] Visit home page - loads without errors
- [ ] View individual posts - shows correctly
- [ ] Upload new photo - works properly
- [ ] Edit existing post - data persists
- [ ] Check logs for errors: `tail -f storage/logs/laravel.log`

### Data Sampling (Within 30 minutes)

```bash
php artisan tinker
# Sample 10 random posts
>>> \App\Models\Post::inRandomOrder()->limit(10)->get(['id', 'caption', 'display_date'])

# Verify photo-post relationships
>>> \App\Models\Photo::with('post')->inRandomOrder()->limit(5)->get()

# Check for orphaned records
>>> \App\Models\Photo::whereNull('post_id')->count()  // Should be 0
>>> \App\Models\Post::whereDoesntHave('photos')->count()  // Depends on your logic
```

## Automated Testing Integration

### PHPUnit/Pest Tests

Create migration test:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

it('migrates photo data to posts correctly', function () {
    // Create test photo with caption
    $photo = Photo::factory()->create([
        'caption' => 'Test caption',
        'is_completed' => true,
    ]);

    // Run migration
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_11_25_120000_convert_photos_to_multi_photo_posts.php'
    ]);

    // Verify post created
    $post = Post::find($photo->id);
    expect($post)->not->toBeNull()
        ->and($post->caption)->toBe('Test caption')
        ->and($post->is_completed)->toBeTrue();

    // Verify photo linked
    $photo->refresh();
    expect($photo->post_id)->toBe($post->id);
})->uses(RefreshDatabase::class);

it('rolls back migration correctly', function () {
    // Run migration first
    Artisan::call('migrate');

    // Run rollback
    Artisan::call('migrate:rollback');

    // Verify old structure restored
    expect(Schema::hasTable('posts'))->toBeFalse()
        ->and(Schema::hasColumn('photos', 'caption'))->toBeTrue();
})->uses(RefreshDatabase::class);
```

## Common Test Failures and Solutions

### "SQLSTATE[HY000]: General error: 1 near 'SET'"

**Problem**: Using MySQL-specific syntax on SQLite
**Solution**: Replace with Laravel query builder or cross-database SQL

### "Column not found: 1054 Unknown column 'caption'"

**Problem**: Trying to migrate data after column already dropped
**Solution**: Reorder steps - migrate data BEFORE dropping columns

### "Duplicate entry '1' for key 'PRIMARY'"

**Problem**: Running data migration twice without idempotency check
**Solution**: Add `WHERE` clause to prevent re-insertion

```php
// Before (runs multiple times)
DB::statement('INSERT INTO posts SELECT * FROM photos');

// After (idempotent)
DB::statement('
    INSERT INTO posts SELECT * FROM photos
    WHERE NOT EXISTS (SELECT 1 FROM posts WHERE posts.id = photos.id)
');
```

### "Cannot add foreign key constraint"

**Problem**: Trying to add foreign key when referenced table doesn't exist
**Solution**: Check table existence and create in correct order

```php
if (!Schema::hasTable('posts')) {
    Schema::create('posts', function (Blueprint $table) {
        // Create posts first
    });
}

Schema::table('photos', function (Blueprint $table) {
    // Then add foreign key to posts
    $table->foreignId('post_id')->constrained();
});
```

## Testing Anti-Patterns

### ❌ Don't Do This

```php
// Testing only fresh migration
php artisan migrate:fresh  // ✗ Not enough

// Assuming production is fresh
// ✗ Production has existing data!

// Skipping rollback test
// ✗ You won't know if it works until emergency

// Testing only on SQLite when production is MySQL
// ✗ Different SQL dialects will fail in production
```

### ✅ Do This Instead

```php
// Test complete cycle
php artisan migrate:fresh --seed
php artisan migrate:rollback
php artisan migrate
php artisan migrate  # Test idempotency

// Test with production-like data
php artisan db:seed --class=ProductionLikeSeeder

// Test on production database engine
DB_CONNECTION=mysql php artisan migrate:fresh
```
