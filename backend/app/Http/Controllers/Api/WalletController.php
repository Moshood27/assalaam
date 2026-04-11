<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\Project;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function resolveRecipient(Request $request)
    {
        $validated = $request->validate([
            'to_type' => 'required|in:phone,membership',
            'to' => 'required|string',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $recipient = null;
        $multiple = false;
        $branches = [];

        if ($validated['to_type'] === 'membership') {
            $mn = trim($validated['to']);
            $branchId = $validated['branch_id'] ?? null;
            if ($branchId) {
                $recipient = User::where('membership_number', $mn)->where('branch_id', $branchId)->first();
            } else {
                $matches = User::where('membership_number', $mn)->get();
                if ($matches->count() === 1) {
                    $recipient = $matches->first();
                } elseif ($matches->count() > 1) {
                    $multiple = true;
                    // Provide list of branches to help client disambiguate
                    $branchIds = $matches->pluck('branch_id')->filter()->unique()->values();
                    if ($branchIds->isNotEmpty()) {
                        $branches = Branch::whereIn('id', $branchIds)->get(['id','name'])->toArray();
                    }
                }
            }
        } else {
            $raw = trim($validated['to']);
            $digits = preg_replace('/[^0-9]/', '', $raw);
            $variants = array_values(array_filter(array_unique([
                $raw,
                $digits,
                (strlen($digits) === 11 && str_starts_with($digits, '0')) ? ('234'.substr($digits, 1)) : null,
                (strlen($digits) === 13 && str_starts_with($digits, '234')) ? ('0'.substr($digits, 3)) : null,
                $digits ? ('+'.$digits) : null,
            ])));
            if (!empty($variants)) {
                $recipient = User::whereIn('phone', $variants)->first();
            }
        }

        if ($multiple) {
            return response()->json([
                'message' => 'Multiple members found. Please select a branch.',
                'multiple' => true,
                'branches' => $branches,
            ], 422);
        }

        if (!$recipient) {
            return response()->json(['message' => 'Recipient not found'], 404);
        }

        $branchName = null;
        if (!empty($recipient->branch_id)) {
            $branch = Branch::find($recipient->branch_id);
            $branchName = $branch?->name;
        }

        return response()->json([
            'id' => $recipient->id,
            'name' => $recipient->name,
            'membership_number' => $recipient->membership_number,
            'branch_id' => $recipient->branch_id,
            'branch_name' => $branchName,
        ]);
    }

    public function getWallet(Request $request)
    {
        $user = $request->user();
        $recent = $user->walletTransactions()->latest()->limit(10)->get();

        // Calculate running balance for these 10 transactions
        $currentBalance = (float) $user->balance;
        foreach ($recent as $tx) {
            $tx->setAttribute('balance_after', (float) $currentBalance);
            $tx->setAttribute('running_balance', (float) $currentBalance);
            if (strtolower((string)$tx->type) === 'credit') {
                $currentBalance -= (float) $tx->amount;
            } else {
                $currentBalance += (float) $tx->amount;
            }
        }

        // Reuse helper for tiered withdrawal logic
        $breakdown = method_exists($user, 'withdrawableBreakdown') ? $user->withdrawableBreakdown() : [
            'available_for_withdrawal' => (float) $user->balance,
        ];

        return response()->json([
            'balance' => (float) $user->balance,
            'available_for_withdrawal' => (float) ($breakdown['available_for_withdrawal'] ?? 0),
            'breakdown' => $breakdown,
            'virtual_account' => [
                'paystack_customer_code' => $user->paystack_customer_code,
                'account_number' => $user->dva_account_number,
                'account_name' => $user->dva_account_name,
                'bank_name' => $user->dva_bank_name,
                'bvn_assigned' => (bool) ($user->bvn || $user->bvn_verified_at || ($user->dva_account_number && $user->dva_bank_name)),
                'verification_details' => ($user->dva_bank_name && $user->dva_account_number)
                    ? ($user->dva_bank_name . ' - ' . $user->dva_account_number . (
                        $user->dva_account_name ? (' (' . $user->dva_account_name . ')') : ''
                    ))
                    : null,
            ],
            'recent_transactions' => $recent,
        ]);
    }

    public function transactions(Request $request)
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'type' => 'nullable|in:credit,debit',
        ]);
        $user = $request->user();

        $query = $user->walletTransactions()->latest();
        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $perPage = $validated['per_page'] ?? 15;
        return response()->json($query->paginate($perPage));
    }

    public function initiateTopup(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'callback_url' => 'nullable|url',
            'gateway' => 'nullable|in:paystack,flutterwave',
        ]);

        $user = $request->user();
        $gateway = strtolower($validated['gateway'] ?? 'paystack');

        $reference = 'WALLET_TOPUP_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        if ($gateway === 'flutterwave') {
            $flwSecret = config('services.flutterwave.secret_key');
            if (!$flwSecret) {
                Log::warning('Flutterwave secret key is not set');
                return response()->json(['message' => 'Payment provider not configured'], 500);
            }

            $payloadFlw = [
                'tx_ref' => $reference,
                'amount' => round((float)$validated['amount'], 2),
                'currency' => 'NGN',
                'redirect_url' => $validated['callback_url'] ?? null,
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'phonenumber' => $user->phone,
                ],
                'meta' => [
                    'user_id' => $user->id,
                    'wallet_topup' => true,
                ],
            ];
            if (empty($validated['callback_url'])) {
                unset($payloadFlw['redirect_url']);
            }

            $respFlw = Http::withToken($flwSecret)
                ->acceptJson()
                ->post('https://api.flutterwave.com/v3/payments', $payloadFlw);

            if (!$respFlw->ok() || ($respFlw->json('status') !== 'success')) {
                Log::error('Flutterwave wallet topup initialize failed', ['reference' => $reference, 'body' => $respFlw->json()]);
                return response()->json([
                    'message' => 'Failed to initialize top-up',
                    'errors' => $respFlw->json('message') ?? 'Unknown error',
                ], 502);
            }

            $dataFlw = $respFlw->json('data');
            return response()->json([
                'authorization_url' => $dataFlw['link'] ?? null,
                'checkout_url' => $dataFlw['link'] ?? null,
                'reference' => $reference,
                'amount' => (float)$validated['amount'],
            ]);
        }

        $secret = config('services.paystack.secret_key');
        if (!$secret) {
            Log::warning('Paystack secret key is not set');
            return response()->json(['message' => 'Payment provider not configured'], 500);
        }

        $payload = [
            'email' => $user->email,
            'amount' => (int) round(((float)$validated['amount']) * 100), // Kobo
            'reference' => $reference,
            'currency' => 'NGN',
            'metadata' => [
                'user_id' => $user->id,
                'wallet_topup' => true,
            ],
        ];
        if (!empty($validated['callback_url'])) {
            $payload['callback_url'] = $validated['callback_url'];
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post('https://api.paystack.co/transaction/initialize', $payload);

        if (!$response->ok() || !($response->json('status') === true)) {
            Log::error('Paystack wallet topup initialize failed', ['reference' => $reference, 'body' => $response->json()]);
            return response()->json([
                'message' => 'Failed to initialize top-up',
                'errors' => $response->json('message') ?? 'Unknown error',
            ], 502);
        }

        $data = $response->json('data');
        return response()->json([
            'authorization_url' => $data['authorization_url'] ?? null,
            'checkout_url' => $data['authorization_url'] ?? null,
            'access_code' => $data['access_code'] ?? null,
            'reference' => $data['reference'] ?? $reference,
            'amount' => (float)$validated['amount'],
        ]);
    }

    public function allocateToSchemes(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.scheme_id' => 'required|exists:schemes,id',
            'items.*.project_id' => 'nullable|integer|exists:projects,id',
            'items.*.savings_group_id' => 'nullable|integer|exists:savings_groups,id',
            'items.*.units' => 'nullable|integer|min:1',
            'items.*.amount' => 'required|numeric|min:1',
            'pin' => ['required','regex:/^\d{4}$/'],
        ]);

        $user = $request->user();

        // Enforce Transaction PIN
        if (empty($user->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }
        if (!$user->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }

        $items = collect($validated['items'])
            ->map(function($i){
                $row = [
                    'scheme_id' => (int)$i['scheme_id'],
                    'amount' => (float)($i['amount'] ?? 0),
                    'units' => !empty($i['units']) ? (int)$i['units'] : null,
                ];
                if (!empty($i['project_id'])) {
                    $row['project_id'] = (int) $i['project_id'];
                }
                if (!empty($i['savings_group_id'])) {
                    $row['savings_group_id'] = (int) $i['savings_group_id'];
                }
                return $row;
            })
            ->filter(fn($i) => $i['amount'] > 0);

        // Add project_ids from savings groups if not already in items
        $savingsGroupIds = $items->pluck('savings_group_id')->filter()->unique()->values();
        $savingsGroups = $savingsGroupIds->isNotEmpty() ? \App\Models\SavingsGroup::whereIn('id', $savingsGroupIds)->get()->keyBy('id') : collect();

        $items = $items->map(function($item) use ($savingsGroups) {
            if (!empty($item['savings_group_id']) && empty($item['project_id'])) {
                $sg = $savingsGroups[$item['savings_group_id']] ?? null;
                if ($sg && $sg->project_id) {
                    $item['project_id'] = $sg->project_id;
                }
            }
            return $item;
        });

        // Validate any provided project_ids are active and check units
        $projectIds = $items->pluck('project_id')->filter()->unique()->values();
        if ($projectIds->isNotEmpty()) {
            $projects = Project::whereIn('id', $projectIds)->get()->keyBy('id');
            foreach ($items as $item) {
                if (!empty($item['project_id'])) {
                    $p = $projects[$item['project_id']] ?? null;
                    if (!$p || !($p->active)) {
                        return response()->json(['message' => 'Selected project is not available'], 422);
                    }
                    if ($p->is_unit_based) {
                        if (empty($item['units']) || $item['units'] <= 0) {
                            return response()->json(['message' => 'Units are required for unit-based project: ' . $p->name], 422);
                        }
                        if ($item['units'] > $p->available_units) {
                            return response()->json(['message' => "Only {$p->available_units} units available for " . $p->name], 422);
                        }
                    }
                }
            }
        }

        $total = $items->sum('amount');
        if ($total <= 0) {
            return response()->json(['message' => 'Total must be greater than zero'], 422);
        }

        if ((float)$user->balance < $total) {
            return response()->json(['message' => 'Insufficient wallet balance'], 422);
        }

        $reference = 'WALLET_ALLOC_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        $insufficient = false;
        DB::transaction(function () use ($user, $items, $reference, $total, &$insufficient) {
            $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->first();
            if ((float)$lockedUser->balance < (float)$total) {
                $insufficient = true;
                return;
            }

            foreach ($items as $item) {
                $row = [
                    'user_id' => $lockedUser->id,
                    'scheme_id' => $item['scheme_id'],
                    'amount' => $item['amount'],
                    'reference' => $reference,
                    'status' => 'success',
                ];
                if (!empty($item['project_id'])) {
                    $row['project_id'] = (int) $item['project_id'];
                    $row['units'] = $item['units'] ?? null;
                }
                if (!empty($item['savings_group_id'])) {
                    $row['savings_group_id'] = (int) $item['savings_group_id'];
                }
                Contribution::create($row);
            }

            // Deduct wallet safely
            $lockedUser->decrement('balance', $total);

            // Record debit transaction
            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'amount' => $total,
                'reference' => $reference,
                'source' => 'wallet_allocation',
                'meta' => [
                    'distribution' => $items->values()->all(),
                ],
            ]);
        });

        if ($insufficient) {
            return response()->json(['message' => 'Insufficient wallet balance'], 422);
        }

        // Return updated balance and summary
        $schemes = Scheme::whereIn('id', $items->pluck('scheme_id'))->pluck('name', 'id');
        $summary = $items->map(function ($i) use ($schemes) {
            return [
                'scheme_id' => $i['scheme_id'],
                'scheme_name' => $schemes[$i['scheme_id']] ?? '',
                'amount' => $i['amount'],
            ];
        });

        $user->refresh();

        // Notify member via preferences
        $user->notifyMember('Scheme Allocation', 'Wallet debit: ₦'.number_format($total, 2).' allocated to schemes. Ref: '.$reference.'. New bal: ₦'.number_format((float)$user->balance, 2), [
            'type' => 'wallet_allocation',
            'amount' => $total,
            'reference' => $reference,
            'balance' => (float)$user->balance,
        ]);

        return response()->json([
            'reference' => $reference,
            'debited' => $total,
            'balance' => (float)$user->balance,
            'distribution' => $summary,
        ]);
    }
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'to_type' => 'required|in:phone,membership',
            'to' => 'required|string',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'amount' => 'required|numeric|min:1',
            'pin' => ['required','regex:/^\\d{4}$/'],
            'note' => 'nullable|string|max:120',
        ]);

        $sender = $request->user();

        if (empty($sender->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }
        if (!$sender->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }

        $amount = (float) $validated['amount'];
        if ($amount <= 0) {
            return response()->json(['message' => 'Amount must be greater than zero'], 422);
        }

        // Resolve recipient
        $recipient = null;
        if ($validated['to_type'] === 'membership') {
            $mn = trim($validated['to']);
            $branchId = $validated['branch_id'] ?? null;
            if ($branchId) {
                $recipient = User::where('membership_number', $mn)->where('branch_id', $branchId)->first();
            } else {
                $matches = User::where('membership_number', $mn)->get();
                if ($matches->count() === 1) {
                    $recipient = $matches->first();
                } elseif ($matches->count() > 1) {
                    return response()->json(['message' => 'Multiple members found for this ID. Please select a branch.'], 422);
                }
            }
        } else {
            $raw = trim($validated['to']);
            $digits = preg_replace('/[^0-9]/', '', $raw);
            $variants = array_values(array_filter(array_unique([
                $raw,
                $digits,
                (strlen($digits) === 11 && str_starts_with($digits, '0')) ? ('234'.substr($digits, 1)) : null,
                (strlen($digits) === 13 && str_starts_with($digits, '234')) ? ('0'.substr($digits, 3)) : null,
                $digits ? ('+'.$digits) : null,
            ])));
            if (!empty($variants)) {
                $recipient = User::whereIn('phone', $variants)->first();
            }
        }

        if (!$recipient) {
            return response()->json(['message' => 'Recipient not found'], 404);
        }
        if ($recipient->id === $sender->id) {
            return response()->json(['message' => 'You cannot transfer to yourself'], 422);
        }

        if ((float)$sender->balance < $amount) {
            return response()->json(['message' => 'Insufficient wallet balance'], 422);
        }

        $groupRef = 'P2P_'.now()->format('YmdHis').'_FROM_'.$sender->id.'_'.bin2hex(random_bytes(3));
        $referenceDebit = $groupRef.'_D';
        $referenceCredit = $groupRef.'_C';
        $insufficient = false;
        $finalSenderBal = null;

        DB::transaction(function () use ($sender, $recipient, $amount, $groupRef, $referenceDebit, $referenceCredit, $validated, &$insufficient, &$finalSenderBal) {
            // Lock rows in ascending order to reduce deadlocks
            $ids = [$sender->id, $recipient->id];
            sort($ids);
            $locked = User::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
            $lockedSender = $locked[$sender->id];
            $lockedRecipient = $locked[$recipient->id];

            if ((float)$lockedSender->balance < (float)$amount) {
                $insufficient = true;
                return;
            }

            // Perform transfer
            $lockedSender->decrement('balance', $amount);
            $lockedRecipient->increment('balance', $amount);

            $metaDebit = [
                'to_user_id' => $lockedRecipient->id,
                'to_name' => $lockedRecipient->name,
                'to_membership' => $lockedRecipient->membership_number,
                'group_ref' => $groupRef,
            ];
            if (!empty($validated['note'])) $metaDebit['note'] = $validated['note'];

            $metaCredit = [
                'from_user_id' => $lockedSender->id,
                'from_name' => $lockedSender->name,
                'from_membership' => $lockedSender->membership_number,
                'group_ref' => $groupRef,
            ];
            if (!empty($validated['note'])) $metaCredit['note'] = $validated['note'];

            // Determine how much of this transfer came from withdrawable vs restricted funds
            $senderAvail = method_exists($lockedSender, 'availableForWithdrawal') ? (float) $lockedSender->availableForWithdrawal() : (float) $lockedSender->balance;
            $withdrawablePortion = max(0.0, min((float) $amount, $senderAvail));
            $restrictedPortion = max(0.0, (float) $amount - $withdrawablePortion);

            // Record sender debit with breakdown
            $metaDebit['withdrawable_portion'] = round($withdrawablePortion, 2);
            $metaDebit['restricted_portion'] = round($restrictedPortion, 2);
            WalletTransaction::create([
                'user_id' => $lockedSender->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $referenceDebit,
                'source' => 'p2p_transfer',
                'meta' => $metaDebit,
            ]);

            // Record recipient credits, splitting to preserve restrictions
            if ($withdrawablePortion > 0) {
                $metaCreditW = $metaCredit;
                $metaCreditW['portion'] = 'withdrawable';
                $metaCreditW['portion_amount'] = round($withdrawablePortion, 2);
                WalletTransaction::create([
                    'user_id' => $lockedRecipient->id,
                    'type' => 'credit',
                    'amount' => $withdrawablePortion,
                    'reference' => $referenceCredit . '-W',
                    'source' => 'p2p_transfer',
                    'withdrawable' => true,
                    'meta' => $metaCreditW,
                ]);
            }
            if ($restrictedPortion > 0) {
                $metaCreditR = $metaCredit;
                $metaCreditR['portion'] = 'restricted';
                $metaCreditR['portion_amount'] = round($restrictedPortion, 2);
                WalletTransaction::create([
                    'user_id' => $lockedRecipient->id,
                    'type' => 'credit',
                    'amount' => $restrictedPortion,
                    'reference' => $referenceCredit . '-R',
                    'source' => 'p2p_transfer',
                    'withdrawable' => false,
                    'meta' => $metaCreditR,
                ]);
            }

            $finalSenderBal = (float) $lockedSender->fresh()->balance;
        });

        if ($insufficient) {
            return response()->json(['message' => 'Insufficient wallet balance'], 422);
        }

        // Notify members via preferences
        $msgFrom = 'Wallet debit: ₦'.number_format($amount, 2).' sent to '.$recipient->name.' ('.$recipient->membership_number.'). Ref: '.$groupRef.'. New bal: ₦'.number_format((float)$finalSenderBal, 2);
        $sender->notifyMember('Wallet Transfer Sent', $msgFrom, [
            'type' => 'p2p_transfer_sent',
            'amount' => $amount,
            'recipient_id' => $recipient->id,
            'reference' => $groupRef,
            'balance' => (float)$finalSenderBal,
        ], ['mail', 'sms', 'push', 'database']); // respect prefs but we can force certain channels if we wanted

        $msgTo = 'Wallet credit: ₦'.number_format($amount, 2).' received from '.$sender->name.' ('.$sender->membership_number.'). Ref: '.$groupRef.'.';
        $recipient->notifyMember('Wallet Transfer Received', $msgTo, [
            'type' => 'p2p_transfer_received',
            'amount' => $amount,
            'sender_id' => $sender->id,
            'reference' => $groupRef,
        ]);

        return response()->json([
            'reference' => $groupRef,
            'debited' => $amount,
            'balance' => (float) $finalSenderBal,
            'recipient' => [
                'id' => $recipient->id,
                'name' => $recipient->name,
                'membership_number' => $recipient->membership_number,
                'branch_id' => $recipient->branch_id,
            ],
        ]);
    }

    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'pin' => ['required','regex:/^\\d{4}$/'],
            'note' => 'nullable|string|max:200',
        ]);

        $user = $request->user();

        if (empty($user->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }
        if (!$user->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }

        // Ensure verified bank details exist
        $hasBank = !empty($user->bank_code) && !empty($user->account_number) && !empty($user->account_name);
        if (!$hasBank) {
            return response()->json(['message' => 'Add and verify your bank details first in Profile > Bank Settings.'], 422);
        }

        $amount = round((float) $validated['amount'], 2);
        if ($amount <= 0) {
            return response()->json(['message' => 'Amount must be greater than zero'], 422);
        }

        $available = method_exists($user, 'availableForWithdrawal') ? (float) $user->availableForWithdrawal() : (float) $user->balance;
        if ($amount > $available) {
            return response()->json([
                'message' => 'Amount exceeds your available-for-withdrawal balance.',
                'available_for_withdrawal' => $available,
            ], 422);
        }

        if ((float)$user->balance < $amount) {
            return response()->json(['message' => 'Insufficient wallet balance'], 422);
        }

        // Prevent multiple concurrent pending withdrawal requests
        $hasPending = WithdrawalRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
        if ($hasPending) {
            return response()->json([
                'message' => 'You already have a pending withdrawal request. Please wait for it to be processed.'
            ], 422);
        }

        $reference = 'WD-'.now()->format('YmdHis').'-'.$user->id.'-'.Str::upper(Str::random(6));

        // Wrap creation in a transaction and lock the user to avoid race conditions
        $req = null;
        try {
            DB::transaction(function () use ($user, $amount, $reference, $validated, $request, &$req) {
                // Lock the user row to serialize concurrent requests
                User::where('id', $user->id)->lockForUpdate()->first();

                // Double-check for any pending request within the same transaction
                $hasPendingAgain = WithdrawalRequest::where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->exists();
                if ($hasPendingAgain) {
                    throw new \RuntimeException('PENDING_DUPLICATE');
                }

                $req = WithdrawalRequest::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'reference' => $reference,
                    'status' => 'pending',
                    'bank_code' => $user->bank_code,
                    'bank_name' => $user->bank_name,
                    'account_number' => $user->account_number,
                    'account_name' => $user->account_name,
                    'reason' => $validated['note'] ?? null,
                    'meta' => [
                        'ip' => $request->ip(),
                        'user_agent' => substr((string)$request->userAgent(), 0, 255),
                    ],
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'PENDING_DUPLICATE') {
                return response()->json([
                    'message' => 'You already have a pending withdrawal request. Please wait for it to be processed.'
                ], 422);
            }
            throw $e;
        }

        // Best-effort alert to admins (optional)
        try {
            $admins = User::query()->where('is_admin', true)->get(['id','email','device_token','fcm_token']);
            if ($admins->isNotEmpty()) {
                $push = app(\App\Services\PushService::class);
                $title = 'Withdrawal Request';
                $body = $user->name.' requested ₦'.number_format($amount, 2).' (Ref: '.$reference.').';
                foreach ($admins as $a) {
                    $token = $a->fcm_token ?: $a->device_token;
                    if (!empty($token)) {
                        $push->send($token, $title, $body, [
                            'type' => 'withdrawal_request',
                            'request_id' => $req->id,
                            'member_id' => $user->id,
                            'amount' => (float) $amount,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) { /* ignore */ }

        // Notify member via preferences
        $user->notifyMember('Withdrawal Request', 'Withdrawal request received: ₦'.number_format($amount, 2).'. Ref: '.$reference.'.', [
            'type' => 'withdrawal_request_initiated',
            'amount' => $amount,
            'reference' => $reference,
        ]);

        return response()->json([
            'id' => $req->id,
            'reference' => $req->reference,
            'status' => $req->status,
            'amount' => (float)$req->amount,
            'bank' => [
                'bank_code' => $req->bank_code,
                'bank_name' => $req->bank_name,
                'account_number' => $req->account_number,
                'account_name' => $req->account_name,
            ],
        ], 201);
    }

    public function withdrawals(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,paid,declined',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $user = $request->user();
        $query = WithdrawalRequest::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        $perPage = $validated['per_page'] ?? 15;
        return response()->json($query->paginate($perPage));
    }

    public function cancelWithdrawal(Request $request, $id)
    {
        $user = $request->user();
        $wr = WithdrawalRequest::where('id', (int)$id)
            ->where('user_id', $user->id)
            ->first();
        if (!$wr) {
            return response()->json(['message' => 'Withdrawal request not found'], 404);
        }
        if ($wr->status !== 'pending') {
            return response()->json(['message' => 'Only pending requests can be cancelled'], 422);
        }

        $wr->status = 'declined';
        $wr->reason = 'Cancelled by member';
        $wr->processed_at = now();
        $wr->save();

        // Notify member via preferences
        $user->notifyMember('Withdrawal Cancelled', 'Withdrawal cancelled: ₦'.number_format((float)$wr->amount, 2).'. Ref: '.$wr->reference.'.', [
            'type' => 'withdrawal_cancelled',
            'amount' => (float)$wr->amount,
            'reference' => $wr->reference,
        ]);

        return response()->json([
            'id' => $wr->id,
            'status' => $wr->status,
            'reference' => $wr->reference,
        ]);
    }
}
