<?php

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\Crm\TimelineEvent;
use App\Models\WebsiteFormSubmission;
use App\Support\BusinessLineContext;
use Database\Seeders\CrmSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CrmSeeder::class);
});

it('stores a prospect from the public api', function () {
    $payload = [
        'name' => 'Jordan Smith',
        'email' => 'jordan@example.com',
        'phone' => '555-0142',
        'interested_in' => 'Attending a Water Awareness Show',
        'source' => 'website',
        'form_context' => 'about-contact',
        'referrer_name' => 'Alex Rivera',
        'message' => 'I would like to learn more about hydrogen water.',
        'consent_given' => true,
        'page_url' => 'https://www.molecularh2water.com/about#contact',
    ];

    $this->postJson('/api/prospects', $payload)
        ->assertCreated()
        ->assertJsonPath('message', 'Thank you. A team member will be in touch soon.')
        ->assertJsonPath('data.lifecycle', 'prospect')
        ->assertJsonPath('data.first_name', 'Jordan');

    $this->assertDatabaseHas('prospects', [
        'first_name' => 'Jordan',
        'last_name' => 'Smith',
        'email' => 'jordan@example.com',
        'lifecycle_id' => Lifecycle::idFor(LeadLifecycle::Prospect),
        'interested_in' => 'Attending a Water Awareness Show',
    ]);

    $prospect = Prospect::query()->where('email', 'jordan@example.com')->first();

    expect($prospect)->not->toBeNull()
        ->and($prospect->consent_given)->toBeTrue()
        ->and($prospect->funnel_stage_id)->not->toBeNull()
        ->and($prospect->metadata['referrer_name'] ?? null)->toBe('Alex Rivera');

    $this->assertDatabaseHas('website_form_submissions', [
        'form_type' => 'contact_us',
        'email' => 'jordan@example.com',
        'prospect_id' => $prospect->id,
        'interested_in' => 'Attending a Water Awareness Show',
        'referrer_name' => 'Alex Rivera',
    ]);

    expect(
        TimelineEvent::query()
            ->where('contact_type', $prospect->getMorphClass())
            ->where('contact_id', $prospect->id)
            ->where('event_type', 'prospect_captured')
            ->exists(),
    )->toBeTrue();
});

it('requires email or phone and consent for prospect capture', function () {
    $this->postJson('/api/prospects', [
        'name' => 'No Contact',
        'consent_given' => true,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'phone']);

    $this->postJson('/api/prospects', [
        'name' => 'No Consent',
        'email' => 'noconsent@example.com',
        'consent_given' => false,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['consent_given']);
});

it('requires warranty concern and media when warranty service is selected', function () {
    $this->postJson('/api/prospects', [
        'name' => 'Warranty Customer',
        'email' => 'warranty@example.com',
        'interested_in' => 'Warranty Service',
        'consent_given' => true,
        'form_context' => 'about-contact',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['warranty_concern', 'warranty_media']);
});

it('stores warranty concern and uploaded media for warranty service requests', function () {
    Storage::fake('public');

    $this->post('/api/prospects', [
        'name' => 'Warranty Customer',
        'email' => 'warranty@example.com',
        'interested_in' => 'Warranty Service',
        'warranty_concern' => 'The unit is leaking under the sink.',
        'warranty_media' => [UploadedFile::fake()->image('leak-photo.jpg')],
        'consent_given' => '1',
        'form_context' => 'about-contact',
        'source' => 'website',
    ], [
        'Accept' => 'application/json',
    ])->assertCreated();

    $submission = WebsiteFormSubmission::query()->where('email', 'warranty@example.com')->first();

    expect($submission)->not->toBeNull()
        ->and($submission->warranty_concern)->toBe('The unit is leaking under the sink.')
        ->and($submission->warrantyMediaItems())->toHaveCount(1);

    foreach ($submission->warrantyMediaItems() as $item) {
        Storage::disk('public')->assertExists($item['path']);
    }
});

it('silently accepts honeypot prospect submissions', function () {
    $this->postJson('/api/prospects', [
        'name' => 'Spam Bot',
        'email' => 'spam@example.com',
        'consent_given' => true,
        'company_website' => 'https://spam.test',
    ])->assertCreated();

    $this->assertDatabaseMissing('prospects', [
        'email' => 'spam@example.com',
    ]);

    $this->assertDatabaseMissing('website_form_submissions', [
        'email' => 'spam@example.com',
    ]);
});

it('lists captured prospects in the admin crm prospects view', function () {
    $this->seed(\Database\Seeders\RolesSeeder::class);

    $this->postJson('/api/prospects', [
        'name' => 'CRM Visible Prospect',
        'email' => 'visible@example.com',
        'consent_given' => true,
        'source' => 'website',
    ])->assertCreated();

    $admin = \App\Models\User::factory()->create();
    $admin->roles()->attach(\App\Models\Role::query()->where('slug', 'super-admin')->first());

    BusinessLineContext::setCurrent('h2s', $admin);

    $this->actingAs($admin)
        ->get(route('admin.crm.prospects.index'))
        ->assertOk()
        ->assertSee('CRM Visible Prospect')
        ->assertSee('visible@example.com');
});
