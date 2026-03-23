<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add is_defaulter flag to users if not exists
        if (!Schema::hasColumn('users', 'is_defaulter')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_defaulter')->default(false)->after('is_admin');
            });
        }

        // Pivot table for loan guarantors
        if (!Schema::hasTable('qard_hasan_guarantors')) {
            Schema::create('qard_hasan_guarantors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('qard_hasan_id')->constrained('qard_hasans')->cascadeOnDelete();
                $table->foreignId('guarantor_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['qard_hasan_id', 'guarantor_id'], 'qh_g_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('qard_hasan_guarantors')) {
            Schema::dropIfExists('qard_hasan_guarantors');
        }
        if (Schema::hasColumn('users', 'is_defaulter')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_defaulter');
            });
        }
    }
};
