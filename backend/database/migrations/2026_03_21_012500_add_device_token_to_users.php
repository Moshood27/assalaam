<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'device_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('device_token', 255)->nullable()->after('phone');
                $table->index('device_token');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'device_token')) {
            Schema::table('users', function (Blueprint $table) {
                try { $table->dropIndex(['device_token']); } catch (\Throwable $e) {}
                $table->dropColumn('device_token');
            });
        }
    }
};
