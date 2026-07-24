<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormSubmissionAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string|null>  $details
     */
    public function __construct(
        public string $formLabel,
        public string $subjectLine,
        public array $details,
        public string $adminUrl,
        public ?string $replyToEmail = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            replyTo: filled($this->replyToEmail) ? [$this->replyToEmail] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.form-submission-alert',
        );
    }
}
