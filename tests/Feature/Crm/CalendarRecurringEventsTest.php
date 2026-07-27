<?php

use App\Livewire\Crm\Calendar\CalendarEventModal;
use App\Livewire\Crm\Calendar\CalendarGrid;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\CalendarEventService;
use App\Support\Crm\CalendarRecurrence;
use Carbon\Carbon;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

function recurringAgent(string $name = 'Recurring Agent'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('creates a weekly recurring series from the event modal', function () {
    $agent = recurringAgent();
    $type = CalendarEventType::query()->where('slug', 'personal-task')->first();
    $start = now()->startOfMonth()->addDays(3)->setHour(10)->setMinute(0);

    Livewire::actingAs($agent)
        ->test(CalendarEventModal::class)
        ->call('openCreate', $start->toDateString())
        ->set('calendar_event_type_id', $type->id)
        ->set('title', 'Weekly standup')
        ->set('start_at', $start->format('Y-m-d\TH:i'))
        ->set('end_at', $start->copy()->addHour()->format('Y-m-d\TH:i'))
        ->set('recurrence', 'weekly')
        ->set('recurrence_count', 4)
        ->call('save')
        ->assertHasNoErrors();

    $events = CalendarEvent::query()
        ->where('title', 'Weekly standup')
        ->orderBy('start_at')
        ->get();

    expect($events)->toHaveCount(4)
        ->and($events->pluck('metadata.recurrence_group_id')->unique())->toHaveCount(1)
        ->and($events->first()->metadata['recurrence_rule'])->toBe('weekly')
        ->and($events->last()->metadata['recurrence_index'])->toBe(4)
        ->and($events[1]->start_at->equalTo($events[0]->start_at->copy()->addWeek()))->toBeTrue();
});

it('builds daily all-day occurrences with preserved multi-day span', function () {
    $start = Carbon::parse('2026-08-03')->startOfDay();
    $end = Carbon::parse('2026-08-04')->endOfDay();

    $occurrences = CalendarRecurrence::buildOccurrences($start, $end, 'daily', 3, true);

    expect($occurrences)->toHaveCount(3)
        ->and($occurrences[0][0]->toDateString())->toBe('2026-08-03')
        ->and($occurrences[0][1]->toDateString())->toBe('2026-08-04')
        ->and($occurrences[2][0]->toDateString())->toBe('2026-08-05')
        ->and($occurrences[2][1]->toDateString())->toBe('2026-08-06');
});

it('shows recurring events across weeks on the month grid', function () {
    $agent = recurringAgent();
    $type = CalendarEventType::query()->where('slug', 'personal-task')->first();
    $start = now()->startOfMonth()->addDays(2)->setHour(9)->setMinute(0);

    app(CalendarEventService::class)->createSeries([
        'calendar_event_type_id' => $type->id,
        'title' => 'Recurring coaching',
        'start_at' => $start,
        'end_at' => $start->copy()->addHour(),
        'recurrence' => 'weekly',
        'recurrence_count' => 4,
        'business_line' => 'h2s',
    ], $agent);

    Livewire::actingAs($agent)
        ->test(CalendarGrid::class, [
            'focusDate' => $start->toDateString(),
            'view' => 'month',
            'filters' => ['user_id' => $agent->id],
        ])
        ->assertSee('Recurring coaching');

    expect(CalendarEvent::query()->where('title', 'Recurring coaching')->count())->toBe(4);
});
