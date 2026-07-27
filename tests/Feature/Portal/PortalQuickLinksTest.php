<?php

use App\Models\Role;
use App\Models\User;
use App\Services\Portal\PortalDashboardService;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('excludes profile and resources from quick links', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'member')->first());

    $links = app(PortalDashboardService::class)->quickLinks($user);
    $actions = app(PortalDashboardService::class)->quickActions($user);
    $actionLabels = collect($actions)->pluck('label')->all();

    expect($links)->toBeEmpty()
        ->and($actionLabels)->not->toContain('Admin Portal')
        ->and($actionLabels)->not->toContain('My Profile')
        ->and($actionLabels)->not->toContain('Resources')
        ->and($actionLabels)->not->toContain('Member Invites')
        ->and($actionLabels)->toContain('Prospects');
});
