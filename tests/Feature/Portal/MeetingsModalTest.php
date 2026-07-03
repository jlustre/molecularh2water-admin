<?php

use App\Livewire\Portal\MeetingsModal;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Lead;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\MeetingService;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

function meetingConsultant(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('shows meetings quick action on the dashboard', function () {
    $consultant = meetingConsultant();

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Meetings')
        ->assertSeeLivewire(MeetingsModal::class);
});

it('schedules an in-person meeting on the calendar', function () {
    $consultant = meetingConsultant();
    $lead = Lead::factory()->assignedTo($consultant)->create([
        'first_name' => 'Pat',
        'last_name' => 'Prospect',
        'lifecycle' => 'prospect',
    ]);

    Livewire::actingAs($consultant)
        ->test(MeetingsModal::class)
        ->call('open')
        ->call('selectContact', 'prospect', $lead->id)
        ->set('meeting_format', 'in_person')
        ->set('meeting_when', 'tomorrow_10')
        ->set('duration_minutes', 60)
        ->set('location', 'Coffee shop downtown')
        ->set('notes', 'Discuss product options')
        ->call('schedule')
        ->assertHasNoErrors();

    $event = CalendarEvent::query()
        ->where('title', 'Meeting with Pat Prospect')
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->related_id)->toBe($lead->id)
        ->and($event->location)->toBe('Coffee shop downtown')
        ->and($event->type?->slug)->toBe('in-person-meeting')
        ->and($event->metadata['recurrence_rule'])->toBe('none');

    expect(app(MeetingService::class)->upcomingMeetings($consultant))->toHaveCount(1);
});

it('creates recurring online meetings synced to the calendar', function () {
    $consultant = meetingConsultant();
    $lead = Lead::factory()->assignedTo($consultant)->create([
        'first_name' => 'Alex',
        'last_name' => 'Client',
        'lifecycle' => 'client',
    ]);

    Livewire::actingAs($consultant)
        ->test(MeetingsModal::class)
        ->call('open')
        ->call('selectContact', 'customer', $lead->id)
        ->set('meeting_format', 'online')
        ->set('meeting_when', 'tomorrow_10')
        ->set('meeting_link', 'https://zoom.us/j/weekly-checkin')
        ->set('recurrence', 'weekly')
        ->set('recurrence_count', 4)
        ->call('schedule')
        ->assertHasNoErrors();

    $events = CalendarEvent::query()
        ->where('title', 'Meeting with Alex Client')
        ->orderBy('start_at')
        ->get();

    expect($events)->toHaveCount(4)
        ->and($events->first()->type?->slug)->toBe('zoom-meeting')
        ->and($events->first()->meeting_link)->toBe('https://zoom.us/j/weekly-checkin')
        ->and($events->pluck('metadata.recurrence_group_id')->unique())->toHaveCount(1)
        ->and($events->last()->metadata['recurrence_index'])->toBe(4);

    $this->actingAs($consultant)
        ->get(route('portal.crm.calendar.index'))
        ->assertOk()
        ->assertSee('Meeting with Alex Client');
});

it('requires a location for in-person meetings', function () {
    $consultant = meetingConsultant();
    $lead = Lead::factory()->assignedTo($consultant)->create();

    Livewire::actingAs($consultant)
        ->test(MeetingsModal::class)
        ->call('open')
        ->call('selectContact', 'prospect', $lead->id)
        ->set('meeting_format', 'in_person')
        ->call('schedule')
        ->assertHasErrors(['location']);
});

it('adds invitee groups as calendar attendees', function () {
    $consultant = meetingConsultant();
    $manager = User::factory()->create(['name' => 'Team Manager']);
    $manager->roles()->attach(Role::query()->where('slug', 'manager')->first());

    $lead = Lead::factory()->assignedTo($consultant)->create([
        'first_name' => 'Pat',
        'last_name' => 'Prospect',
        'lifecycle' => 'prospect',
    ]);

    Livewire::actingAs($consultant)
        ->test(MeetingsModal::class)
        ->call('open')
        ->call('selectContact', 'prospect', $lead->id)
        ->set('meeting_format', 'online')
        ->set('meeting_when', 'tomorrow_10')
        ->set('meeting_link', 'https://zoom.us/j/team-sync')
        ->set('invitee_group', 'managers')
        ->call('schedule')
        ->assertHasNoErrors();

    $event = CalendarEvent::query()
        ->where('title', 'Meeting with Pat Prospect')
        ->with('attendees')
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->metadata['invitee_group'])->toBe('managers')
        ->and($event->attendees->pluck('user_id')->all())->toContain($manager->id);
});
