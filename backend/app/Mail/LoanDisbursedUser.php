<?php

namespace App\Mail;

use App\Models\QardHasan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanDisbursedUser extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QardHasan $loan, public float $creditedAmount)
    {
    }

    public function build()
    {
        return $this
            ->subject('AlhamdulillÄh â€” Your Qard Hasan Has Been Disbursed')
            ->view('emails.loan_disbursed_user', [
                'loan' => $this->loan,
                'creditedAmount' => $this->creditedAmount,
            ]);
    }
}
