<?php

namespace App\Mail;

use App\Models\QardHasan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanApprovedUser extends Mailable
{
    use Queueable, SerializesModels;

    public QardHasan $loan;

    public function __construct(QardHasan $loan)
    {
        $this->loan = $loan;
    }

    public function build(): self
    {
        return $this->subject('Your Loan Request Was Approved: ' . ($this->loan->qard_id_string ?? ('QH-'.$this->loan->id)))
            ->view('emails.loan_approved_user')
            ->with([
                'loan' => $this->loan,
                'member' => $this->loan->user,
            ]);
    }
}
