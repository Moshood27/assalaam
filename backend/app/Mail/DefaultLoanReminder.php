<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class DefaultLoanReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $loans;
    public float $totalOutstanding;

    public function __construct(public User $user, array $loans, float $totalOutstanding)
    {
        $this->loans = $loans;
        $this->totalOutstanding = $totalOutstanding;
    }

    public function build()
    {
        return $this
            ->subject('Gentle Reminder: Qard Hasan Repayment (Barakah-Focused)')
            ->view('emails.default_loan_reminder', [
                'user' => $this->user,
                'loans' => $this->loans,
                'totalOutstanding' => $this->totalOutstanding,
            ]);
    }
}
