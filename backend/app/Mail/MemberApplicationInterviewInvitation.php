<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemberApplicationInterviewInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $meetingType,
        public string $meetingDateTime,
        public ?string $meetingLocationOrLink = null,
        public ?string $customMessage = null
    ) {
    }

    public function build()
    {
        $appName = config('app.name');
        return $this
            ->subject("Interview Invitation: Member Application - $appName")
            ->view('emails.member_application_interview', [
                'name' => $this->name,
                'meetingType' => $this->meetingType,
                'meetingDateTime' => $this->meetingDateTime,
                'meetingLocationOrLink' => $this->meetingLocationOrLink,
                'customMessage' => $this->customMessage,
            ]);
    }
}
