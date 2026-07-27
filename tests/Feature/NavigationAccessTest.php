<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Navigation\AppNavigation;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

function navigationConsultant(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

function navigationAdmin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

it('hides content and insights from consultants by default', function () {
    $labels = collect(AppNavigation::links(navigationConsultant()))->pluck('label');

    expect($labels)->not->toContain('FAQs Management')
        ->and($labels)->not->toContain('Blog Management')
        ->and($labels)->not->toContain('Media Management')
        ->and($labels)->not->toContain('Executive Dashboard')
        ->and($labels)->not->toContain('Reports')
        ->and($labels)->not->toContain('Team Calendar')
        ->and($labels)->not->toContain('Member Invites')
        ->and($labels)->not->toContain('My Team');
});

it('shows content and insights for admins with permissions', function () {
    $links = collect(AppNavigation::links(navigationAdmin()));

    expect($links->firstWhere('key', 'faqs'))->not->toBeNull()
        ->and($links->firstWhere('key', 'faqs')['section'])->toBe('content')
        ->and($links->firstWhere('key', 'crm-dashboard')['section'])->toBe('crm_insights')
        ->and($links->firstWhere('key', 'crm-reports')['section'])->toBe('crm_insights')
        ->and($links->firstWhere('key', 'crm-calendar'))->toBeNull()
        ->and($links->firstWhere('key', 'invites'))->toBeNull()
        ->and($links->firstWhere('key', 'team'))->toBeNull();
});
