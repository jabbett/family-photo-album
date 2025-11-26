# Migration Code Examples

## Complete Migration Structure

### Full Example: Table Restructuring with Data Preservation

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migration: Convert single-photo structure to multi-photo posts
     *
     * Current state:
     * - photos table has: caption, is_completed
     * - One photo = one post
     *
     * Target state:
     * - posts table has: caption, is_completed
     * - photos table has: post_id, position
     * - One post can have many photos
     *
     * Data to preserve:
     * - photos.caption → posts.caption
     * - photos.is_completed → posts.is_completed
     * - photos.id → posts.id (for URL compatibility)
     */
    public function up(): void
    {
        // Step 1: Create posts table (if not exists)
        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('caption')->nullable();
                $table->timestamp('display_date')->nullable()->index();
                $table->boolean('is_completed')->default(false);
                $table->timestamps();
            });
        }

        // Step 2: Migrate data BEFORE dropping columns
        $hasCaptionColumn = Schema::hasColumn('photos', 'caption');
        $hasIsCompletedColumn = Schema::hasColumn('photos', 'is_completed');

        if ($hasCaptionColumn && $hasIsCompletedColumn) {
            // Data migration happens while old columns still exist
            DB::statement('
                INSERT INTO posts (id, user_id, caption, display_date, is_completed, created_at, updated_at)
                SELECT id, user_id, caption, COALESCE(taken_at, created_at), is_completed, created_at, updated_at
                FROM photos
            ');
        }

        // Step 3: Add new columns (idempotent)
        Schema::table('photos', function (Blueprint $table) {
            if (!Schema::hasColumn('photos', 'post_id')) {
                $table->foreignId('post_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('photos', 'position')) {
                $table->unsignedInteger('position')->default(0)->after('post_id');
            }
        });

        // Step 4: Link relationships
        DB::statement('UPDATE photos SET post_id = id, position = 0 WHERE post_id IS NULL');

        // Step 5: Drop old columns (LAST, after data is safe)
        Schema::table('photos', function (Blueprint $table) {
            if (Schema::hasColumn('photos', 'caption')) {
                $table->dropColumn('caption');
            }
            if (Schema::hasColumn('photos', 'is_completed')) {
                $table->dropColumn('is_completed');
            }
        });
    }

    public function down(): void
    {
        // Reverse the migration (see rollback-patterns.md)
        Schema::table('photos', function (Blueprint $table) {
            if (!Schema::hasColumn('photos', 'caption')) {
                $table->text('caption')->nullable();
            }
            if (!Schema::hasColumn('photos', 'is_completed')) {
                $table->boolean('is_completed')->default(false);
            }
        });

        DB::statement('
            UPDATE photos p
            INNER JOIN posts po ON p.post_id = po.id
            SET p.caption = po.caption, p.is_completed = po.is_completed
            WHERE p.position = 0
        ');

        Schema::table('photos', function (Blueprint $table) {
            if (Schema::hasColumn('photos', 'post_id')) {
                $table->dropForeign(['post_id']);
                $table->dropColumn('post_id');
            }
            if (Schema::hasColumn('photos', 'position')) {
                $table->dropColumn('position');
            }
        });

        Schema::dropIfExists('posts');
    }
};
```

## Common Patterns

### Pattern 1: Moving Column Between Tables

```php
// CORRECT: Migrate data before dropping column
if (Schema::hasColumn('old_table', 'column_name')) {
    // First: Copy data to new location
    DB::statement('
        INSERT INTO new_table (id, column_name)
        SELECT id, column_name FROM old_table
    ');

    // Then: Drop old column
    Schema::table('old_table', function (Blueprint $table) {
        $table->dropColumn('column_name');
    });
}
```

❌ **WRONG** - This loses data:
```php
// Don't do this!
Schema::table('old_table', function (Blueprint $table) {
    $table->dropColumn('column_name');  // Data lost here!
});

// Too late - column already gone
DB::statement('INSERT INTO new_table...');
```

### Pattern 2: Renaming Column with Data Transformation

```php
// Step 1: Add new column
Schema::table('users', function (Blueprint $table) {
    if (!Schema::hasColumn('users', 'full_name')) {
        $table->string('full_name')->nullable()->after('last_name');
    }
});

// Step 2: Migrate/transform data
DB::statement("
    UPDATE users
    SET full_name = CONCAT(first_name, ' ', last_name)
    WHERE full_name IS NULL
");

// Step 3: Drop old columns (only after data migrated)
Schema::table('users', function (Blueprint $table) {
    if (Schema::hasColumn('users', 'first_name')) {
        $table->dropColumn(['first_name', 'last_name']);
    }
});
```

### Pattern 3: Preserving IDs for URL Compatibility

When restructuring data, preserve IDs to keep existing URLs working:

```php
// Preserve photo IDs as post IDs
DB::statement('
    INSERT INTO posts (id, user_id, caption, created_at, updated_at)
    SELECT id, user_id, caption, created_at, updated_at
    FROM photos
');
```

This ensures `/photo/123` continues working after migration.

### Pattern 4: Adding Relationship Column

```php
// Step 1: Add nullable foreign key
Schema::table('photos', function (Blueprint $table) {
    if (!Schema::hasColumn('photos', 'post_id')) {
        $table->foreignId('post_id')->nullable()->constrained();
    }
});

// Step 2: Populate the relationship
DB::statement('UPDATE photos SET post_id = id WHERE post_id IS NULL');

// Step 3: Optional - Make not nullable after data migration
Schema::table('photos', function (Blueprint $table) {
    $table->foreignId('post_id')->nullable(false)->change();
});
```

### Pattern 5: Conditional Data Migration

Only migrate data if it hasn't been migrated yet:

```php
// Check if migration already ran
$needsMigration = Schema::hasColumn('old_table', 'old_column')
    && DB::table('new_table')->count() === 0;

if ($needsMigration) {
    DB::statement('
        INSERT INTO new_table (column_name)
        SELECT old_column FROM old_table
    ');
}
```

## Idempotency Checks

### Table-Level Checks

```php
// Create table only if it doesn't exist
if (!Schema::hasTable('posts')) {
    Schema::create('posts', function (Blueprint $table) {
        // ...
    });
}

// Drop table only if it exists
Schema::dropIfExists('posts');
```

### Column-Level Checks

```php
// Add column only if missing
Schema::table('photos', function (Blueprint $table) {
    if (!Schema::hasColumn('photos', 'post_id')) {
        $table->foreignId('post_id')->nullable();
    }
});

// Drop column only if exists
Schema::table('photos', function (Blueprint $table) {
    if (Schema::hasColumn('photos', 'caption')) {
        $table->dropColumn('caption');
    }
});

// Check multiple columns
$hasCaptionColumn = Schema::hasColumn('photos', 'caption');
$hasIsCompletedColumn = Schema::hasColumn('photos', 'is_completed');

if ($hasCaptionColumn && $hasIsCompletedColumn) {
    // Safe to migrate data
}
```

### Foreign Key Handling

```php
// Drop foreign key before dropping column
Schema::table('photos', function (Blueprint $table) {
    if (Schema::hasColumn('photos', 'post_id')) {
        $table->dropForeign(['post_id']);
        $table->dropColumn('post_id');
    }
});
```

## Database-Agnostic Queries

### ✅ Cross-Database Compatible

```php
// Use Laravel query builder
DB::table('posts')
    ->join('photos', 'posts.id', '=', 'photos.post_id')
    ->update(['posts.caption' => DB::raw('photos.caption')]);

// Use COALESCE for null handling (works in MySQL, PostgreSQL, SQLite)
DB::statement('
    INSERT INTO posts (display_date)
    SELECT COALESCE(taken_at, created_at) FROM photos
');
```

### ❌ Database-Specific (Avoid)

```php
// Don't use MySQL-specific syntax
DB::statement('SET FOREIGN_KEY_CHECKS=0');  // MySQL only

// Don't use MySQL-specific UPDATE with JOIN
DB::statement('
    UPDATE photos p
    INNER JOIN posts po ON p.post_id = po.id
    SET p.caption = po.caption
');  // Fails on SQLite
```

## Data Validation During Migration

```php
// Verify data integrity after migration
$photosCount = DB::table('photos')->count();
$postsCount = DB::table('posts')->count();

if ($photosCount !== $postsCount) {
    throw new \Exception("Data migration failed: photo/post count mismatch");
}

// Verify no data loss
$nullCaptions = DB::table('posts')->whereNull('caption')->count();
$originalNulls = DB::table('photos')->whereNull('caption')->count();

if ($nullCaptions > $originalNulls) {
    throw new \Exception("Data loss detected during caption migration");
}
```
