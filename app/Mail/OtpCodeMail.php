<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public string $purpose = 'login') {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'קוד הכניסה שלך ל-'.config('endless.brand.name', 'Endless').': '.$this->code);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp', with: [
            'code' => $this->code,
            'purpose' => $this->purpose,
            'ttl' => config('endless.otp.ttl_minutes', 10),
        ]);
    }
}
