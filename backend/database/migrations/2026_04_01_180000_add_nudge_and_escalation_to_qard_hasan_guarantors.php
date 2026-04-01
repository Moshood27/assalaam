<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('qard_hasan_guarantors')) {
            Schema::table('qard_hasan_guarantors', function (Blueprint $table) {
                if (!Schema::hasColumn('qard_hasan_guarantors', 'nudge_count')) {
                    $table->unsignedSmallInteger('nudge_count')->default(0)->after('responded_at');
                }
                if (!Schema::hasColumn('qard_hasan_guarantors', 'last_nudged_at')) {
                    $table->timestamp('last_nudged_at')->nullable()->after('nudge_count');
                }
                if (!Schema::hasColumn('qard_hasan_guarantors', 'escalated_at')) {
                    $table->timestamp('escalated_at')->nullable()->after('last_nudged_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('qard_hasan_guarantors')) {
            Schema::table('qard_hasan_guarantors', function (Blueprint $table) {
                if (Schema::hasColumn('qard_hasan_guarantors', 'escalated_at')) {
                    $table->dropColumn('escalated_at');
                }
                if (Schema::hasColumn('qard_hasan_guarantors', 'last_nudged_at')) {
                    $table->dropColumn('last_nudged_at');
                }
                if (Schema::hasColumn('qard_hasan_guarantors', 'nudge_count')) {
                    $table->dropColumn('nudge_count');
                }
            });
        }
    }
};
