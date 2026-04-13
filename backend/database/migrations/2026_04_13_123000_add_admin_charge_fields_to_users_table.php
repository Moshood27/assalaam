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
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->decimal('admin_charge_balance', 15, 2)->default(0.00);
            $blueprint->boolean('admin_charge_auto_deduct')->default(true);
            $blueprint->timestamp('last_admin_charge_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['admin_charge_balance', 'admin_charge_auto_deduct', 'last_admin_charge_at']);
        });
    }
};
