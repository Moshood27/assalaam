<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CharityEntry extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'amount', 'source', 'description', 'reference'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $table = 'charity_ledger';

    protected $fillable = [
        'user_id',
        'source',
        'amount',
        'note',
        'status',
        'processed_at',
        'ledger_journal_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionApprovals(): MorphMany
    {
        return $this->morphMany(TransactionApproval::class, 'approvable');
    }

    public function isHighValue(): bool
    {
        // Re-use expense threshold for charity as it's an outflow
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
