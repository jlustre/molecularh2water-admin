<?php

use App\Livewire\Portal\Dashboard;
use App\Models\MediaItem;
use App\Models\RegistrationInvite;
use App\Models\Role;
use App\Models\User;
use App\Services\RegistrationInviteService;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

function portalMember(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'member')->first());

    return $user;
}

function portalConsultant(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('renders role-aware member dashboard stats', function () {
    $member = portalMember();

    MediaItem::create([
        'title' => 'Hydrogen Guide',
        'category' => 'documents',
        'status' => 'published',
        'url' => 'https://example.com/guide.pdf',
    ]);

    $invite = app(RegistrationInviteService::class)->generate($member, $member);

    $html = $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome back, '.$member->name)
        ->assertDontSee('Account Overview')
        ->assertDontSee('Resources Library')
        ->assertSee('Network & Growth')
        ->assertSee('Team Members')
        ->assertSee('Member Invites')
        ->assertSee('Quick Links')
        ->assertSee('Demos')
        ->assertSee('My CRM Snapshot')
        ->getContent();

    expect($html)
        ->toContain('data-portal-dashboard-scope')
        ->toContain('data-portal-page-loading-overlay')
        ->toContain('portalPageLoadingOverlay')
        ->toContain('Refreshing dashboard...');

    expect(preg_match_all('/data-portal-page-loading-overlay(?=[\s>])/i', $html))->toBe(1)
        ->and(preg_match_all('/data-portal-dashboard-scope(?=[\s>])/i', $html))->toBe(1);
});

it('renders consultant crm snapshot cards', function () {
    $consultant = portalConsultant();

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My CRM Snapshot')
        ->assertSee('My Leads')
        ->assertSee('Follow-Ups Today')
        ->assertSee('My CRM Snapshot')
        ->assertSee('Upcoming Events')
        ->assertSee('Upcoming Tasks')
        ->assertSee('Demos')
        ->assertSee('CRM Insights');
});

it('renders organization crm cards for super admins', function () {
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Operations')
        ->assertSee('Organization CRM')
        ->assertSee('Total Leads');
});

it('exposes dashboard providers through portal config', function () {
    expect(config('portal.dashboard_section_providers'))
        ->toContain(\App\Support\Portal\Dashboard\Providers\CrmMetricsSectionProvider::class);
});

it('refreshes crm insights when the dashboard refresh event is dispatched', function () {
    $consultant = portalConsultant();

    Livewire::actingAs($consultant)
        ->test(Dashboard::class)
        ->assertSee('Pipeline Summary')
        ->dispatch('crm-dashboard-refresh')
        ->assertSee('Pipeline Summary');
});

it('renders grouped pipeline summary sections on the dashboard', function () {
    $this->seed(\Database\Seeders\CrmSeeder::class);

    $consultant = portalConsultant();

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pipeline Summary')
        ->assertSee('Early')
        ->assertSee('Demo')
        ->assertSee('Sales')
        ->assertSee('Fulfillment')
        ->assertSee('Close')
        ->assertSee('Referrals');
});
