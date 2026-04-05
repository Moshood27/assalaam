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
        Schema::table('utility_transactions', function (Blueprint $table) {
            $table->string('order_id')->nullable()->after('reference')->index();
            $table->string('provider')->nullable()->after('order_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_transactions', function (Blueprint $table) {
            $table->dropColumn(['order_id', 'provider']);
        });
    }
};
