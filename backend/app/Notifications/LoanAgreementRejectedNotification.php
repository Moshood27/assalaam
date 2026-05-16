<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LoanAgreementRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public int $loanId,
        public string $qardIdString,
        public string $reason
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'loan_agreement_rejected',
            'title' => $this->title,
            'message' => $this->message,
            'loan_id' => $this->loanId,
            'qard_id_string' => $this->qardIdString,
            'reason' => $this->reason,
        ];
    }
}
