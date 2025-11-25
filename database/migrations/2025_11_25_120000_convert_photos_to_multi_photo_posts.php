<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration converts single-photo structure to multi-photo posts:
     * 1. Creates posts table
     * 2. Migrates existing photo data to posts (BEFORE dropping columns)
     * 3. Adds post_id and position to photos
     * 4. Links photos to their posts
     * 5. Drops caption/is_completed from photos (data already preserved)
     */
    public function up(): void
    {
        // Step 1: Create posts table
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

        // Step 2: Check if we need to migrate data (columns still exist)
        $hasCaptionColumn = Schema::hasColumn('photos', 'caption');
        $hasIsCompletedColumn = Schema::hasColumn('photos', 'is_completed');

        if ($hasCaptionColumn && $hasIsCompletedColumn) {
            // Step 2a: Migrate existing photos to posts, preserving IDs for URL compatibility
            DB::statement('
                INSERT INTO posts (id, user_id, caption, display_date, is_completed, created_at, updated_at)
                SELECT id, user_id, caption, COALESCE(taken_at, created_at), is_completed, created_at, updated_at
                FROM photos
            ');
        }

        // Step 3: Add post relationship columns to photos table
        Schema::table('photos', function (Blueprint $table) {
            if (!Schema::hasColumn('photos', 'post_id')) {
                $table->foreignId('post_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('photos', 'position')) {
                $table->unsignedInteger('position')->default(0)->after('post_id');
            }
        });

        // Step 4: Link photos to their corresponding posts
        DB::statement('UPDATE photos SET post_id = id, position = 0 WHERE post_id IS NULL');

        // Step 5: Now it's safe to drop the old columns from photos
        Schema::table('photos', function (Blueprint $table) {
            if (Schema::hasColumn('photos', 'caption')) {
                $table->dropColumn('caption');
            }
            if (Schema::hasColumn('photos', 'is_completed')) {
                $table->dropColumn('is_completed');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This will restore the old structure but data may be lost
     * if multi-photo posts were created after this migration.
     */
    public function down(): void
    {
        // Step 1: Re-add caption and is_completed to photos
        Schema::table('photos', function (Blueprint $table) {
            if (!Schema::hasColumn('photos', 'caption')) {
                $table->text('caption')->nullable();
            }
            if (!Schema::hasColumn('photos', 'is_completed')) {
                $table->boolean('is_completed')->default(false);
            }
        });

        // Step 2: Restore photo data from posts (only first photo in each post)
        DB::statement('
            UPDATE photos p
            INNER JOIN posts po ON p.post_id = po.id
            SET p.caption = po.caption, p.is_completed = po.is_completed
            WHERE p.position = 0
        ');

        // Step 3: Drop post relationship columns
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
};
