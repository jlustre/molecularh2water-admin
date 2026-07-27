<?php

use App\Livewire\Crm\Calendar\CalendarDashboard;
use App\Livewire\Crm\Calendar\CalendarGrid;
use App\Livewire\Crm\Calendar\UserCalendarsPanel;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\UserCalendar;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\CalendarQueryService;
use App\Services\Crm\UserCalendarService;
use Carbon\Carbon;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

function userCalendarOwner(string $name = 'Calendar Owner'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('creates default personal and work calendars on my calendar', function () {
    $owner = userCalendarOwner();

    Livewire::actingAs($owner)
        ->test(UserCalendarsPanel::class)
        ->assertSee('Personal')
        ->assertSee('Work')
        ->assertSee('US Holidays')
        ->assertSee('Canadian Holidays');

    expect(UserCalendar::query()->where('user_id', $owner->id)->count())->toBe(2)
        ->and(UserCalendar::query()->where('user_id', $owner->id)->where('kind', 'personal')->exists())->toBeTrue()
        ->and(UserCalendar::query()->where('user_id', $owner->id)->where('kind', 'work')->exists())->toBeTrue();
});

it('shows themed calendar events on the main grid and hides them when toggled off', function () {
    $owner = userCalendarOwner();
    $calendars = app(UserCalendarService::class);
    $calendars->ensureDefaults($owner);

    $personal = UserCalendar::query()->where('user_id', $owner->id)->where('kind', 'personal')->firstOrFail();
    $work = UserCalendar::query()->where('user_id', $owner->id)->where('kind', 'work')->firstOrFail();
    $type = CalendarEventType::query()->firstOrFail();
    $day = now()->startOfDay()->addDays(2);

    CalendarEvent::factory()->forUser($owner)->create([
        'calendar_event_type_id' => $type->id,
        'user_calendar_id' => $personal->id,
        'title' => 'Personal birthday planning',
        'start_at' => $day->copy()->setTime(10, 0),
        'end_at' => $day->copy()->setTime(11, 0),
    ]);

    CalendarEvent::factory()->forUser($owner)->create([
        'calendar_event_type_id' => $type->id,
        'user_calendar_id' => $work->id,
        'title' => 'Work client review',
        'start_at' => $day->copy()->setTime(14, 0),
        'end_at' => $day->copy()->setTime(15, 0),
    ]);

    $filters = [
        'user_id' => $owner->id,
        'personal_only' => true,
        'show_tasks' => false,
        'show_appointments' => false,
        'show_demos' => false,
        'show_meetings' => true,
    ];

    Livewire::actingAs($owner)
        ->test(CalendarGrid::class, [
            'focusDate' => $day->toDateString(),
            'view' => 'month',
            'filters' => $filters,
            'canManage' => true,
        ])
        ->assertSee('Personal birthday planning')
        ->assertSee('Work client review');

    Livewire::actingAs($owner)
        ->test(UserCalendarsPanel::class)
        ->set('calendarVisibility.'.$work->id, false);

    expect(app(UserCalendarService::class)->visibleCalendarIds($owner))->not->toContain($work->id);

    $entries = app(CalendarQueryService::class)->entries(
        $day->copy()->startOfDay(),
        $day->copy()->endOfDay(),
        $filters,
        $owner,
    );

    expect($entries->pluck('title')->all())
        ->toContain('Personal birthday planning')
        ->not->toContain('Work client review');
});

it('adds us holidays to the grid with the holiday theme', function () {
    $owner = userCalendarOwner();
    $service = app(UserCalendarService::class);
    $service->ensureDefaults($owner);

    Livewire::actingAs($owner)
        ->test(UserCalendarsPanel::class)
        ->call('addHolidayCalendar', 'us_holidays')
        ->assertSee('US Holidays');

    $calendar = UserCalendar::query()
        ->where('user_id', $owner->id)
        ->where('kind', 'us_holidays')
        ->firstOrFail();

    expect($calendar->color)->toBe('rose')
        ->and(CalendarEvent::query()->where('user_calendar_id', $calendar->id)->count())->toBeGreaterThan(0);

    $july4 = Carbon::create(now()->year, 7, 4)->startOfDay();
    $entries = app(CalendarQueryService::class)->entries(
        $july4->copy()->startOfDay(),
        $july4->copy()->endOfDay(),
        ['user_id' => $owner->id, 'show_tasks' => false, 'show_appointments' => false, 'show_demos' => false],
        $owner,
    );

    expect($entries->pluck('title')->all())->toContain('Independence Day')
        ->and($entries->firstWhere('title', 'Independence Day')->color)->toBe('rose');
});

it('shares a calendar so the recipient sees its events', function () {
    $owner = userCalendarOwner('Owner User');
    $recipient = userCalendarOwner('Recipient User');
    $service = app(UserCalendarService::class);
    $service->ensureDefaults($owner);
    $service->ensureDefaults($recipient);

    $work = UserCalendar::query()->where('user_id', $owner->id)->where('kind', 'work')->firstOrFail();
    $type = CalendarEventType::query()->firstOrFail();
    $day = now()->startOfDay()->addDays(4);

    CalendarEvent::factory()->forUser($owner)->create([
        'calendar_event_type_id' => $type->id,
        'user_calendar_id' => $work->id,
        'title' => 'Shared team sync',
        'start_at' => $day->copy()->setTime(11, 0),
        'end_at' => $day->copy()->setTime(12, 0),
    ]);

    $service->share($work, $owner, $recipient);

    $entries = app(CalendarQueryService::class)->entries(
        $day->copy()->startOfDay(),
        $day->copy()->endOfDay(),
        ['user_id' => $recipient->id, 'show_tasks' => false, 'show_appointments' => false, 'show_demos' => false],
        $recipient,
    );

    expect($entries->pluck('title')->all())->toContain('Shared team sync');

    Livewire::actingAs($recipient)
        ->test(UserCalendarsPanel::class)
        ->assertSee('Work')
        ->assertSee('Shared')
        ->set('calendarVisibility.'.$work->id, false);

    $hiddenEntries = app(CalendarQueryService::class)->entries(
        $day->copy()->startOfDay(),
        $day->copy()->endOfDay(),
        ['user_id' => $recipient->id, 'show_tasks' => false, 'show_appointments' => false, 'show_demos' => false],
        $recipient,
    );

    expect($hiddenEntries->pluck('title')->all())->not->toContain('Shared team sync');
});

it('renders my calendars panel on the my calendar page', function () {
    $owner = userCalendarOwner();

    Livewire::actingAs($owner)
        ->test(CalendarDashboard::class, ['personal' => true])
        ->assertSee('My Calendar')
        ->assertSee('My Calendars')
        ->assertSee('Personal')
        ->assertSee('Work');
});
