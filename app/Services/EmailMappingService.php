<?php

namespace App\Services;

use App\Enums\NotifiableForm;
use App\Mail\FormSubmissionAlert;
use App\Mail\InstallationQuestionnaireSubmitted;
use App\Models\EmailMapping;
use App\Models\InstallationQuestionnaire;
use App\Models\WarrantyRegistration;
use App\Models\WebsiteFormSubmission;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class EmailMappingService
{
    /**
     * @return list<string>
     */
    public function activeEmailsFor(NotifiableForm $form): array
    {
        if (! Schema::hasTable('email_mappings')) {
            return [];
        }

        return EmailMapping::query()
            ->active()
            ->forForm($form)
            ->orderBy('email')
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function send(NotifiableForm $form, Mailable $mailable): void
    {
        $recipients = $this->activeEmailsFor($form);

        if ($recipients === []) {
            return;
        }

        Mail::to($recipients)->send($mailable);
    }

    public function notifyWebsiteFormSubmission(WebsiteFormSubmission $submission): void
    {
        $form = NotifiableForm::fromWebsiteFormType($submission->form_type);
        $adminUrl = $this->adminUrlForWebsiteForm($submission);

        $this->send($form, new FormSubmissionAlert(
            formLabel: $form->label(),
            subjectLine: 'New '.$submission->form_type->singularLabel().' #'.$submission->id,
            details: array_filter([
                'Name' => $submission->name,
                'Email' => $submission->email,
                'Phone' => $submission->phone,
                'Referrer' => $submission->referrer_name,
                'Preferred time' => $submission->preferred_time,
                'Interested in' => $submission->interested_in,
                'Message' => $submission->message,
                'Source' => $submission->source,
                'Page URL' => $submission->page_url,
                'Submitted' => $submission->created_at?->timezone(config('app.timezone'))->format('F j, Y g:i A T'),
            ], fn ($value) => filled($value)),
            adminUrl: $adminUrl,
            replyToEmail: $submission->email,
        ));
    }

    public function notifyWarrantyRegistration(WarrantyRegistration $registration): void
    {
        $form = NotifiableForm::WarrantyRegistration;
        $adminUrl = route('admin.warranty-registrations.show', $registration);

        $this->send($form, new FormSubmissionAlert(
            formLabel: $form->label(),
            subjectLine: 'New warranty registration #'.$registration->id,
            details: [
                'Customer' => $registration->customer_name,
                'Email' => $registration->email,
                'Phone' => $registration->phone,
                'Serial number' => $registration->serial_number,
                'Machine model' => $registration->machine_model,
                'Purchase date' => $registration->purchase_date?->format('F j, Y'),
                'Purchased from' => $registration->purchased_from ?: 'Not provided',
                'Notes' => $registration->notes ?: 'None',
                'Submitted' => $registration->created_at?->timezone(config('app.timezone'))->format('F j, Y g:i A T'),
            ],
            adminUrl: $adminUrl,
            replyToEmail: $registration->email,
        ));
    }

    public function notifyInstallationQuestionnaire(InstallationQuestionnaire $questionnaire): void
    {
        $this->send(
            NotifiableForm::InstallationQuestionnaire,
            new InstallationQuestionnaireSubmitted($questionnaire),
        );
    }

    private function adminUrlForWebsiteForm(WebsiteFormSubmission $submission): string
    {
        return route('admin.website-forms.show', [
            $submission->form_type->routeKey(),
            $submission,
        ]);
    }
}
