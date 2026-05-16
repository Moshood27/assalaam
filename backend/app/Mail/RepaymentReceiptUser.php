<?php

namespace App\Mail;

use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RepaymentReceiptUser extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QardHasan $loan, public QardHasanRepayment $repayment)
    {
    }

    public function build()
    {
        return $this
            ->subject('Payment receipt for loan ' . $this->loan->qard_id_string)
            ->view('emails.repayment_receipt_user', [
                'loan' => $this->loan,
                'repayment' => $this->repayment,
            ]);
    }
}
