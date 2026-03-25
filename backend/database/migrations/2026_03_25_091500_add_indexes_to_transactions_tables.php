<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                // Speed up member transaction listings and type filtering
                $table->index(['user_id', 'created_at'], 'wt_user_created');
                $table->index(['type', 'created_at'], 'wt_type_created');
            });
        }

        if (Schema::hasTable('utility_transactions')) {
            Schema::table('utility_transactions', function (Blueprint $table) {
                // Speed up user listings and admin filters
                $table->index(['user_id', 'created_at'], 'ut_user_created');
                $table->index(['status', 'created_at'], 'ut_status_created');
                $table->index(['type', 'created_at'], 'ut_type_created');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropIndex('wt_user_created');
                $table->dropIndex('wt_type_created');
            });
        }

        if (Schema::hasTable('utility_transactions')) {
            Schema::table('utility_transactions', function (Blueprint $table) {
                $table->dropIndex('ut_user_created');
                $table->dropIndex('ut_status_created');
                $table->dropIndex('ut_type_created');
            });
        }
    }
};
