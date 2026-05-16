<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WalletCredited extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public float $amount,
        public ?string $note = null,
        public ?float $newBalance = null,
    ) {
    }

    public function build()
    {
        $appName = config('app.name');

        return $this
            ->subject("Wallet Credit Notification - {$appName}")
            ->view('emails.wallet_credited', [
                'user' => $this->user,
                'amount' => $this->amount,
                'note' => $this->note,
                'newBalance' => $this->newBalance,
                'appName' => $appName,
                'timestamp' => now(),
            ]);
    }
}
