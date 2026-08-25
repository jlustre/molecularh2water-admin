<?php

namespace App\Mail;

use App\Models\IssueReport;
use App\Models\IssueReportStatusUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IssueReportStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public IssueReport $report,
        public IssueReportStatusUpdate $statusUpdate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on issue '.$this->report->reference_code.': '.$this->report->status->label(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.issue-report-status-updated',
        );
    }
}
