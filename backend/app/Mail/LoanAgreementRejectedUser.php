<?php

namespace App\Mail;

use App\Models\QardHasan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanAgreementRejectedUser extends Mailable
{
    use Queueable, SerializesModels;

    public QardHasan $loan;
    public string $reason;

    public function __construct(QardHasan $loan, string $reason)
    {
        $this->loan = $loan;
        $this->reason = $reason;
    }

    public function build(): self
    {
        return $this->subject('Action Required: Loan Agreement Rejected - ' . ($this->loan->qard_id_string ?? ('QH-'.$this->loan->id)))
            ->view('emails.loan_agreement_rejected_user')
            ->with([
                'loan' => $this->loan,
                'member' => $this->loan->user,
                'reason' => $this->reason,
            ]);
    }
}
