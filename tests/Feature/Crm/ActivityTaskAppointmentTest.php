<?php

use App\Enums\Crm\TaskStatus;
use App\Livewire\Crm\ActivityManager;
use App\Livewire\Crm\AppointmentCalendar;
use App\Livewire\Crm\LeadEngagementPanel;
use App\Livewire\Crm\TaskManager;
use App\Models\Crm\Activity;
use App\Models\Crm\ActivityType;
use App\Models\Crm\Appointment;
use App\Models\Crm\Lead;
use App\Models\Crm\Task;
use App\Models\Crm\TimelineEvent;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Crm\AppointmentReminderNotification;
use App\Notifications\Crm\TaskReminderNotification;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function phase4Agent(string $name = 'CRM Agent'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

function phase4Admin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

it('logs an activity and updates lead contact timestamps', function () {
    $agent = phase4Agent();
    $lead = Lead::factory()->assignedTo($agent)->create();
    $type = ActivityType::query()->first();

    Livewire::actingAs($agent)
        ->test(ActivityManager::class)
        ->set('lead_id', $lead->id)
        ->set('activity_type_id', $type->id)
        ->set('title', 'Product demo call')
        ->set('description', 'Discussed product benefits.')
        ->set('outcome', 'interested')
        ->call('save')
        ->assertHasNoErrors();

    expect(Activity::query()->whereLeadId($lead->id)->count())->toBe(1);
    expect($lead->fresh()->last_contacted_at)->not->toBeNull();
    expect(TimelineEvent::query()->whereLeadId($lead->id)->where('event_type', 'activity_logged')->exists())->toBeTrue();
});

it('edits an activity and filters by completed date', function () {
    $agent = phase4Agent();
    $lead = Lead::factory()->assignedTo($agent)->create();
    $type = ActivityType::query()->first();

    $activity = Activity::factory()->create([
        'user_id' => $agent->id,
        'contact_type' => 'lead',
        'contact_id' => $lead->id,
        'activity_type_id' => $type->id,
        'title' => 'Original title',
        'completed_at' => now()->subDays(2),
    ]);

    Activity::factory()->create([
        'user_id' => $agent->id,
        'contact_type' => 'lead',
        'contact_id' => $lead->id,
        'activity_type_id' => $type->id,
        'title' => 'Old activity',
        'completed_at' => now()->subDays(10),
    ]);

    Livewire::actingAs($agent)
        ->test(ActivityManager::class)
        ->call('openForm', $activity->id)
        ->assertSet('editingId', $activity->id)
        ->set('title', 'Updated title')
        ->call('save')
        ->assertHasNoErrors();

    expect($activity->fresh()->title)->toBe('Updated title');
    expect(TimelineEvent::query()->whereLeadId($lead->id)->where('event_type', 'activity_updated')->exists())->toBeTrue();

    Livewire::actingAs($agent)
        ->test(ActivityManager::class)
        ->set('dateFrom', now()->subDays(3)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->assertSee('Updated title')
        ->assertDontSee('Old activity');
});

it('creates completes and deletes tasks with scoping', function () {
    $agentA = phase4Agent('Task Agent A');
    $agentB = phase4Agent('Task Agent B');
    $lead = Lead::factory()->assignedTo($agentA)->create();

    Livewire::actingAs($agentA)
        ->test(TaskManager::class)
        ->set('title', 'Send brochure')
        ->set('lead_id', $lead->id)
        ->set('due_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $task = Task::query()->where('title', 'Send brochure')->first();
    expect($task)->not->toBeNull();

    Livewire::actingAs($agentA)
        ->test(TaskManager::class)
        ->call('completeTask', $task->id);

    expect($task->fresh()->status)->toBe(TaskStatus::Completed);

    Task::factory()->forUser($agentB)->create(['title' => 'Hidden task']);

    Livewire::actingAs($agentA)
        ->test(TaskManager::class)
        ->assertDontSee('Hidden task');
});

it('filters tasks by due date presets', function () {
    $agent = phase4Agent('Due Preset Agent');

    Task::factory()->forUser($agent)->create([
        'title' => 'Due today task',
        'due_at' => now()->setTime(15, 0),
        'status' => TaskStatus::Pending,
    ]);

    Task::factory()->forUser($agent)->create([
        'title' => 'Overdue task',
        'due_at' => now()->subDays(2),
        'status' => TaskStatus::Pending,
    ]);

    Task::factory()->forUser($agent)->create([
        'title' => 'Upcoming task',
        'due_at' => now()->addDays(3),
        'status' => TaskStatus::Pending,
    ]);

    Livewire::actingAs($agent)
        ->test(TaskManager::class)
        ->set('duePreset', 'today')
        ->assertSee('Due today task')
        ->assertDontSee('Overdue task')
        ->assertDontSee('Upcoming task')
        ->set('duePreset', 'overdue')
        ->assertSee('Overdue task')
        ->assertDontSee('Due today task')
        ->set('duePreset', 'upcoming')
        ->assertSee('Upcoming task')
        ->assertDontSee('Overdue task');
});

it('schedules appointments on the calendar', function () {
    $agent = phase4Agent();
    $lead = Lead::factory()->assignedTo($agent)->create();

    Livewire::actingAs($agent)
        ->test(AppointmentCalendar::class)
        ->set('title', 'Product demo')
        ->set('lead_id', $lead->id)
        ->set('starts_at', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Appointment::query()->where('title', 'Product demo')->exists())->toBeTrue();
    expect(TimelineEvent::query()->whereLeadId($lead->id)->where('event_type', 'appointment_scheduled')->exists())->toBeTrue();
});

it('logs activity from the lead profile engagement panel', function () {
    $agent = phase4Agent();
    $lead = Lead::factory()->assignedTo($agent)->create();
    $type = ActivityType::query()->first();

    Livewire::actingAs($agent)
        ->test(LeadEngagementPanel::class, ['lead' => $lead])
        ->set('activity_type_id', $type->id)
        ->set('activity_description', 'Quick follow-up call')
        ->call('logActivity')
        ->assertHasNoErrors();

    expect(Activity::query()->whereLeadId($lead->id)->count())->toBe(1);
});

it('sends task and appointment reminders via artisan command', function () {
    Notification::fake();

    $agent = phase4Agent();
    $lead = Lead::factory()->assignedTo($agent)->create();

    Task::factory()
        ->forUser($agent)
        ->forLead($lead)
        ->withReminder()
        ->create(['title' => 'Reminder task']);

    Appointment::factory()
        ->forUser($agent)
        ->forLead($lead)
        ->startingSoon()
        ->create(['title' => 'Soon appointment']);

    Artisan::call('crm:send-reminders');

    Notification::assertSentTo($agent, TaskReminderNotification::class);
    Notification::assertSentTo($agent, AppointmentReminderNotification::class);
});

it('renders the activities tasks and appointments modules', function () {
    $admin = phase4Admin();

    $this->actingAs($admin)
        ->get(route('admin.crm.activities.index'))
        ->assertOk()
        ->assertSee('Activities');

    $this->actingAs($admin)
        ->get(route('admin.crm.tasks.index'))
        ->assertOk()
        ->assertSee('Follow-Ups');

    $this->actingAs($admin)
        ->get(route('admin.crm.appointments.index'))
        ->assertOk()
        ->assertSee('Appointments');
});
