<?php

use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Crm\CrmSettingsManager;
use App\Livewire\Crm\ReportDashboard;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use App\Models\Crm\LostReason;
use App\Models\Crm\Tag;
use App\Models\Crm\Team;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function phase6Admin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

function phase6Manager(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'manager')->first());

    return $user;
}

function phase6Agent(string $name = 'Report Agent'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('allows admins to view the reports dashboard', function () {
    $admin = phase6Admin();

    $this->actingAs($admin)
        ->get(route('admin.crm.reports.index'))
        ->assertOk()
        ->assertSee('Reports')
        ->assertSee('Performance')
        ->assertSee('Lead Sources');
});

it('denies agents access to crm settings', function () {
    $agent = phase6Agent();

    $this->actingAs($agent)
        ->get(route('portal.crm.settings.index'))
        ->assertForbidden();
});

it('scopes report summary totals to the current user for managers', function () {
    $manager = phase6Manager();
    $otherAgent = phase6Agent('Other Agent');

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Lead,
        'status' => 'new',
        'temperature' => 'cold',
        'first_name' => 'Mine',
        'email' => 'manager-lead@example.com',
        'assigned_user_id' => $manager->id,
        'consent_given' => true,
    ]);

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Lead,
        'status' => 'new',
        'temperature' => 'cold',
        'first_name' => 'Theirs',
        'email' => 'other-lead@example.com',
        'assigned_user_id' => $otherAgent->id,
        'consent_given' => true,
    ]);

    Livewire::actingAs($manager)
        ->test(ReportDashboard::class)
        ->assertSet('summary.total_records', 1);
});

it('shows agent leaderboard for admins with view-all permission', function () {
    $admin = phase6Admin();
    $agent = phase6Agent('Leaderboard Agent');

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Lead,
        'status' => 'new',
        'temperature' => 'warm',
        'first_name' => 'Board',
        'email' => 'board@example.com',
        'assigned_user_id' => $agent->id,
        'consent_given' => true,
    ]);

    Livewire::actingAs($admin)
        ->test(ReportDashboard::class)
        ->assertSee('Consultant Leaderboard')
        ->assertSee('Leaderboard Agent');
});

it('hides agent leaderboard from scoped managers', function () {
    $manager = phase6Manager();

    Livewire::actingAs($manager)
        ->test(ReportDashboard::class)
        ->assertDontSee('Consultant Leaderboard');
});

it('exports report dashboard csv', function () {
    $admin = phase6Admin();

    Livewire::actingAs($admin)
        ->test(ReportDashboard::class)
        ->call('exportCsv')
        ->assertFileDownloaded();
});

it('allows admins to manage lead sources in crm settings', function () {
    $admin = phase6Admin();

    Livewire::actingAs($admin)
        ->test(CrmSettingsManager::class)
        ->assertSee('CRM Settings')
        ->set('sourceName', 'Trade Show')
        ->set('sourceDescription', 'In-person events')
        ->call('saveSource')
        ->assertHasNoErrors();

    expect(LeadSource::query()->where('name', 'Trade Show')->exists())->toBeTrue();
});

it('allows admins to manage lost reasons in crm settings', function () {
    $admin = phase6Admin();

    Livewire::actingAs($admin)
        ->test(CrmSettingsManager::class)
        ->set('activeTab', 'lost-reasons')
        ->assertSee('Lost Reasons')
        ->assertSee('No Response')
        ->set('lostReasonName', 'Seasonal Pause')
        ->call('saveLostReason')
        ->assertHasNoErrors();

    expect(LostReason::query()->where('name', 'Seasonal Pause')->exists())->toBeTrue();
});

it('allows admins to create tags and teams', function () {
    $admin = phase6Admin();
    $agent = phase6Agent('Team Member');

    Livewire::actingAs($admin)
        ->test(CrmSettingsManager::class)
        ->set('activeTab', 'tags')
        ->set('tagName', 'Enterprise')
        ->set('tagColor', 'indigo')
        ->call('saveTag')
        ->assertHasNoErrors();

    expect(Tag::query()->where('name', 'Enterprise')->exists())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(CrmSettingsManager::class)
        ->set('activeTab', 'teams')
        ->set('teamName', 'West Coast')
        ->set('teamManagerId', $agent->id)
        ->set('teamMemberIds', [$agent->id])
        ->call('saveTeam')
        ->assertHasNoErrors();

    $team = Team::query()->where('name', 'West Coast')->first();

    expect($team)->not->toBeNull()
        ->and($team->manager_id)->toBe($agent->id)
        ->and($team->users()->where('users.id', $agent->id)->exists())->toBeTrue();
});

it('prevents deleting lead sources that are in use', function () {
    $admin = phase6Admin();
    $source = LeadSource::query()->first();

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Prospect,
        'status' => 'new',
        'temperature' => 'warm',
        'first_name' => 'Sourced',
        'email' => 'sourced@example.com',
        'lead_source_id' => $source->id,
        'consent_given' => true,
    ]);

    Livewire::actingAs($admin)
        ->test(CrmSettingsManager::class)
        ->call('deleteSource', $source->id)
        ->assertHasErrors(['item']);

    expect(LeadSource::query()->whereKey($source->id)->exists())->toBeTrue();
});
