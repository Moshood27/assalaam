<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class MerchantPayController extends Controller
{
    // attawqa:pay QR generator for a merchant (any member can act as a merchant)
    public function generateQr(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'to_type' => 'nullable|in:membership,phone',
            'amount' => 'nullable|numeric|min:1',
            'note' => 'nullable|string|max:120',
        ]);

        $toType = $validated['to_type'] ?? 'membership';
        // Fallback to phone if membership number is missing
        if ($toType === 'membership' && empty($user->membership_number)) {
            $toType = 'phone';
        }

        $params = [
            'to_type' => $toType,
            'to' => $toType === 'membership' ? ($user->membership_number) : ($user->phone),
        ];
        if (!empty($user->branch_id) && $toType === 'membership') {
            $params['branch_id'] = $user->branch_id;
        }
        if (!empty($validated['amount'])) {
            $params['amount'] = (float) $validated['amount'];
        }
        if (!empty($validated['note'])) {
            $params['note'] = $validated['note'];
        }
        $params['medium'] = 'qr';

        $payload = 'assalaam:pay?' . http_build_query($params);

        return response()->json([
            'payload' => $payload,
            'display' => [
                'merchant' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'membership_number' => $user->membership_number,
                    'branch_id' => $user->branch_id,
                ],
                'suggested_amount' => isset($params['amount']) ? (float) $params['amount'] : null,
                'note' => $params['note'] ?? null,
            ],
        ]);
    }

    public function resolve(Request $request)
    {
        $validated = $request->validate([
            'qr' => 'required|string',
        ]);

        $parsed = $this->parseQrPayload($validated['qr']);
        if (!$parsed || empty($parsed['to']) || empty($parsed['to_type'])) {
            return response()->json(['message' => 'Invalid QR payload'], 422);
        }

        $recipient = null;
        $multiple = false;
        $branches = [];

        if ($parsed['to_type'] === 'membership') {
            $mn = trim($parsed['to']);
            $branchId = $parsed['branch_id'] ?? null;
            if ($branchId) {
                $recipient = User::where('membership_number', $mn)->where('branch_id', $branchId)->first();
            } else {
                $matches = User::where('membership_number', $mn)->get();
                if ($matches->count() === 1) {
                    $recipient = $matches->first();
                } elseif ($matches->count() > 1) {
                    $multiple = true;
                    $branchIds = $matches->pluck('branch_id')->filter()->unique()->values();
                    if ($branchIds->isNotEmpty()) {
                        $branches = Branch::whereIn('id', $branchIds)->get(['id','name'])->toArray();
                    }
                }
            }
        } else { // phone
            $raw = trim($parsed['to']);
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
                'to_type' => $parsed['to_type'],
                'to' => $parsed['to'],
                'amount' => isset($parsed['amount']) ? (float) $parsed['amount'] : null,
                'note' => $parsed['note'] ?? null,
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
            'to_type' => $parsed['to_type'],
            'to' => $parsed['to'],
            'branch_id' => $parsed['branch_id'] ?? $recipient->branch_id,
            'recipient' => [
                'id' => $recipient->id,
                'name' => $recipient->name,
                'membership_number' => $recipient->membership_number,
                'branch_id' => $recipient->branch_id,
                'branch_name' => $branchName,
            ],
            'amount' => isset($parsed['amount']) ? (float) $parsed['amount'] : null,
            'note' => $parsed['note'] ?? null,
        ]);
    }

    public function pay(Request $request)
    {
        $validated = $request->validate([
            'qr' => 'required|string',
            'pin' => ['required','regex:/^\\d{4}$/'],
            'amount' => 'nullable|numeric|min:1',
            'note' => 'nullable|string|max:120',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $parsed = $this->parseQrPayload($validated['qr']);
        if (!$parsed || empty($parsed['to']) || empty($parsed['to_type'])) {
            return response()->json(['message' => 'Invalid QR payload'], 422);
        }

        $amount = isset($validated['amount']) ? (float) $validated['amount'] : ($parsed['amount'] ?? null);
        if (!$amount || $amount <= 0) {
            return response()->json(['message' => 'Amount is required'], 422);
        }

        // Build a transfer request to reuse WalletController::transfer
        $transferParams = [
            'to_type' => $parsed['to_type'],
            'to' => $parsed['to'],
            'amount' => $amount,
            'pin' => $validated['pin'],
        ];
        if (!empty($validated['note'])) {
            $transferParams['note'] = $validated['note'];
        } elseif (!empty($parsed['note'])) {
            $transferParams['note'] = $parsed['note'];
        }
        $branchId = $validated['branch_id'] ?? ($parsed['branch_id'] ?? null);
        if ($branchId) {
            $transferParams['branch_id'] = $branchId;
        }

        // Create a synthetic request with same user
        $transferRequest = Request::create('/api/wallet/transfer', 'POST', $transferParams);
        $transferRequest->setUserResolver(function () use ($request) {
            return $request->user();
        });

        return app(\App\Http\Controllers\Api\WalletController::class)->transfer($transferRequest);
    }

    private function parseQrPayload(string $qr): ?array
    {
        $text = trim($qr);
        $result = [];

        // If it looks like our scheme
        if (str_starts_with($text, 'assalaam:')) {
            $after = substr($text, strlen('assalaam:'));
            // e.g., pay?to_type=...&to=...
            $parts = explode('?', $after, 2);
            if (count($parts) === 2) {
                parse_str($parts[1], $params);
                $result = is_array($params) ? $params : [];
            }
        } else if (str_contains($text, '?')) {
            // Raw query string or URL-like string
            $q = explode('?', $text, 2)[1];
            parse_str($q, $params);
            $result = is_array($params) ? $params : [];
        } else {
            // Treat the whole text as an identifier (membership by default; if digits-like, could be phone)
            $digits = preg_replace('/[^0-9]/', '', $text);
            if ($digits === $text || str_starts_with($text, '+')) {
                $result = ['to_type' => 'phone', 'to' => $text];
            } else {
                $result = ['to_type' => 'membership', 'to' => $text];
            }
        }

        // Normalize keys we care about
        $toType = isset($result['to_type']) && in_array($result['to_type'], ['membership', 'phone'])
            ? $result['to_type'] : 'membership';
        $to = $result['to'] ?? null;
        $branchId = isset($result['branch_id']) && is_numeric($result['branch_id']) ? (int) $result['branch_id'] : null;
        $amount = isset($result['amount']) && is_numeric($result['amount']) ? (float) $result['amount'] : null;
        $note = $result['note'] ?? null;

        if (!$to) return null;

        return [
            'to_type' => $toType,
            'to' => $to,
            'branch_id' => $branchId,
            'amount' => $amount,
            'note' => $note,
        ];
    }
}
