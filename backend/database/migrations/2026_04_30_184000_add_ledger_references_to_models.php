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
        // Add more basic accounts
        $this->seedMoreAccounts();

        // Add ledger_journal_id to financial models for easy reference
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->foreignId('ledger_journal_id')->nullable()->constrained('ledger_journals')->onDelete('set null');
        });

        Schema::table('income_entries', function (Blueprint $table) {
            $table->foreignId('ledger_journal_id')->nullable()->constrained('ledger_journals')->onDelete('set null');
        });

        Schema::table('expense_entries', function (Blueprint $table) {
            $table->foreignId('ledger_journal_id')->nullable()->constrained('ledger_journals')->onDelete('set null');
        });

        Schema::table('charity_ledger', function (Blueprint $table) {
            $table->foreignId('ledger_journal_id')->nullable()->constrained('ledger_journals')->onDelete('set null');
        });

        Schema::table('contributions', function (Blueprint $table) {
            $table->foreignId('ledger_journal_id')->nullable()->constrained('ledger_journals')->onDelete('set null');
        });

        Schema::table('qard_hasans', function (Blueprint $table) {
            $table->foreignId('ledger_journal_id')->nullable()->constrained('ledger_journals')->onDelete('set null');
        });

        Schema::table('qard_hasan_repayments', function (Blueprint $table) {
            $table->foreignId('ledger_journal_id')->nullable()->constrained('ledger_journals')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qard_hasan_repayments', function (Blueprint $table) {
            $table->dropForeign(['ledger_journal_id']);
            $table->dropColumn('ledger_journal_id');
        });

        Schema::table('qard_hasans', function (Blueprint $table) {
            $table->dropForeign(['ledger_journal_id']);
            $table->dropColumn('ledger_journal_id');
        });

        Schema::table('contributions', function (Blueprint $table) {
            $table->dropForeign(['ledger_journal_id']);
            $table->dropColumn('ledger_journal_id');
        });

        Schema::table('charity_ledger', function (Blueprint $table) {
            $table->dropForeign(['ledger_journal_id']);
            $table->dropColumn('ledger_journal_id');
        });

        Schema::table('expense_entries', function (Blueprint $table) {
            $table->dropForeign(['ledger_journal_id']);
            $table->dropColumn('ledger_journal_id');
        });

        Schema::table('income_entries', function (Blueprint $table) {
            $table->dropForeign(['ledger_journal_id']);
            $table->dropColumn('ledger_journal_id');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['ledger_journal_id']);
            $table->dropColumn('ledger_journal_id');
        });
    }

    private function seedMoreAccounts(): void
    {
        $now = now();
        $accounts = [
            ['name' => 'Charity Expenses', 'code' => '5200', 'type' => 'expense', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fine Income', 'code' => '4200', 'type' => 'income', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Loan Interest/Mark-up', 'code' => '4300', 'type' => 'income', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Loans Receivable', 'code' => '1300', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('ledger_accounts')->insert($accounts);
    }
};
