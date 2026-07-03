<?php

namespace App\Mail;

use App\Models\RegistrationInvite;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RegistrationInvite $invite,
        public User $sponsor,
        public string $inviteUrl,
        public ?string $personalMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->sponsor->name.' invited you to join '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-invite',
        );
    }
}
