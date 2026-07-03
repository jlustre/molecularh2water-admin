<?php

use App\Enums\BusinessLine;
use App\Livewire\BusinessLineSwitcher;
use App\Livewire\Crm\Calendar\CalendarDashboard;
use App\Livewire\Crm\Calendar\CalendarEventModal;
use App\Livewire\Crm\Calendar\CalendarGrid;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Lead;
use App\Models\Role;
use App\Models\User;
use App\Support\BusinessLineContext;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

function businessLineUser(array $lines, string $name = 'Line User'): User
{
    $user = User::factory()->create([
        'name' => $name,
        'business_lines' => $lines,
    ]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('shows the business line switcher only for multi-line members', function () {
    $dual = businessLineUser([BusinessLine::Hcc->value, BusinessLine::H2s->value], 'Dual Line');
    $single = businessLineUser([BusinessLine::Hcc->value], 'HCC Only');

    Livewire::actingAs($dual)
        ->test(BusinessLineSwitcher::class)
        ->assertSee('HCC')
        ->assertSee('H2S')
        ->assertDontSee('Both');

    Livewire::actingAs($single)
        ->test(BusinessLineSwitcher::class)
        ->assertSee('HCC')
        ->assertDontSee('Both');
});

it('filters calendar events by selected business line', function () {
    $user = businessLineUser([BusinessLine::Hcc->value, BusinessLine::H2s->value]);
    $cookingType = CalendarEventType::query()->where('slug', 'cooking-show')->first();
    $waterType = CalendarEventType::query()->where('slug', 'water-awareness-show')->first();

    CalendarEvent::factory()->forUser($user)->create([
        'calendar_event_type_id' => $cookingType->id,
        'business_line' => BusinessLine::Hcc->value,
        'title' => 'HCC cooking night',
        'start_at' => now()->addDays(3)->setHour(10),
        'end_at' => now()->addDays(3)->setHour(12),
    ]);

    CalendarEvent::factory()->forUser($user)->create([
        'calendar_event_type_id' => $waterType->id,
        'business_line' => BusinessLine::H2s->value,
        'title' => 'H2S awareness event',
        'start_at' => now()->addDays(3)->setHour(14),
        'end_at' => now()->addDays(3)->setHour(16),
    ]);

    BusinessLineContext::setCurrent(BusinessLine::Hcc->value, $user);

    Livewire::actingAs($user)
        ->test(CalendarGrid::class, [
            'focusDate' => now()->addDays(3)->toDateString(),
            'view' => 'month',
            'filters' => [],
        ])
        ->assertSee('HCC cooking night')
        ->assertDontSee('H2S awareness event');
});

it('scopes leads to the member business lines', function () {
    $user = businessLineUser([BusinessLine::Hcc->value]);
    $visible = Lead::factory()->assignedTo($user)->create([
        'business_line' => BusinessLine::Hcc->value,
        'first_name' => 'Cookware',
        'last_name' => 'Prospect',
    ]);
    Lead::factory()->assignedTo($user)->create([
        'business_line' => BusinessLine::H2s->value,
        'first_name' => 'Hidden',
        'last_name' => 'Prospect',
    ]);

    expect(\App\Support\Crm\CrmScope::leads(Lead::query(), $user)->pluck('id'))
        ->toContain($visible->id)
        ->and(\App\Support\Crm\CrmScope::leads(Lead::query(), $user)->count())->toBe(1);
});

it('assigns cooking shows to HCC when created from calendar', function () {
    $user = businessLineUser([BusinessLine::Hcc->value, BusinessLine::H2s->value]);
    $lead = Lead::factory()->assignedTo($user)->create(['business_line' => BusinessLine::Hcc->value]);
    $type = CalendarEventType::query()->where('slug', 'cooking-show')->first();

    Livewire::actingAs($user)
        ->test(CalendarEventModal::class)
        ->call('openCreateShow', 'cooking-show')
        ->set('related_key', 'lead:'.$lead->id)
        ->set('start_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('end_at', now()->addDay()->addHours(2)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect(CalendarEvent::query()->latest('id')->first()?->business_line?->value)->toBe('hcc');
});
