<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemberInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $name, public ?string $customMessage = null)
    {
    }

    public function build()
    {
        $appName = config('app.name');
        return $this
            ->subject("Invitation to Join $appName")
            ->view('emails.member_invitation', [
                'name' => $this->name,
                'customMessage' => $this->customMessage,
                'registrationUrl' => config('app.frontend_url', config('app.url')) . '/register',
            ]);
    }
}
