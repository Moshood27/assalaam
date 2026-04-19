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
            if (!Schema::hasColumn('expense_entries', 'status')) {
                $table->string('status')->default('pending')->after('notes');
            }
            if (!Schema::hasColumn('expense_entries', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('status');
            }

            $table->foreignId('vendor_id')->nullable()->after('created_by')->constrained('vendors')->nullOnDelete();
            $table->string('bank_name')->nullable()->after('vendor_id');
            $table->string('bank_code')->nullable()->after('bank_name');
            $table->string('account_number')->nullable()->after('bank_code');
            $table->string('account_name')->nullable()->after('account_number');
            $table->string('receipt_path')->nullable()->after('account_name');
            $table->string('source_of_funds')->default('administrative_fund')->after('receipt_path');
            $table->foreignId('approved_by')->nullable()->after('source_of_funds')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'status',
                'processed_at',
                'vendor_id',
                'bank_name',
                'bank_code',
                'account_number',
                'account_name',
                'receipt_path',
                'source_of_funds',
                'approved_by',
                'rejection_reason',
            ]);
        });
    }
};
