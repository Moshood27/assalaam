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
            $cols = [
                'ordinary_savings' => 'decimal',
                'shares_capital' => 'decimal',
                'building_balance' => 'decimal',
                'development_fund_balance' => 'decimal',
                'agm_balance' => 'decimal',
                'loan_repayment_balance' => 'decimal',
                'fine_balance' => 'decimal',
                'welfare_balance' => 'decimal',
                'lateness_balance' => 'decimal',
                'stationery_balance' => 'decimal',
                'loan_form_balance' => 'decimal',
                'others_balance' => 'decimal',
                'id_card_balance' => 'decimal',
                'emergency_balance' => 'decimal',
                'entrance_balance' => 'decimal',
                'h_savings_balance' => 'decimal',
                'investment_balance' => 'decimal',
                'group_savings_balance' => 'decimal',
            ];

            foreach ($cols as $col => $type) {
                if (!Schema::hasColumn('users', $col)) {
                    $table->decimal($col, 15, 2)->default(0);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ordinary_savings', 'shares_capital', 'building_balance', 'development_fund_balance',
                'agm_balance', 'loan_repayment_balance', 'fine_balance', 'welfare_balance',
                'lateness_balance', 'stationery_balance', 'loan_form_balance', 'others_balance',
                'id_card_balance', 'emergency_balance', 'entrance_balance', 'h_savings_balance',
                'investment_balance', 'group_savings_balance'
            ]);
        });
    }
};
