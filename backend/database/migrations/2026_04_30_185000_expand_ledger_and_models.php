<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->seedMoreAccounts();

        Schema::table('store_orders', function (Blueprint $table) {
            $table->foreignId('ledger_journal_id')->nullable()->constrained('ledger_journals')->onDelete('set null');
        });

        Schema::table('project_profits', function (Blueprint $table) {
            $table->foreignId('ledger_journal_id')->nullable()->constrained('ledger_journals')->onDelete('set null');
        });

        Schema::table('project_profit_payouts', function (Blueprint $table) {
            $table->foreignId('ledger_journal_id')->nullable()->constrained('ledger_journals')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_profit_payouts', function (Blueprint $table) {
            $table->dropForeign(['ledger_journal_id']);
            $table->dropColumn('ledger_journal_id');
        });

        Schema::table('project_profits', function (Blueprint $table) {
            $table->dropForeign(['ledger_journal_id']);
            $table->dropColumn('ledger_journal_id');
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropForeign(['ledger_journal_id']);
            $table->dropColumn('ledger_journal_id');
        });
    }

    private function seedMoreAccounts(): void
    {
        $now = now();
        $accounts = [
            ['name' => 'Murabahah Receivables', 'code' => '1310', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Murabahah Profit', 'code' => '4400', 'type' => 'income', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Project Profits Payable', 'code' => '2300', 'type' => 'liability', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Management Fee Income', 'code' => '4500', 'type' => 'income', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Takaful Pool Fund', 'code' => '2210', 'type' => 'liability', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Charity Fund (Restricted)', 'code' => '2220', 'type' => 'liability', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Statutory Reserve', 'code' => '3200', 'type' => 'equity', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Education Fund', 'code' => '3300', 'type' => 'equity', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Investments', 'code' => '1400', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('ledger_accounts')->insert($accounts);
    }
};
