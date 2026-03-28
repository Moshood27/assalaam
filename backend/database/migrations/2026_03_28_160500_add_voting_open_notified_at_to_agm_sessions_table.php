<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agm_sessions') && !Schema::hasColumn('agm_sessions', 'voting_open_notified_at')) {
            Schema::table('agm_sessions', function (Blueprint $table) {
                $table->timestamp('voting_open_notified_at')->nullable()->after('end_at');
                $table->index('voting_open_notified_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agm_sessions') && Schema::hasColumn('agm_sessions', 'voting_open_notified_at')) {
            Schema::table('agm_sessions', function (Blueprint $table) {
                try { $table->dropIndex(['voting_open_notified_at']); } catch (\Throwable $e) {}
                $table->dropColumn('voting_open_notified_at');
            });
        }
    }
};
