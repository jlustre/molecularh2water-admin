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
