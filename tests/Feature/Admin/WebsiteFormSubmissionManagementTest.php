<?php

use App\Enums\WebsiteFormSubmissionStatus;
use App\Enums\WebsiteFormType;
use App\Models\WebsiteFormSubmission;
use App\Support\FrontendUrl;

it('allows an admin to manage website form submissions for each inbox', function () {
    config([
        'frontend.url' => 'http://localhost:8000',
        'frontend.environment_label' => 'Local',
    ]);

    $admin = superAdminUser(['name' => 'Admin User']);
    $type = WebsiteFormType::ContactUs;

    $submission = WebsiteFormSubmission::factory()->ofType($type)->create([
        'name' => 'Jordan Smith',
        'email' => 'jordan@example.com',
        'phone' => '555-0142',
        'interested_in' => 'Learning about the H2 water machine',
        'message' => 'Please contact me.',
        'status' => WebsiteFormSubmissionStatus::New,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.website-forms.index', $type->routeKey()))
        ->assertOk()
        ->assertSee('Contact Us')
        ->assertSee(FrontendUrl::websiteForm($type))
        ->assertSee('Jordan Smith')
        ->assertSee('jordan@example.com');

    $this->actingAs($admin)
        ->get(route('admin.website-forms.index', [
            'formType' => $type->routeKey(),
            'search' => 'Jordan',
            'status' => 'new',
        ]))
        ->assertOk()
        ->assertSee('Jordan Smith');

    $this->actingAs($admin)
        ->get(route('admin.website-forms.show', [$type->routeKey(), $submission]))
        ->assertOk()
        ->assertSee('Please contact me.');

    $this->actingAs($admin)
        ->get(route('admin.website-forms.create', $type->routeKey()))
        ->assertOk()
        ->assertSee('Add submission');

    $this->actingAs($admin)
        ->post(route('admin.website-forms.store', $type->routeKey()), [
            'status' => WebsiteFormSubmissionStatus::Contacted->value,
            'name' => 'Manual Entry',
            'email' => 'manual@example.com',
            'phone' => '555-0199',
            'interested_in' => 'Becoming a Wellness Advocate',
            'message' => 'Called in.',
            'consent_given' => '1',
        ])
        ->assertRedirect();

    $created = WebsiteFormSubmission::query()->where('email', 'manual@example.com')->first();
    expect($created)->not->toBeNull()
        ->and($created->form_type)->toBe($type)
        ->and($created->status)->toBe(WebsiteFormSubmissionStatus::Contacted);

    $this->actingAs($admin)
        ->put(route('admin.website-forms.update', [$type->routeKey(), $submission]), [
            'status' => WebsiteFormSubmissionStatus::Scheduled->value,
            'name' => 'Jordan Smith',
            'email' => 'jordan@example.com',
            'phone' => '555-0142',
            'interested_in' => 'Learning about the H2 water machine',
            'message' => 'Please contact me.',
            'admin_notes' => 'Followed up by phone.',
            'consent_given' => '1',
        ])
        ->assertRedirect(route('admin.website-forms.show', [$type->routeKey(), $submission]));

    expect($submission->fresh()->status)->toBe(WebsiteFormSubmissionStatus::Scheduled)
        ->and($submission->fresh()->admin_notes)->toBe('Followed up by phone.');

    $this->actingAs($admin)
        ->delete(route('admin.website-forms.destroy', [$type->routeKey(), $submission]))
        ->assertRedirect(route('admin.website-forms.index', $type->routeKey()));

    $this->assertDatabaseMissing('website_form_submissions', [
        'id' => $submission->id,
    ]);
});

it('keeps website form inboxes isolated by type', function () {
    $admin = superAdminUser();

    WebsiteFormSubmission::factory()->ofType(WebsiteFormType::ContactUs)->create([
        'name' => 'Contact Person',
        'email' => 'contact@example.com',
    ]);
    WebsiteFormSubmission::factory()->ofType(WebsiteFormType::WaterAwarenessShow)->create([
        'name' => 'Show Guest',
        'email' => 'show@example.com',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.website-forms.index', WebsiteFormType::ContactUs->routeKey()))
        ->assertOk()
        ->assertSee('Contact Person')
        ->assertDontSee('Show Guest');

    $this->actingAs($admin)
        ->get(route('admin.website-forms.index', WebsiteFormType::WaterAwarenessShow->routeKey()))
        ->assertOk()
        ->assertSee('Show Guest')
        ->assertDontSee('Contact Person');
});

it('converts an inbox submission into a CRM prospect', function () {
    $this->seed(\Database\Seeders\CrmSeeder::class);
    $admin = superAdminUser();

    $submission = WebsiteFormSubmission::factory()->ofType(WebsiteFormType::ContactUs)->create([
        'name' => 'Convert Me',
        'email' => 'convert-me@example.com',
        'phone' => '555-0101',
        'interested_in' => 'Learning about the H2 water machine',
        'consent_given' => true,
        'prospect_id' => null,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.website-forms.convert-to-prospect', [
            WebsiteFormType::ContactUs->routeKey(),
            $submission,
        ]))
        ->assertRedirect();

    $submission->refresh();

    expect($submission->prospect_id)->not->toBeNull()
        ->and($submission->status)->toBe(WebsiteFormSubmissionStatus::Contacted);

    $this->assertDatabaseHas('prospects', [
        'id' => $submission->prospect_id,
        'email' => 'convert-me@example.com',
    ]);
});
