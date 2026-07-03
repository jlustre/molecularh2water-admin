<?php

use App\Livewire\Portal\AppointmentsModal;
use App\Livewire\Portal\QuickLinks;
use App\Models\Crm\Appointment;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use App\Services\Portal\PortalAppointmentService;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function appointmentConsultant(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('shows appointments quick action on the dashboard', function () {
    $consultant = appointmentConsultant();

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Appointments')
        ->assertSeeLivewire(AppointmentsModal::class);
});

it('lists upcoming appointments and schedules a new one', function () {
    $consultant = appointmentConsultant();
    $lead = Lead::factory()->assignedTo($consultant)->prospect()->create([
        'first_name' => 'Sam',
        'last_name' => 'Prospect',
    ]);

    Appointment::factory()
        ->forLead($lead)
        ->forUser($consultant)
        ->create([
            'title' => 'Kitchen demo',
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'ends_at' => now()->addDays(2)->setTime(11, 0),
        ]);

    Livewire::actingAs($consultant)
        ->test(AppointmentsModal::class)
        ->call('open')
        ->assertSet('show', true)
        ->assertSee('Kitchen demo')
        ->call('selectContact', $lead->id)
        ->set('meeting_type', 'home_demo')
        ->set('appointment_when', 'tomorrow_10')
        ->set('duration_minutes', 60)
        ->set('location', 'Client home')
        ->set('notes', 'Bring samples')
        ->call('schedule')
        ->assertHasNoErrors()
        ->assertDispatched('appointment-scheduled');

    $appointment = Appointment::query()
        ->whereLeadId($lead->id)
        ->where('location', 'Client home')
        ->latest('id')
        ->first();

    expect($appointment)->not->toBeNull()
        ->and($appointment->title)->toBe('Appointment with Sam Prospect')
        ->and($appointment->meeting_type)->toBe('home_demo')
        ->and($appointment->reminder_notes)->toBe('Bring samples');

    expect(app(PortalAppointmentService::class)->upcomingAppointments($consultant))->toHaveCount(2);
});

it('creates a prospect when scheduling with an unknown contact', function () {
    $consultant = appointmentConsultant();

    Livewire::actingAs($consultant)
        ->test(AppointmentsModal::class)
        ->call('open')
        ->set('contact_search', 'Taylor Newperson')
        ->set('meeting_type', 'home_demo')
        ->set('appointment_when', 'tomorrow_10')
        ->call('schedule')
        ->call('confirmAddProspect')
        ->set('new_email', 'taylor@example.com')
        ->call('createProspectAndSchedule')
        ->assertHasNoErrors();

    $prospect = Prospect::query()->where('email', 'taylor@example.com')->first();

    expect($prospect)->not->toBeNull()
        ->and($prospect->lifecycleSlug()->value)->toBe('prospect')
        ->and(Appointment::query()->where('contact_type', 'prospect')->where('contact_id', $prospect->id)->exists())->toBeTrue();
});

it('refreshes dashboard stats when an appointment is scheduled', function () {
    $consultant = appointmentConsultant();
    $stats = app(DashboardStatsService::class);
    $before = $stats->get($consultant)['appointmentsToday'];

    Livewire::actingAs($consultant)
        ->test(AppointmentsModal::class)
        ->call('open')
        ->set('title', 'Walk-in consult')
        ->set('meeting_type', 'in_person')
        ->set('appointment_when', 'today_14')
        ->call('schedule')
        ->assertHasNoErrors();

    $after = $stats->get($consultant)['appointmentsToday'];

    expect($after)->toBe($before + 1);
});

it('opens the appointments modal from quick links', function () {
    $consultant = appointmentConsultant();

    Livewire::actingAs($consultant)
        ->test(QuickLinks::class)
        ->call('openAppointments')
        ->assertDispatched('open-appointments');
});
