<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\MonnifyService;
use App\Services\OpayService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\RepaymentReceiptUser;
use App\Mail\LoanDisbursedUser;
use App\Mail\LoanDisbursedAdminNotification;
use App\Mail\LoanRequestedAdminNotification;
use App\Services\AttaqwaScoreService;
use App\Notifications\LoanApprovedNotification;
use App\Traits\VerifiesOtp;
use App\Notifications\OtpNotification;
use Laravel\Pennant\Feature;

class LoanController extends Controller
{
    use VerifiesOtp;
    // Return loans for the authenticated user only
    public function index(Request $request)
    {
        $user = $request->user();
        $loans = QardHasan::with(['repayments', 'guarantors.branch'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($loans);
    }

    // Compute member's eligibility with policy adjustments (6-month rule and first-loan 5% cap)
    public function eligibility(Request $request)
    {
        $user = $request->user();
        $adj = $user->adjustedLoanEligibility();
        $months = $user->monthsInSystem();
        $canRequest = $months >= 6 && ($adj['eligibility_adjusted'] ?? 0) > 0;

        // Attaqwa Score and guidance
        $scoreEnabled = (bool) \App\Models\Setting::get('loan_credit_score_enabled', config('cooperative.loan_credit_score_enabled'));
        $scoreSvc = app(AttaqwaScoreService::class);
        $score = $scoreSvc->scoreForUser($user);

        $requiredMeetings = (int) \App\Models\Setting::get('required_loan_meetings', config('cooperative.attendance.required_loan_meetings', 8));
        $currentMeetings = $user->meetingAttendanceCount();

        if ($scoreEnabled) {
            $instant = ($score['score'] ?? 0) >= ($score['thresholds']['instant'] ?? AttaqwaScoreService::INSTANT_THRESHOLD);
            $low = ($score['score'] ?? 0) < ($score['thresholds']['low'] ?? AttaqwaScoreService::LOW_THRESHOLD);

            // Meeting attendance requirement for instant approval
            if ($currentMeetings < $requiredMeetings) {
                $instant = false;
            }
        } else {
            // If credit score is disabled, default to standard path (not instant, not low)
            $instant = false;
            $low = false;
        }

        // Score-based limit boost (applies only after first loan is completed)
        $boostPct = 0.0;
        if ($scoreEnabled) {
            $scoreVal = (float) ($score['score'] ?? 0);
            if ($scoreVal >= 90) {
                $boostPct = 15.0;
            } elseif ($scoreVal >= 80) {
                $boostPct = 10.0;
            } elseif ($scoreVal >= 70) {
                $boostPct = 5.0;
            }
        }
        $eligWithScore = (float) ($adj['eligibility_adjusted'] ?? 0);
        $hasCompleted = !$adj['is_first_loan'];

        // Self-heal stale defaulter flag if balance is 0
        if ($user->is_defaulter) {
            $user->syncLoanDefaulterStatus();
        }

        if ($user->is_defaulter || $user->hasActiveLoanPenalty()) {
            $canRequest = false;
            $eligWithScore = 0;
            $adj['eligibility_adjusted'] = 0;
        }

        if ($hasCompleted && $eligWithScore > 0 && $boostPct > 0) {
            $eligWithScore = round($eligWithScore * (1 + ($boostPct / 100.0)), 2);
        } else {
            // No boost on first loan (keeps 5% cap) or when score is low
            $boostPct = 0.0;
        }

        $recommendedDuration = \App\Support\DurationHelper::getLoanDuration($eligWithScore);

        $reason = null;
        if ($user->is_defaulter) {
            $reason = 'You cannot apply for a new loan until you clear your outstanding defaulted loan.';
        } elseif ($user->hasActiveLoanPenalty()) {
            $reason = "You must wait until {$user->loan_penalty_until->format('Y-m-d H:i')} before you can apply for a new loan due to your previous default.";
        } elseif ($months < 6) {
            $reason = 'Member must be in the system for at least 6 months before requesting a loan.';
        }

        $resp = array_merge($adj, [
            'can_request' => $canRequest,
            'reason' => $reason,
            'coop_score' => $score,
            'instant_approval' => $instant,
            'recommended_duration' => $recommendedDuration,
            'required_guarantors' => $instant ? 0 : ($low ? 3 : 2),
            'limit_boost_pct' => $boostPct,
            'eligibility_with_score' => $eligWithScore,
            'is_defaulted' => (bool) $user->is_defaulter,
            'meeting_attendance_count' => $currentMeetings,
            'required_loan_meetings' => $requiredMeetings,
            'features' => [
                'apply-for-loan' => Feature::for('global')->active('apply-for-loan'),
            ]
        ]);
        return response()->json($resp);
    }

    // Create a Qard Hasan loan for the authenticated member using auto principal and Loan ID
    public function store(Request $request)
    {
        $user = $request->user();

        if (Feature::for('global')->inactive('apply-for-loan')) {
            return response()->json(['message' => 'Loan applications are currently restricted for your account level or system-wide.'], 403);
        }

        $data = $request->validate([
            'total_installments' => ['required', 'integer', 'min:1'],
            'interval' => ['nullable', 'in:daily,weekly,monthly,Monthly,Weekly,Daily'],
            'admin_fee_flat' => ['nullable', 'numeric', 'min:0'],
            'admin_fee_pct' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'guarantor_ids' => ['nullable', 'array', 'max:3'],
            'guarantor_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'guarantor_memberships' => ['nullable', 'array', 'max:3'],
            'guarantor_memberships.*' => ['string', 'distinct'],
            'pin' => ['nullable', 'string'],
            'otp' => ['nullable', 'string'],
        ]);

        if ($request->filled('pin') && !$user->verifyTransactionPin($data['pin'])) {
            return response()->json(['message' => 'Invalid transaction PIN.'], 403);
        }

        if ($request->filled('otp') && !$this->verifyOtp($user, 'loan_request', $request->input('otp'))) {
            return response()->json(['message' => 'Invalid or expired authorization code (OTP).'], 403);
        }

        // Compute Attaqwa Score and derived requirements
        $scoreEnabled = (bool) \App\Models\Setting::get('loan_credit_score_enabled', config('cooperative.loan_credit_score_enabled'));
        $scoreSvc = app(AttaqwaScoreService::class);
        $score = $scoreSvc->scoreForUser($user);

        $requiredMeetings = (int) \App\Models\Setting::get('required_loan_meetings', config('cooperative.attendance.required_loan_meetings', 8));
        $currentMeetings = $user->meetingAttendanceCount();

        if ($scoreEnabled) {
            $instant = ($score['score'] ?? 0) >= ($score['thresholds']['instant'] ?? AttaqwaScoreService::INSTANT_THRESHOLD);
            $low = ($score['score'] ?? 0) < ($score['thresholds']['low'] ?? AttaqwaScoreService::LOW_THRESHOLD);

            // Meeting attendance requirement for instant approval
            if ($currentMeetings < $requiredMeetings) {
                $instant = false;
            }
        } else {
            // Default: no instant approval, 2 guarantors
            $instant = false;
            $low = false;
        }
        $requiredGuarantors = $instant ? 0 : ($low ? 3 : 2);

        // Compute policy-aware eligibility (handles 6-month rule and migrated status)
        $adj = $user->adjustedLoanEligibility();
        if ($user->monthsInSystem() < 6) {
            return response()->json(['message' => 'You must be a member for at least 6 months before requesting a loan.'], 422);
        }

        $principal = (float) ($adj['eligibility_adjusted'] ?? 0);

        // Apply score-based limit boost (only after first loan is completed OR if migrated)
        $boostPct = 0.0;
        if ($scoreEnabled) {
            $scoreVal = (float) ($score['score'] ?? 0);
            if ($scoreVal >= 90) {
                $boostPct = 15.0;
            } elseif ($scoreVal >= 80) {
                $boostPct = 10.0;
            } elseif ($scoreVal >= 70) {
                $boostPct = 5.0;
            }
        }

        // Allow boost if user has completed a loan OR is a migrated member
        if (($user->hasCompletedLoan() || $user->migrated_at) && $principal > 0 && $boostPct > 0) {
            $principal = round($principal * (1 + ($boostPct / 100.0)), 2);
        }

        if ($principal <= 0) {
            return response()->json(['message' => 'You are not eligible for a loan at this time.'], 422);
        }

        $totalInstallments = (int) $data['total_installments'];
        $maxDuration = \App\Support\DurationHelper::getLoanDuration($principal);

        if ($totalInstallments > $maxDuration) {
            return response()->json(['message' => "The maximum allowed duration for a loan of ₦" . number_format($principal, 2) . " is $maxDuration months."], 422);
        }

        $perInstallment = round($principal / max($totalInstallments, 1), 2);
        $interval = strtolower($data['interval'] ?? 'monthly');

        // Block if user already has an incomplete loan
        if ($user->hasActiveLoan()) {
            $msg = $user->is_defaulter
                ? 'You cannot apply for a new loan until you clear your outstanding defaulted loan.'
                : 'You must complete your existing loan before taking a new one.';
            return response()->json(['message' => $msg], 422);
        }

        // Block if user has an active loan penalty
        if ($user->hasActiveLoanPenalty()) {
            return response()->json(['message' => "You must wait until {$user->loan_penalty_until->format('Y-m-d H:i')} before you can apply for a new loan due to your previous default."], 422);
        }

        // Validate guarantors based on Attaqwa Score policy
        // Build guarantor ID list from either numeric IDs or membership numbers (alphanumeric)
        $guarantorIds = array_values(array_unique(array_map('intval', $data['guarantor_ids'] ?? [])));
        $membershipInputs = $data['guarantor_memberships'] ?? null;
        if (is_array($membershipInputs) && !empty($membershipInputs)) {
            // Normalize and deduplicate membership strings (case-insensitive)
            $map = [];
            foreach ($membershipInputs as $raw) {
                $code = trim((string) $raw);
                if ($code === '') continue;
                $k = strtolower($code);
                if (!isset($map[$k])) $map[$k] = $code;
            }
            foreach (array_values($map) as $mn) {
                $matches = User::where('membership_number', $mn)->get(['id','membership_number']);
                if ($matches->count() === 0) {
                    return response()->json(['message' => 'Guarantor not found for membership: ' . $mn], 422);
                }
                if ($matches->count() > 1) {
                    return response()->json(['message' => 'Multiple members found for membership: ' . $mn . '. Please select a different identifier or contact support.'], 422);
                }
                $guarantorIds[] = (int) $matches->first()->id;
            }
            $guarantorIds = array_values(array_unique($guarantorIds));
        }

        if ($requiredGuarantors > 0) {
            if (count($guarantorIds) < $requiredGuarantors || count($guarantorIds) > 3) {
                return response()->json(['message' => 'Select at least ' . $requiredGuarantors . ' and at most three guarantors.'], 422);
            }
            if (in_array($user->id, $guarantorIds, true)) {
                return response()->json(['message' => 'You cannot select yourself as a guarantor.'], 422);
            }
            $guarantors = User::with('branch')
                ->whereIn('id', $guarantorIds)
                ->get();
            if ($guarantors->count() !== count($guarantorIds)) {
                return response()->json(['message' => 'One or more guarantors are invalid.'], 422);
            }
            // Must not be defaulters at time of creating the loan
            $defaulters = $guarantors->where('is_defaulter', true);
            if ($defaulters->isNotEmpty()) {
                return response()->json(['message' => 'Selected guarantors must not be in default.'], 422);
            }
        } else {
            // Instant approval path: guarantors optional, ignore if provided
            $guarantors = collect();
            $guarantorIds = [];
        }

        $q = QardHasan::create([
            'user_id' => $user->id,
            'qard_id_string' => 'QH-'.now()->format('Y').'-'.Str::upper(Str::random(6)),
            'principal_amount' => $principal,
            'total_installments' => $totalInstallments,
            'per_installment' => $perInstallment,
            'interval' => $interval,
            'admin_fee_flat' => $data['admin_fee_flat'] ?? 0,
            'admin_fee_pct' => $data['admin_fee_pct'] ?? 0,
            'paid_amount' => 0,
            'meeting_attendance_count' => $user->meetingAttendanceCount(),
            'status' => $instant ? 'active' : 'pending', // Instant approval activates immediately
        ]);

        if ($instant) {
            // Instant approval: credit wallet now and notify
            $principalAmount = (float) $q->principal_amount;
            $fee = (float) ($q->admin_fee_flat ?? 0) + ($principalAmount * ((float) ($q->admin_fee_pct ?? 0) / 100));
            $credit = max($principalAmount - $fee, 0);

            $reference = 'QHDISB-'.now()->format('YmdHis').'-'.$user->id.'-'.Str::upper(Str::random(6));
            DB::transaction(function () use ($q, $user, $credit, $reference) {
                // Ensure status is active and credit wallet
                $q->update(['status' => 'active']);
                $user->increment('balance', $credit);

                // Record wallet transaction for loan disbursement (default: internal credit, non-withdrawable)
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'credit',
                    'amount' => $credit,
                    'reference' => $reference,
                    'source' => 'loan_disbursement',
                    'withdrawable' => false,
                    'meta' => [
                        'qard_hasan_id' => $q->id,
                        'qard_id_string' => $q->qard_id_string,
                        'mode' => 'internal',
                        'auto_instant' => true,
                    ],
                ]);
            });

            // Refresh relations
            $q->refresh();
            $q->loadMissing('user');

            // Emails
            try {
                if (!empty($q->user?->email)) {
                    Mail::to($q->user->email)->send(new LoanDisbursedUser($q, $credit));
                }
                $adminEmails = User::query()->where('is_admin', true)->whereNotNull('email')->pluck('email')->all();
                if (!empty($adminEmails)) {
                    Mail::to($adminEmails)->send(new LoanDisbursedAdminNotification($q, $credit));
                }
            } catch (\Throwable $e) {
                // ignore email errors
            }

            // Best-effort: Notification to admins (Filament) about disbursement
            try {
                $q->user->getAuthorizedAdmins()->each(function ($a) use ($q, $credit) {
                    $memberName = $q->user?->full_name ?: 'Member';
                    $body = 'Loan ' . $q->qard_id_string . ' disbursed: ₦' . number_format($credit, 2) . ' to ' . $memberName;
                    $a->notifyMember('Loan Disbursed', $body, [
                        'type' => 'loan_disbursed_admin',
                        'loan_id' => $q->id,
                        'qard_id_string' => $q->qard_id_string,
                        'member_id' => $q->user?->id,
                        'credited_amount' => (float) $credit,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to notify admins of loan disbursement: ' . $e->getMessage());
            }

            // Notify member via preferences (SMS, Push, Email, Database)
            if ($q->user) {
                $msg = 'Loan approved instantly: ₦'.number_format($credit, 2).' credited. Loan ID: '.($q->qard_id_string).'. Bal: ₦'.number_format((float) ($q->user->balance ?? 0), 2);
                $q->user->notifyMember('Loan Approved', $msg, [
                    'type' => 'loan_disbursed',
                    'loan_id' => $q->id,
                    'qard_id_string' => $q->qard_id_string,
                    'credited_amount' => $credit,
                    'balance' => (float) ($q->user->balance ?? 0),
                ]);
            }

            ShariahAudit::log($user, 'create_qard_hasan_instant', [
                'qard' => $q->qard_id_string,
                'principal' => $principal,
                'eligibility' => $adj,
                'coop_score' => $score,
                'credited_amount' => $credit,
                'instant_approval' => true,
                'required_guarantors' => 0,
            ]);

            return response()->json(array_merge($q->toArray(), [
                'credited_amount' => $credit,
                'instant_approved' => true,
            ]), 201);
        } else {
            // Attach guarantors with pending status and unique tokens
            $attach = [];
            foreach ($guarantorIds as $gid) {
                $attach[$gid] = [
                    'status' => 'pending',
                    'token' => Str::upper(Str::random(10)),
                ];
            }
            $q->guarantors()->attach($attach);
            $q->loadMissing(['guarantors.branch', 'user']);

            // Notify guarantors via preferences
            foreach ($guarantors as $g) {
                $msg = 'Guarantor request: Member '.($user->name).' requested a loan (ID: '.($q->qard_id_string).', ₦'.number_format((float)$q->principal_amount, 2).'). Please open your Coop app > Loans to Accept or Decline.';
                $g->notifyMember('Guarantor Request', $msg, [
                    'type' => 'guarantor_request',
                    'loan_id' => $q->id,
                    'qard_id_string' => $q->qard_id_string,
                ]);
            }

            // Email admins about new loan request (best-effort)
            try {
                $adminEmails = $user->getAuthorizedAdmins()
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
                    Mail::to($adminEmails)->send(new LoanRequestedAdminNotification($q));
                }
            } catch (\Throwable $e) {
                // ignore email errors
            }

            ShariahAudit::log($user, 'create_qard_hasan_auto', [
                'qard' => $q->qard_id_string,
                'principal' => $principal,
                'eligibility' => $adj,
                'coop_score' => $score,
                'guarantors' => $guarantorIds,
                'instant_approval' => false,
                'required_guarantors' => $requiredGuarantors,
            ]);

            return response()->json([
                'loan' => $q,
                'message' => "Loan request submitted. You have attended {$q->meeting_attendance_count} meetings. Final loan approval will be decided by the administrator or president.",
            ], 201);
        }
    }

    // Repay endpoint: applies payment toward principal, enforces remaining cap, and returns transparent summary
    public function repay(Request $request, int $id)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'source' => ['nullable', 'in:auto,wallet,paystack,flutterwave,monnify,opay,bank_transfer,ussd'],
            'callback_url' => ['nullable', 'url'],
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($id, $data, $user, $request) {
            $q = QardHasan::lockForUpdate()
                ->where('user_id', $user->id)
                ->findOrFail($id);

            if (in_array($q->status, ['completed', 'cancelled'])) {
                return response()->json(['message' => 'This loan is not eligible for repayment.'], 422);
            }

            // Block repayments until the loan is disbursed and active
            if (!in_array($q->status, ['active', 'defaulted'])) {
                return response()->json([
                    'message' => 'You cannot repay this loan until it has been disbursed and activated. Please wait for all guarantors to accept or contact the admin.'
                ], 422);
            }

            $before = [
                'paid_amount' => (float) $q->paid_amount,
                'remaining_principal' => max(0, (float) $q->principal_amount - (float) $q->paid_amount),
            ];

            if ($before['remaining_principal'] <= 0) {
                $q->status = 'completed';
                $q->save();
                return response()->json(['message' => 'This loan has already been fully repaid.'], 422);
            }

            $inputAmount = (float) $data['amount'];
            $appliedAmount = round(min($inputAmount, $before['remaining_principal']), 2);
            $wasCapped = $inputAmount > $before['remaining_principal'];

            $source = $data['source'] ?? 'auto';

            // Offline instruction paths (bank transfer / USSD)
            if (in_array($source, ['bank_transfer', 'ussd'], true)) {
                $va = [
                    'account_number' => $user->dva_account_number,
                    'account_name' => $user->dva_account_name,
                    'bank_name' => $user->dva_bank_name,
                ];
                $hasVa = !empty($va['account_number']) && !empty($va['bank_name']);

                // Best-effort audit log
                try {
                    ShariahAudit::log($user, 'repay_qard_hasan_instructions', [
                        'loan_id' => $q->id,
                        'qard' => $q->qard_id_string,
                        'source' => $source,
                        'expected_amount' => $appliedAmount,
                        'has_virtual_account' => $hasVa,
                    ]);
                } catch (\Throwable $e) {}

                $steps = [];
                if ($hasVa) {
                    if ($source === 'bank_transfer') {
                        $steps = [
                            'Open your banking app and make a transfer to the virtual account below.',
                            'Use the loan ID ' . $q->qard_id_string . ' as narration/reference if your bank supports it.',
                            'Once funds reflect in your wallet, return to this screen and choose Wallet as the source to apply repayment instantly.'
                        ];
                    } else { // ussd
                        $steps = [
                            'Dial your bank\'s USSD code and choose transfer.',
                            'Transfer the amount to the virtual account below.',
                            'Use the loan ID ' . $q->qard_id_string . ' as narration/reference if prompted.',
                            'After funds reflect in your wallet, choose Wallet as the source to apply repayment.'
                        ];
                    }
                } else {
                    $steps = [
                        'Assign your dedicated virtual account first.',
                        'Use the endpoint /api/virtual-account/assign from your profile settings to generate a virtual account.',
                        'Then make a transfer/USSD payment to that account and repay using Wallet once funds reflect.'
                    ];
                }

                return response()->json([
                    'instructions' => [
                        'method' => $source,
                        'expected_amount' => $appliedAmount,
                        'virtual_account' => $hasVa ? $va : null,
                        'assign_endpoint' => $hasVa ? null : '/api/virtual-account/assign',
                        'reference_hint' => $q->qard_id_string,
                        'steps' => $steps,
                        'note' => 'Transfers credit your wallet first; repayment is applied from wallet to maintain transparent records.'
                    ],
                    'summary' => [
                        'amount_input' => $inputAmount,
                        'amount_applied' => $appliedAmount,
                        'capped' => $wasCapped,
                        'before' => $before,
                        'after' => [
                            'paid_amount' => (float) $q->paid_amount,
                            'remaining_principal' => $q->remaining_principal,
                        ],
                        'source' => $source,
                        'initiated' => false,
                    ],
                ]);
            }

            // Decide funding path
            $useWallet = $source === 'wallet' || ($source === 'auto' && ((float)$user->balance >= $appliedAmount));
            if ($source === 'wallet' && ((float)$user->balance < $appliedAmount)) {
                return response()->json(['message' => 'Insufficient wallet balance'], 422);
            }

            if ($useWallet) {
                // Wallet path: deduct and mark repayment successful immediately
                $reference = 'QHREP-WALLET-' . now()->format('YmdHis') . '-' . $user->id . '-' . Str::upper(Str::random(5));

                // Create repayment record
                $rep = QardHasanRepayment::create([
                    'qard_hasan_id' => $q->id,
                    'amount' => $appliedAmount,
                    'reference' => $reference,
                    'status' => 'success',
                    'paid_at' => now(),
                ]);

                // Deduct wallet and record transaction
                $user->decrement('balance', $appliedAmount);
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'debit',
                    'amount' => $appliedAmount,
                    'reference' => $reference,
                    'source' => 'loan_repayment',
                    'meta' => [
                        'qard_hasan_id' => $q->id,
                        'qard_id_string' => $q->qard_id_string,
                    ],
                ]);

                // Update aggregates
                $q->paid_amount = (float) $q->paid_amount + $appliedAmount;
                if ($q->paid_amount >= $q->principal_amount) {
                    $q->status = 'completed';
                }
                $q->save();
                $q->refresh();

                // Best-effort: email receipt to user (do not block on failure)
                try {
                    if (!empty($user->email)) {
                        Mail::to($user->email)->send(new RepaymentReceiptUser($q, $rep));
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to send repayment receipt email (wallet path)', [
                        'user_id' => $user->id,
                        'loan_id' => $q->id,
                        'repayment_id' => $rep->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                ShariahAudit::log($user, 'repay_qard_hasan_wallet', [
                    'qard' => $q->qard_id_string,
                    'amount_input' => $inputAmount,
                    'amount_applied' => $appliedAmount,
                    'reference' => $rep->reference,
                ]);

                // Notify member via preferences
                $remaining = number_format((float) $q->remaining_principal, 2);
                $newBal = number_format((float) $user->balance, 2);
                $msg = 'Loan repayment: ₦'.number_format($appliedAmount, 2).' applied to '.($q->qard_id_string).'. Remaining: ₦'.$remaining.'. Ref: '.$rep->reference.'. Wallet: ₦'.$newBal;
                $user->notifyMember('Loan Repayment', $msg, [
                    'type' => 'loan_repayment',
                    'loan_id' => $q->id,
                    'repayment_id' => $rep->id,
                    'amount' => $appliedAmount,
                    'remaining' => (float) $q->remaining_principal,
                ]);

                $after = [
                    'paid_amount' => (float) $q->paid_amount,
                    'remaining_principal' => $q->remaining_principal,
                ];

                return response()->json([
                    'qard' => $q,
                    'repayment' => $rep,
                    'summary' => [
                        'amount_input' => $inputAmount,
                        'amount_applied' => $appliedAmount,
                        'capped' => $wasCapped,
                        'before' => $before,
                        'after' => $after,
                        'source' => 'wallet',
                    ],
                ]);
            }

            // Paystack path: initialize payment and create pending repayment
            // Pre-validate user's email for online gateway flows (Paystack requires a valid email)
            $rawEmail = (string) ($user->email ?? '');
            $isEmailValid = !empty($rawEmail) && filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
            if (!$isEmailValid) {
                Log::warning('Loan repayment via gateway blocked due to invalid/missing email', [
                    'user_id' => $user->id,
                    'loan_id' => $q->id,
                    'email' => $user->email,
                ]);
                return response()->json([
                    'message' => 'Your profile email is missing or invalid for online payment. Please update your email to a valid, supported address and try again (or choose Wallet if you have sufficient balance).',
                ], 422);
            }

            // If explicitly requested, initialize via Flutterwave
            if ($source === 'monnify') {
                $reference = 'QHREP-MON-' . now()->format('YmdHis') . '-' . $user->id . '-' . Str::upper(Str::random(5));
                $rep = QardHasanRepayment::create([
                    'qard_hasan_id' => $q->id,
                    'amount' => $appliedAmount,
                    'reference' => $reference,
                    'status' => 'pending',
                ]);

                $service = app(MonnifyService::class);
                $monnifyData = $service->initializeTransaction([
                    'amount' => round($appliedAmount, 2),
                    'customerName' => $user->name,
                    'customerEmail' => $user->email,
                    'paymentReference' => $reference,
                    'paymentDescription' => 'Loan Repayment: ' . $q->qard_id_string,
                    'redirectUrl' => $data['callback_url'] ?? config('app.url'),
                ]);

                if (!$monnifyData) {
                    return response()->json(['message' => 'Failed to initialize Monnify payment'], 502);
                }

                return response()->json([
                    'authorization_url' => $monnifyData['checkoutUrl'] ?? null,
                    'checkout_url' => $monnifyData['checkoutUrl'] ?? null,
                    'reference' => $reference,
                    'summary' => [
                        'amount_input' => $inputAmount,
                        'amount_applied' => $appliedAmount,
                        'capped' => $wasCapped,
                        'source' => 'monnify',
                        'initiated' => true,
                    ],
                ]);
            }

            if ($source === 'opay') {
                $reference = 'QHREP-OPY-' . now()->format('YmdHis') . '-' . $user->id . '-' . Str::upper(Str::random(5));
                $rep = QardHasanRepayment::create([
                    'qard_hasan_id' => $q->id,
                    'amount' => $appliedAmount,
                    'reference' => $reference,
                    'status' => 'pending',
                ]);

                $service = app(OpayService::class);
                $opayData = $service->initializeTransaction([
                    'amount' => round($appliedAmount, 2),
                    'customerName' => $user->name,
                    'customerEmail' => $user->email,
                    'reference' => $reference,
                    'paymentDescription' => 'Loan Repayment: ' . $q->qard_id_string,
                    'callbackUrl' => $data['callback_url'] ?? config('app.url'),
                ]);

                if (!$opayData) {
                    return response()->json(['message' => 'Failed to initialize Opay payment'], 502);
                }

                return response()->json([
                    'authorization_url' => $opayData['cashierUrl'] ?? null,
                    'checkout_url' => $opayData['cashierUrl'] ?? null,
                    'reference' => $reference,
                    'summary' => [
                        'amount_input' => $inputAmount,
                        'amount_applied' => $appliedAmount,
                        'capped' => $wasCapped,
                        'source' => 'opay',
                        'initiated' => true,
                    ],
                ]);
            }

            if ($source === 'flutterwave') {
                $flwSecret = config('services.flutterwave.secret_key');
                if (!$flwSecret) {
                    Log::warning('Flutterwave secret key is not set');
                    return response()->json(['message' => 'Payment provider not configured'], 500);
                }

                $reference = 'QHREP_FLW_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

                $payloadFlw = [
                    'tx_ref' => $reference,
                    'amount' => round($appliedAmount, 2),
                    'currency' => 'NGN',
                    'redirect_url' => $data['callback_url'] ?? null,
                    'customer' => [
                        'email' => $user->email,
                        'name' => $user->name,
                        'phonenumber' => $user->phone,
                    ],
                    'meta' => [
                        'user_id' => $user->id,
                        'loan_repayment' => true,
                        'qard_hasan_id' => $q->id,
                        'qard_id_string' => $q->qard_id_string,
                        'expected_amount' => $appliedAmount,
                    ],
                ];
                if (empty($data['callback_url'])) {
                    unset($payloadFlw['redirect_url']);
                }

                $respFlw = Http::withToken($flwSecret)
                    ->acceptJson()
                    ->post('https://api.flutterwave.com/v3/payments', $payloadFlw);

                if (!$respFlw->ok() || ($respFlw->json('status') !== 'success')) {
                    $body = $respFlw->json();
                    Log::error('Flutterwave loan repayment initialize failed', ['reference' => $reference, 'body' => $body]);
                    return response()->json([
                        'message' => 'Failed to initialize payment',
                        'errors' => is_array($body) ? ($body['message'] ?? 'Unknown error') : 'Unknown error',
                    ], 502);
                }

                $dataFlw = $respFlw->json('data');

                // Create pending repayment record linked to this loan
                $rep = QardHasanRepayment::create([
                    'qard_hasan_id' => $q->id,
                    'amount' => $appliedAmount,
                    'reference' => $reference, // match webhook tx_ref
                    'status' => 'pending',
                    'paid_at' => null,
                ]);

                ShariahAudit::log($user, 'repay_qard_hasan_flutterwave_init', [
                    'qard' => $q->qard_id_string,
                    'amount_input' => $inputAmount,
                    'amount_applied' => $appliedAmount,
                    'reference' => $rep->reference,
                ]);

                return response()->json([
                    'authorization_url' => $dataFlw['link'] ?? null,
                    'reference' => $rep->reference,
                    'summary' => [
                        'amount_input' => $inputAmount,
                        'amount_applied' => $appliedAmount,
                        'capped' => $wasCapped,
                        'before' => $before,
                        'after' => [
                            'paid_amount' => (float) $q->paid_amount,
                            'remaining_principal' => $q->remaining_principal,
                        ],
                        'source' => 'flutterwave',
                        'initiated' => true,
                    ],
                ]);
            }

            $secret = config('services.paystack.secret_key');
            if (!$secret) {
                Log::warning('Paystack secret key is not set');
                return response()->json(['message' => 'Payment provider not configured'], 500);
            }

            $reference = 'QHREP_PS_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

            $payload = [
                'email' => $user->email,
                'amount' => (int) round($appliedAmount * 100), // Kobo
                'reference' => $reference,
                'currency' => 'NGN',
                'metadata' => [
                    'user_id' => $user->id,
                    'loan_repayment' => true,
                    'qard_hasan_id' => $q->id,
                    'qard_id_string' => $q->qard_id_string,
                    'expected_amount' => $appliedAmount,
                ],
            ];
            if (!empty($data['callback_url'])) {
                $payload['callback_url'] = $data['callback_url'];
            }

            $response = Http::withToken($secret)
                ->acceptJson()
                ->post('https://api.paystack.co/transaction/initialize', $payload);

            if (!$response->ok() || !($response->json('status') === true)) {
                $body = $response->json();
                Log::error('Paystack loan repayment initialize failed', ['reference' => $reference, 'body' => $body]);

                $psMessage = is_array($body) ? ($body['message'] ?? null) : null;
                $psType = is_array($body) ? ($body['type'] ?? null) : null;
                $psCode = is_array($body) ? ($body['code'] ?? null) : null;

                $msgLower = is_string($psMessage) ? strtolower($psMessage) : '';
                $isEmailError = ($psCode === 'invalid_email_address') || ($psType === 'validation_error' && str_contains($msgLower, 'email'));

                if ($isEmailError) {
                    return response()->json([
                        'message' => 'Your email address is not supported for online payment. Please update your profile email to a valid address and try again, or choose Wallet if you have sufficient balance.',
                        'provider_message' => $psMessage,
                    ], 422);
                }

                return response()->json([
                    'message' => 'Failed to initialize payment',
                    'errors' => $psMessage ?? 'Unknown error',
                ], 502);
            }

            $dataPs = $response->json('data');

            // Create pending repayment record linked to this loan
            $rep = QardHasanRepayment::create([
                'qard_hasan_id' => $q->id,
                'amount' => $appliedAmount,
                'reference' => $dataPs['reference'] ?? $reference,
                'status' => 'pending',
                'paid_at' => null,
            ]);

            ShariahAudit::log($user, 'repay_qard_hasan_paystack_init', [
                'qard' => $q->qard_id_string,
                'amount_input' => $inputAmount,
                'amount_applied' => $appliedAmount,
                'reference' => $rep->reference,
            ]);

            return response()->json([
                'authorization_url' => $dataPs['authorization_url'] ?? null,
                'access_code' => $dataPs['access_code'] ?? null,
                'reference' => $rep->reference,
                'summary' => [
                    'amount_input' => $inputAmount,
                    'amount_applied' => $appliedAmount,
                    'capped' => $wasCapped,
                    'before' => $before,
                    'after' => [
                        'paid_amount' => (float) $q->paid_amount,
                        'remaining_principal' => $q->remaining_principal,
                    ],
                    'source' => 'paystack',
                    'initiated' => true,
                ],
            ]);
        });
    }

    // Member uploads the signed agreement for a loan
    public function uploadAgreement(Request $request, int $id)
    {
        $user = $request->user();
        $q = QardHasan::where('user_id', $user->id)->findOrFail($id);

        if ($q->status !== 'pending') {
            return response()->json(['message' => 'Agreement can only be uploaded for pending loans.'], 422);
        }

        if (empty($q->approved_at)) {
            return response()->json(['message' => 'This loan has not been approved yet. Please wait for admin approval before uploading the signed agreement.'], 422);
        }

        $request->validate([
            'signed_agreement' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB limit
        ]);

        $path = $request->file('signed_agreement')->store('loan-signed', 'public');

        $q->update([
            'signed_agreement' => $path,
            'agreement_uploaded_at' => now(),
            'agreement_rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Agreement uploaded successfully. Admin will verify it before disbursement.',
            'signed_agreement' => $path,
        ]);
    }

    public function analysis(Request $request)
    {
        $user = $request->user();
        $loans = QardHasan::with(['guarantors.branch'])->where('user_id', $user->id)->get();

        $totalBorrowed = (float) $loans->sum('principal_amount');
        $totalPaid = (float) $loans->sum('paid_amount');
        $outstanding = $totalBorrowed - $totalPaid;
        $loanCount = $loans->count();
        $activeLoansCount = $loans->whereIn('status', ['active', 'defaulted'])->count();

        // Repayment history over last 6 months
        $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
        $repayments = QardHasanRepayment::whereIn('qard_hasan_id', $loans->pluck('id'))
            ->where('status', 'success')
            ->where('paid_at', '>=', $sixMonthsAgo)
            ->orderBy('paid_at')
            ->get();

        $repaymentTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('M Y');
            $amount = $repayments->filter(function ($r) use ($month) {
                return $r->paid_at && $r->paid_at->format('Y-m') === $month->format('Y-m');
            })->sum('amount');
            $repaymentTrend[$key] = (float) $amount;
        }

        // Status distribution
        $statusDist = $loans->groupBy('status')->map->count();

        return response()->json([
            'summary' => [
                'total_borrowed' => $totalBorrowed,
                'total_paid' => $totalPaid,
                'outstanding' => $outstanding,
                'loan_count' => $loanCount,
                'active_loans_count' => $activeLoansCount,
            ],
            'repayment_trend' => $repaymentTrend,
            'status_distribution' => $statusDist,
            'loans' => $loans->sortByDesc('created_at')->values(),
            'recent_loans' => $loans->sortByDesc('created_at')->take(5)->values(),
        ]);
    }
}
