<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormSubmissionAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string|null>  $details
     * @param  list<array{disk: string, path: string, as: string|null}>  $fileAttachments
     * @param  list<array{name: string, url: string, is_image: bool, is_video: bool}>  $mediaPreviewItems
     */
    public function __construct(
        public string $formLabel,
        public string $subjectLine,
        public array $details,
        public string $adminUrl,
        public ?string $replyToEmail = null,
        public array $fileAttachments = [],
        public array $mediaPreviewItems = [],
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

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return collect($this->fileAttachments)
            ->filter(fn (array $attachment) => filled($attachment['path'] ?? null))
            ->map(function (array $attachment): Attachment {
                $mailAttachment = Attachment::fromStorageDisk(
                    $attachment['disk'] ?? 'public',
                    (string) $attachment['path'],
                );

                return filled($attachment['as'] ?? null)
                    ? $mailAttachment->as((string) $attachment['as'])
                    : $mailAttachment;
            })
            ->values()
            ->all();
    }
}
