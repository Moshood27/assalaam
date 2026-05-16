<?php

namespace App\Mail;

use App\Models\QardHasan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanAgreementVerifiedUser extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public QardHasan $loan;

    public function __construct(QardHasan $loan)
    {
        $this->loan = $loan;
    }

    public function build(): self
    {
        return $this->subject('Loan Agreement Verified: ' . ($this->loan->qard_id_string ?? ('QH-'.$this->loan->id)))
            ->view('emails.loan_agreement_verified_user')
            ->with([
                'loan' => $this->loan,
                'member' => $this->loan->user,
            ]);
    }
}
