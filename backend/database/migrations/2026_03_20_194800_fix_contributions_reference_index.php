<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('contributions')) {
            Schema::table('contributions', function (Blueprint $table) {
                // Remove incorrect unique constraint so multiple rows can share the same batch reference
                try {
                    $table->dropUnique('contributions_reference_unique');
                } catch (\Throwable $e) {
                    // Ignore if it doesn't exist
                }
                // Add a normal (non-unique) index for performance on lookups by reference
                try {
                    $table->index('reference', 'contributions_reference_index');
                } catch (\Throwable $e) {
                    // Ignore if it already exists
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contributions')) {
            Schema::table('contributions', function (Blueprint $table) {
                // Revert to previous (problematic) state: drop normal index and add unique
                try {
                    $table->dropIndex('contributions_reference_index');
                } catch (\Throwable $e) {
                    // Ignore if it doesn't exist
                }
                try {
                    $table->unique('reference', 'contributions_reference_unique');
                } catch (\Throwable $e) {
                    // Ignore if it already exists
                }
            });
        }
    }
};
