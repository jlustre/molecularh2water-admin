<?php

namespace App\Mail;

use App\Models\InstallationQuestionnaire;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InstallationQuestionnaireSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public InstallationQuestionnaire $questionnaire) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New H2 Pre-Installation Questionnaire #'.$this->questionnaire->id,
            replyTo: [
                $this->questionnaire->email,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.installation-questionnaire-submitted',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->questionnaire->sinkPhotoItems() as $index => $photo) {
            if (! Storage::disk('public')->exists($photo['path'])) {
                continue;
            }

            $attachments[] = Attachment::fromStorageDisk('public', $photo['path'])
                ->as($photo['original_name'] ?: 'sink-photo-'.($index + 1).'.jpg');
        }

        return $attachments;
    }
}
