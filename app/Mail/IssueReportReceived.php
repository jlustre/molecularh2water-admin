<?php

namespace App\Mail;

use App\Models\IssueReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IssueReportReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public IssueReport $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your issue report '.$this->report->reference_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.issue-report-received',
        );
    }
}
