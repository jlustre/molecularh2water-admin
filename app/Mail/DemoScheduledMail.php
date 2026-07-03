<?php

namespace App\Mail;

use App\Models\Crm\Customer;
use App\Models\Crm\Demonstration;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Demonstration $demonstration,
        public Lead|Prospect|Customer|Recruit $lead,
        public User $host,
        public ?string $onlineDemoLink = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.$this->demonstration->type->label().' with '.$this->host->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-scheduled',
        );
    }
}
