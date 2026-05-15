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
        if (Schema::hasTable('contributions')) {
            Schema::table('contributions', function (Blueprint $table) {
                // Better composite index for common aggregate queries
                $table->index(['user_id', 'status', 'scheme_id'], 'idx_contrib_user_status_scheme');
            });
        }

        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                // Improve performance of history listings
                $table->index(['user_id', 'type', 'created_at'], 'idx_wallet_tx_user_type_created');
            });
        }

        if (Schema::hasTable('qard_hasans')) {
            Schema::table('qard_hasans', function (Blueprint $table) {
                // Improve performance for loan eligibility checks
                $table->index(['user_id', 'status'], 'idx_qard_user_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('contributions')) {
            Schema::table('contributions', function (Blueprint $table) {
                $table->dropIndex('idx_contrib_user_status_scheme');
            });
        }

        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropIndex('idx_wallet_tx_user_type_created');
            });
        }

        if (Schema::hasTable('qard_hasans')) {
            Schema::table('qard_hasans', function (Blueprint $table) {
                $table->dropIndex('idx_qard_user_status');
            });
        }
    }
};
