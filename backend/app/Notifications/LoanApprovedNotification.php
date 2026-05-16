<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LoanApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public int $loanId,
        public string $qardIdString,
        public float $creditedAmount,
        public ?float $balance = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'loan_approved',
            'title' => $this->title,
            'message' => $this->message,
            'loan_id' => $this->loanId,
            'qard_id_string' => $this->qardIdString,
            'credited_amount' => $this->creditedAmount,
            'balance' => $this->balance,
        ];
    }
}
