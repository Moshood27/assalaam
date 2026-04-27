<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PregnancyGraceAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    public User $member;

    public function __construct(User $member)
    {
        $this->member = $member;
    }

    public function build(): self
    {
        return $this->subject('New Pregnancy Grace Request: ' . $this->member->full_name)
            ->view('emails.pregnancy_grace_admin')
            ->with([
                'member' => $this->member,
            ]);
    }
}
