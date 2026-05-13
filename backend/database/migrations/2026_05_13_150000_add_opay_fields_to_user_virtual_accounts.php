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
        Schema::table('user_virtual_accounts', function (Blueprint $table) {
            $table->string('opay_user_reference')->nullable()->index()->after('monnify_dva_data');
            $table->json('opay_dva_data')->nullable()->after('opay_user_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_virtual_accounts', function (Blueprint $table) {
            $table->dropColumn(['opay_user_reference', 'opay_dva_data']);
        });
    }
};
