<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $status; // success|failed|pending|other
    public string $title;
    public string $messageText;
    public ?float $amount;
    public ?string $reference;
    public ?string $channel;
    public ?string $route;
    public array $meta;

    public function __construct(
        string $status,
        string $title,
        string $message,
        ?float $amount = null,
        ?string $reference = null,
        ?string $channel = null,
        ?string $route = null,
        array $meta = []
    ) {
        $this->status = $status;
        $this->title = $title;
        $this->messageText = $message;
        $this->amount = $amount;
        $this->reference = $reference;
        $this->channel = $channel;
        $this->route = $route;
        $this->meta = $meta;
    }

    public function build(): self
    {
        $subject = '[' . ucfirst($this->status) . '] ' . ($this->title ?: 'Payment Update');
        return $this->subject($subject)
            ->view('emails.payment_status');
    }
}
