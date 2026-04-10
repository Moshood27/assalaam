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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('deceased_at');
            $table->timestamp('wellness_check_notified_at')->nullable()->after('last_activity_at');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->string('asset_type')->default('all')->after('percentage'); // all, shares, savings, takaful
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_activity_at', 'wellness_check_notified_at']);
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropColumn('asset_type');
        });
    }
};
