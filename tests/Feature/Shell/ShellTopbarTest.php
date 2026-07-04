<?php

use App\Enums\Crm\TaskStatus;
use App\Models\Crm\Task;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Crm\TaskReminderNotification;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('removes the messages icon from the shared shell topbar', function () {
    $user = superAdminUser();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee('aria-label="Messages"', false);
});

it('shows notifications and tasks icons for users with permissions', function () {
    $user = superAdminUser();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('aria-label="Notifications"', false)
        ->assertSee('aria-label="Tasks"', false)
        ->assertSee('View all tasks');
});

it('shows unread notification count and message in the topbar', function () {
    $user = superAdminUser();
    $task = Task::factory()->forUser($user)->create([
        'title' => 'Call prospect about demo',
        'business_line' => 'both',
        'status' => TaskStatus::Pending,
    ]);

    $user->notify(new TaskReminderNotification($task));

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Task reminder: Call prospect about demo')
        ->assertSee('1 unread');
});

it('marks a notification as read and redirects to the related page', function () {
    $user = superAdminUser();
    $task = Task::factory()->forUser($user)->create([
        'title' => 'Send proposal',
        'business_line' => 'both',
    ]);

    $user->notify(new TaskReminderNotification($task));

    $notification = $user->unreadNotifications()->first();
    expect($notification)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('notifications.read', ['notification' => $notification->id]))
        ->assertRedirect(route('admin.crm.tasks.index'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('shows open task count and titles in the topbar', function () {
    $user = superAdminUser();

    Task::factory()->forUser($user)->create([
        'title' => 'Prepare quotation packet',
        'business_line' => 'both',
        'status' => TaskStatus::Pending,
    ]);
    Task::factory()->forUser($user)->create([
        'title' => 'Completed already',
        'business_line' => 'both',
        'status' => TaskStatus::Completed,
    ]);

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Prepare quotation packet')
        ->assertDontSee('Completed already')
        ->assertSee(route('admin.crm.tasks.index'), false);
});

it('hides notifications and tasks icons without permissions', function () {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('slug', 'editor')->first());

    $this->actingAs($editor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('aria-label="Notifications"', false)
        ->assertDontSee('aria-label="Tasks"', false)
        ->assertDontSee('aria-label="Messages"', false);
});

it('shows the same topbar actions on portal pages', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    Task::factory()->forUser($consultant)->create([
        'title' => 'Portal follow-up task',
        'business_line' => 'h2s',
        'status' => TaskStatus::InProgress,
    ]);

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('aria-label="Notifications"', false)
        ->assertSee('aria-label="Tasks"', false)
        ->assertSee('Portal follow-up task')
        ->assertSee(route('portal.crm.tasks.index'), false)
        ->assertDontSee('aria-label="Messages"', false);
});
