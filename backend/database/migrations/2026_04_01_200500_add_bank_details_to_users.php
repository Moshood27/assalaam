<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('dva_verification_meta');
            }
            if (!Schema::hasColumn('users', 'bank_code')) {
                $table->string('bank_code', 20)->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('users', 'account_number')) {
                $table->string('account_number', 20)->nullable()->after('bank_code');
            }
            if (!Schema::hasColumn('users', 'account_name')) {
                $table->string('account_name')->nullable()->after('account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'account_name')) {
                $table->dropColumn('account_name');
            }
            if (Schema::hasColumn('users', 'account_number')) {
                $table->dropColumn('account_number');
            }
            if (Schema::hasColumn('users', 'bank_code')) {
                $table->dropColumn('bank_code');
            }
            if (Schema::hasColumn('users', 'bank_name')) {
                $table->dropColumn('bank_name');
            }
        });
    }
};
