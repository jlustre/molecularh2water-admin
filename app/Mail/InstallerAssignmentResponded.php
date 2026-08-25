<?php

namespace App\Mail;

use App\Enums\InstallerAssignmentResponse;
use App\Models\Installer;
use App\Models\InstallerInstallation;
use App\Models\InstallationQuestionnaire;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstallerAssignmentResponded extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public InstallerInstallation $installation,
        public InstallationQuestionnaire $questionnaire,
        public Installer $installer,
        public string $adminUrl,
        public ?User $assignor = null,
    ) {}

    public function envelope(): Envelope
    {
        $accepted = $this->installation->assignment_response === InstallerAssignmentResponse::Accepted;
        $replyTo = array_values(array_filter([$this->installer->email]));

        return new Envelope(
            subject: ($accepted ? 'Installer accepted' : 'Installer declined').': '.$this->questionnaire->full_name,
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.installer-assignment-responded',
        );
    }
}
