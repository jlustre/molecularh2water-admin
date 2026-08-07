<?php

use App\Enums\NotifiableForm;
use App\Mail\FormSubmissionAlert;
use App\Mail\InstallationQuestionnaireSubmitted;
use App\Models\EmailMapping;
use Database\Seeders\CrmSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

it('notifies mapped recipients for installation questionnaires', function () {
    Mail::fake();

    EmailMapping::factory()->create([
        'form_key' => NotifiableForm::InstallationQuestionnaire,
        'email' => 'install-team@example.com',
        'is_active' => true,
    ]);

    EmailMapping::factory()->inactive()->create([
        'form_key' => NotifiableForm::InstallationQuestionnaire,
        'email' => 'inactive@example.com',
    ]);

    $this->postJson('/api/installation-questionnaires', [
        'first_name' => 'Alex',
        'last_name' => 'Buyer',
        'email' => 'alex@example.com',
        'phone' => '555-0100',
        'street_address' => '123 Main St',
        'city' => 'Austin',
        'state' => 'TX',
        'postal_code' => '78701',
        'country' => 'United States',
        'property_type' => 'Single Family Home',
        'water_source' => 'Well',
    ])->assertCreated();

    Mail::assertSent(InstallationQuestionnaireSubmitted::class, function (InstallationQuestionnaireSubmitted $mail) {
        return $mail->hasTo('install-team@example.com')
            && ! $mail->hasTo('inactive@example.com')
            && ! $mail->hasTo('shipping@happycooking.com');
    });
});

it('does not email anyone for installation questionnaires without mappings', function () {
    Mail::fake();

    $this->postJson('/api/installation-questionnaires', [
        'first_name' => 'Alex',
        'last_name' => 'Buyer',
        'email' => 'alex@example.com',
        'phone' => '555-0100',
        'street_address' => '123 Main St',
        'city' => 'Austin',
        'state' => 'TX',
        'postal_code' => '78701',
        'country' => 'United States',
        'property_type' => 'Condo',
        'water_source' => 'Municipal (connected to the city)',
    ])->assertCreated();

    Mail::assertNothingSent();
});

it('notifies mapped recipients for warranty registrations', function () {
    Mail::fake();

    EmailMapping::factory()->create([
        'form_key' => NotifiableForm::WarrantyRegistration,
        'email' => 'warranty-desk@example.com',
        'is_active' => true,
    ]);

    $this->postJson('/api/warranty-registrations', [
        'customer_name' => 'Jane Owner',
        'email' => 'jane@example.com',
        'phone' => '555-0100',
        'serial_number' => 'H2-MAP-001',
        'machine_model' => 'H2 Hydrogen Water Machine',
        'purchase_date' => '2026-06-01',
    ])->assertCreated();

    Mail::assertSent(FormSubmissionAlert::class, function (FormSubmissionAlert $mail) {
        return $mail->hasTo('warranty-desk@example.com')
            && $mail->formLabel === 'Warranty Registrations'
            && str_contains($mail->subjectLine, 'warranty registration');
    });
});

it('notifies mapped recipients for website form captures', function () {
    Mail::fake();
    $this->seed(CrmSeeder::class);

    EmailMapping::factory()->create([
        'form_key' => NotifiableForm::ContactUs,
        'email' => 'contact-inbox@example.com',
        'is_active' => true,
    ]);

    $this->postJson('/api/prospects', [
        'name' => 'Casey Contact',
        'email' => 'casey@example.com',
        'form_context' => 'about-contact',
        'message' => 'Please call me back.',
        'consent_given' => true,
    ])->assertCreated();

    Mail::assertSent(FormSubmissionAlert::class, function (FormSubmissionAlert $mail) {
        return $mail->hasTo('contact-inbox@example.com')
            && $mail->formLabel === 'Contact Us'
            && ($mail->details['Message'] ?? null) === 'Please call me back.';
    });
});

it('attaches warranty media files to website form capture emails', function () {
    Mail::fake();
    Storage::fake('public');
    $this->seed(CrmSeeder::class);

    EmailMapping::factory()->create([
        'form_key' => NotifiableForm::ContactUs,
        'email' => 'contact-inbox@example.com',
        'is_active' => true,
    ]);

    $this->post('/api/prospects', [
        'name' => 'Warranty Contact',
        'email' => 'warranty-contact@example.com',
        'form_context' => 'about-contact',
        'interested_in' => 'Warranty Service',
        'warranty_concern' => 'Machine started leaking under the sink.',
        'warranty_media' => [
            UploadedFile::fake()->image('warranty-photo-1.jpg'),
            UploadedFile::fake()->image('warranty-photo-2.jpg'),
        ],
        'consent_given' => '1',
    ], [
        'Accept' => 'application/json',
    ])->assertCreated();

    Mail::assertSent(FormSubmissionAlert::class, function (FormSubmissionAlert $mail) {
        return $mail->hasTo('contact-inbox@example.com')
            && $mail->formLabel === 'Contact Us'
            && ($mail->details['Interested in'] ?? null) === 'Warranty Service'
            && count($mail->attachments()) === 2;
    });
});

it('includes warranty video attachments and preview links in website form capture emails', function () {
    Mail::fake();
    Storage::fake('public');
    $this->seed(CrmSeeder::class);

    EmailMapping::factory()->create([
        'form_key' => NotifiableForm::ContactUs,
        'email' => 'contact-inbox@example.com',
        'is_active' => true,
    ]);

    $this->post('/api/prospects', [
        'name' => 'Warranty Video Contact',
        'email' => 'warranty-video@example.com',
        'form_context' => 'about-contact',
        'interested_in' => 'Warranty Service',
        'warranty_concern' => 'Video shows intermittent leak during operation.',
        'warranty_media' => [
            UploadedFile::fake()->create('warranty-video.mp4', 1200, 'video/mp4'),
        ],
        'consent_given' => '1',
    ], [
        'Accept' => 'application/json',
    ])->assertCreated();

    Mail::assertSent(FormSubmissionAlert::class, function (FormSubmissionAlert $mail) {
        return $mail->hasTo('contact-inbox@example.com')
            && count($mail->attachments()) === 1
            && ($mail->mediaPreviewItems[0]['is_video'] ?? false) === true
            && filled($mail->mediaPreviewItems[0]['url'] ?? null);
    });
});
