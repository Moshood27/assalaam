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
                $q->whereIn('status', ['active', 'pending']);
            }])
            ->whereHas('qardHasans', function ($q) {
                $q->whereIn('status', ['active', 'pending']);
            })
            ->get();

        $this->membersOnLoan = $members->map(function (User $u) {
            $outstanding = 0.0;
            $count = 0;
            foreach ($u->qardHasans as $loan) {
                $rem = max((float) $loan->principal_amount - (float) $loan->paid_amount, 0);
                if ($rem > 0) {
                    $outstanding += $rem;
                }
                $count++;
            }
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'branch' => optional($u->branch)->name,
                'loans_count' => $count,
                'outstanding' => round($outstanding, 2),
                'is_defaulter' => (bool) $u->is_defaulter,
            ];
        })->sortByDesc('outstanding')->values()->all();

        // Defaulters (flagged) and their outstanding
        $defs = User::query()
            ->where('is_defaulter', true)
            ->with(['qardHasans' => function ($q) {
                $q->whereIn('status', ['active', 'pending']);
            }])
            ->get();

        $this->defaulters = $defs->map(function (User $u) {
            $outstanding = 0.0;
            $loans = 0;
            foreach ($u->qardHasans as $loan) {
                $rem = max((float) $loan->principal_amount - (float) $loan->paid_amount, 0);
                if ($rem > 0) {
                    $outstanding += $rem;
                }
                $loans++;
            }
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'branch' => optional($u->branch)->name,
                'loans_count' => $loans,
                'outstanding' => round($outstanding, 2),
            ];
        })->sortByDesc('outstanding')->values()->all();
    }

    public function sendReminder(int $userId): void
    {
        $user = User::with(['qardHasans' => function ($q) {
            $q->whereIn('status', ['active', 'pending']);
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

        Mail::to($user->email)->send(new DefaultLoanReminder($user, $loansData, $totalOutstanding));

        // Best-effort push notification to the member
        try {
            $push = app(\App\Services\PushService::class);
            $token = $user->fcm_token ?: ($user->device_token ?? null);
            $title = 'Loan Repayment Reminder';
            $body = 'You have outstanding loan balance of ₦' . number_format($totalOutstanding, 2) . '. Please make a repayment.';
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
