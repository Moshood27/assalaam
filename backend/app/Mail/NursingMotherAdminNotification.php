<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NursingMotherAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    public User $member;

    public function __construct(User $member)
    {
        $this->member = $member;
    }

    public function build(): self
    {
        return $this->subject('New Nursing Mother Grace Request: ' . $this->member->full_name)
            ->view('emails.nursing_mother_admin')
            ->with([
                'member' => $this->member,
            ]);
    }
}
