<?php

namespace App\Jobs;

use App\Models\Contribution;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectInvestment;
use App\Models\CharityEntry;
use App\Services\AttendanceService;
use App\Services\AttaqwaScoreService;
use App\Services\LedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessContributionSideEffects implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $contributionId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $contributionId)
    {
        $this->contributionId = $contributionId;
        $this->onQueue('contributions');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $model = Contribution::with(['user', 'scheme'])->find($this->contributionId);
            if (!$model || $model->status !== 'success') {
                return;
            }

            $user = $model->user;

            // 1. Sync user scheme balance (Recalculate total sum)
            if ($model->scheme && $model->category !== 'fine') {
                $user->syncSchemeBalance($model->scheme->name);
            }

            // 2. Handling for Fine category
            if ($model->category === 'fine') {
                try {
                    // Decrement outstanding fines (Done synchronously in hook too, but here for redundancy/consistency)
                    // Note: We should be careful about double-decrementing if we leave it in the hook.
                    // Actually, I will move it here entirely.

                    // Settle attendance records
                    app(AttendanceService::class)->settleOutstandingFines($user, (float) $model->amount);

                    if (!CharityEntry::where('user_id', $user->id)->where('source', 'Manual Fine Payment')->where('amount', $model->amount)->where('created_at', '>=', $model->created_at)->exists()) {
                        CharityEntry::create([
                            'user_id' => $user->id,
                            'source' => 'Manual Fine Payment',
                            'amount' => $model->amount,
                            'note' => "Manual payment of fine (Reference: {$model->reference})",
                            'status' => 'processed',
                            'processed_at' => now(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error("Fine processing failed in job", ['contribution_id' => $model->id, 'error' => $e->getMessage()]);
                }
            }

            // 3. Update Attaqwa Score
            try {
                app(AttaqwaScoreService::class)->calculateAndUpdateScore($user);
            } catch (\Throwable $e) {
                Log::error("Attaqwa score update failed in job", ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }

            // 4. Record in Ledger
            if (!$model->ledger_journal_id) {
                try {
                    $ledger = app(LedgerService::class);
                    $journal = $model->category === 'fine'
                        ? $ledger->recordFine($model)
                        : $ledger->recordContribution($model);
                    $model->updateQuietly(['ledger_journal_id' => $journal->id]);
                } catch (\Throwable $e) {
                    Log::error("Ledger recording failed in job", ['contribution_id' => $model->id, 'error' => $e->getMessage()]);
                }
            }

            // 5. Notifications
            try {
                $schemeName = $model->scheme?->name ?? 'Contribution';

                // Notify user
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
            } catch (\Throwable $e) {
                Log::warning("Notifications failed in job", ['contribution_id' => $model->id, 'error' => $e->getMessage()]);
            }

            // 6. Project Investment (if not already created)
            if ($model->project_id) {
                if (!ProjectInvestment::where('contribution_id', $model->id)->exists()) {
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
            Log::error('ProcessContributionSideEffects job failed', [
                'contribution_id' => $this->contributionId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
