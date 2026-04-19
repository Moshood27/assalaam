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
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->string('payout_reference')->nullable()->unique()->after('status');
            $table->string('recipient_code')->nullable()->after('payout_reference');
            $table->string('transfer_code')->nullable()->after('recipient_code');
        });
    }

    public function down(): void
    {
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->dropColumn(['payout_reference', 'recipient_code', 'transfer_code']);
        });
    }
};
