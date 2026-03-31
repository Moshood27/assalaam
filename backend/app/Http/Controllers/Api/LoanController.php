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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\RepaymentReceiptUser;
use App\Mail\LoanDisbursedUser;
use App\Mail\LoanDisbursedAdminNotification;
use App\Services\CoopScoreService;
use App\Notifications\LoanApprovedNotification;

class LoanController extends Controller
{
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

        // Coop Score and guidance
        $scoreSvc = app(CoopScoreService::class);
        $score = $scoreSvc->scoreForUser($user);
        $instant = ($score['score'] ?? 0) >= ($score['thresholds']['instant'] ?? CoopScoreService::INSTANT_THRESHOLD);
        $low = ($score['score'] ?? 0) < ($score['thresholds']['low'] ?? CoopScoreService::LOW_THRESHOLD);

        // Score-based limit boost (applies only after first loan is completed)
        $boostPct = 0.0;
        $scoreVal = (float) ($score['score'] ?? 0);
        if ($scoreVal >= 90) {
            $boostPct = 15.0;
        } elseif ($scoreVal >= 80) {
            $boostPct = 10.0;
        } elseif ($scoreVal >= 70) {
            $boostPct = 5.0;
        }
        $eligWithScore = (float) ($adj['eligibility_adjusted'] ?? 0);
        $hasCompleted = !$adj['is_first_loan'];
        if ($hasCompleted && $eligWithScore > 0 && $boostPct > 0) {
            $eligWithScore = round($eligWithScore * (1 + ($boostPct / 100.0)), 2);
        } else {
            // No boost on first loan (keeps 5% cap) or when score is low
            $boostPct = 0.0;
        }

        $resp = array_merge($adj, [
            'can_request' => $canRequest,
            'reason' => $months < 6 ? 'Member must be in the system for at least 6 months before requesting a loan.' : null,
            'coop_score' => $score,
            'instant_approval' => $instant,
            'required_guarantors' => $instant ? 0 : ($low ? 3 : 2),
            'limit_boost_pct' => $boostPct,
            'eligibility_with_score' => $eligWithScore,
        ]);
        return response()->json($resp);
    }

    // Create a Qard Hasan loan for the authenticated member using auto principal and Loan ID
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'total_installments' => ['required', 'integer', 'min:1'],
            'interval' => ['nullable', 'in:daily,weekly,monthly,Monthly,Weekly,Daily'],
            'admin_fee_flat' => ['nullable', 'numeric', 'min:0'],
            'admin_fee_pct' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'guarantor_ids' => ['nullable', 'array', 'max:3'],
            'guarantor_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        // Compute Coop Score and derived requirements
        $scoreSvc = app(CoopScoreService::class);
        $score = $scoreSvc->scoreForUser($user);
        $instant = ($score['score'] ?? 0) >= ($score['thresholds']['instant'] ?? CoopScoreService::INSTANT_THRESHOLD);
        $low = ($score['score'] ?? 0) < ($score['thresholds']['low'] ?? CoopScoreService::LOW_THRESHOLD);
        $requiredGuarantors = $instant ? 0 : ($low ? 3 : 2);

        // Enforce 6-month membership before requesting any loan
        if ($user->monthsInSystem() < 6) {
            return response()->json(['message' => 'You must be a member for at least 6 months before requesting a loan.'], 422);
        }

        // Calculate base totals and policy-adjusted principal
        $calc = $user->savingsSharesEligibility();
        $base = (float) ($calc['base'] ?? 0);
        $principal = $user->hasCompletedLoan()
            ? round($base * 2, 2)               // After completing first loan: full eligibility (2x)
            : round($base * 0.05, 2);           // First loan: 5% of (Savings + Shares)

        // Apply score-based limit boost (only after first loan is completed)
        $boostPct = 0.0;
        $scoreVal = (float) ($score['score'] ?? 0);
        if ($scoreVal >= 90) {
            $boostPct = 15.0;
        } elseif ($scoreVal >= 80) {
            $boostPct = 10.0;
        } elseif ($scoreVal >= 70) {
            $boostPct = 5.0;
        }
        if ($user->hasCompletedLoan() && $principal > 0 && $boostPct > 0) {
            $principal = round($principal * (1 + ($boostPct / 100.0)), 2);
        }

        if ($principal <= 0) {
            return response()->json(['message' => 'You are not eligible for a loan at this time.'], 422);
        }

        $totalInstallments = (int) $data['total_installments'];
        $perInstallment = round($principal / max($totalInstallments, 1), 2);
        $interval = strtolower($data['interval'] ?? 'monthly');

        // Block if user already has an incomplete loan
        $hasOpenLoan = QardHasan::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->exists();
        if ($hasOpenLoan) {
            return response()->json(['message' => 'You must complete your existing loan before taking a new one.'], 422);
        }

        // Validate guarantors based on Coop Score policy
        $guarantorIds = array_values(array_unique($data['guarantor_ids'] ?? []));
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
            // Guarantors must be from different branches
            $branchIds = $guarantors->pluck('branch_id')->all();
            if (in_array(null, $branchIds, true)) {
                return response()->json(['message' => 'All guarantors must belong to a branch.'], 422);
            }
            if (count(array_unique($branchIds)) !== count($branchIds)) {
                return response()->json(['message' => 'Guarantors must be selected from different branches.'], 422);
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
            'status' => $instant ? 'active' : 'pending', // Instant approval activates immediately
        ]);

        if ($instant) {
            // Instant approval: credit wallet now and notify
            $principalAmount = (float) $q->principal_amount;
            $fee = (float) ($q->admin_fee_flat ?? 0) + ($principalAmount * ((float) ($q->admin_fee_pct ?? 0) / 100));
            $credit = max($principalAmount - $fee, 0);

            DB::transaction(function () use ($q, $user, $credit) {
                // Ensure status is active and credit wallet
                $q->update(['status' => 'active']);
                $user->increment('balance', $credit);
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

            // Best-effort: Push notification to admins (Filament) about disbursement
            try {
                $admins = User::query()
                    ->where('is_admin', true)
                    ->get(['id', 'name', 'device_token', 'fcm_token']);
                if ($admins->isNotEmpty()) {
                    $push = app(\App\Services\PushService::class);
                    $title = 'Loan Disbursed';
                    $memberName = $q->user?->name ?: 'Member';
                    $body = 'Loan ' . $q->qard_id_string . ' disbursed: ₦' . number_format($credit, 2) . ' to ' . $memberName;
                    foreach ($admins as $a) {
                        $token = $a->fcm_token ?: $a->device_token;
                        if (!empty($token)) {
                            $push->send($token, $title, $body, [
                                'type' => 'loan_disbursed_admin',
                                'loan_id' => $q->id,
                                'qard_id_string' => $q->qard_id_string,
                                'member_id' => $q->user?->id,
                                'credited_amount' => (float) $credit,
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore admin push errors
            }

            // Best-effort SMS + Push to member
            try {
                $fresh = $q->user?->fresh();
                if ($fresh) {
                    $sms = app(\App\Services\SmsService::class);
                    $push = app(\App\Services\PushService::class);
                    $msg = 'Loan approved instantly: ₦'.number_format($credit, 2).' credited. Loan ID: '.($q->qard_id_string).'. Bal: ₦'.number_format((float) ($fresh->balance ?? 0), 2);
                    $sms->send($fresh->phone ?? null, $msg);
                    $token = $fresh->fcm_token ?: ($fresh->device_token ?? null);
                    $push->send($token, 'Loan Approved', $msg, [
                        'type' => 'loan_disbursed',
                        'loan_id' => $q->id,
                        'qard_id_string' => $q->qard_id_string,
                        'credited_amount' => $credit,
                        'balance' => (float) ($fresh->balance ?? 0),
                    ]);
                    // Log to Inbox (database notifications)
                    try {
                        $fresh->notify(new LoanApprovedNotification(
                            title: 'Loan Approved',
                            message: $msg,
                            loanId: $q->id,
                            qardIdString: $q->qard_id_string,
                            creditedAmount: (float) $credit,
                            balance: (float) ($fresh->balance ?? 0),
                        ));
                    } catch (\Throwable $e) {
                        // swallow
                    }
                }
            } catch (\Throwable $e) {
                // ignore notification errors
            }

            ShariahAudit::log($user, 'create_qard_hasan_instant', [
                'qard' => $q->qard_id_string,
                'principal' => $principal,
                'eligibility' => $calc,
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
            $q->loadMissing(['guarantors.branch']);

            // Notify guarantors via SMS and Push (best-effort)
            try {
                $sms = app(\App\Services\SmsService::class);
                $push = app(\App\Services\PushService::class);
                foreach ($guarantors as $g) {
                    $msg = 'Guarantor request: Member '.($user->name).' requested a loan (ID: '.($q->qard_id_string).', ₦'.number_format((float)$q->principal_amount, 2).'). Please open your Coop app > Loans to Accept or Decline.';
                    $sms->send($g->phone ?? null, $msg);
                    // Push notification to guarantor device if available
                    $token = $g->fcm_token ?: ($g->device_token ?? null);
                    $push->send($token, 'Guarantor Request', $msg, [
                        'type' => 'guarantor_request',
                        'loan_id' => $q->id,
                        'qard_id_string' => $q->qard_id_string,
                    ]);
                }
            } catch (\Throwable $e) {
                // ignore notification errors
            }

            ShariahAudit::log($user, 'create_qard_hasan_auto', [
                'qard' => $q->qard_id_string,
                'principal' => $principal,
                'eligibility' => $calc,
                'coop_score' => $score,
                'guarantors' => $guarantorIds,
                'instant_approval' => false,
                'required_guarantors' => $requiredGuarantors,
            ]);

            return response()->json($q, 201);
        }
    }

    // Repay endpoint: applies payment toward principal, enforces remaining cap, and returns transparent summary
    public function repay(Request $request, int $id)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'source' => ['nullable', 'in:auto,wallet,paystack,flutterwave'],
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

                // Best-effort SMS notification to member
                try {
                    $user->refresh();
                    $sms = app(\App\Services\SmsService::class);
                    $remaining = number_format((float) $q->remaining_principal, 2);
                    $newBal = number_format((float) $user->balance, 2);
                    $msg = 'Loan repayment: ₦'.number_format($appliedAmount, 2).' applied to '.($q->qard_id_string).'. Remaining: ₦'.$remaining.'. Ref: '.$rep->reference.'. Wallet: ₦'.$newBal;
                    $sms->send($user->phone ?? null, $msg);
                } catch (\Throwable $e) {
                    // ignore SMS errors
                }

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
}
