<?php

namespace App\Mail;

use App\Models\QardHasan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanDisbursedAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public QardHasan $loan, public float $creditedAmount)
    {
    }

    public function build()
    {
        return $this
            ->subject('Loan disbursed: ' . $this->loan->qard_id_string)
            ->view('emails.loan_disbursed_admin', [
                'loan' => $this->loan,
                'creditedAmount' => $this->creditedAmount,
            ]);
    }
}
