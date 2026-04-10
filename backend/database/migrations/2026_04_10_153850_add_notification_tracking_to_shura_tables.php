<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agm_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('agm_sessions', 'voting_open_notified_at')) {
                $table->timestamp('voting_open_notified_at')->nullable();
            }
            $table->timestamp('results_notified_at')->nullable();
        });

        Schema::table('project_proposals', function (Blueprint $table) {
            $table->timestamp('voting_open_notified_at')->nullable();
            $table->timestamp('results_notified_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agm_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('agm_sessions', 'voting_open_notified_at')) {
                $table->dropColumn('voting_open_notified_at');
            }
            $table->dropColumn('results_notified_at');
        });

        Schema::table('project_proposals', function (Blueprint $table) {
            $table->dropColumn(['voting_open_notified_at', 'results_notified_at']);
        });
    }
};
