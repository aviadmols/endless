<?php

namespace App\Mail;

use App\Models\Memory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the memorial owner when someone submits a memory through the share link. */
class NewMemoryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Memory $memory) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'זיכרון חדש הועלה לעמוד של '.$this->memory->memorial->full_name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-memory', with: [
            'memory' => $this->memory,
            'memorial' => $this->memory->memorial,
            'dashboardUrl' => route('dashboard.memories.index', ['status' => 'pending']),
        ]);
    }
}
