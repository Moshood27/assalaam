<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function getWallet(Request $request)
    {
        $user = $request->user();
        $recent = $user->walletTransactions()->latest()->limit(10)->get();
        return response()->json([
            'balance' => (float) $user->balance,
            'virtual_account' => [
                'paystack_customer_code' => $user->paystack_customer_code,
                'account_number' => $user->dva_account_number,
                'account_name' => $user->dva_account_name,
                'bank_name' => $user->dva_bank_name,
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
            'items.*.amount' => 'required|numeric|min:1',
        ]);

        $user = $request->user();
        $items = collect($validated['items'])
            ->map(fn($i) => ['scheme_id' => (int)$i['scheme_id'], 'amount' => (float)$i['amount']])
            ->filter(fn($i) => $i['amount'] > 0);

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
                Contribution::create([
                    'user_id' => $lockedUser->id,
                    'scheme_id' => $item['scheme_id'],
                    'amount' => $item['amount'],
                    'reference' => $reference,
                    'status' => 'success',
                ]);
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

        // Best-effort SMS notification
        try {
            $sms = app(\App\Services\SmsService::class);
            $msg = 'Wallet debit: ₦'.number_format($total, 2).' allocated to schemes. Ref: '.$reference.'. New bal: ₦'.number_format((float)$user->balance, 2);
            $sms->send($user->phone ?? null, $msg);
        } catch (\Throwable $e) {
            // ignore SMS errors
        }

        return response()->json([
            'reference' => $reference,
            'debited' => $total,
            'balance' => (float)$user->balance,
            'distribution' => $summary,
        ]);
    }
}
