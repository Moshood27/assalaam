<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('contributions')) {
            Schema::table('contributions', function (Blueprint $table) {
                try {
                    $table->unique('reference', 'contributions_reference_unique');
                } catch (\Throwable $e) {
                    // Ignore if it already exists
                }
                try {
                    $table->index(['user_id', 'scheme_id'], 'contributions_user_scheme_index');
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
                try {
                    $table->dropUnique('contributions_reference_unique');
                } catch (\Throwable $e) {
                    // Ignore if it doesn't exist
                }
                try {
                    $table->dropIndex('contributions_user_scheme_index');
                } catch (\Throwable $e) {
                    // Ignore if it doesn't exist
                }
            });
        }
    }
};
