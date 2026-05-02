<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class QardHasanRepayment extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'qard_hasan_id',
        'amount',
        'reference',
        'status',
        'paid_at',
        'ledger_journal_id',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updated(function (self $model) {
            if ($model->status === 'success' && $model->wasChanged('status')) {
                try {
                    // Record in Ledger
                    if (!$model->ledger_journal_id) {
                        $journal = app(\App\Services\LedgerService::class)->recordLoanRepayment($model);
                        $model->updateQuietly(['ledger_journal_id' => $journal->id]);
                    }

                    app(\App\Services\AttaqwaScoreService::class)->calculateAndUpdateScore($model->qardHasan->user);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to record loan repayment in ledger: " . $e->getMessage());
                }
            }
        });

        static::created(function (self $model) {
            if ($model->status === 'success') {
                try {
                    // Record in Ledger
                    if (!$model->ledger_journal_id) {
                        $journal = app(\App\Services\LedgerService::class)->recordLoanRepayment($model);
                        $model->updateQuietly(['ledger_journal_id' => $journal->id]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to record loan repayment in ledger: " . $e->getMessage());
                }
            }
        });
    }

    public function qardHasan()
    {
        return $this->belongsTo(QardHasan::class);
    }

    public function ledgerJournal()
    {
        return $this->belongsTo(LedgerJournal::class);
    }
}
