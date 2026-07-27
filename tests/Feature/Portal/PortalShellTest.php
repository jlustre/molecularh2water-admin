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

it('renders a fixed brand panel separate from the sidebar', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-shell-brand', false)
        ->assertSee('data-shell-sidebar', false)
        ->assertSee(asset('images/brand/h2-systems-logo.png'), false)
        ->assertSee(route('dashboard'), false)
        ->assertSee('--shell-brand-w: 280px', false)
        ->assertSee('--shell-header-h: 5rem', false)
        ->assertSee('top: var(--shell-header-h)', false)
        ->assertDontSee('Close sidebar', false);
});

it('positions topbar and main clear of brand and sidebar', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-shell-topbar', false)
        ->assertSee('data-shell-main', false)
        ->assertSee('left: var(--shell-brand-w)', false)
        ->assertSee('right: 0', false)
        ->assertSee('padding-top: var(--shell-header-h)', false)
        ->assertSee("is-sidebar-open': sidebarOpen", false)
        ->assertSee('[data-shell-main].is-sidebar-open', false)
        ->assertDontSee('lg:ml-[280px]', false)
        ->assertDontSee("sidebarOpen ? 'ml-[280px] lg:ml-0' : 'ml-[280px]'", false);
});

it('renders collapsible nav groups in the portal sidebar', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('sidebarNavGroups')
        ->assertSee("toggle('workspace')", false)
        ->assertSee("toggle('crm_people')", false)
        ->assertDontSee("toggle('crm_schedule')", false)
        ->assertDontSee("toggle('account')", false);
});

it('hides admin portal link from users without admin access', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    foreach ([route('dashboard'), route('profile'), route('resources')] as $url) {
        $this->actingAs($consultant)
            ->get($url)
            ->assertOk()
            ->assertSee('data-shell-brand', false)
            ->assertSee('layoutSidebar()')
            ->assertDontSee('Admin Portal');
    }

    $admin = superAdminUser();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Admin Portal');
});

it('shows crm links but not users for consultants', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Leads')
        ->assertSee('Prospects')
        ->assertSee('Customers')
        ->assertDontSee('>Users<', false)
        ->assertDontSee('Roles &amp; Permissions', false)
        ->assertDontSee('Content Management', false);
});

it('uses the same shell chrome markers as admin pages', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());
    $admin = superAdminUser();

    $portal = $this->actingAs($consultant)->get(route('dashboard'));
    $adminPage = $this->actingAs($admin)->get(route('admin.users.index'));

    foreach (['data-shell-brand', 'data-shell-topbar', 'data-shell-sidebar', 'data-shell-main', '--shell-brand-w: 280px'] as $marker) {
        $portal->assertSee($marker, false);
        $adminPage->assertSee($marker, false);
    }
});
