<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Support\LogOptions;

class ExpenseEntry extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
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
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
}
