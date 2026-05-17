<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Run the fix command to migrate past loan repayments
        // We use the command to encapsulate the logic and allow for manual re-runs if needed
        Artisan::call('app:fix-past-loan-repayments', [
            '--dry-run' => false,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible as it changes ledger entries and loan balances
    }
};
