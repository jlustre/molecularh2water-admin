<?php

use App\Models\User;

it('renders a hamburger toggle for the admin sidebar', function () {
    $user = superAdminUser();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('layoutSidebar()')
        ->assertSee('@click="toggleSidebar()"', false)
        ->assertDontSee('lg:z-auto lg:hidden', false)
        ->assertSee("sidebarOpen ? 'Hide sidebar' : 'Show sidebar'", false);
});

it('renders a fixed brand panel separate from the sidebar', function () {
    $user = superAdminUser();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('data-shell-brand', false)
        ->assertSee('data-shell-sidebar', false)
        ->assertSee(asset('images/brand/h2-systems-logo.png'), false)
        ->assertSee(route('admin.dashboard'), false)
        ->assertSee('--shell-brand-w: 280px', false)
        ->assertSee('--shell-header-h: 5rem', false)
        ->assertSee('top: var(--shell-header-h)', false);
});

it('positions topbar and main clear of brand and sidebar', function () {
    $user = superAdminUser();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
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

it('renders collapsible nav groups in the admin sidebar', function () {
    $user = superAdminUser();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('sidebarNavGroups')
        ->assertSee("toggle('overview')", false)
        ->assertSee("toggle('content')", false)
        ->assertSee("toggle('system')", false);
});

it('shows admin system links for super admins', function () {
    $user = superAdminUser();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Users')
        ->assertSee('Roles &amp; Permissions', false)
        ->assertSee('Settings');
});
