<?php

use App\Livewire\Portal\MemberHierarchy;
use App\Models\Role;
use App\Models\User;
use App\Services\SponsorHierarchyService;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('requires a sponsor for non super-admin users created in admin', function () {
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Missing Sponsor',
            'email' => 'missing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'email_status' => 'verified',
        ])
        ->assertSessionHasErrors('sponsor_id');
});

it('allows super-admin accounts without a sponsor', function () {
    $admin = superAdminUser();

    expect($admin->sponsor_id)->toBeNull();
    expect($admin->requiresSponsor())->toBeFalse();
});

it('builds a sponsor tree for a member downline', function () {
    $root = User::factory()->create(['name' => 'Root Sponsor']);
    $child = User::factory()->create(['name' => 'Child Member', 'sponsor_id' => $root->id]);
    $grandchild = User::factory()->create(['name' => 'Grandchild Member', 'sponsor_id' => $child->id]);

    $service = app(SponsorHierarchyService::class);

    expect($service->descendants($root)->pluck('id')->all())
        ->toBe([$child->id, $grandchild->id]);

    $tree = $service->treeFor($root);

    expect($tree['children'][0]['name'])->toBe('Child Member')
        ->and($tree['children'][0]['children'][0]['name'])->toBe('Grandchild Member');
});

it('shows the portal hierarchy page for members', function () {
    $sponsor = User::factory()->create(['name' => 'Team Lead']);
    $sponsor->roles()->attach(Role::query()->where('slug', 'member')->first());

    $member = User::factory()->create([
        'name' => 'Downline Member',
        'sponsor_id' => $sponsor->id,
    ]);
    $member->roles()->attach(Role::query()->where('slug', 'member')->first());

    $this->actingAs($sponsor)
        ->get(route('portal.team'))
        ->assertOk()
        ->assertSee('Member hierarchy')
        ->assertSee('Downline Member');

    Livewire::actingAs($sponsor)
        ->test(MemberHierarchy::class)
        ->assertSet('downlineCount', 1);
});

it('renders the admin hierarchy page', function () {
    $admin = superAdminUser();
    $member = User::factory()->create(['sponsor_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.users.hierarchy'))
        ->assertOk()
        ->assertSee($admin->name)
        ->assertSee($member->name);
});
