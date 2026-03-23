<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'passport_path')) {
                $table->string('passport_path')->nullable()->after('dva_account_name');
                $table->index('passport_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'passport_path')) {
                $table->dropIndex(['passport_path']);
                $table->dropColumn('passport_path');
            }
        });
    }
};
