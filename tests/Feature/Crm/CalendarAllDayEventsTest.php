<?php

use App\Livewire\Crm\Calendar\CalendarEventModal;
use App\Livewire\Crm\Calendar\CalendarGrid;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\CalendarEventService;
use App\Services\Crm\CalendarQueryService;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

function allDayAgent(string $name = 'All Day Agent'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('saves an all-day multi-day event without requiring times', function () {
    $agent = allDayAgent();
    $type = CalendarEventType::query()->where('slug', 'personal-task')->first();
    $start = now()->startOfMonth()->addDays(2)->toDateString();
    $end = now()->startOfMonth()->addDays(4)->toDateString();

    Livewire::actingAs($agent)
        ->test(CalendarEventModal::class)
        ->call('openCreate', $start)
        ->set('is_all_day', true)
        ->set('calendar_event_type_id', $type->id)
        ->set('title', 'Family vacation')
        ->set('start_at', $start)
        ->set('end_at', $end)
        ->call('save')
        ->assertHasNoErrors();

    $event = CalendarEvent::query()->where('title', 'Family vacation')->first();

    expect($event)->not->toBeNull()
        ->and($event->is_all_day)->toBeTrue()
        ->and($event->start_at->format('Y-m-d H:i'))->toBe($start.' 00:00')
        ->and($event->end_at->format('Y-m-d'))->toBe($end)
        ->and($event->end_at->format('H:i'))->toBe('23:59');
});

it('includes spanning events in range overlap queries', function () {
    $agent = allDayAgent();
    $type = CalendarEventType::query()->where('slug', 'personal-task')->first();

    $event = app(CalendarEventService::class)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Conference week',
        'is_all_day' => true,
        'start_at' => now()->startOfMonth()->subDays(2)->toDateString(),
        'end_at' => now()->startOfMonth()->addDays(10)->toDateString(),
        'business_line' => 'h2s',
    ], $agent);

    $entries = app(CalendarQueryService::class)->entries(
        now()->startOfMonth()->startOfWeek(),
        now()->startOfMonth()->endOfWeek(),
        ['user_id' => $agent->id],
        $agent,
    );

    expect($entries->contains(fn ($entry) => $entry->id === $event->id && $entry->kind === 'event'))->toBeTrue();
});

it('renders a continuous bar for multi-day events on the month grid', function () {
    $agent = allDayAgent();
    $type = CalendarEventType::query()->where('slug', 'personal-task')->first();
    $start = now()->startOfMonth()->addDays(8);

    CalendarEvent::factory()->forUser($agent)->allDay($start, 3)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Trade show stretch',
        'business_line' => 'h2s',
    ]);

    Livewire::actingAs($agent)
        ->test(CalendarGrid::class, [
            'focusDate' => now()->toDateString(),
            'view' => 'month',
            'filters' => ['user_id' => $agent->id],
        ])
        ->assertSee('Trade show stretch')
        ->assertSeeHtml('grid-column:');
});
