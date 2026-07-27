<?php

use App\Livewire\Crm\Calendar\CalendarDashboard;
use App\Livewire\Crm\Calendar\CalendarDetailPanel;
use App\Livewire\Crm\Calendar\CalendarEventModal;
use App\Livewire\Crm\Calendar\CalendarGrid;
use App\Models\Crm\Activity;
use App\Models\Crm\Appointment;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Lead;
use App\Models\Crm\Task;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\CalendarEventService;
use App\Support\Navigation\AppNavigation;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

function calendarAgent(string $name = 'Calendar Agent'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

function calendarAdmin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

it('opens a day popup listing all items when a month cell is clicked', function () {
    $agent = calendarAgent();
    $lead = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Day',
        'last_name' => 'Popup',
    ]);
    $type = CalendarEventType::query()->first();
    $day = now()->startOfDay()->addDays(3);

    CalendarEvent::factory()
        ->forUser($agent)
        ->forLead($lead)
        ->create([
            'calendar_event_type_id' => $type->id,
            'title' => 'Morning follow-up',
            'start_at' => $day->copy()->setTime(9, 0),
            'end_at' => $day->copy()->setTime(9, 30),
        ]);

    CalendarEvent::factory()
        ->forUser($agent)
        ->forLead($lead)
        ->create([
            'calendar_event_type_id' => $type->id,
            'title' => 'Afternoon demo',
            'start_at' => $day->copy()->setTime(14, 0),
            'end_at' => $day->copy()->setTime(15, 0),
        ]);

    Livewire::actingAs($agent)
        ->test(CalendarGrid::class, [
            'focusDate' => $day->toDateString(),
            'view' => 'month',
            'filters' => [],
            'canManage' => true,
        ])
        ->call('openDay', $day->toDateString())
        ->assertSet('selectedDay', $day->toDateString())
        ->assertSee('Day schedule')
        ->assertSee($day->format('l, F j, Y'))
        ->assertSee('Morning follow-up')
        ->assertSee('Afternoon demo')
        ->call('closeDay')
        ->assertSet('selectedDay', null)
        ->assertDontSee('Day schedule');
});

it('renders a year view and can open a day from it', function () {
    $agent = calendarAgent();
    $lead = Lead::factory()->assignedTo($agent)->create();
    $type = CalendarEventType::query()->first();
    $day = now()->startOfMonth()->addDays(10)->setTime(11, 0);

    CalendarEvent::factory()
        ->forUser($agent)
        ->forLead($lead)
        ->create([
            'calendar_event_type_id' => $type->id,
            'title' => 'Year view event',
            'start_at' => $day,
            'end_at' => $day->copy()->addHour(),
        ]);

    Livewire::actingAs($agent)
        ->test(CalendarDashboard::class)
        ->call('setView', 'year')
        ->assertSet('view', 'year')
        ->assertSee((string) now()->year)
        ->assertSee('Year');

    Livewire::actingAs($agent)
        ->test(CalendarGrid::class, [
            'focusDate' => $day->toDateString(),
            'view' => 'year',
            'filters' => [],
            'canManage' => true,
        ])
        ->assertSee($day->format('F'))
        ->call('openDay', $day->toDateString())
        ->assertSee('Year view event')
        ->call('openMonth', $day->copy()->startOfMonth()->toDateString())
        ->assertDispatched('calendar-focus-month');
});

it('navigates by year in year view', function () {
    $agent = calendarAgent();
    $start = now()->toDateString();

    Livewire::actingAs($agent)
        ->test(CalendarDashboard::class)
        ->set('focusDate', $start)
        ->call('setView', 'year')
        ->assertSet('view', 'year')
        ->call('next')
        ->assertSet('focusDate', \Carbon\Carbon::parse($start)->addYear()->toDateString())
        ->call('previous')
        ->assertSet('focusDate', $start);
});

it('includes a single calendar loading overlay on the dashboard', function () {
    $agent = calendarAgent();

    $html = $this->actingAs($agent)
        ->get(route('portal.crm.calendar.index'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('data-crm-calendar-scope')
        ->toContain('data-crm-calendar-loading-overlay')
        ->toContain('crmCalendarLoadingOverlay')
        ->toContain('Loading calendar...')
        ->toContain('animate-spin text-teal-600');

    // The overlay should NOT be duplicated per sub-component; only one overlay DOM node.
    // Count the DOM attribute occurrences (attribute followed by `>` or whitespace), ignoring
    // string references inside the pushed <script> block.
    expect(preg_match_all('/data-crm-calendar-loading-overlay(?=[\s>])/i', $html))->toBe(1)
        ->and(preg_match_all('/data-crm-calendar-scope(?=[\s>])/i', $html))->toBe(1);
});

it('creates a calendar event linked to a lead', function () {
    $agent = calendarAgent();
    $lead = Lead::factory()->assignedTo($agent)->create();
    $type = CalendarEventType::query()->first();

    Livewire::actingAs($agent)
        ->test(CalendarEventModal::class)
        ->set('calendar_event_type_id', $type->id)
        ->set('related_key', 'lead:'.$lead->id)
        ->set('title', 'Follow-up call')
        ->set('start_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('end_at', now()->addDay()->addHour()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $event = CalendarEvent::query()->where('title', 'Follow-up call')->first();

    expect($event)->not->toBeNull()
        ->and($event->related_id)->toBe($lead->id)
        ->and($event->reminders()->count())->toBeGreaterThan(0);
});

it('completes an event and logs a crm activity', function () {
    $agent = calendarAgent();
    $lead = Lead::factory()->assignedTo($agent)->create();
    $type = CalendarEventType::query()->where('slug', 'phone-call')->first();

    $event = app(CalendarEventService::class)->create([
        'calendar_event_type_id' => $type->id,
        'lead_id' => $lead->id,
        'title' => 'Completed call',
        'start_at' => now()->subHour(),
        'end_at' => now(),
        'reminder_minutes' => [15],
    ], $agent);

    app(CalendarEventService::class)->complete($event, $agent, 'Great conversation.');

    expect($event->fresh()->status?->value)->toBe('completed');
    expect(Activity::query()->whereLeadId($lead->id)->count())->toBe(1);
});

it('scopes calendar events to the assigned agent', function () {
    $agentA = calendarAgent('Agent A');
    $agentB = calendarAgent('Agent B');
    $type = CalendarEventType::query()->first();
    $day = now()->startOfMonth()->addDays(10)->setHour(10)->setMinute(0);

    CalendarEvent::factory()->forUser($agentA)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Visible event',
        'start_at' => $day,
        'end_at' => $day->copy()->addHour(),
    ]);

    CalendarEvent::factory()->forUser($agentB)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Hidden event',
        'start_at' => $day,
        'end_at' => $day->copy()->addHour(),
    ]);

    Livewire::actingAs($agentA)
        ->test(CalendarGrid::class, [
            'focusDate' => $day->toDateString(),
            'view' => 'month',
            'filters' => [],
        ])
        ->assertSee('Visible event')
        ->assertDontSee('Hidden event');
});

it('allows admins to view all calendar events', function () {
    $admin = calendarAdmin();
    $agent = calendarAgent();
    $type = CalendarEventType::query()->first();

    $day = now()->startOfMonth()->addDays(10)->setHour(10)->setMinute(0);

    CalendarEvent::factory()->forUser($agent)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Team event',
        'start_at' => $day,
        'end_at' => $day->copy()->addHour(),
    ]);

    Livewire::actingAs($admin)
        ->test(CalendarGrid::class, [
            'focusDate' => $day->toDateString(),
            'view' => 'month',
            'filters' => [],
        ])
        ->assertSee('Team event');
});

it('seeds cooking show and water awareness show event types', function () {
    $cooking = CalendarEventType::query()->where('slug', 'cooking-show')->first();
    $water = CalendarEventType::query()->where('slug', 'water-awareness-show')->first();

    expect($cooking)->not->toBeNull()
        ->and($cooking->category?->value)->toBe('show')
        ->and($water)->not->toBeNull()
        ->and($water->category?->value)->toBe('show')
        ->and(CalendarEventType::query()->active()->count())->toBe(12)
        ->and(CalendarEventType::query()->active()->shows()->count())->toBe(2);
});

it('opens quick-book modal for a cooking show', function () {
    $agent = calendarAgent();

    Livewire::actingAs($agent)
        ->test(CalendarEventModal::class)
        ->call('openCreateShow', 'cooking-show')
        ->assertSet('show', true)
        ->assertSet('title', 'Cooking Show')
        ->assertSet('calendar_event_type_id', CalendarEventType::query()->where('slug', 'cooking-show')->value('id'));
});

it('filters calendar to shows only', function () {
    $agent = calendarAgent();
    $cookingType = CalendarEventType::query()->where('slug', 'cooking-show')->first();
    $callType = CalendarEventType::query()->where('slug', 'phone-call')->first();

    CalendarEvent::factory()->forUser($agent)->create([
        'calendar_event_type_id' => $cookingType->id,
        'title' => 'Saturday cooking show',
        'start_at' => now()->addDays(2)->setHour(10),
        'end_at' => now()->addDays(2)->setHour(12),
    ]);

    CalendarEvent::factory()->forUser($agent)->create([
        'calendar_event_type_id' => $callType->id,
        'title' => 'Prospect phone call',
        'start_at' => now()->addDays(2)->setHour(14),
        'end_at' => now()->addDays(2)->setHour(15),
    ]);

    Livewire::actingAs($agent)
        ->test(CalendarGrid::class, [
            'focusDate' => now()->addDays(2)->toDateString(),
            'view' => 'month',
            'filters' => [
                'show_category' => 'show',
                'show_tasks' => false,
                'show_appointments' => false,
            ],
        ])
        ->assertSee('Saturday cooking show')
        ->assertDontSee('Prospect phone call');
});

it('shows todays phone calls in the call list panel', function () {
    $agent = calendarAgent();
    $lead = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Pat',
        'last_name' => 'Prospect',
        'phone' => '555-0100',
    ]);
    $callType = CalendarEventType::query()->where('slug', 'phone-call')->first();

    CalendarEvent::factory()
        ->forUser($agent)
        ->forLead($lead)
        ->create([
            'calendar_event_type_id' => $callType->id,
            'title' => 'Phone call with Pat Prospect',
            'start_at' => now()->addHours(2),
            'end_at' => now()->addHours(2)->addMinutes(30),
            'metadata' => [
                'phone_call_reason' => 'general_follow_up',
                'phone_number' => '555-0100',
            ],
        ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\Calendar\CalendarWidgets::class, [
            'filters' => [],
            'canManage' => true,
        ])
        ->assertSee('Call Lists Today')
        ->assertSee('Pat Prospect')
        ->assertSee('555-0100')
        ->assertSee('General follow-up')
        ->assertSeeHtml('aria-label="1 item"');
});

it('moves overdue phone calls to the overdue follow-ups panel', function () {
    $agent = calendarAgent();
    $overdueLead = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Over',
        'last_name' => 'Due',
        'phone' => '555-0199',
    ]);
    $todayLead = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Later',
        'last_name' => 'Today',
        'phone' => '555-0188',
    ]);
    $callType = CalendarEventType::query()->where('slug', 'phone-call')->first();

    $overdueEvent = CalendarEvent::factory()
        ->forUser($agent)
        ->forLead($overdueLead)
        ->create([
            'calendar_event_type_id' => $callType->id,
            'title' => 'Phone call with Over Due',
            'start_at' => now()->subHours(2),
            'end_at' => now()->subHours(2)->addMinutes(30),
            'metadata' => [
                'phone_call_reason' => 'general_follow_up',
                'phone_number' => '555-0199',
            ],
        ]);

    $futureCall = CalendarEvent::factory()
        ->forUser($agent)
        ->forLead($todayLead)
        ->create([
            'calendar_event_type_id' => $callType->id,
            'title' => 'Phone call with Later Today',
            'start_at' => now()->addHours(3),
            'end_at' => now()->addHours(3)->addMinutes(30),
            'metadata' => [
                'phone_call_reason' => 'general_follow_up',
                'phone_number' => '555-0188',
            ],
        ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\Calendar\CalendarWidgets::class, [
            'filters' => [],
            'canManage' => true,
        ])
        ->assertSee('Later Today')
        ->assertSee('Over Due');

    $calendar = app(\App\Services\Crm\CalendarQueryService::class);

    expect($calendar->phoneCallsToday($agent)->pluck('id'))->toContain($futureCall->id)
        ->and($calendar->phoneCallsToday($agent)->pluck('id'))->not->toContain($overdueEvent->id)
        ->and($calendar->overduePhoneCalls($agent)->pluck('id'))->toContain($overdueEvent->id);
});

it('opens call results from calendar call list checkbox and completes the call', function () {
    $agent = calendarAgent();
    $lead = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Cal',
        'last_name' => 'Caller',
        'phone' => '555-0200',
    ]);
    $callType = CalendarEventType::query()->where('slug', 'phone-call')->first();

    $event = CalendarEvent::factory()
        ->forUser($agent)
        ->forLead($lead)
        ->create([
            'calendar_event_type_id' => $callType->id,
            'title' => 'Phone call with Cal Caller',
            'start_at' => now()->addHour(),
            'end_at' => now()->addHour()->addMinutes(30),
            'metadata' => [
                'phone_call_reason' => 'general_follow_up',
                'phone_number' => '555-0200',
            ],
        ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\Calendar\CalendarWidgets::class, [
            'filters' => [],
            'canManage' => true,
        ])
        ->call('beginCompleteCall', $event->id)
        ->assertSet('showResults', true)
        ->assertSet('resultsContactLabel', 'Cal Caller')
        ->set('call_result', 'connected')
        ->set('result_comments', 'Discussed product demo')
        ->call('saveCallResults')
        ->assertHasNoErrors()
        ->assertSet('showResults', false)
        ->assertDispatched('calendar-updated');

    $event->refresh();

    expect($event->status?->value)->toBe('completed')
        ->and($event->metadata['phone_call_result'])->toBe('connected');
});

it('excludes tasks from dashboard upcoming events but keeps them on tasks and calendar feeds', function () {
    $agent = calendarAgent();
    $lead = Lead::factory()->assignedTo($agent)->create();
    $calendar = app(\App\Services\Crm\CalendarQueryService::class);

    $task = app(\App\Services\Crm\TaskService::class)->create([
        'lead_id' => $lead->id,
        'title' => 'Follow up on pricing',
        'due_at' => now()->addDay()->setTime(10, 0),
    ], $agent);

    app(CalendarEventService::class)->createFromTask($task, $agent);

    $events = $calendar->upcomingScheduledEvents(10, $agent);
    $tasks = $calendar->upcomingActionTasks(10, $agent);
    $calendarEntries = $calendar->entries(now()->startOfDay(), now()->addDays(14), [], $agent);

    expect($events->pluck('title'))->not->toContain('Follow up on pricing')
        ->and($tasks->pluck('title'))->toContain('Follow up on pricing')
        ->and($calendarEntries->pluck('title'))->toContain('Follow up on pricing');
});

it('shows view buttons on calendar widget panel items', function () {
    $agent = calendarAgent();
    $lead = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Widget',
        'last_name' => 'Viewer',
        'next_follow_up_at' => now()->subDays(2),
    ]);
    $callType = CalendarEventType::query()->where('slug', 'phone-call')->first();

    CalendarEvent::factory()
        ->forUser($agent)
        ->forLead($lead)
        ->create([
            'calendar_event_type_id' => $callType->id,
            'title' => 'Phone call with Widget Viewer',
            'start_at' => now()->addHour(),
            'end_at' => now()->addHour()->addMinutes(30),
            'metadata' => ['phone_call_reason' => 'general_follow_up', 'phone_number' => '555-0300'],
        ]);

    Task::factory()->forUser($agent)->create([
        'title' => 'Widget task item',
        'due_at' => now()->setTime(15, 0),
        'status' => 'pending',
    ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\Calendar\CalendarWidgets::class, [
            'filters' => [],
            'canManage' => true,
        ])
        ->assertSee('View')
        ->assertSee('Widget Viewer')
        ->assertSee('Widget task item');
});

it('opens phone call details modal from calendar widgets and reschedules the call', function () {
    $agent = calendarAgent();
    $lead = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Modal',
        'last_name' => 'Caller',
        'phone' => '555-0400',
    ]);
    $callType = CalendarEventType::query()->where('slug', 'phone-call')->first();

    $event = CalendarEvent::factory()
        ->forUser($agent)
        ->forLead($lead)
        ->create([
            'calendar_event_type_id' => $callType->id,
            'title' => 'Phone call with Modal Caller',
            'start_at' => now()->addHour(),
            'end_at' => now()->addHour()->addMinutes(30),
            'metadata' => [
                'phone_call_reason' => 'general_follow_up',
                'phone_number' => '555-0400',
                'contact_kind' => 'prospect',
            ],
        ]);

    Livewire::actingAs($agent)
        ->test(CalendarDetailPanel::class)
        ->call('openDetails', 'event', $event->id)
        ->assertSet('show', true)
        ->assertSee('Modal Caller')
        ->set('phone_call_when', 'tomorrow_10')
        ->set('phone_number', '555-0401')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('show', false)
        ->assertDispatched('calendar-updated');

    $event->refresh();

    expect($event->metadata['phone_number'])->toBe('555-0401')
        ->and($event->start_at->isTomorrow())->toBeTrue();
});

it('opens task details modal from calendar widgets and updates the due date', function () {
    $agent = calendarAgent();

    $task = Task::factory()->forUser($agent)->create([
        'title' => 'Reschedule me',
        'due_at' => now()->setTime(9, 0),
        'status' => 'pending',
    ]);

    $newDue = now()->addDays(2)->setTime(14, 30);

    Livewire::actingAs($agent)
        ->test(CalendarDetailPanel::class)
        ->call('openDetails', 'task', $task->id)
        ->assertSet('show', true)
        ->assertSee('Reschedule me')
        ->set('task_due_at', $newDue->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('calendar-updated');

    expect($task->fresh()->due_at?->format('Y-m-d H:i'))->toBe($newDue->format('Y-m-d H:i'));
});

it('opens overdue lead follow-up modal and reschedules next follow-up', function () {
    $agent = calendarAgent();
    $lead = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Overdue',
        'last_name' => 'Lead',
        'next_follow_up_at' => now()->subDays(3),
    ]);

    $newFollowUp = now()->addDays(4)->setTime(11, 0);

    Livewire::actingAs($agent)
        ->test(CalendarDetailPanel::class)
        ->call('openDetails', 'lead', $lead->id)
        ->assertSet('show', true)
        ->assertSee('Overdue Lead')
        ->set('lead_follow_up_at', $newFollowUp->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('calendar-updated');

    expect($lead->fresh()->next_follow_up_at?->format('Y-m-d H:i'))->toBe($newFollowUp->format('Y-m-d H:i'));
});

it('dispatches open-calendar-details when widget view is clicked', function () {
    $agent = calendarAgent();

    $task = Task::factory()->forUser($agent)->create([
        'title' => 'Dispatch task',
        'due_at' => now(),
        'status' => 'pending',
    ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\Calendar\CalendarWidgets::class, [
            'filters' => [],
            'canManage' => true,
        ])
        ->call('openDetails', 'task', $task->id)
        ->assertDispatched('open-calendar-details', kind: 'task', id: $task->id);
});

it('places My Calendar under workspace navigation', function () {
    $agent = calendarAgent();
    $links = collect(AppNavigation::links($agent));

    expect($links->firstWhere('key', 'crm-my-calendar'))->not->toBeNull()
        ->and($links->firstWhere('key', 'crm-my-calendar')['section'])->toBe('workspace')
        ->and($links->firstWhere('key', 'crm-my-calendar')['label'])->toBe('My Calendar')
        ->and($links->firstWhere('key', 'crm-my-calendar')['route'])->toBe('portal.crm.my-calendar.index')
        ->and($links->firstWhere('key', 'crm-calendar'))->toBeNull();
});

it('renders My Calendar as a personal schedule locked to the signed-in user', function () {
    $agent = calendarAgent('My Calendar Owner');
    $other = calendarAgent('Other Consultant');
    $type = CalendarEventType::query()->first();
    $day = now()->startOfHour();

    CalendarEvent::factory()->forUser($agent)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Mine meeting',
        'start_at' => $day,
        'end_at' => $day->copy()->addHour(),
    ]);

    CalendarEvent::factory()->forUser($other)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Other meeting',
        'start_at' => $day,
        'end_at' => $day->copy()->addHour(),
    ]);

    Appointment::factory()->forUser($agent)->create([
        'title' => 'Mine appointment',
        'starts_at' => $day->copy()->addHours(2),
        'ends_at' => $day->copy()->addHours(3),
    ]);

    Task::factory()->forUser($agent)->create([
        'title' => 'Mine task',
        'due_at' => $day->copy()->addHours(4),
        'status' => 'pending',
    ]);

    $this->actingAs($agent)
        ->get(route('portal.crm.my-calendar.index'))
        ->assertOk()
        ->assertSee('My Calendar')
        ->assertSee('Your calendars, appointments, demos, tasks, and meetings')
        ->assertSee('Showing only your schedule.');

    Livewire::actingAs($agent)
        ->test(CalendarDashboard::class, ['personal' => true])
        ->assertSet('personalOnly', true)
        ->assertSet('filter_user_id', $agent->id)
        ->assertSet('show_tasks', true)
        ->assertSet('show_appointments', true)
        ->assertSet('show_demos', true)
        ->assertSet('show_meetings', true)
        ->set('filter_user_id', $other->id)
        ->assertSet('filter_user_id', $agent->id);

    Livewire::actingAs($agent)
        ->test(CalendarGrid::class, [
            'focusDate' => $day->toDateString(),
            'view' => 'month',
            'filters' => [
                'user_id' => $agent->id,
                'show_tasks' => true,
                'show_appointments' => true,
                'show_demos' => true,
                'show_meetings' => true,
                'personal_only' => true,
            ],
        ])
        ->assertSee('Mine meeting')
        ->assertSee('Mine appointment')
        ->assertSee('Mine task')
        ->assertDontSee('Other meeting');
});

it('keeps Team Calendar distinct from My Calendar', function () {
    $agent = calendarAgent();

    $this->actingAs($agent)
        ->get(route('portal.crm.calendar.index'))
        ->assertOk()
        ->assertSee('Team Calendar')
        ->assertDontSee('Showing only your schedule.');
});
