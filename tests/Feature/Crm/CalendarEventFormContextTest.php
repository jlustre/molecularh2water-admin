<?php

use App\Livewire\Crm\Calendar\CalendarEventModal;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\UserCalendar;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\UserCalendarService;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

it('shows a simplified form for personal calendar and internal event types', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());
    app(UserCalendarService::class)->ensureDefaults($user);

    $personal = UserCalendar::query()->where('user_id', $user->id)->where('kind', 'personal')->firstOrFail();
    $personalTaskId = CalendarEventType::query()->where('slug', 'personal-task')->value('id');

    Livewire::actingAs($user)
        ->test(CalendarEventModal::class)
        ->call('openCreate', now()->toDateString())
        ->assertSet('user_calendar_id', $personal->id)
        ->assertSet('calendar_event_type_id', $personalTaskId)
        ->assertSee('Internal event')
        ->assertDontSee('Related record')
        ->assertDontSee('Meeting link')
        ->assertDontSee('Assigned to')
        ->assertSee('Personal Task')
        ->assertDontSee('Cooking Show');
});

it('shows crm fields for work calendar sales event types', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());
    $calendars = app(UserCalendarService::class);
    $calendars->ensureDefaults($user);

    $work = UserCalendar::query()->where('user_id', $user->id)->where('kind', 'work')->firstOrFail();

    Livewire::actingAs($user)
        ->test(CalendarEventModal::class)
        ->call('openCreate', now()->toDateString())
        ->assertDontSee('Related record')
        ->set('user_calendar_id', $work->id)
        ->assertSee('Related record')
        ->assertSee('Meeting link')
        ->assertSee('Cooking Show');
});

it('hides crm fields when switching a work calendar event to an internal type', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());
    app(UserCalendarService::class)->ensureDefaults($user);

    $work = UserCalendar::query()->where('user_id', $user->id)->where('kind', 'work')->firstOrFail();
    $personalTaskId = CalendarEventType::query()->where('slug', 'personal-task')->value('id');

    Livewire::actingAs($user)
        ->test(CalendarEventModal::class)
        ->call('openCreate', now()->toDateString())
        ->set('user_calendar_id', $work->id)
        ->assertSee('Related record')
        ->set('calendar_event_type_id', $personalTaskId)
        ->assertSee('Internal event')
        ->assertDontSee('Related record')
        ->assertDontSee('Meeting link');
});
