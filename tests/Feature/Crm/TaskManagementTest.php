<?php

use App\Enums\Crm\TaskStatus;
use App\Livewire\Crm\TaskManagement;
use App\Models\Crm\Task;
use App\Models\Role;
use App\Models\User;
use App\Support\Navigation\AppNavigation;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function taskManagementAdmin(): User
{
    $user = User::factory()->create(['name' => 'Tasks Admin']);
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

function taskManagementMember(string $name = 'Portal Member'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('places Tasks Management under system navigation for admins', function () {
    $admin = taskManagementAdmin();
    $links = collect(AppNavigation::links($admin));

    expect($links->firstWhere('key', 'crm-task-management'))->not->toBeNull()
        ->and($links->firstWhere('key', 'crm-task-management')['section'])->toBe('system')
        ->and($links->firstWhere('key', 'crm-task-management')['label'])->toBe('Tasks Management')
        ->and($links->firstWhere('key', 'crm-task-management')['route'])->toBe('admin.crm.task-management.index');
});

it('hides Tasks Management from consultants without tasks.assign', function () {
    $member = taskManagementMember();
    $labels = collect(AppNavigation::links($member))->pluck('label');

    expect($labels)->not->toContain('Tasks Management')
        ->and($labels)->toContain('My Tasks');
});

it('denies consultants Tasks Management by default', function () {
    $member = taskManagementMember();

    $this->actingAs($member)
        ->get(route('portal.crm.task-management.index'))
        ->assertForbidden();
});

it('allows admins to assign tasks to any portal member', function () {
    $admin = taskManagementAdmin();
    $member = taskManagementMember('Assigned Member');

    $this->actingAs($admin)
        ->get(route('admin.crm.task-management.index'))
        ->assertOk()
        ->assertSee('Tasks Management')
        ->assertSee('Assign Task');

    Livewire::actingAs($admin)
        ->test(TaskManagement::class)
        ->call('openForm')
        ->set('user_id', $member->id)
        ->set('title', 'Follow up with client')
        ->set('due_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $task = Task::query()->where('title', 'Follow up with client')->first();

    expect($task)->not->toBeNull()
        ->and($task->user_id)->toBe($member->id)
        ->and($task->status)->toBe(TaskStatus::Pending);
});

it('shows assigned member tasks across the portal on the management page', function () {
    $admin = taskManagementAdmin();
    $memberA = taskManagementMember('Member A');
    $memberB = taskManagementMember('Member B');

    Task::factory()->forUser($memberA)->create(['title' => 'Alpha task']);
    Task::factory()->forUser($memberB)->create(['title' => 'Bravo task']);

    Livewire::actingAs($admin)
        ->test(TaskManagement::class)
        ->assertSee('Alpha task')
        ->assertSee('Bravo task')
        ->assertSee('Member A')
        ->assertSee('Member B')
        ->set('assigneeFilter', (string) $memberA->id)
        ->assertSee('Alpha task')
        ->assertDontSee('Bravo task');
});
