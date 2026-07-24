<?php

use App\Livewire\Portal\DemosModal;
use App\Livewire\Portal\Dashboard;
use App\Mail\DemoScheduledMail;
use App\Models\Crm\Customer;
use App\Models\Crm\Demonstration;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Prospect;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use App\Services\Portal\PortalDemoService;
use App\Services\SettingsService;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    config(['crm.automation.sync' => true]);
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

function demoConsultant(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('shows demos quick action on the dashboard', function () {
    $consultant = demoConsultant();

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Demos')
        ->assertSeeLivewire(DemosModal::class);
});

it('lists upcoming demos and schedules a new demo', function () {
    Mail::fake();

    $consultant = demoConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Alex',
        'last_name' => 'Prospect',
    ]);

    $existing = Demonstration::query()->create([
        'contact_type' => $lead->getMorphClass(),
        'contact_id' => $lead->id,
        'user_id' => $consultant->id,
        'type' => 'home',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay()->setTime(14, 0),
        'duration_minutes' => 60,
        'venue' => 'Client home',
    ]);

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->assertSet('show', true)
        ->assertSee('Client home')
        ->set('contact_search', 'Alex')
        ->call('selectContact', $lead->id)
        ->set('demo_type', 'home')
        ->set('demo_when', 'tomorrow_10')
        ->set('duration_minutes', 90)
        ->set('venue', 'Kitchen table demo')
        ->set('notes', 'Bring sample bottles')
        ->set('contact_email', 'alex@example.com')
        ->call('schedule')
        ->assertHasNoErrors();

    $demo = Demonstration::query()
        ->whereContact($lead)
        ->where('id', '!=', $existing->id)
        ->first();

    expect($demo)->not->toBeNull()
        ->and($demo->venue)->toBe('Kitchen table demo')
        ->and($demo->duration_minutes)->toBe(90)
        ->and($demo->notes)->toBe('Bring sample bottles')
        ->and($demo->scheduled_at?->format('H:i'))->toBe('10:00');

    expect(app(PortalDemoService::class)->upcomingDemos($consultant))->toHaveCount(2);

    Mail::assertSent(DemoScheduledMail::class, fn (DemoScheduledMail $mail) => $mail->hasTo('alex@example.com'));
});

it('shows scheduled demos on the calendar page', function () {
    Mail::fake();

    $consultant = demoConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Alex',
        'last_name' => 'Prospect',
    ]);

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->call('selectContact', $lead->id)
        ->set('demo_when', 'tomorrow_10')
        ->call('schedule')
        ->assertHasNoErrors();

    $this->actingAs($consultant)
        ->get(route('portal.crm.calendar.index'))
        ->assertOk()
        ->assertSee('Home Demo')
        ->assertSee('Alex Prospect');
});

it('shows scheduled demos in dashboard upcoming events', function () {
    Mail::fake();

    $consultant = demoConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Alex',
        'last_name' => 'Prospect',
    ]);

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->call('selectContact', $lead->id)
        ->set('demo_when', 'tomorrow_10')
        ->call('schedule')
        ->assertHasNoErrors();

    Livewire::actingAs($consultant)
        ->test(Dashboard::class)
        ->assertSee('Upcoming Events')
        ->assertSee('Home Demo')
        ->assertSee('Alex Prospect');
});

it('updates pipeline summary when a demo is scheduled', function () {
    Mail::fake();

    $consultant = demoConsultant();
    $newLeadStage = FunnelStage::query()->where('slug', 'new-lead')->firstOrFail();

    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Alex',
        'last_name' => 'Prospect',
        'funnel_id' => $newLeadStage->funnel_id,
        'funnel_stage_id' => $newLeadStage->id,
    ]);

    $stats = app(DashboardStatsService::class);
    $stats->get($consultant);

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->call('selectContact', $lead->id)
        ->set('demo_when', 'tomorrow_10')
        ->call('schedule')
        ->assertHasNoErrors();

    expect(Demonstration::query()->whereContact($lead)->exists())->toBeTrue();

    // Dashboard stats must resolve demos via contact morph (no lead_id column).
    expect($stats->get($consultant))->toHaveKeys(['demosToday', 'scheduledDemos']);
});

it('updates pipeline summary when a new prospect is created and scheduled', function () {
    Mail::fake();

    $consultant = demoConsultant();
    $stats = app(DashboardStatsService::class);
    $before = $stats->get($consultant);
    $demosTodayBefore = $before['demosToday'];

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->set('contact_search', 'Taylor Newperson')
        ->set('demo_type', 'home')
        ->set('demo_when', 'tomorrow_10')
        ->call('schedule')
        ->call('confirmAddProspect')
        ->set('new_email', 'taylor@example.com')
        ->call('createProspectAndSchedule')
        ->assertHasNoErrors();

    $prospect = Prospect::query()->where('email', 'taylor@example.com')->first();

    expect($prospect)->not->toBeNull()
        ->and(Demonstration::query()->whereContact($prospect)->exists())->toBeTrue();

    // demosToday only counts demos scheduled for today; tomorrow_10 is tomorrow.
    expect($stats->get($consultant)['demosToday'])->toBe($demosTodayBefore);
});

it('shows contact search results for prospects and customers', function () {
    $consultant = demoConsultant();

    Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Jamie',
        'last_name' => 'Prospect',
    ]);

    Customer::factory()->assignedTo($consultant)->create([
        'first_name' => 'Jamie',
        'last_name' => 'Client',
    ]);

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->set('contact_search', 'Jam')
        ->assertSee('Jamie Prospect')
        ->assertSee('Jamie Client')
        ->assertSee('Prospect')
        ->assertSee('Customer');
});

it('prompts to add an unknown contact as a prospect before scheduling', function () {
    $consultant = demoConsultant();

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->set('contact_search', 'Taylor Newperson')
        ->set('demo_type', 'home')
        ->set('demo_when', 'tomorrow_10')
        ->call('schedule')
        ->assertSet('show_add_prospect_prompt', true)
        ->assertSee('not in your prospect or customer list yet');
});

it('creates a prospect and schedules a demo when confirmed', function () {
    Mail::fake();

    $consultant = demoConsultant();

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->set('contact_search', 'Taylor Newperson')
        ->set('demo_type', 'home')
        ->set('demo_when', 'tomorrow_10')
        ->call('schedule')
        ->call('confirmAddProspect')
        ->assertSet('show_new_prospect_form', true)
        ->assertSet('new_first_name', 'Taylor')
        ->assertSet('new_last_name', 'Newperson')
        ->set('new_email', 'taylor@example.com')
        ->call('createProspectAndSchedule')
        ->assertHasNoErrors();

    $lead = Prospect::query()->where('email', 'taylor@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->lifecycle?->value)->toBe('prospect')
        ->and($lead->stage?->slug)->toBe('demo-scheduled')
        ->and(Demonstration::query()->whereContact($lead)->exists())->toBeTrue();
});

it('requires a contact when scheduling a demo', function () {
    $consultant = demoConsultant();

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->set('demo_type', 'home')
        ->call('schedule')
        ->assertHasErrors(['contact_search']);
});

it('sends a demo confirmation email when contact email is provided', function () {
    Mail::fake();

    $consultant = demoConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Alex',
        'last_name' => 'Prospect',
        'email' => 'alex@example.com',
    ]);

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->call('selectContact', $lead->id)
        ->assertSet('contact_email', 'alex@example.com')
        ->set('demo_type', 'home')
        ->set('demo_when', 'tomorrow_10')
        ->call('schedule')
        ->assertHasNoErrors();

    Mail::assertSent(DemoScheduledMail::class, function (DemoScheduledMail $mail) {
        return $mail->hasTo('alex@example.com')
            && $mail->onlineDemoLink === null;
    });
});

it('includes the online demo link from settings for zoom demos', function () {
    Mail::fake();

    app(SettingsService::class)->set('portal.online_demo_link', 'https://zoom.us/j/test-meeting-123');

    $consultant = demoConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Alex',
        'last_name' => 'Prospect',
        'email' => 'alex@example.com',
    ]);

    Livewire::actingAs($consultant)
        ->test(DemosModal::class)
        ->call('open')
        ->call('selectContact', $lead->id)
        ->set('demo_type', 'zoom')
        ->set('demo_when', 'tomorrow_10')
        ->call('schedule')
        ->assertHasNoErrors();

    Mail::assertSent(DemoScheduledMail::class, function (DemoScheduledMail $mail) {
        return $mail->hasTo('alex@example.com')
            && $mail->onlineDemoLink === 'https://zoom.us/j/test-meeting-123';
    });
});

it('counts demos today without querying lead_id', function () {
    $consultant = demoConsultant();
    $prospect = Prospect::factory()->assignedTo($consultant)->create();

    Demonstration::query()->create([
        'contact_type' => $prospect->getMorphClass(),
        'contact_id' => $prospect->id,
        'user_id' => $consultant->id,
        'type' => 'home',
        'status' => 'scheduled',
        'scheduled_at' => now()->setTime(14, 0),
        'duration_minutes' => 60,
    ]);

    $stats = app(DashboardStatsService::class)->get($consultant);

    expect($stats['demosToday'])->toBe(1);
});
