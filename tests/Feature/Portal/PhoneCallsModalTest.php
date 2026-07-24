<?php

use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Portal\PhoneCallsModal;
use App\Models\Crm\Activity;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Customer;
use App\Models\Crm\Prospect;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\PhoneCallService;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

function phoneCallConsultant(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('shows phone calls quick action on the dashboard', function () {
    $consultant = phoneCallConsultant();

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Phone Calls')
        ->assertSeeLivewire(PhoneCallsModal::class);
});

it('lists upcoming phone calls sorted by time and schedules a new call for a prospect', function () {
    $consultant = phoneCallConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Pat',
        'last_name' => 'Prospect',
        'phone' => '555-0100',
    ]);
    $type = CalendarEventType::query()->where('slug', 'phone-call')->first();

    CalendarEvent::factory()->forUser($consultant)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Morning follow-up',
        'start_at' => now()->setTime(9, 0),
        'end_at' => now()->setTime(9, 30),
    ]);

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->assertSet('show', true)
        ->assertSee('Morning follow-up')
        ->assertSee('Search contact')
        ->call('selectContact', 'prospect', $lead->id)
        ->assertSet('phone_number', '555-0100')
        ->set('call_when', 'in_30')
        ->set('call_reason', 'schedule_product_demo')
        ->set('notes', 'Confirm hydrogen machine interest')
        ->call('schedule')
        ->assertHasNoErrors();

    $event = CalendarEvent::query()
        ->where('title', 'Phone call with Pat Prospect')
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->related_id)->toBe($lead->id)
        ->and($event->metadata['phone_number'])->toBe('555-0100')
        ->and($event->description)->toContain('Schedule product demo')
        ->and($event->description)->toContain('Confirm hydrogen machine interest');

    expect(app(PhoneCallService::class)->upcomingCalls($consultant))->toHaveCount(2);
});

it('opens results popup when checking off a call and saves to calendar', function () {
    $consultant = phoneCallConsultant();
    $type = CalendarEventType::query()->where('slug', 'phone-call')->first();

    $event = CalendarEvent::factory()->forUser($consultant)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Call to complete',
        'start_at' => now()->setTime(11, 0),
        'end_at' => now()->setTime(11, 30),
    ]);

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->call('beginCompleteCall', $event->id)
        ->assertSet('showResults', true)
        ->set('call_result', 'connected')
        ->set('result_comments', 'Left a message about the demo')
        ->call('saveCallResults')
        ->assertHasNoErrors()
        ->assertSet('showResults', false);

    $event->refresh();

    expect($event->status?->value)->toBe('completed')
        ->and($event->metadata['phone_call_result'])->toBe('connected')
        ->and($event->metadata['phone_call_result_comments'])->toBe('Left a message about the demo')
        ->and($event->completion_notes)->toBe('Connected — Left a message about the demo');
});

it('syncs call results to CRM activities for lead calls', function () {
    $consultant = phoneCallConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create();
    $type = CalendarEventType::query()->where('slug', 'phone-call')->first();

    $event = CalendarEvent::factory()->forUser($consultant)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Phone call with '.$lead->fullName(),
        'related_type' => $lead->getMorphClass(),
        'related_id' => $lead->id,
        'start_at' => now()->setTime(11, 0),
        'end_at' => now()->setTime(11, 30),
    ]);

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->call('beginCompleteCall', $event->id)
        ->set('call_result', 'interested')
        ->set('result_comments', 'Wants a home demo next week')
        ->call('saveCallResults')
        ->assertHasNoErrors();

    $activity = Activity::query()
        ->where('contact_id', $lead->id)
        ->where('contact_type', $lead->getMorphClass())
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->outcome)->toBe('interested')
        ->and($activity->description)->toBe('Interested — Wants a home demo next week')
        ->and($activity->metadata['calendar_event_id'])->toBe($event->id);
});

it('schedules a follow-up call from the results form', function () {
    $consultant = phoneCallConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Pat',
        'last_name' => 'Prospect',
        'phone' => '555-0400',
    ]);
    $type = CalendarEventType::query()->where('slug', 'phone-call')->first();

    $event = CalendarEvent::factory()->forUser($consultant)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Phone call with Pat Prospect',
        'related_type' => $lead->getMorphClass(),
        'related_id' => $lead->id,
        'start_at' => now()->setTime(11, 0),
        'end_at' => now()->setTime(11, 30),
        'metadata' => [
            'phone_call_reason' => 'schedule_product_demo',
            'phone_number' => '555-0400',
            'contact_kind' => 'prospect',
        ],
    ]);

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->call('beginCompleteCall', $event->id)
        ->set('call_result', 'follow_up_needed')
        ->assertSet('reschedule_enabled', true)
        ->set('reschedule_when', 'tomorrow_10')
        ->set('reschedule_reason', 'general_follow_up')
        ->set('reschedule_notes', 'Try again in the morning')
        ->call('saveCallResults')
        ->assertHasNoErrors();

    $followUp = CalendarEvent::query()
        ->where('title', 'Phone call with Pat Prospect')
        ->where('id', '!=', $event->id)
        ->first();

    expect($event->fresh()->status?->value)->toBe('completed')
        ->and($followUp)->not->toBeNull()
        ->and($followUp->status?->value)->toBe('scheduled')
        ->and($followUp->start_at?->format('H:i'))->toBe('10:00')
        ->and($followUp->metadata['phone_call_reason'])->toBe('general_follow_up')
        ->and($lead->fresh()->next_follow_up_at?->format('H:i'))->toBe('10:00');
});

it('requires notes when other reason is selected', function () {
    $consultant = phoneCallConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'phone' => '555-0199',
    ]);

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->call('selectContact', 'prospect', $lead->id)
        ->set('call_reason', 'other')
        ->set('notes', '')
        ->call('schedule')
        ->assertHasErrors(['notes']);
});

it('shows contact search results for prospects, customers, and team members', function () {
    $consultant = phoneCallConsultant();
    $sponsor = User::factory()->create(['name' => 'Team Sponsor']);
    $consultant->update(['sponsor_id' => $sponsor->id]);

    Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Pat',
        'last_name' => 'Prospect',
    ]);

    Customer::factory()->assignedTo($consultant)->create([
        'first_name' => 'Casey',
        'last_name' => 'Client',
    ]);

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->set('contact_search', 'Pat')
        ->assertSee('Pat Prospect')
        ->assertSee('Prospect')
        ->set('contact_search', 'Case')
        ->assertSee('Casey Client')
        ->assertSee('Customer')
        ->set('contact_search', 'Team')
        ->assertSee('Team Sponsor')
        ->assertSee('Team');
});

it('schedules a call for an other contact with name and phone', function () {
    $consultant = phoneCallConsultant();

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->set('contact_search', 'Vendor Rep')
        ->set('call_when', 'tomorrow_10')
        ->set('call_reason', 'vendor_supplier')
        ->call('schedule')
        ->assertSet('show_add_prospect_prompt', true)
        ->call('useOtherContact')
        ->assertSet('contact_type', 'other')
        ->set('phone_number', '555-0200')
        ->call('schedule')
        ->assertHasNoErrors();

    $event = CalendarEvent::query()
        ->where('title', 'Phone call with Vendor Rep')
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->metadata['other_contact_name'])->toBe('Vendor Rep')
        ->and($event->metadata['phone_number'])->toBe('555-0200');
});

it('creates a prospect and schedules a call when confirmed', function () {
    $consultant = phoneCallConsultant();

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->set('contact_search', 'Taylor Newperson')
        ->set('call_when', 'tomorrow_10')
        ->set('call_reason', 'general_follow_up')
        ->call('schedule')
        ->call('confirmAddProspect')
        ->assertSet('show_new_prospect_form', true)
        ->set('new_phone', '555-0500')
        ->call('createProspectAndSchedule')
        ->assertHasNoErrors();

    $lead = \App\Models\Crm\Lead::query()->where('first_name', 'Taylor')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->lifecycleSlug())->toBe(LeadLifecycle::Lead)
        ->and(CalendarEvent::query()->where('title', 'Phone call with Taylor Newperson')->exists())->toBeTrue();
});

it('reschedules a phone call from the edit popup', function () {
    $consultant = phoneCallConsultant();
    $type = CalendarEventType::query()->where('slug', 'phone-call')->first();

    $event = CalendarEvent::factory()->forUser($consultant)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Call to move',
        'start_at' => now()->setTime(9, 0),
        'end_at' => now()->setTime(9, 30),
        'metadata' => [
            'phone_call_reason' => 'general_follow_up',
            'phone_number' => '555-0300',
            'contact_kind' => 'prospect',
        ],
    ]);

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->call('openEditCall', $event->id)
        ->assertSet('showEdit', true)
        ->set('edit_call_when', 'tomorrow_10')
        ->set('edit_phone_number', '555-0301')
        ->set('edit_call_reason', 'schedule_product_demo')
        ->set('edit_notes', 'Moved to afternoon')
        ->call('saveEditCall')
        ->assertHasNoErrors()
        ->assertSet('showEdit', false);

    $event->refresh();

    expect($event->start_at?->format('H:i'))->toBe('10:00')
        ->and($event->metadata['phone_number'])->toBe('555-0301')
        ->and($event->metadata['phone_call_reason'])->toBe('schedule_product_demo')
        ->and($event->description)->toContain('Moved to afternoon');
});

it('schedules a phone call with a custom date and time', function () {
    $consultant = phoneCallConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'first_name' => 'Custom',
        'last_name' => 'Slot',
        'phone' => '555-0600',
    ]);

    $customDate = now()->addDays(3)->format('Y-m-d');

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->call('selectContact', 'prospect', $lead->id)
        ->set('call_when', 'custom')
        ->set('call_date', $customDate)
        ->set('call_time', '13:45')
        ->set('call_reason', 'general_follow_up')
        ->call('schedule')
        ->assertHasNoErrors();

    $event = CalendarEvent::query()
        ->where('title', 'Phone call with Custom Slot')
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->start_at?->format('Y-m-d H:i'))->toBe($customDate.' 13:45')
        ->and($event->end_at?->format('Y-m-d H:i'))->toBe($customDate.' 14:15');
});

it('requires date and time when pick date and time is selected', function () {
    $consultant = phoneCallConsultant();
    $lead = Prospect::factory()->assignedTo($consultant)->create([
        'phone' => '555-0601',
    ]);

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->call('selectContact', 'prospect', $lead->id)
        ->set('call_when', 'custom')
        ->set('call_date', '')
        ->set('call_time', '')
        ->set('call_reason', 'general_follow_up')
        ->call('schedule')
        ->assertHasErrors(['call_date', 'call_time']);
});

it('reschedules a phone call with a custom date and time from the edit popup', function () {
    $consultant = phoneCallConsultant();
    $type = CalendarEventType::query()->where('slug', 'phone-call')->first();
    $customDate = now()->addDays(5)->format('Y-m-d');

    $event = CalendarEvent::factory()->forUser($consultant)->create([
        'calendar_event_type_id' => $type->id,
        'title' => 'Call to customize',
        'start_at' => now()->setTime(9, 0),
        'end_at' => now()->setTime(9, 30),
        'metadata' => [
            'phone_call_reason' => 'general_follow_up',
            'phone_number' => '555-0700',
            'contact_kind' => 'prospect',
        ],
    ]);

    Livewire::actingAs($consultant)
        ->test(PhoneCallsModal::class)
        ->call('open')
        ->call('openEditCall', $event->id)
        ->assertSet('edit_call_when', 'custom')
        ->assertSet('edit_call_date', now()->format('Y-m-d'))
        ->assertSet('edit_call_time', '09:00')
        ->set('edit_call_when', 'custom')
        ->set('edit_call_date', $customDate)
        ->set('edit_call_time', '15:30')
        ->set('edit_phone_number', '555-0700')
        ->set('edit_call_reason', 'general_follow_up')
        ->call('saveEditCall')
        ->assertHasNoErrors();

    $event->refresh();

    expect($event->start_at?->format('Y-m-d H:i'))->toBe($customDate.' 15:30');
});
