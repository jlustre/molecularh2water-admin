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
    $labels = collect($links)->pluck('label')->all();
    $routes = collect($links)->pluck('route')->all();
    $actions = app(PortalDashboardService::class)->quickActions($user);
    $actionLabels = collect($actions)->pluck('label')->all();

    expect($labels)->not->toContain('My Profile')
        ->and($labels)->not->toContain('Resources')
        ->and($labels)->not->toContain('Member Invites')
        ->and($labels)->not->toContain('CRM Home')
        ->and($labels)->not->toContain('Calendar')
        ->and($labels)->not->toContain('My Team')
        ->and($labels)->not->toContain('Executive Dashboard')
        ->and($routes)->not->toContain(route('profile'))
        ->and($routes)->not->toContain(route('resources'))
        ->and($routes)->not->toContain(route('portal.invites'))
        ->and($actionLabels)->toContain('Member Invites')
        ->and($actionLabels)->toContain('Prospects');
});
