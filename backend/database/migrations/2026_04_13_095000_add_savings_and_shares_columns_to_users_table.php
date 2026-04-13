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
            $table->decimal('ordinary_savings', 15, 2)->default(0)->after('balance');
            $table->decimal('shares_capital', 15, 2)->default(0)->after('ordinary_savings');
        });

        // Populate existing balances
        try {
            \App\Models\User::all()->each(function ($user) {
                $calc = $user->savingsSharesEligibility();
                $user->update([
                    'ordinary_savings' => $calc['savings'],
                    'shares_capital' => $calc['shares'],
                ]);
            });
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ordinary_savings', 'shares_capital']);
        });
    }
};
