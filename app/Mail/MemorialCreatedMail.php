<?php

namespace App\Mail;

use App\Models\Memorial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Welcome mail with the memorial link + share link, sent after registration is verified. */
class MemorialCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Memorial $memorial) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'עמוד ההנצחה של '.$this->memorial->full_name.' נוצר');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.memorial-created', with: [
            'memorial' => $this->memorial,
            'dashboardUrl' => route('dashboard.index'),
        ]);
    }
}
