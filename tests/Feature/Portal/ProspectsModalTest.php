<?php

use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\ProspectsModal;
use App\Livewire\Portal\QuickLinks;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function prospectsModalConsultant(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('shows prospects quick action on the dashboard', function () {
    $consultant = prospectsModalConsultant();

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Prospects')
        ->assertSeeLivewire(ProspectsModal::class);
});

it('excludes executive calendar team and crm home from quick link navigation', function () {
    $consultant = prospectsModalConsultant();
    $service = app(\App\Services\Portal\PortalDashboardService::class);

    $links = $service->quickLinks($consultant);
    $labels = collect($links)->pluck('label')->all();

    expect($labels)->not->toContain('CRM Home')
        ->and($labels)->not->toContain('Calendar')
        ->and($labels)->not->toContain('My Team')
        ->and($labels)->not->toContain('Executive Dashboard');

    Livewire::actingAs($consultant)
        ->test(QuickLinks::class)
        ->assertSee('Prospects')
        ->assertDontSeeHtml('wire:navigate.hover');
});

it('lists recent prospects and creates a new prospect', function () {
    $consultant = prospectsModalConsultant();

    Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Morgan',
        'last_name' => 'Rivera',
        'email' => 'morgan@example.com',
    ]);

    Livewire::actingAs($consultant)
        ->test(ProspectsModal::class)
        ->call('open')
        ->assertSet('show', true)
        ->assertSee('Morgan Rivera')
        ->set('first_name', 'Casey')
        ->set('last_name', 'Nguyen')
        ->set('email', 'casey@example.com')
        ->set('company', 'River Wellness')
        ->set('notes', 'Met at cooking show')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('prospect-created');

    $created = Lead::query()->where('email', 'casey@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->lifecycleSlug())->toBe(LeadLifecycle::Lead)
        ->and($created->company)->toBe('River Wellness')
        ->and($created->message)->toBe('Met at cooking show');
});

it('requires email or phone when adding a prospect', function () {
    $consultant = prospectsModalConsultant();

    Livewire::actingAs($consultant)
        ->test(ProspectsModal::class)
        ->call('open')
        ->set('first_name', 'Taylor')
        ->call('create')
        ->assertHasErrors(['email', 'phone']);
});

it('refreshes dashboard stats when a prospect is created', function () {
    $consultant = prospectsModalConsultant();
    $stats = app(DashboardStatsService::class);

    $before = $stats->get($consultant)['totalLeads'];

    Livewire::actingAs($consultant)
        ->test(ProspectsModal::class)
        ->call('open')
        ->set('first_name', 'Jordan')
        ->set('last_name', 'Lee')
        ->set('phone', '555-0100')
        ->call('create')
        ->assertHasNoErrors();

    $after = $stats->get($consultant)['totalLeads'];

    expect($after)->toBe($before + 1);
});

it('opens the prospects modal from quick links', function () {
    $consultant = prospectsModalConsultant();

    Livewire::actingAs($consultant)
        ->test(QuickLinks::class)
        ->call('openProspects')
        ->assertDispatched('open-prospects');
});
