<?php

namespace App\Jobs;

use App\Models\UtilityTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReconcileUtilityTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pendingTransactions = UtilityTransaction::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(5))
            ->get();

        if ($pendingTransactions->isEmpty()) {
            return;
        }

        Log::info("Reconciliation: Processing " . $pendingTransactions->count() . " pending transactions.");

        foreach ($pendingTransactions as $tx) {
            try {
                $this->reconcile($tx);
            } catch (\Throwable $e) {
                Log::error("Reconciliation: Failed to reconcile tx {$tx->id}: " . $e->getMessage());
            }
        }
    }

    private function reconcile(UtilityTransaction $tx)
    {
        // Determine provider. In the future we should use $tx->provider column.
        // For now, we try ClubKonnect first as requested.
        $response = $this->queryClubKonnect($tx);

        if (!$response['ok']) {
            // If ClubKonnect failed or wasn't the provider, we might want to try VTpass
            // but the request was specific about ClubKonnect's APIQueryV1.asp
            return;
        }

        $body = $response['body'];

        if ($this->isSuccess($body)) {
            $tx->update(['status' => 'success']);
            Log::info("Reconciliation: Transaction {$tx->id} marked as success.");
        } elseif ($this->isFailed($body)) {
            $this->processRefund($tx, $body);
        } else {
            // Still pending or unknown status
            Log::debug("Reconciliation: Transaction {$tx->id} still pending or unknown.", ['response' => $body]);
        }
    }

    private function queryClubKonnect(UtilityTransaction $tx): array
    {
        $cfg = config('services.vtu.clubkonnect', []);
        $userId = $cfg['user_id'] ?? null;
        $apiKey = $cfg['api_key'] ?? null;
        $baseUrl = rtrim((string)($cfg['base_url'] ?? 'https://www.nellobytesystems.com'), '/');

        if (!$userId || !$apiKey) {
            return ['ok' => false, 'error' => 'ClubKonnect not configured'];
        }

        try {
            // Try by OrderID if we have it in reference (it was updated to orderid in controller)
            // or if it's in the new order_id column
            $orderId = $tx->order_id ?: (is_numeric($tx->reference) ? $tx->reference : null);
            $requestId = !$orderId ? $tx->reference : null;

            $params = [
                'UserID' => $userId,
                'APIKey' => $apiKey,
            ];

            if ($orderId) {
                $params['OrderID'] = $orderId;
            } else {
                $params['RequestID'] = $requestId;
            }

            $resp = Http::timeout(20)->get($baseUrl . '/APIQueryV1.asp', $params);

            if ($resp->ok()) {
                $json = $resp->json();
                return ['ok' => true, 'body' => is_array($json) ? $json : ['raw' => $resp->body()]];
            }
            return ['ok' => false, 'error' => 'HTTP ' . $resp->status()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function isSuccess(array $body): bool
    {
        $status = (string)($body['statuscode'] ?? ($body['status_code'] ?? ($body['StatusCode'] ?? ($body['status'] ?? ''))));
        if (in_array($status, ['200', 'ORDER_COMPLETED', 'COMPLETED', 'SUCCESS'])) {
            return true;
        }

        $orderStatus = strtoupper((string)($body['orderstatus'] ?? ($body['order_status'] ?? ($body['OrderStatus'] ?? ''))));
        if (in_array($orderStatus, ['ORDER_COMPLETED', 'COMPLETED', 'SUCCESS'])) {
            return true;
        }

        return false;
    }

    private function isFailed(array $body): bool
    {
        $status = (string)($body['statuscode'] ?? ($body['status_code'] ?? ($body['StatusCode'] ?? ($body['status'] ?? ''))));
        // 300 = Cancelled, 400 = Failed
        if (in_array($status, ['300', '400', 'ORDER_CANCELLED', 'FAILED', 'CANCELLED'])) {
            return true;
        }

        $orderStatus = strtoupper((string)($body['orderstatus'] ?? ($body['order_status'] ?? ($body['OrderStatus'] ?? ''))));
        if (in_array($orderStatus, ['ORDER_CANCELLED', 'FAILED', 'CANCELLED'])) {
            return true;
        }

        return false;
    }

    private function processRefund(UtilityTransaction $tx, array $body): void
    {
        DB::transaction(function () use ($tx, $body) {
            // Refresh to avoid race conditions
            $tx = $tx->fresh();
            if ($tx->status !== 'pending') {
                return;
            }

            $tx->update([
                'status' => 'failed',
                'provider_response' => array_merge((array)$tx->provider_response, ['reconciliation_refund' => $body])
            ]);

            $user = User::lockForUpdate()->find($tx->user_id);
            if (!$user) {
                return;
            }

            // Check if already refunded to be safe
            $refundExists = WalletTransaction::where('user_id', $user->id)
                ->where('source', 'vtu_refund')
                ->where('reference', 'LIKE', '%' . $tx->reference . '%')
                ->exists();

            if ($refundExists) {
                Log::warning("Reconciliation: Refund already exists for tx {$tx->id}");
                return;
            }

            $amount = (float)$tx->amount;
            $user->increment('balance', $amount);

            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'reference' => 'REFUND-' . $tx->reference . '-' . time(),
                'source' => 'vtu_refund',
                'meta' => [
                    'utility_tx_id' => $tx->id,
                    'original_reference' => $tx->reference,
                    'type' => $tx->type,
                    'reason' => 'VTU failure reconciliation refund',
                ],
            ]);

            Log::info("Reconciliation: Refund of {$amount} processed for user {$user->id} (tx {$tx->id})");
        });
    }
}
