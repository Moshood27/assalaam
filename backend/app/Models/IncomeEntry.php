<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class IncomeEntry extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'income_entries';

    protected $fillable = [
        'date',
        'title',
        'category',
        'amount',
        'notes',
        'created_by',
        'ledger_journal_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (IncomeEntry $income) {
            try {
                if (!$income->ledger_journal_id) {
                    $journal = app(\App\Services\LedgerService::class)->recordIncome($income);
                    $income->updateQuietly(['ledger_journal_id' => $journal->id]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Failed to record income in ledger: " . $e->getMessage());
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ledgerJournal()
    {
        return $this->belongsTo(LedgerJournal::class);
    }
}
