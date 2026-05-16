<?php

namespace App\Mail;

use App\Models\MemberApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemberApplicationRejected extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public $application,
        public string $reason
    ) {}

    public function build(): self
    {
        return $this->subject('Update on Your Membership Application — ' . config('app.name'))
            ->view('emails.member_application_rejected', [
                'application' => $this->application,
                'reason' => $this->reason,
            ]);
    }
}
