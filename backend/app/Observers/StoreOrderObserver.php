<?php

namespace App\Observers;

use App\Models\StoreOrder;
use App\Services\PushService;
use App\Services\LedgerService;
use Illuminate\Support\Facades\Log;

class StoreOrderObserver
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Handle the StoreOrder "created" event.
     */
    public function created(StoreOrder $order): void
    {
        $this->handlePushNotification($order);
    }

    /**
     * Handle the StoreOrder "updated" event.
     */
    public function updated(StoreOrder $order): void
    {
        if ($order->wasChanged('status')) {
            $this->handlePushNotification($order);

            if ($order->status === 'murabaha_active' && !$order->ledger_journal_id) {
                $this->recordMurabahahToLedger($order);
            }
        }
    }

    protected function recordMurabahahToLedger(StoreOrder $order): void
    {
        try {
            // Murabahah Active:
            // Dr Murabahah Receivables (1310) - Total Amount (Cost + Profit)
            // Cr Bank/Cash (1100) - Total Cost
            // Cr Murabahah Profit (4400) - Total Profit

            $journal = $this->ledgerService->recordByCode([
                'date' => now(),
                'reference' => 'MURABAHA-' . $order->id,
                'description' => "Murabahah Financing for order: {$order->reference}",
                'created_by' => auth()->id(),
            ], [
                ['code' => '1310', 'debit' => $order->total_amount], // Receivable
                ['code' => '1100', 'credit' => $order->total_cost], // Cost paid to vendor/inventory
                ['code' => '4400', 'credit' => $order->total_profit], // Profit
            ]);

            $order->updateQuietly(['ledger_journal_id' => $journal->id]);
        } catch (\Exception $e) {
            \Log::error("Failed to record murabahah in ledger: " . $e->getMessage());
        }
    }

    protected function handlePushNotification(StoreOrder $order): void
    {
        $user = $order->user;
        if (!$user || !$user->fcm_token) return;

        $newStatus = $order->status;
        $oldStatus = $order->getOriginal('status');

        $push = app(PushService::class);
        $title = "Order Status Updated";
        $body = "Your order #{$order->reference} status is now: " . ucfirst(str_replace('_', ' ', (string) $newStatus));
        $send = true;

        if ($order->wasRecentlyCreated) {
            $title = "Order Confirmed";
            $body = "Your order #{$order->reference} has been placed successfully.";

            if ($order->status === 'murabaha_pending') {
                $title = "Financing Application Received";
                $body = "Your Murabaha financing application for order #{$order->reference} has been received and is pending review.";
            }
        } else {
            switch ($newStatus) {
                case 'murabaha_active':
                    if ($oldStatus === 'murabaha_pending') {
                        $title = "Financing Approved! ðŸŽ‰";
                        $body = "Your Murabaha financing application for order #{$order->reference} has been approved. You can now view your installment schedule.";
                    }
                    break;
                case 'processing':
                    $body = "We are currently processing your order #{$order->reference}. We will notify you once it's ready.";
                    break;
                case 'completed':
                    $title = "Order Completed";
                    $body = "Your order #{$order->reference} has been completed/delivered. Thank you for shopping with us!";
                    break;
                case 'cancelled':
                    $title = "Order Cancelled";
                    $body = "Your order #{$order->reference} has been cancelled.";
                    break;
                case 'paid':
                    $body = "Payment for your order #{$order->reference} has been confirmed.";
                    break;
                default:
                    $send = false;
                    break;
            }
        }

        if ($send) {
            $push->send($user->fcm_token, $title, $body, [
                'type' => 'order_update',
                'order_id' => (string) $order->id,
                'route' => "/store/orders/{$order->id}",
            ]);
        }
    }
}
