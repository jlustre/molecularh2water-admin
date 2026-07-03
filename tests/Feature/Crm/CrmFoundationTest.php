<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Crm\CrmPermissions;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('allows super admin to access crm dashboard and leads', function () {
    $user = User::factory()->create();
    $role = Role::query()->where('slug', 'super-admin')->first();
    $user->roles()->attach($role);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Sales Command Center');

    $this->actingAs($user)
        ->get(route('admin.crm.leads.index'))
        ->assertOk()
        ->assertSee('Lead');
});

it('allows member access to portal crm leads', function () {
    $user = User::factory()->create();
    $role = Role::query()->where('slug', 'member')->first();
    $user->roles()->attach($role);

    $this->actingAs($user)
        ->get(route('portal.crm.leads.index'))
        ->assertOk()
        ->assertSee('Lead');

    $this->actingAs($user)
        ->get(route('admin.crm.leads.index'))
        ->assertForbidden();
});

it('allows consultant to access portal crm leads', function () {
    $user = User::factory()->create();
    $role = Role::query()->where('slug', 'consultant')->first();
    $user->roles()->attach($role);

    $this->actingAs($user)
        ->get(route('portal.crm.leads.index'))
        ->assertOk()
        ->assertSee('Lead');

    $this->actingAs($user)
        ->get(route('admin.crm.leads.index'))
        ->assertForbidden();
});

it('seeds default funnel stages and lead sources', function () {
    $this->seed(\Database\Seeders\CrmSeeder::class);

    expect(\App\Models\Crm\Funnel::query()->where('is_default', true)->exists())->toBeTrue();
    expect(\App\Models\Crm\FunnelStage::query()->count())->toBeGreaterThanOrEqual(21);
    expect(\App\Models\Crm\LeadSource::query()->count())->toBeGreaterThanOrEqual(5);
    expect(\App\Models\Crm\FunnelStage::query()->where('slug', 'demo-scheduled')->exists())->toBeTrue();
    expect(\App\Models\Crm\ActivityType::query()->where('is_active', true)->where('slug', 'cooking-show')->exists())->toBeTrue();
    expect(\App\Models\Crm\ActivityType::query()->where('slug', 'policy-review')->value('is_active'))->toBeFalsy();
});

it('resolves user permissions from roles', function () {
    $user = User::factory()->create();
    $consultantRole = Role::query()->where('slug', 'consultant')->first();

    $user->roles()->attach($consultantRole);

    expect($user->hasPermission('leads.view'))->toBeTrue();
    expect($user->hasPermission('crm.settings.manage'))->toBeFalse();
    expect(CrmPermissions::all())->not->toBeEmpty();
});
