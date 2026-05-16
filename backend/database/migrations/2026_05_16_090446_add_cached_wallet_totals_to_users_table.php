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
            $table->decimal('total_credits_withdrawable', 15, 2)->default(0)->after('outstanding_loans');
            $table->decimal('total_credits_restricted', 15, 2)->default(0)->after('total_credits_withdrawable');
            $table->decimal('total_debits', 15, 2)->default(0)->after('total_credits_restricted');
            $table->decimal('total_cashout_debits', 15, 2)->default(0)->after('total_debits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'total_credits_withdrawable',
                'total_credits_restricted',
                'total_debits',
                'total_cashout_debits',
            ]);
        });
    }
};
