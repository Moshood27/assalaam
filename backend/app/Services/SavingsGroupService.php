<?php

namespace App\Services;

use App\Models\SavingsGroup;
use App\Models\SavingsGroupMember;
use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SavingsGroupService
{
    /**
     * Charge monthly contributions for all active members in active savings groups.
     * This typically runs on the 1st of every month.
     */
    public function chargeMonthly($period = null)
    {
        $period = $period ?: now()->format('Y-m');
        $scheme = Scheme::where('name', 'Group Savings')->first();

        if (!$scheme) {
            Log::error('Group Savings scheme not found. Automated charge aborted.');
            return ['error' => 'Scheme not found'];
        }

        $groups = SavingsGroup::where('status', 'active')->get();
        $results = [
            'groups_processed' => 0,
            'members_processed' => 0,
            'successful_charges' => 0,
            'failed_insufficient' => 0,
            'total_amount' => 0,
        ];

        foreach ($groups as $group) {
            $results['groups_processed']++;
            $members = $group->activeMembers()->get();

            foreach ($members as $member) {
                $results['members_processed']++;
                $user = $member->user;
                $amount = $group->monthly_contribution_amount;
                $units = null;

                if ($group->project_id) {
                    $project = $group->project;
                    if ($project && $project->is_unit_based && (float)$project->unit_price > 0) {
                        $units = (int) ($amount / (float)$project->unit_price);
                    }
                }

                // Check if already contributed for this period
                $referencePrefix = "SG_AUTO_{$group->id}_{$period}_";
                $exists = Contribution::where('user_id', $user->id)
                    ->where('savings_group_id', $group->id)
                    ->where('reference', 'like', $referencePrefix . '%')
                    ->where('status', 'success')
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Attempt to charge from wallet
                $success = DB::transaction(function() use ($user, $group, $scheme, $amount, $period, $referencePrefix, $units) {
                    $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

                    if ((float) $lockedUser->balance < (float) $amount) {
                        return false;
                    }

                    $reference = $referencePrefix . bin2hex(random_bytes(3));

                    // Create Contribution
                    Contribution::create([
                        'user_id' => $lockedUser->id,
                        'scheme_id' => $scheme->id,
                        'savings_group_id' => $group->id,
                        'project_id' => $group->project_id,
                        'amount' => $amount,
                        'units' => $units,
                        'reference' => $reference,
                        'status' => 'success',
                    ]);

                    // Deduct from wallet
                    $lockedUser->decrement('balance', $amount);

                    // Record Wallet Transaction
                    WalletTransaction::create([
                        'user_id' => $lockedUser->id,
                        'type' => 'debit',
                        'amount' => $amount,
                        'reference' => $reference,
                        'source' => 'savings_group_contribution',
                        'meta' => [
                            'savings_group_id' => $group->id,
                            'period' => $period,
                        ],
                    ]);

                    return true;
                });

                if ($success) {
                    $results['successful_charges']++;
                    $results['total_amount'] += $amount;

                    // Notify user
                    try {
                        $user->notify(new \App\Notifications\PaymentNotification(
                            title: "Monthly Contribution: {$group->name}",
                            message: "Your monthly contribution of ₦" . number_format($amount, 2) . " for {$group->name} has been processed.",
                            amount: (float) $amount,
                            reference: $referencePrefix,
                            source: 'savings_group_auto'
                        ));
                    } catch (\Throwable $e) {}
                } else {
                    $results['failed_insufficient']++;
                    // Notify user about failed charge
                    try {
                        $user->notify(new \App\Notifications\PaymentNotification(
                            title: "Failed Contribution: {$group->name}",
                            message: "Your monthly contribution for {$group->name} failed due to insufficient wallet balance. Please top up your wallet.",
                            amount: (float) $amount,
                            reference: $referencePrefix,
                            source: 'savings_group_auto_fail'
                        ));
                    } catch (\Throwable $e) {}
                }
            }
        }

        return $results;
    }
}
