<?php

namespace App\Mail;

use App\Models\Installer;
use App\Models\InstallerInstallation;
use App\Models\InstallationQuestionnaire;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class InstallerAssignmentOffered extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{name: string, url: string}>  $photoPreviews
     */
    public function __construct(
        public InstallerInstallation $installation,
        public InstallationQuestionnaire $questionnaire,
        public Installer $installer,
        public string $acceptUrl,
        public string $rejectUrl,
        public array $photoPreviews = [],
    ) {}

    public static function make(
        InstallerInstallation $installation,
        InstallationQuestionnaire $questionnaire,
        Installer $installer,
    ): self {
        $questionnaire->loadMissing(['seller', 'assignedBy']);

        $expires = now()->addDays(14);
        $params = [
            'installation' => $installation,
            'installer' => $installer,
        ];

        $photos = collect($questionnaire->sinkPhotoItems())
            ->filter(fn (array $photo) => Storage::disk('public')->exists($photo['path']))
            ->map(function (array $photo, int $index) use ($installation, $installer, $expires): array {
                return [
                    'name' => $photo['original_name'] ?: 'Photo '.($index + 1),
                    'url' => URL::temporarySignedRoute(
                        'installation-assignments.photos.show',
                        $expires,
                        [
                            'installation' => $installation,
                            'installer' => $installer,
                            'photo' => $index,
                        ],
                    ),
                ];
            })
            ->values()
            ->all();

        return new self(
            $installation,
            $questionnaire,
            $installer,
            URL::temporarySignedRoute('installation-assignments.accept', $expires, $params),
            URL::temporarySignedRoute('installation-assignments.reject', $expires, $params),
            $photos,
        );
    }

    public function envelope(): Envelope
    {
        $replyTo = collect([
            $this->questionnaire->assignedBy?->email,
            $this->questionnaire->seller?->email,
        ])->filter()->unique()->values()->all();

        return new Envelope(
            subject: 'Installation assignment: '.$this->questionnaire->full_name,
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.installer-assignment-offered',
        );
    }
}
