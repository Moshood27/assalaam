<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'flw_dva_data')) {
                $table->json('flw_dva_data')->nullable()->after('dva_verification_meta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'flw_dva_data')) {
                $table->dropColumn('flw_dva_data');
            }
        });
    }
};
