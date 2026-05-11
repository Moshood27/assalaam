<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'flw_dva_account_number')) {
                $table->string('flw_dva_account_number')->nullable()->unique()->after('dva_verification_meta');
            }
            if (!Schema::hasColumn('users', 'flw_dva_account_name')) {
                $table->string('flw_dva_account_name')->nullable()->after('flw_dva_account_number');
            }
            if (!Schema::hasColumn('users', 'flw_dva_bank_name')) {
                $table->string('flw_dva_bank_name')->nullable()->after('flw_dva_account_name');
            }
            if (!Schema::hasColumn('users', 'flw_dva_bank_code')) {
                $table->string('flw_dva_bank_code')->nullable()->after('flw_dva_bank_name');
            }
            if (!Schema::hasColumn('users', 'flw_dva_order_ref')) {
                $table->string('flw_dva_order_ref')->nullable()->after('flw_dva_bank_code');
            }
            if (!Schema::hasColumn('users', 'flw_dva_flw_ref')) {
                $table->string('flw_dva_flw_ref')->nullable()->after('flw_dva_order_ref');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'flw_dva_account_number',
                'flw_dva_account_name',
                'flw_dva_bank_name',
                'flw_dva_bank_code',
                'flw_dva_order_ref',
                'flw_dva_flw_ref',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
