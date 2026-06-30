<?php

namespace App\Mail;

use App\Models\WarrantyRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WarrantyRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WarrantyRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your H2 Machine Warranty Registration Is Confirmed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.warranty-registration-confirmation',
        );
    }
}
