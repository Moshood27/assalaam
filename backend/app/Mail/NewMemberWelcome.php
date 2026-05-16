<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewMemberWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function build()
    {
        return $this
            ->subject('Welcome to '.config('app.name').' — Bismillāh and Barakah')
            ->view('emails.new_member_welcome', [
                'user' => $this->user,
            ]);
    }
}
