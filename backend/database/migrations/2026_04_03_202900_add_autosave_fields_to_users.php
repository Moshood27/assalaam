<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'paystack_authorization_code')) {
                $table->string('paystack_authorization_code')->nullable()->after('paystack_customer_code');
            }
            if (!Schema::hasColumn('users', 'autosave_enabled')) {
                $table->boolean('autosave_enabled')->default(false)->after('dva_account_name');
            }
            if (!Schema::hasColumn('users', 'autosave_amount')) {
                $table->decimal('autosave_amount', 12, 2)->default(5000.00)->after('autosave_enabled');
            }
            if (!Schema::hasColumn('users', 'autosave_weekday')) {
                // 0 = Sunday, 1 = Monday, ..., 5 = Friday, 6 = Saturday
                $table->tinyInteger('autosave_weekday')->default(5)->after('autosave_amount');
            }
            if (!Schema::hasColumn('users', 'autosave_last_run_at')) {
                $table->timestamp('autosave_last_run_at')->nullable()->after('autosave_weekday');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'autosave_last_run_at')) {
                $table->dropColumn('autosave_last_run_at');
            }
            if (Schema::hasColumn('users', 'autosave_weekday')) {
                $table->dropColumn('autosave_weekday');
            }
            if (Schema::hasColumn('users', 'autosave_amount')) {
                $table->dropColumn('autosave_amount');
            }
            if (Schema::hasColumn('users', 'autosave_enabled')) {
                $table->dropColumn('autosave_enabled');
            }
            if (Schema::hasColumn('users', 'paystack_authorization_code')) {
                $table->dropColumn('paystack_authorization_code');
            }
        });
    }
};
