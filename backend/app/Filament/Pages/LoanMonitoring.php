<?php

namespace App\Filament\Pages;

use App\Mail\DefaultLoanReminder;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

class LoanMonitoring extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Loans';
    protected static ?string $navigationLabel = 'Loan Monitoring';
    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.loan-monitoring';

    public function getSubheading(): ?string
    {
        return 'Track and monitor loan performance, repayments, and defaults.';
    }

    public array $membersOnLoan = [];
    public array $defaulters = [];

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        // Members currently on loan (active or pending)
        $members = User::query()
            ->with(['qardHasans' => function ($q) {
                $q->whereIn('status', ['active', 'pending'])
                  ->whereColumn('paid_amount', '<', 'principal_amount');
            }])
            ->whereHas('qardHasans', function ($q) {
                $q->whereIn('status', ['active', 'pending'])
                  ->whereColumn('paid_amount', '<', 'principal_amount');
            })
            ->get();

        $this->membersOnLoan = $members->map(function (User $u) {
            $outstanding = 0.0;
            $overdue = 0.0;
            $count = 0;
            $earliestReceived = null;
            foreach ($u->qardHasans as $loan) {
                $rem = max((float) $loan->principal_amount - (float) $loan->paid_amount, 0);
                if ($rem > 0) {
                    $outstanding += $rem;
                }
                $overdue += (float) $loan->getOverdueAmount();
                $count++;

                if ($loan->received_at) {
                    if (!$earliestReceived || $loan->received_at->lt($earliestReceived)) {
                        $earliestReceived = $loan->received_at;
                    }
                }
            }
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'branch' => optional($u->branch)->name,
                'loans_count' => $count,
                'outstanding' => round($outstanding, 2),
                'overdue' => round($overdue, 2),
                'is_defaulter' => (bool) $u->is_defaulter,
                'received_at' => $earliestReceived ? $earliestReceived->format('Y-m-d') : '—',
            ];
        })->sortByDesc('overdue')->values()->all();

        // Defaulters (flagged) and their outstanding
        $defs = User::query()
            ->where('is_defaulter', true)
            ->with(['qardHasans' => function ($q) {
                $q->whereIn('status', ['active', 'pending', 'defaulted'])
                  ->whereColumn('paid_amount', '<', 'principal_amount');
            }])
            ->get();

        $this->defaulters = $defs->map(function (User $u) {
            $outstanding = 0.0;
            $overdue = 0.0;
            $loans = 0;
            $earliestDefault = null;
            foreach ($u->qardHasans as $loan) {
                $rem = max((float) $loan->principal_amount - (float) $loan->paid_amount, 0);
                if ($rem > 0) {
                    $outstanding += $rem;
                }
                $overdue += (float) $loan->getOverdueAmount();
                $loans++;

                if ($loan->defaulted_at && $loan->defaulted_at->year > 1970) {
                    if (!$earliestDefault || $loan->defaulted_at->lt($earliestDefault)) {
                        $earliestDefault = $loan->defaulted_at;
                    }
                }
            }
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'branch' => optional($u->branch)->name,
                'loans_count' => $loans,
                'outstanding' => round($outstanding, 2),
                'overdue' => round($overdue, 2),
                'defaulted_at' => $earliestDefault ? $earliestDefault->format('Y-m-d') : '—',
            ];
        })->sortByDesc('overdue')->values()->all();
    }

    public function sendReminder(int $userId): void
    {
        $user = User::with(['qardHasans' => function ($q) {
            $q->whereIn('status', ['active', 'pending', 'defaulted'])
              ->whereColumn('paid_amount', '<', 'principal_amount');
        }])->find($userId);
        if (! $user) {
            Notification::make()->danger()->title('User not found')->send();
            return;
        }
        if (empty($user->email)) {
            Notification::make()->warning()->title('Cannot send email')->body('This member does not have an email address configured.')->send();
            return;
        }

        $loansData = [];
        $totalOutstanding = 0.0;
        foreach ($user->qardHasans as $loan) {
            $remaining = max((float) $loan->principal_amount - (float) $loan->paid_amount, 0);
            if ($remaining <= 0) continue;
            $loansData[] = [
                'loan_id' => $loan->qard_id_string ?: ('QH-' . $loan->id),
                'status' => $loan->status,
                'principal' => (float) $loan->principal_amount,
                'paid' => (float) $loan->paid_amount,
                'remaining' => $remaining,
            ];
            $totalOutstanding += $remaining;
        }

        if (empty($loansData)) {
            Notification::make()->warning()->title('No outstanding')->body('This member has no outstanding amount.')->send();
            return;
        }

        Mail::to($user->email)->queue(new DefaultLoanReminder($user, $loansData, $totalOutstanding));

        // Best-effort push notification to the member
        try {
            $push = app(\App\Services\PushService::class);
            $token = $user->fcm_token ?: ($user->device_token ?? null);
            $title = 'Loan Repayment Reminder';
            $body = 'You have outstanding loan balance of ' . number_format($totalOutstanding, 2) . '. Please make a repayment.';
            $push->send($token, $title, $body, [
                'type' => 'loan_reminder',
                'total_outstanding' => (float) $totalOutstanding,
            ]);
        } catch (\Throwable $e) {
            // ignore push errors
        }

        Notification::make()->success()->title('Reminder sent')->body('Email reminder has been sent to ' . $user->name)->send();
    }

    public function sendAllDefaultersReminders(): void
    {
        $sent = 0;
        $defs = User::query()->where('is_defaulter', true)->whereNotNull('email')->get();
        foreach ($defs as $user) {
            $this->sendReminder($user->id);
            $sent++;
        }
        Notification::make()->success()->title('Reminders queued')->body("Processed {$sent} defaulters.")->send();
        $this->refreshData();
    }

    // Alias method to match requirement naming
    public function sendAllDefaultReminders(): void
    {
        $this->sendAllDefaultersReminders();
    }
}
