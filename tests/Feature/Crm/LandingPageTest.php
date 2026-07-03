<?php

use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Crm\LandingPageManager;
use App\Models\Crm\LandingPage;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadCaptureForm;
use App\Models\Crm\TimelineEvent;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Crm\CrmLeadCapturedNotification;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function landingAdmin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

it('returns published landing page config from the public api', function () {
    $this->getJson('/api/crm/landing-pages/water-awareness-show')
        ->assertOk()
        ->assertJsonPath('data.slug', 'water-awareness-show')
        ->assertJsonPath('data.title', 'Water Awareness Show')
        ->assertJsonStructure(['data' => ['form' => ['fields', 'settings']]]);
});

it('captures a lead from a landing page via the crm leads api', function () {
    Notification::fake();

    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $page = LandingPage::query()->where('slug', 'water-awareness-show')->first();

    LeadCaptureForm::query()->where('landing_page_id', $page->id)->update([
        'settings' => [
            'assignment' => 'round_robin',
            'lifecycle' => 'prospect',
        ],
    ]);

    $this->postJson('/api/crm/leads', [
        'landing_page_slug' => 'water-awareness-show',
        'first_name' => 'Landing',
        'last_name' => 'Lead',
        'email' => 'landing-lead@example.com',
        'interested_in' => 'Home show',
        'consent_given' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.lifecycle', 'prospect')
        ->assertJsonPath('data.assigned_user_id', $agent->id);

    $lead = Lead::query()->where('email', 'landing-lead@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->assigned_user_id)->toBe($agent->id)
        ->and($lead->metadata['landing_page_slug'] ?? null)->toBe('water-awareness-show');

    expect($page->fresh()->conversion_count)->toBe(1);

    expect(TimelineEvent::query()
        ->where('lead_id', $lead->id)
        ->where('event_type', 'landing_page_conversion')
        ->exists())->toBeTrue();

    Notification::assertSentTo($agent, CrmLeadCapturedNotification::class);
});

it('checks whether an email already exists in the crm', function () {
    Lead::factory()->create([
        'email' => 'exists@example.com',
        'lifecycle' => LeadLifecycle::Prospect,
    ]);

    $this->getJson('/api/crm/leads/check-email?email=exists@example.com&lifecycle=prospect')
        ->assertOk()
        ->assertJsonPath('exists', true);

    $this->getJson('/api/crm/leads/check-email?email=new@example.com')
        ->assertOk()
        ->assertJsonPath('exists', false);
});

it('rejects submissions to unpublished landing pages', function () {
    $page = LandingPage::factory()->create(['is_published' => false]);

    $this->postJson('/api/crm/leads', [
        'landing_page_id' => $page->id,
        'first_name' => 'Hidden',
        'email' => 'hidden@example.com',
        'consent_given' => true,
    ])->assertNotFound();
});

it('lets admins manage landing pages in the admin ui', function () {
    $admin = landingAdmin();

    $this->actingAs($admin)
        ->get(route('admin.crm.landing-pages.index'))
        ->assertOk()
        ->assertSee('Water Awareness Show');

    Livewire::actingAs($admin)
        ->test(LandingPageManager::class)
        ->call('openForm')
        ->set('title', 'Hydration Webinar')
        ->set('headline', 'Join our free webinar')
        ->set('is_published', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(LandingPage::query()->where('title', 'Hydration Webinar')->exists())->toBeTrue();
});

it('keeps the legacy prospects api working', function () {
    $this->postJson('/api/prospects', [
        'name' => 'Legacy Prospect',
        'email' => 'legacy@example.com',
        'consent_given' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.lifecycle', 'prospect');

    expect(Lead::query()->where('email', 'legacy@example.com')->exists())->toBeTrue();
});
