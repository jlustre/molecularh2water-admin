<?php

use App\Enums\NotifiableForm;
use App\Mail\InstallationQuestionnaireSubmitted;
use App\Models\EmailMapping;
use App\Models\InstallationQuestionnaire;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

it('stores an installation questionnaire and emails mapped recipients', function () {
    Mail::fake();
    Storage::fake('public');

    EmailMapping::factory()->create([
        'form_key' => NotifiableForm::InstallationQuestionnaire,
        'email' => 'shipping@happycooking.com',
        'is_active' => true,
    ]);

    $payload = [
        'first_name' => 'Alex',
        'last_name' => 'Buyer',
        'email' => 'alex@example.com',
        'phone' => '(555) 010-2222',
        'street_address' => '123 Main St',
        'street_address_2' => 'Unit 4',
        'city' => 'Austin',
        'state' => 'TX',
        'postal_code' => '78701',
        'country' => 'United States',
        'property_type' => 'Single Family Home',
        'existing_equipment' => [
            'Water Softener',
        ],
        'ownership' => 'own',
        'water_source' => 'Municipal (connected to the city)',
        'special_requirements' => 'Need weekend install.',
        'additional_notes' => 'Gate code 1234',
        'sink_photos' => [
            UploadedFile::fake()->image('sink-1.jpg'),
            UploadedFile::fake()->image('sink-2.jpg'),
        ],
    ];

    $this->post('/api/installation-questionnaires', $payload, [
        'Accept' => 'application/json',
    ])
        ->assertCreated()
        ->assertJsonPath('message', 'Thank you. Your pre-installation questionnaire has been submitted.');

    $questionnaire = InstallationQuestionnaire::query()->first();

    expect($questionnaire)->not->toBeNull()
        ->and($questionnaire->first_name)->toBe('Alex')
        ->and($questionnaire->last_name)->toBe('Buyer')
        ->and($questionnaire->existing_equipment)->toBe(['Water Softener'])
        ->and($questionnaire->sink_photo_path)->not->toBeNull()
        ->and($questionnaire->sinkPhotoItems())->toHaveCount(2);

    foreach ($questionnaire->sinkPhotoItems() as $photo) {
        Storage::disk('public')->assertExists($photo['path']);
    }

    Mail::assertSent(InstallationQuestionnaireSubmitted::class, function (InstallationQuestionnaireSubmitted $mail) {
        return $mail->hasTo('shipping@happycooking.com')
            && $mail->questionnaire->email === 'alex@example.com'
            && $mail->questionnaire->full_name === 'Alex Buyer';
    });
});

it('requires water_source_other when water source is other', function () {
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
        'water_source' => 'Other',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['water_source_other']);
});
