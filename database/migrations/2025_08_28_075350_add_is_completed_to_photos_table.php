<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->boolean('is_completed')->default(false)->after('caption');
        });

        // Mark existing photos with thumbnails as completed
        // This ensures no disruption to currently visible photos
        DB::table('photos')
            ->whereNotNull('thumbnail_path')
            ->update(['is_completed' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('is_completed');
        });
    }
};
