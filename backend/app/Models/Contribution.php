<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Contribution extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'units' => 'decimal:6',
    ];

    protected $fillable = [
        'user_id',
        'scheme_id',
        'project_id',
        'savings_group_id',
        'amount',
        'units',
        'reference',
        'status',
        'category',
        'ledger_journal_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference)) {
                $model->reference = self::generateReference();
            }
        });

        static::created(function (self $model) {
            // Sync user scheme balance if successful
            try {
                if ($model->status === 'success' && $model->scheme && $model->category !== 'fine') {
                    $model->user->syncSchemeBalance($model->scheme->name);
                }
            } catch (\Throwable $e) {}

            // Special handling for Fine category
            if ($model->status === 'success' && $model->category === 'fine') {
                try {
                    $user = $model->user;
                    $user->decrement('outstanding_fines', min($user->outstanding_fines, $model->amount));

                    // Settle attendance records
                    app(\App\Services\AttendanceService::class)->settleOutstandingFines($user, (float) $model->amount);

                    \App\Models\CharityEntry::create([
                        'user_id' => $user->id,
                        'source' => 'Manual Fine Payment',
                        'amount' => $model->amount,
                        'note' => "Manual payment of fine (Reference: {$model->reference})",
                        'status' => 'processed',
                        'processed_at' => now(),
                    ]);
                } catch (\Throwable $e) {}
            }

            // Special handling for Loan Repayment category
            if ($model->status === 'success' && $model->category === 'loan_repayment') {
                try {
                    $user = $model->user;
                    $q = QardHasan::where('user_id', $user->id)
                        ->whereIn('status', ['active', 'defaulted'])
                        ->whereColumn('paid_amount', '<', 'principal_amount')
                        ->first();

                    if ($q) {
                        if (!QardHasanRepayment::where('reference', $model->reference)->exists()) {
                            $before = (float) $q->paid_amount;
                            $remaining = max(0, (float) $q->principal_amount - $before);
                            $applied = round(min((float) $model->amount, $remaining), 2);

                            if ($applied > 0) {
                                QardHasanRepayment::create([
                                    'qard_hasan_id' => $q->id,
                                    'amount' => $applied,
                                    'reference' => $model->reference,
                                    'status' => 'success',
                                    'paid_at' => now(),
                                ]);

                                $q->paid_amount = $before + $applied;
                                if ($q->paid_amount >= $q->principal_amount) {
                                    $q->status = 'completed';
                                }
                                $q->save();
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to process loan repayment from contribution: " . $e->getMessage());
                }
            }

            // Record in Ledger if successful on creation (typical for migration)
            if ($model->status === 'success' && !$model->ledger_journal_id) {
                try {
                    $ledger = app(\App\Services\LedgerService::class);
                    if ($model->category === 'fine') {
                        $journal = $ledger->recordFine($model);
                        $model->updateQuietly(['ledger_journal_id' => $journal->id]);
                    } elseif ($model->category === 'loan_repayment') {
                        // Skip recordContribution; QardHasanRepayment will record its own ledger entry
                    } else {
                        $journal = $ledger->recordContribution($model);
                        $model->updateQuietly(['ledger_journal_id' => $journal->id]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to record contribution in ledger: " . $e->getMessage());
                }
            }

            // If created already successful and linked to a project (e.g., wallet allocation), create investment
            try {
                if ($model->project_id && $model->status === 'success') {
                    // Decrement available units if applicable
                    if ($model->units > 0) {
                        $project = Project::find($model->project_id);
                        if ($project && $project->is_unit_based) {
                            $project->decrement('available_units', $model->units);
                        }
                    }

                    if (! ProjectInvestment::where('contribution_id', $model->id)->exists()) {
                        ProjectInvestment::create([
                            'user_id' => $model->user_id,
                            'project_id' => $model->project_id,
                            'contribution_id' => $model->id,
                            'amount' => $model->amount,
                            'units' => $model->units,
                            'reference' => $model->reference,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Don't block creation on investment linkage failures
            }
        });

        static::updated(function (self $model) {
            // When a contribution tied to a project is marked successful, create a ProjectInvestment once
            try {
                if ($model->status === 'success' && ($model->wasChanged('status') || $model->wasChanged('amount'))) {
                    // Sync user scheme balance if not a fine
                    try {
                        if ($model->scheme && $model->category !== 'fine') {
                            $model->user->syncSchemeBalance($model->scheme->name);
                        }
                    } catch (\Throwable $e) {}

                    // Special handling for Fine category when marked as success (e.g. from webhook)
                    if ($model->category === 'fine') {
                        try {
                            $user = $model->user;
                            $user->decrement('outstanding_fines', min($user->outstanding_fines, $model->amount));

                            // Settle attendance records
                            app(\App\Services\AttendanceService::class)->settleOutstandingFines($user, (float) $model->amount);

                            \App\Models\CharityEntry::create([
                                'user_id' => $user->id,
                                'source' => 'Manual Fine Payment',
                                'amount' => $model->amount,
                                'note' => "Manual payment of fine (Reference: {$model->reference})",
                                'status' => 'processed',
                                'processed_at' => now(),
                            ]);
                        } catch (\Throwable $e) {}
                    }

                    // Special handling for Loan Repayment category
                    if ($model->category === 'loan_repayment') {
                        try {
                            $user = $model->user;
                            $q = QardHasan::where('user_id', $user->id)
                                ->whereIn('status', ['active', 'defaulted'])
                                ->whereColumn('paid_amount', '<', 'principal_amount')
                                ->first();

                            if ($q) {
                                if (!QardHasanRepayment::where('reference', $model->reference)->exists()) {
                                    $before = (float) $q->paid_amount;
                                    $remaining = max(0, (float) $q->principal_amount - $before);
                                    $applied = round(min((float) $model->amount, $remaining), 2);

                                    if ($applied > 0) {
                                        QardHasanRepayment::create([
                                            'qard_hasan_id' => $q->id,
                                            'amount' => $applied,
                                            'reference' => $model->reference,
                                            'status' => 'success',
                                            'paid_at' => now(),
                                        ]);

                                        $q->paid_amount = $before + $applied;
                                        if ($q->paid_amount >= $q->principal_amount) {
                                            $q->status = 'completed';
                                        }
                                        $q->save();
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::error("Failed to process loan repayment from contribution: " . $e->getMessage());
                        }
                    }

                    // Update assalaam Score
                    try {
                        app(\App\Services\AssalaamScoreService::class)->calculateAndUpdateScore($model->user);
                    } catch (\Throwable $e) {}

                    // Notify admins and user about successful contribution
                    try {
                        // Record in Ledger if not already recorded
                        if (!$model->ledger_journal_id) {
                            $ledger = app(\App\Services\LedgerService::class);
                            if ($model->category === 'fine') {
                                $journal = $ledger->recordFine($model);
                                $model->updateQuietly(['ledger_journal_id' => $journal->id]);
                            } elseif ($model->category === 'loan_repayment') {
                                // Skip; QardHasanRepayment will record its own ledger entry
                            } else {
                                $journal = $ledger->recordContribution($model);
                                $model->updateQuietly(['ledger_journal_id' => $journal->id]);
                            }
                        }

                        $user = $model->user;
                        $schemeName = $model->scheme?->name ?? 'Contribution';

                        // Notify user (triggers real-time update)
                        $user->notifyMember(
                            "Contribution Successful",
                            "Your payment of ₦" . number_format($model->amount, 2) . " for {$schemeName} was successful.",
                            ['type' => 'contribution_success', 'contribution_id' => $model->id]
                        );

                        // Notify relevant admins
                        $user->getAuthorizedAdmins()->each(function ($admin) use ($user, $model, $schemeName) {
                            $admin->notifyMember(
                                "Payment Received: {$schemeName}",
                                "Member {$user->name} successfully paid ₦" . number_format($model->amount, 2) . " for {$schemeName}.",
                                ['type' => 'contribution_success', 'contribution_id' => $model->id]
                            );
                        });
                    } catch (\Throwable $e) {}

                    if ($model->project_id) {
                        // Decrement available units if applicable
                        if ($model->units > 0) {
                            $project = Project::find($model->project_id);
                            if ($project && $project->is_unit_based) {
                                $project->decrement('available_units', $model->units);
                            }
                        }

                        // Avoid duplicates if re-updated
                        if (! ProjectInvestment::where('contribution_id', $model->id)->exists()) {
                            ProjectInvestment::create([
                                'user_id' => $model->user_id,
                                'project_id' => $model->project_id,
                                'contribution_id' => $model->id,
                                'amount' => $model->amount,
                                'units' => $model->units,
                                'reference' => $model->reference,
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Swallow to prevent blocking payment finalization; logs can be added if needed
            }
        });

        static::deleted(function (self $model) {
            // Sync user scheme balance if it was successful
            try {
                if ($model->status === 'success' && $model->scheme) {
                    $model->user->syncSchemeBalance($model->scheme->name);
                }
            } catch (\Throwable $e) {}
        });
    }

    public static function generateReference(): string
    {
        return 'CNTRB-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function scheme()
    {
        return $this->belongsTo(Scheme::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function savingsGroup()
    {
        return $this->belongsTo(SavingsGroup::class);
    }

    public function ledgerJournal()
    {
        return $this->belongsTo(LedgerJournal::class);
    }
}
