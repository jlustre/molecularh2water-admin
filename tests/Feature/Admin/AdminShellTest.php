<?php

use App\Models\User;

it('renders a hamburger toggle for the admin sidebar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('sidebarOpen')
        ->assertSee('@click="sidebarOpen = ! sidebarOpen"', false)
        ->assertSee("sidebarOpen ? 'Hide sidebar' : 'Show sidebar'", false);
});
