<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LoanAgreementVerifiedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public int $loanId,
        public string $qardIdString
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'loan_agreement_verified',
            'title' => $this->title,
            'message' => $this->message,
            'loan_id' => $this->loanId,
            'qard_id_string' => $this->qardIdString,
        ];
    }
}
