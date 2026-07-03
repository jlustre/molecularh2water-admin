<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('renders a hamburger toggle for the portal sidebar', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('/build/assets/', false)
        ->assertDontSee(':5173', false)
        ->assertSee('layoutSidebar()')
        ->assertSee('@click="toggleSidebar()"', false)
        ->assertDontSee('lg:z-auto lg:hidden', false)
        ->assertSee("sidebarOpen ? 'Hide sidebar' : 'Show sidebar'", false);
});

it('renders collapsible nav groups in the portal sidebar', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('sidebarNavGroups')
        ->assertSee("toggle('workspace')", false)
        ->assertSee("toggle('crm')", false)
        ->assertSee("toggle('account')", false);
});

it('hides admin portal link from users without admin access', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    foreach ([route('dashboard'), route('profile'), route('resources')] as $url) {
        $this->actingAs($consultant)
            ->get($url)
            ->assertOk()
            ->assertSee('Associate Portal')
            ->assertSee('layoutSidebar()')
            ->assertDontSee('Admin Portal');
    }

    $admin = superAdminUser();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Admin Portal');
});
