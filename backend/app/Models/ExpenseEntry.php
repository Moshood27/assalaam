<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ExpenseEntry extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'expense_entries';

    protected $fillable = [
        'date',
        'title',
        'category',
        'amount',
        'notes',
        'created_by',
        'status',
        'processed_at',
        'vendor_id',
        'recipient_type',
        'member_id',
        'bank_name',
        'bank_code',
        'account_number',
        'account_name',
        'receipt_path',
        'source_of_funds',
        'approved_by',
        'rejection_reason',
        'payout_reference',
        'recipient_code',
        'transfer_code',
        'ledger_journal_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updated(function (ExpenseEntry $expense) {
            // Record in Ledger when status becomes 'processed'
            if ($expense->status === 'processed' && $expense->wasChanged('status') && !$expense->ledger_journal_id) {
                try {
                    $journal = app(\App\Services\LedgerService::class)->recordExpense($expense);
                    $expense->updateQuietly(['ledger_journal_id' => $journal->id]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to record expense in ledger: " . $e->getMessage());
                }
            }
        });

        static::created(function (ExpenseEntry $expense) {
            // If already processed on creation
            if ($expense->status === 'processed' && !$expense->ledger_journal_id) {
                try {
                    $journal = app(\App\Services\LedgerService::class)->recordExpense($expense);
                    $expense->updateQuietly(['ledger_journal_id' => $journal->id]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to record expense in ledger: " . $e->getMessage());
                }
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transactionApprovals(): MorphMany
    {
        return $this->morphMany(TransactionApproval::class, 'approvable');
    }

    public function isHighValue(): bool
    {
        $threshold = config('cooperative.approvals.high_value_expense_threshold', 200000);
        return (float) $this->amount >= (float) $threshold;
    }

    public function hasSufficientApprovals(): bool
    {
        if (!$this->isHighValue()) {
            return true;
        }

        $requiredCount = config('cooperative.approvals.required_approvals_count', 2);
        $approvedCount = $this->transactionApprovals()
            ->where('status', 'approved')
            ->count();

        return $approvedCount >= $requiredCount;
    }

    public function isAwaitingApprovals(): bool
    {
        return $this->isHighValue() && !$this->hasSufficientApprovals();
    }

    public function ledgerJournal()
    {
        return $this->belongsTo(LedgerJournal::class);
    }
}
