<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function assertSidebarToggle($response): void
{
    $response
        ->assertOk()
        ->assertSee('layoutSidebar()', false)
        ->assertSee('@click="toggleSidebar()"', false)
        ->assertDontSee('lg:z-auto lg:hidden', false)
        ->assertSee("sidebarOpen ? 'Hide sidebar' : 'Show sidebar'", false)
        ->assertSee('closeSidebarOnMobile', false);
}

it('renders sidebar toggle on representative admin routes', function (string $routeName) {
    $user = superAdminUser();

    assertSidebarToggle($this->actingAs($user)->get(route($routeName)));
})->with([
    'admin dashboard' => 'admin.dashboard',
    'admin users' => 'admin.users.index',
    'admin crm leads' => 'admin.crm.leads.index',
    'admin crm calendar' => 'admin.crm.calendar.index',
    'admin crm pipeline' => 'admin.crm.pipeline.index',
    'admin crm reports' => 'admin.crm.reports.index',
]);

it('renders sidebar toggle on representative portal routes', function (string $routeName) {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    assertSidebarToggle($this->actingAs($consultant)->get(route($routeName)));
})->with([
    'portal dashboard' => 'dashboard',
    'portal profile' => 'profile',
    'portal resources' => 'resources',
    'portal crm leads' => 'portal.crm.leads.index',
    'portal crm calendar' => 'portal.crm.calendar.index',
    'portal crm prospects' => 'portal.crm.prospects.index',
    'portal invites' => 'portal.invites',
    'portal team' => 'portal.team',
]);

it('does not render sidebar toggle on guest auth pages', function (string $path) {
    $this->get($path)
        ->assertOk()
        ->assertDontSee('@click="toggleSidebar()"', false);
})->with([
    'login' => '/login',
    'register' => '/register',
    'welcome' => '/',
]);
