<?php

namespace App\Observers;

use App\Models\StoreOrder;
use App\Services\PushService;
use Illuminate\Support\Facades\Log;

class StoreOrderObserver
{
    /**
     * Handle the StoreOrder "created" event.
     */
    public function created(StoreOrder $order): void
    {
        $user = $order->user;
        if (!$user || !$user->fcm_token) return;

        $push = app(PushService::class);
        $title = "Order Confirmed";
        $body = "Your order #{$order->reference} has been placed successfully.";

        if ($order->status === 'murabaha_pending') {
            $title = "Financing Application Received";
            $body = "Your Murabaha financing application for order #{$order->reference} has been received and is pending review.";
        }

        $push->send($user->fcm_token, $title, $body, [
            'type' => 'order_update',
            'order_id' => (string) $order->id,
            'route' => "/store/orders/{$order->id}",
        ]);
    }

    /**
     * Handle the StoreOrder "updated" event.
     */
    public function updated(StoreOrder $order): void
    {
        if (!$order->isDirty('status')) return;

        $user = $order->user;
        if (!$user || !$user->fcm_token) return;

        $newStatus = $order->status;
        $oldStatus = $order->getOriginal('status');

        $push = app(PushService::class);
        $title = "Order Status Updated";
        $body = "Your order #{$order->reference} status is now: " . ucfirst(str_replace('_', ' ', $newStatus));
        $send = true;

        switch ($newStatus) {
            case 'murabaha_active':
                if ($oldStatus === 'murabaha_pending') {
                    $title = "Financing Approved! 🎉";
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
                // Usually set at creation for cash, but if updated later:
                $body = "Payment for your order #{$order->reference} has been confirmed.";
                break;
            default:
                $send = false;
                break;
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
