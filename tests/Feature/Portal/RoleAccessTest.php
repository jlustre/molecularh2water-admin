<?php

use App\Models\Crm\Lead;
use App\Models\Crm\Team;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('allows consultants to use portal crm but not the admin shell', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $this->actingAs($consultant)
        ->get(route('portal.crm.leads.index'))
        ->assertOk();

    $this->actingAs($consultant)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('allows managers to use portal crm but not the admin shell', function () {
    $manager = User::factory()->create();
    $manager->roles()->attach(Role::query()->where('slug', 'manager')->first());

    $this->actingAs($manager)
        ->get(route('portal.crm.leads.index'))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('admin.crm.leads.index'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('portal.crm.reports.index'))
        ->assertOk();
});

it('scopes manager visibility to team members', function () {
    $manager = User::factory()->create(['name' => 'Team Manager']);
    $manager->roles()->attach(Role::query()->where('slug', 'manager')->first());

    $consultant = User::factory()->create(['name' => 'Team Consultant']);
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $outsider = User::factory()->create(['name' => 'Outside Consultant']);
    $outsider->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $team = Team::query()->create([
        'name' => 'Alpha Team',
        'slug' => 'alpha-team',
        'manager_id' => $manager->id,
    ]);
    $team->users()->attach($consultant->id, ['role' => 'member']);

    Lead::factory()->assignedTo($consultant)->create(['first_name' => 'Visible', 'last_name' => 'Lead']);
    Lead::factory()->assignedTo($outsider)->create(['first_name' => 'Hidden', 'last_name' => 'Lead']);

    $this->actingAs($manager)
        ->get(route('portal.crm.leads.index'))
        ->assertOk()
        ->assertSee('Visible Lead')
        ->assertDontSee('Hidden Lead');
});

it('allows members to use portal crm', function () {
    $member = User::factory()->create();
    $member->roles()->attach(Role::query()->where('slug', 'member')->first());

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk();

    $this->actingAs($member)
        ->get(route('portal.crm.leads.index'))
        ->assertOk();
});
