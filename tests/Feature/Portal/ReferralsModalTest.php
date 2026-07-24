<?php

use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\QuickLinks;
use App\Livewire\Portal\ReferralsModal;
use App\Models\Crm\Customer;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Referral;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function referralsModalConsultant(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('shows referrals quick action on the dashboard', function () {
    $consultant = referralsModalConsultant();

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Referrals')
        ->assertSeeLivewire(ReferralsModal::class);
});

it('lists recent referrals and logs a new referral', function () {
    $consultant = referralsModalConsultant();

    $client = Customer::factory()->assignedTo($consultant)->create([
        'first_name' => 'Pat',
        'last_name' => 'Client',
        'email' => 'pat@example.com',
    ]);

    $existing = app(\App\Services\Crm\ReferralService::class)->recordReferral($client, [
        'first_name' => 'Alex',
        'last_name' => 'Existing',
        'email' => 'alex@example.com',
    ], $consultant);

    Livewire::actingAs($consultant)
        ->test(ReferralsModal::class)
        ->call('open')
        ->assertSet('show', true)
        ->assertSee('Alex Existing')
        ->call('selectReferrer', 'customer', $client->id)
        ->set('first_name', 'Jamie')
        ->set('last_name', 'Referral')
        ->set('email', 'jamie@example.com')
        ->set('notes', 'Met at wellness event')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('referral-created');

    $created = Referral::query()
        ->where('referrer_type', 'customer')
        ->where('referrer_id', $client->id)
        ->whereHas('referred', fn ($query) => $query->where('email', 'jamie@example.com'))
        ->first();

    expect($created)->not->toBeNull()
        ->and($created->referred->lifecycleSlug())->toBe(LeadLifecycle::Lead)
        ->and($created->referred->referred_by_id)->toBe($client->id)
        ->and($created->referred->referred_by_type)->toBe('customer')
        ->and($created->referred->stage?->slug)->toBe('referral-received')
        ->and($existing->id)->not->toBe($created->id);
});

it('requires a referring person when logging a referral', function () {
    $consultant = referralsModalConsultant();

    Livewire::actingAs($consultant)
        ->test(ReferralsModal::class)
        ->call('open')
        ->set('first_name', 'Taylor')
        ->call('create')
        ->assertHasErrors(['referrer_search']);
});

it('logs a referral with a free-text referring person who is not in CRM', function () {
    $consultant = referralsModalConsultant();

    Livewire::actingAs($consultant)
        ->test(ReferralsModal::class)
        ->call('open')
        ->set('referrer_search', 'Neighbor Friend')
        ->call('useTypedReferrer')
        ->assertSet('referrer_is_external', true)
        ->set('first_name', 'Chris')
        ->set('last_name', 'Referred')
        ->set('email', 'chris@example.com')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('referral-created');

    $referrer = Lead::query()
        ->where('first_name', 'Neighbor')
        ->where('last_name', 'Friend')
        ->first();

    $created = Referral::query()
        ->where('referrer_type', 'lead')
        ->where('referrer_id', $referrer?->id)
        ->whereHas('referred', fn ($query) => $query->where('email', 'chris@example.com'))
        ->first();

    expect($referrer)->not->toBeNull()
        ->and($created)->not->toBeNull()
        ->and($created->referred->referred_by_type)->toBe('lead')
        ->and($created->referred->referred_by_id)->toBe($referrer->id);
});

it('searches prospects and customers as referring people', function () {
    $consultant = referralsModalConsultant();

    $prospect = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Sam',
        'last_name' => 'Prospect',
        'email' => 'sam@example.com',
    ]);

    $client = Customer::factory()->assignedTo($consultant)->create([
        'first_name' => 'Sam',
        'last_name' => 'Customer',
        'email' => 'sam.client@example.com',
    ]);

    Lead::factory()->assignedTo($consultant)->create([
        'first_name' => 'Sam',
        'last_name' => 'Lead',
        'email' => 'sam.lead@example.com',
    ]);

    $service = app(\App\Services\Portal\PortalReferralService::class);
    $results = $service->searchReferrers('Sam', $consultant);

    expect($results->count())->toBeGreaterThanOrEqual(2)
        ->and($results->pluck('email')->all())->toContain('sam@example.com', 'sam.client@example.com');
});

it('logs a referral with a prospect as the referring person', function () {
    $consultant = referralsModalConsultant();

    $prospect = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Riley',
        'last_name' => 'Prospect',
        'email' => 'riley@example.com',
    ]);

    Livewire::actingAs($consultant)
        ->test(ReferralsModal::class)
        ->call('open')
        ->assertSee('Referring person')
        ->call('selectReferrer', 'prospect', $prospect->id)
        ->set('first_name', 'Casey')
        ->set('last_name', 'Referred')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('referral-created');

    $created = Referral::query()
        ->where('referrer_type', 'prospect')
        ->where('referrer_id', $prospect->id)
        ->whereHas('referred', fn ($query) => $query->where('first_name', 'Casey'))
        ->first();

    expect($created)->not->toBeNull()
        ->and($created->referred->lifecycleSlug())->toBe(LeadLifecycle::Lead)
        ->and($created->referred->referred_by_id)->toBe($prospect->id)
        ->and($created->referred->referred_by_type)->toBe('prospect');
});

it('refreshes dashboard stats when a referral is logged', function () {
    $consultant = referralsModalConsultant();
    $stats = app(DashboardStatsService::class);

    $client = Customer::factory()->assignedTo($consultant)->create();

    $before = $stats->get($consultant)['totalLeads'];

    Livewire::actingAs($consultant)
        ->test(ReferralsModal::class)
        ->call('open')
        ->call('selectReferrer', 'customer', $client->id)
        ->set('first_name', 'Jordan')
        ->set('last_name', 'Lee')
        ->set('phone', '555-0100')
        ->call('create')
        ->assertHasNoErrors();

    $after = $stats->get($consultant)['totalLeads'];

    expect($after)->toBe($before + 1);
});

it('updates pipeline summary when a referral is logged', function () {
    $consultant = referralsModalConsultant();
    $stats = app(DashboardStatsService::class);

    $client = Customer::factory()->assignedTo($consultant)->create([
        'first_name' => 'Pat',
        'last_name' => 'Client',
    ]);

    $beforeCount = $stats->get($consultant)['funnelStages']
        ->firstWhere('slug', 'referral-received')?->leads_count ?? 0;

    Livewire::actingAs($consultant)
        ->test(ReferralsModal::class)
        ->call('open')
        ->call('selectReferrer', 'customer', $client->id)
        ->set('first_name', 'Jordan')
        ->set('last_name', 'Lee')
        ->set('phone', '555-0100')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('referral-created');

    $referralStage = FunnelStage::query()->where('slug', 'referral-received')->firstOrFail();
    $afterCount = $stats->get($consultant)['funnelStages']
        ->firstWhere('slug', 'referral-received')?->leads_count ?? 0;

    expect($afterCount)->toBe($beforeCount + 1)
        ->and(Lead::query()->where('funnel_stage_id', $referralStage->id)->count())->toBeGreaterThan(0);
});

it('opens the referrals modal from quick links', function () {
    $consultant = referralsModalConsultant();

    Livewire::actingAs($consultant)
        ->test(QuickLinks::class)
        ->call('openReferrals')
        ->assertDispatched('open-referrals');
});

it('dispatches dashboard refresh when referral is created via quick links listener', function () {
    $consultant = referralsModalConsultant();

    Livewire::actingAs($consultant)
        ->test(QuickLinks::class)
        ->dispatch('referral-created')
        ->assertDispatched('crm-dashboard-refresh');
});

it('refreshes dashboard when referral is created', function () {
    $consultant = referralsModalConsultant();

    Livewire::actingAs($consultant)
        ->test(Dashboard::class)
        ->dispatch('referral-created')
        ->assertStatus(200);
});

it('creates referred person as lead who can convert to prospect', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    $client = Customer::factory()->assignedTo($admin)->create([
        'first_name' => 'Morgan',
        'last_name' => 'Client',
    ]);

    Livewire::actingAs($admin)
        ->test(ReferralsModal::class)
        ->call('open')
        ->call('selectReferrer', 'customer', $client->id)
        ->set('first_name', 'Drew')
        ->set('last_name', 'Referred')
        ->set('email', 'drew@example.com')
        ->call('create')
        ->assertHasNoErrors();

    $referred = Referral::query()
        ->where('referrer_type', 'customer')
        ->where('referrer_id', $client->id)
        ->whereHas('referred', fn ($query) => $query->where('email', 'drew@example.com'))
        ->first()
        ?->referred;

    expect($referred)->not->toBeNull()
        ->and($referred->lifecycleSlug())->toBe(LeadLifecycle::Lead);

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Crm\LeadProfile::class, ['lead' => $referred])
        ->call('convertTo', LeadLifecycle::Prospect)
        ->assertRedirect(route('admin.crm.prospects.show', Prospect::query()->where('email', 'drew@example.com')->first()));

    $prospect = Prospect::query()->where('email', 'drew@example.com')->first();

    expect($prospect)->not->toBeNull()
        ->and($prospect->lifecycleSlug())->toBe(LeadLifecycle::Prospect)
        ->and(Lead::query()->where('email', 'drew@example.com')->exists())->toBeFalse();
});

it('shows helper copy about leads and prospect conversion', function () {
    $consultant = referralsModalConsultant();

    Livewire::actingAs($consultant)
        ->test(ReferralsModal::class)
        ->call('open')
        ->assertSee('added to your leads')
        ->assertSee('do not need to be customers or members');
});

it('denies referrals modal without clients view permission', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'editor')->first());

    Livewire::actingAs($user)
        ->test(ReferralsModal::class)
        ->call('open')
        ->assertForbidden();
});
