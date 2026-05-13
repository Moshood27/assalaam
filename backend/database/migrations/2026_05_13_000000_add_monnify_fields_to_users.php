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
            if (!Schema::hasColumn('users', 'monnify_customer_reference')) {
                $table->string('monnify_customer_reference')->nullable()->after('paystack_customer_code');
            }
            if (!Schema::hasColumn('users', 'monnify_dva_data')) {
                $table->json('monnify_dva_data')->nullable()->after('flw_dva_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['monnify_customer_reference', 'monnify_dva_data']);
        });
    }
};
