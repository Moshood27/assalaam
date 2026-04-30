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
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('ledger_accounts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ledger_journals', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_journal_id')->constrained('ledger_journals')->onDelete('cascade');
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed some basic accounts
        $this->seedBasicAccounts();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_journals');
        Schema::dropIfExists('ledger_accounts');
    }

    private function seedBasicAccounts(): void
    {
        $now = now();
        $accounts = [
            // Assets
            ['name' => 'Cash', 'code' => '1000', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bank', 'code' => '1100', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Accounts Receivable', 'code' => '1200', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now],

            // Liabilities
            ['name' => 'Accounts Payable', 'code' => '2000', 'type' => 'liability', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Loans Payable', 'code' => '2100', 'type' => 'liability', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Member Deposits', 'code' => '2200', 'type' => 'liability', 'created_at' => $now, 'updated_at' => $now],

            // Equity
            ['name' => 'Retained Earnings', 'code' => '3000', 'type' => 'equity', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Member Equity', 'code' => '3100', 'type' => 'equity', 'created_at' => $now, 'updated_at' => $now],

            // Income
            ['name' => 'Service Income', 'code' => '4000', 'type' => 'income', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Investment Income', 'code' => '4100', 'type' => 'income', 'created_at' => $now, 'updated_at' => $now],

            // Expenses
            ['name' => 'Operating Expenses', 'code' => '5000', 'type' => 'expense', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Administrative Fees', 'code' => '5100', 'type' => 'expense', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('ledger_accounts')->insert($accounts);
    }
};
