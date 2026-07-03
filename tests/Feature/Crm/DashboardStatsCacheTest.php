<?php

use App\Livewire\Crm\DashboardStats;
use App\Models\Crm\Lead;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Crm\TaskReminderNotification;
use App\Services\Crm\DashboardStatsService;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
    Cache::flush();
});

function phase7Agent(string $name = 'Cache Agent'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('caches dashboard stats for five minutes by default', function () {
    $agent = phase7Agent();

    Lead::factory()->count(2)->assignedTo($agent)->create();

    Livewire::actingAs($agent)
        ->test(DashboardStats::class)
        ->assertSet('totalLeads', 2);

    Lead::factory()->assignedTo($agent)->create();

    Livewire::actingAs($agent)
        ->test(DashboardStats::class)
        ->assertSet('totalLeads', 2);

    Cache::flush();

    Livewire::actingAs($agent)
        ->test(DashboardStats::class)
        ->assertSet('totalLeads', 3);
});

it('uses separate cache keys for scoped users and admins', function () {
    $agent = phase7Agent();
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    Lead::factory()->assignedTo($agent)->create();
    Lead::factory()->create();

    Livewire::actingAs($agent)
        ->test(DashboardStats::class)
        ->assertSet('totalLeads', 1);

    Livewire::actingAs($admin)
        ->test(DashboardStats::class)
        ->assertSet('totalLeads', 2);
});

it('can bust cached dashboard stats for a user', function () {
    $agent = phase7Agent();
    $service = app(DashboardStatsService::class);

    Lead::factory()->assignedTo($agent)->create();
    expect($service->get($agent)['totalLeads'])->toBe(1);

    Lead::factory()->assignedTo($agent)->create();
    expect($service->get($agent)['totalLeads'])->toBe(1);

    $service->forget($agent);
    expect($service->get($agent)['totalLeads'])->toBe(2);
});

it('queues crm notifications on the configured queue', function () {
    config(['crm.queue.notifications' => 'crm']);

    $notification = new TaskReminderNotification(
        \App\Models\Crm\Task::factory()->make(['title' => 'Queued task']),
    );

    expect($notification->queue)->toBe('crm');
});
