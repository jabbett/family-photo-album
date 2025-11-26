# Migration Rollback Patterns

The `down()` method must completely reverse the `up()` method's changes.

## Core Principle

**Rollback order is the reverse of migration order:**

```php
public function up(): void
{
    // 1. Create table
    // 2. Migrate data
    // 3. Add columns
    // 4. Link relationships
    // 5. Drop old columns
}

public function down(): void
{
    // 5. Re-add dropped columns
    // 4. Unlink relationships (restore data)
    // 3. Drop added columns
    // 2. Restore data to original location
    // 1. Drop created table
}
```

## Pattern 1: Reverse Table Creation

### Up: Create Table

```php
public function up(): void
{
    if (!Schema::hasTable('posts')) {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->text('caption')->nullable();
            $table->timestamps();
        });
    }
}
```

### Down: Drop Table

```php
public function down(): void
{
    Schema::dropIfExists('posts');
}
```

## Pattern 2: Reverse Column Addition

### Up: Add Columns

```php
public function up(): void
{
    Schema::table('photos', function (Blueprint $table) {
        if (!Schema::hasColumn('photos', 'post_id')) {
            $table->foreignId('post_id')->nullable()->constrained();
        }
        if (!Schema::hasColumn('photos', 'position')) {
            $table->unsignedInteger('position')->default(0);
        }
    });
}
```

### Down: Drop Columns (With Foreign Key Handling)

```php
public function down(): void
{
    Schema::table('photos', function (Blueprint $table) {
        // Drop foreign key BEFORE dropping column
        if (Schema::hasColumn('photos', 'post_id')) {
            $table->dropForeign(['post_id']);
            $table->dropColumn('post_id');
        }

        if (Schema::hasColumn('photos', 'position')) {
            $table->dropColumn('position');
        }
    });
}
```

## Pattern 3: Reverse Data Migration

### Up: Move Data to New Table

```php
public function up(): void
{
    if (Schema::hasColumn('photos', 'caption')) {
        DB::statement('
            INSERT INTO posts (id, caption, created_at)
            SELECT id, caption, created_at
            FROM photos
        ');

        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('caption');
        });
    }
}
```

### Down: Restore Data to Original Table

```php
public function down(): void
{
    // Step 1: Re-add the column
    Schema::table('photos', function (Blueprint $table) {
        if (!Schema::hasColumn('photos', 'caption')) {
            $table->text('caption')->nullable();
        }
    });

    // Step 2: Restore data from new table
    DB::statement('
        UPDATE photos p
        INNER JOIN posts po ON p.id = po.id
        SET p.caption = po.caption
    ');

    // Step 3: Drop new table
    Schema::dropIfExists('posts');
}
```

## Pattern 4: Reverse Column Transformation

### Up: Transform First Name + Last Name → Full Name

```php
public function up(): void
{
    // Add new column
    Schema::table('users', function (Blueprint $table) {
        if (!Schema::hasColumn('users', 'full_name')) {
            $table->string('full_name')->nullable();
        }
    });

    // Transform data
    DB::statement("
        UPDATE users
        SET full_name = CONCAT(first_name, ' ', last_name)
        WHERE full_name IS NULL
    ");

    // Drop old columns
    Schema::table('users', function (Blueprint $table) {
        if (Schema::hasColumn('users', 'first_name')) {
            $table->dropColumn(['first_name', 'last_name']);
        }
    });
}
```

### Down: Restore Original Columns

```php
public function down(): void
{
    // Re-add original columns
    Schema::table('users', function (Blueprint $table) {
        if (!Schema::hasColumn('users', 'first_name')) {
            $table->string('first_name')->nullable();
        }
        if (!Schema::hasColumn('users', 'last_name')) {
            $table->string('last_name')->nullable();
        }
    });

    // Reverse transformation (split full_name)
    DB::statement("
        UPDATE users
        SET
            first_name = SUBSTRING_INDEX(full_name, ' ', 1),
            last_name = SUBSTRING_INDEX(full_name, ' ', -1)
        WHERE first_name IS NULL
    ");

    // Drop transformed column
    Schema::table('users', function (Blueprint $table) {
        if (Schema::hasColumn('users', 'full_name')) {
            $table->dropColumn('full_name');
        }
    });
}
```

**Note**: This assumes full_name format is "First Last". Complex names may need more sophisticated parsing.

## Pattern 5: Complete Table Restructuring Rollback

### Up: Convert Photos to Multi-Photo Posts

```php
public function up(): void
{
    // Create posts table
    if (!Schema::hasTable('posts')) {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->text('caption')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    // Migrate data
    if (Schema::hasColumn('photos', 'caption')) {
        DB::statement('
            INSERT INTO posts (id, user_id, caption, is_completed, created_at, updated_at)
            SELECT id, user_id, caption, is_completed, created_at, updated_at
            FROM photos
        ');
    }

    // Add relationship columns
    Schema::table('photos', function (Blueprint $table) {
        if (!Schema::hasColumn('photos', 'post_id')) {
            $table->foreignId('post_id')->nullable()->constrained();
        }
        if (!Schema::hasColumn('photos', 'position')) {
            $table->unsignedInteger('position')->default(0);
        }
    });

    // Link relationships
    DB::statement('UPDATE photos SET post_id = id, position = 0 WHERE post_id IS NULL');

    // Drop old columns
    Schema::table('photos', function (Blueprint $table) {
        if (Schema::hasColumn('photos', 'caption')) {
            $table->dropColumn(['caption', 'is_completed']);
        }
    });
}
```

### Down: Restore Original Structure

```php
public function down(): void
{
    // Step 1: Re-add dropped columns to photos
    Schema::table('photos', function (Blueprint $table) {
        if (!Schema::hasColumn('photos', 'caption')) {
            $table->text('caption')->nullable();
        }
        if (!Schema::hasColumn('photos', 'is_completed')) {
            $table->boolean('is_completed')->default(false);
        }
    });

    // Step 2: Restore data from posts to photos
    DB::statement('
        UPDATE photos p
        INNER JOIN posts po ON p.post_id = po.id
        SET p.caption = po.caption, p.is_completed = po.is_completed
        WHERE p.position = 0
    ');

    // Step 3: Drop relationship columns
    Schema::table('photos', function (Blueprint $table) {
        if (Schema::hasColumn('photos', 'post_id')) {
            $table->dropForeign(['post_id']);
            $table->dropColumn('post_id');
        }
        if (Schema::hasColumn('photos', 'position')) {
            $table->dropColumn('position');
        }
    });

    // Step 4: Drop posts table
    Schema::dropIfExists('posts');
}
```

## Rollback with Data Loss Warning

Sometimes rollback may lose data if the new structure allows scenarios the old one didn't.

### Example: Multi-Photo Posts → Single Photo

```php
public function down(): void
{
    // WARNING: This rollback will lose data if multiple photos exist per post

    // Re-add caption to photos
    Schema::table('photos', function (Blueprint $table) {
        if (!Schema::hasColumn('photos', 'caption')) {
            $table->text('caption')->nullable();
        }
    });

    // Restore caption only for first photo in each post
    DB::statement('
        UPDATE photos p
        INNER JOIN posts po ON p.post_id = po.id
        SET p.caption = po.caption
        WHERE p.position = 0  -- Only first photo gets caption
    ');
    // Photos at position > 0 lose their association to the caption!

    // Drop relationship
    Schema::table('photos', function (Blueprint $table) {
        if (Schema::hasColumn('photos', 'post_id')) {
            $table->dropForeign(['post_id']);
            $table->dropColumn(['post_id', 'position']);
        }
    });

    Schema::dropIfExists('posts');
}
```

**Document this clearly in migration comments:**

```php
/**
 * Reverse the migrations.
 *
 * WARNING: This rollback will restore the old structure but data may be lost
 * if multi-photo posts were created after this migration.
 * Only the first photo in each post will retain the caption.
 */
public function down(): void
{
    // Implementation...
}
```

## Foreign Key Rollback Considerations

### Order Matters for Foreign Keys

```php
public function up(): void
{
    // Add foreign key
    Schema::table('photos', function (Blueprint $table) {
        $table->foreignId('post_id')->constrained();
    });
}

public function down(): void
{
    Schema::table('photos', function (Blueprint $table) {
        // MUST drop foreign key BEFORE dropping column
        $table->dropForeign(['post_id']);
        $table->dropColumn('post_id');
    });

    // Can't drop posts table while foreign key exists
    Schema::dropIfExists('posts');
}
```

### Wrong Order (Will Fail)

```php
public function down(): void
{
    // ❌ This will fail!
    Schema::dropIfExists('posts');  // Error: Foreign key constraint exists

    Schema::table('photos', function (Blueprint $table) {
        $table->dropColumn('post_id');  // Too late
    });
}
```

## Testing Rollbacks

Always test rollback before deploying:

```bash
# Run migration
php artisan migrate

# Test rollback
php artisan migrate:rollback

# Verify data restored
php artisan tinker
>>> Schema::hasColumn('photos', 'caption')  // Should be true
>>> Schema::hasTable('posts')  // Should be false

# Test re-migration works after rollback
php artisan migrate
```

## Rollback Best Practices

1. **Always implement `down()`** - Never leave it empty
2. **Test rollback locally** - Don't find out in production it doesn't work
3. **Reverse the order** - Last operation in `up()` is first in `down()`
4. **Handle foreign keys** - Drop foreign keys before dropping columns
5. **Document data loss** - Warn if rollback can't fully restore state
6. **Use existence checks** - Make rollback idempotent too
7. **Match data types** - Restored columns should match originals

## When Rollback Isn't Possible

Some migrations can't be fully reversed:

### Data Transformations That Lose Information

```php
// UP: Store only year
DB::statement("UPDATE events SET year = YEAR(date)");
Schema::table('events', function (Blueprint $table) {
    $table->dropColumn('date');
});

// DOWN: Can't restore full date from year alone
Schema::table('events', function (Blueprint $table) {
    $table->date('date')->nullable();
});
DB::statement("UPDATE events SET date = CONCAT(year, '-01-01')");
// ⚠️ Lost month and day information!
```

### Destructive Operations

```php
// UP: Merge duplicate users
DB::statement('
    DELETE FROM users
    WHERE id NOT IN (
        SELECT MIN(id) FROM users GROUP BY email
    )
');

// DOWN: Can't restore deleted users
// ⚠️ Data permanently lost!
```

**For irreversible migrations:**
1. Document clearly that rollback is not possible
2. Implement partial rollback (restore what you can)
3. Require explicit database backup before running
4. Consider providing a data export before destructive operations

```php
/**
 * Reverse the migrations.
 *
 * WARNING: Cannot fully reverse this migration.
 * Users deleted during deduplication cannot be restored.
 * Ensure database backup exists before running this migration.
 */
public function down(): void
{
    // Restore table structure but not deleted data
}
```
