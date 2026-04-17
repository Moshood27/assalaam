<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('member_applications', function (Blueprint $table) {
            $table->string('fcm_token')->nullable()->after('token');
            $table->string('device_token')->nullable()->after('fcm_token');
        });
    }

    public function down(): void
    {
        Schema::table('member_applications', function (Blueprint $table) {
            $table->dropColumn(['fcm_token', 'device_token']);
        });
    }
};
