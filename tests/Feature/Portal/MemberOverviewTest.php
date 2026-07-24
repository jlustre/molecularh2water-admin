<?php

use App\Livewire\Portal\MemberOverview;
use App\Models\Crm\Team;
use App\Models\Role;
use App\Models\User;
use App\Services\MemberOverviewAccess;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

function overviewMember(string $name, string $roleSlug = 'member', ?User $sponsor = null): User
{
    $user = User::factory()->create([
        'name' => $name,
        'sponsor_id' => $sponsor?->id,
    ]);
    $user->roles()->attach(Role::query()->where('slug', $roleSlug)->first());

    return $user;
}

it('allows a sponsor to open a downline member overview', function () {
    $lead = overviewMember('Team Lead', 'team-admin');
    $member = overviewMember('Downline Consultant', 'consultant', $lead);

    $this->actingAs($lead)
        ->get(route('portal.team.member', $member))
        ->assertOk()
        ->assertSee('Member Overview')
        ->assertSee('Downline Consultant')
        ->assertSee('Performance')
        ->assertSee('Direct members');
});

it('forbids viewing a member outside sponsor and team scope', function () {
    $lead = overviewMember('Scoped Lead', 'team-admin');
    $outsider = overviewMember('Outside Member', 'consultant');

    $this->actingAs($lead)
        ->get(route('portal.team.member', $outsider))
        ->assertForbidden();
});

it('allows a manager to open a CRM team members overview', function () {
    $manager = overviewMember('Coach Manager', 'manager');
    $member = overviewMember('Team Consultant', 'consultant');

    $team = Team::query()->create([
        'name' => 'Overview Team',
        'slug' => 'overview-team',
        'manager_id' => $manager->id,
    ]);
    $team->users()->attach([
        $manager->id => ['role' => 'lead'],
        $member->id => ['role' => 'member'],
    ]);

    Livewire::actingAs($manager)
        ->test(MemberOverview::class, ['user' => $member])
        ->assertSee('Team Consultant')
        ->assertDontSee('Performance counters')
        ->assertSee('Performance summary')
        ->assertSee('Prev')
        ->assertSee('Next')
        ->assertSee('Week')
        ->assertSee('Month')
        ->assertSee('Year')
        ->call('setRecruitsPeriod', 'month')
        ->assertSet('recruitsPeriod', 'month')
        ->call('setSalesPeriod', 'year')
        ->assertSet('salesPeriod', 'year')
        ->assertDontSeeHtml('wire:model.live="subjectUserId"');
});

it('links hierarchy members to the overview page', function () {
    $lead = overviewMember('Hierarchy Lead', 'team-admin');
    $member = overviewMember('Clickable Member', 'consultant', $lead);

    $this->actingAs($lead)
        ->get(route('portal.team'))
        ->assertOk()
        ->assertSee(route('portal.team.member', $member), false)
        ->assertSee('View overview');
});

it('resolves overview access through MemberOverviewAccess', function () {
    $lead = overviewMember('Access Lead', 'team-admin');
    $member = overviewMember('Access Member', 'consultant', $lead);
    $outsider = overviewMember('Access Outsider', 'consultant');
    $access = app(MemberOverviewAccess::class);

    expect($access->canBrowse($lead))->toBeTrue()
        ->and($access->canView($lead, $member))->toBeTrue()
        ->and($access->canView($lead, $outsider))->toBeFalse()
        ->and($access->canView($lead, $lead))->toBeTrue();
});
