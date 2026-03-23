<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'paystack_customer_code')) {
                $table->string('paystack_customer_code')->nullable()->unique()->after('membership_number');
            }
            if (!Schema::hasColumn('users', 'dva_account_number')) {
                $table->string('dva_account_number')->nullable()->unique()->after('paystack_customer_code');
            }
            if (!Schema::hasColumn('users', 'dva_bank_name')) {
                $table->string('dva_bank_name')->nullable()->after('dva_account_number');
            }
            if (!Schema::hasColumn('users', 'dva_account_name')) {
                $table->string('dva_account_name')->nullable()->after('dva_bank_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dva_account_name')) {
                $table->dropColumn('dva_account_name');
            }
            if (Schema::hasColumn('users', 'dva_bank_name')) {
                $table->dropColumn('dva_bank_name');
            }
            if (Schema::hasColumn('users', 'dva_account_number')) {
                $table->dropColumn('dva_account_number');
            }
            if (Schema::hasColumn('users', 'paystack_customer_code')) {
                $table->dropColumn('paystack_customer_code');
            }
        });
    }
};
