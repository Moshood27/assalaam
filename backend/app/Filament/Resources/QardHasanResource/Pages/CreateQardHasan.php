<?php

namespace App\Filament\Resources\QardHasanResource\Pages;

use App\Filament\Resources\QardHasanResource;
use App\Models\QardHasan;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoanRequestedAdminNotification;
use Filament\Notifications\Notification;

class CreateQardHasan extends CreateRecord
{
    protected static string $resource = QardHasanResource::class;

    protected array $guarantorIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure we have a valid user
        $user = User::find($data['user_id'] ?? null);
        if ($user) {
            $data['meeting_attendance_count'] = $user->meetingAttendanceCount();
            // Enforce 6-month minimum membership
            if ($user->monthsInSystem() < 6) {
                Notification::make()->title('Eligibility Error')->body('Member must be in the system for at least 6 months before taking a loan.')->danger()->send();
                throw ValidationException::withMessages([
                    'user_id' => 'Member must be in the system for at least 6 months before taking a loan.'
                ]);
            }

            // Prevent multiple open loans
            if ($user->hasActiveLoan()) {
                Notification::make()->title('Eligibility Error')->body('Member has an existing loan that is not yet completed.')->danger()->send();
                throw ValidationException::withMessages([
                    'user_id' => 'Member has an existing loan that is not yet completed.'
                ]);
            }

            // Compute policy-adjusted eligibility
            $adj = $user->adjustedLoanEligibility();
            $principal = (float) ($adj['eligibility_adjusted'] ?? 0);
            if ($principal <= 0) {
                Notification::make()->title('Eligibility Error')->body('Selected member is not eligible for a loan at this time.')->danger()->send();
                throw ValidationException::withMessages([
                    'user_id' => 'Selected member is not eligible for a loan at this time.'
                ]);
            }
            $data['principal_amount'] = $data['principal_amount'] ?? $principal;
            // Auto-generate Loan ID
            $data['qard_id_string'] = $data['qard_id_string'] ?? ('QH-'.now()->format('Y').'-'.Str::upper(Str::random(6)));
            // Auto compute per_installment if possible
            $totalInstallments = (int) ($data['total_installments'] ?? 1);
            if (empty($data['per_installment'])) {
                $data['per_installment'] = $totalInstallments > 0 ? round(((float)$data['principal_amount']) / $totalInstallments, 2) : 0;
            }
        }

        // Normalize interval casing
        if (!empty($data['interval'])) {
            $data['interval'] = strtolower($data['interval']);
        }

        // Defaults
        $data['admin_fee_flat'] = $data['admin_fee_flat'] ?? 0;
        $data['admin_fee_pct'] = $data['admin_fee_pct'] ?? 0;
        $data['paid_amount'] = $data['paid_amount'] ?? 0;
        $data['status'] = $data['status'] ?? 'pending';

        // Validate guarantors from form state
        $state = $this->form->getRawState();
        $g = array_values(array_unique($state['guarantor_ids'] ?? []));
        if (count($g) < 2 || count($g) > 3) {
            Notification::make()->title('Validation Error')->body('Select at least two and at most three guarantors.')->danger()->send();
            throw ValidationException::withMessages([
                'guarantor_ids' => 'Select at least two and at most three guarantors.'
            ]);
        }
        if (!empty($data['user_id']) && in_array((int)$data['user_id'], $g, true)) {
            Notification::make()->title('Validation Error')->body('Member cannot be their own guarantor.')->danger()->send();
            throw ValidationException::withMessages([
                'guarantor_ids' => 'Member cannot be their own guarantor.'
            ]);
        }
        $guarantors = User::with('branch')->whereIn('id', $g)->get();
        if ($guarantors->count() !== count($g)) {
            Notification::make()->title('Validation Error')->body('One or more guarantors are invalid.')->danger()->send();
            throw ValidationException::withMessages([
                'guarantor_ids' => 'One or more guarantors are invalid.'
            ]);
        }
        if ($guarantors->where('is_defaulter', true)->isNotEmpty()) {
            Notification::make()->title('Validation Error')->body('Guarantors must not be in default.')->danger()->send();
            throw ValidationException::withMessages([
                'guarantor_ids' => 'Guarantors must not be in default.'
            ]);
        }
        $this->guarantorIds = $g;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (!empty($this->guarantorIds)) {
            // Attach guarantors with pending status and unique tokens
            $attach = [];
            foreach ($this->guarantorIds as $gid) {
                $attach[$gid] = [
                    'status' => 'pending',
                    'token' => Str::upper(Str::random(10)),
                ];
            }
            $this->record->guarantors()->attach($attach);

            // Best-effort SMS notifications
            try {
                $sms = app(\App\Services\SmsService::class);
                $member = $this->record->user?->name;
                $amount = number_format((float) $this->record->principal_amount, 2);
                foreach ($this->record->guarantors as $g) {
                    $msg = 'Guarantor request: Member '.($member).' requested a loan (ID: '.($this->record->qard_id_string).', â‚¦'.$amount.'). Please open your Coop app > Loans to Accept or Decline.';
                    $sms->send($g->phone ?? null, $msg);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Email admins about new loan request (best-effort)
        try {
            $loan = $this->record->loadMissing('user');
            $adminEmails = User::query()
                ->where('is_admin', true)
                ->whereNotNull('email')
                ->pluck('email')
                ->all();
            $fallback = trim((string) env('ADMIN_NOTIFICATION_EMAILS', ''));
            if (!empty($fallback)) {
                foreach (preg_split('/[,;]/', $fallback) as $em) {
                    $em = trim($em);
                    if ($em !== '' && !in_array($em, $adminEmails, true)) {
                        $adminEmails[] = $em;
                    }
                }
            }
            if (!empty($adminEmails)) {
                Mail::to($adminEmails)->send(new LoanRequestedAdminNotification($loan));
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
