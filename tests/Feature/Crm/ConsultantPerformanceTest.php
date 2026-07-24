<?php

use App\Livewire\Crm\ActivityManager;
use App\Livewire\Crm\Calendar\CalendarDashboard;
use App\Livewire\Crm\ConsultantPerformancePanel;
use App\Livewire\Crm\ConsultantPerformanceSummary;
use App\Models\Crm\ConsultantPerformanceDaily;
use App\Models\Crm\Team;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\ConsultantPerformanceService;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class]);
});

function performanceConsultant(string $name = 'Perf Consultant'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

function performanceManager(): User
{
    $user = User::factory()->create(['name' => 'Perf Manager']);
    $user->roles()->attach(Role::query()->where('slug', 'manager')->first());

    return $user;
}

it('increments and decrements daily performance metrics for self', function () {
    $consultant = performanceConsultant();
    $service = app(ConsultantPerformanceService::class);

    $service->adjust($consultant, $consultant, 'leads_added', 2);
    $service->adjust($consultant, $consultant, 'phone_calls', 1);
    $service->adjust($consultant, $consultant, 'leads_added', -1);

    $row = ConsultantPerformanceDaily::query()
        ->where('user_id', $consultant->id)
        ->whereDate('stat_date', now()->toDateString())
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->leads_added)->toBe(1)
        ->and($row->phone_calls)->toBe(1);

    $service->adjust($consultant, $consultant, 'phone_calls', -5);

    expect($row->fresh()->phone_calls)->toBe(0);
});

it('aggregates daily rows for week and month periods', function () {
    $consultant = performanceConsultant();
    $service = app(ConsultantPerformanceService::class);
    $monday = now()->startOfWeek();

    ConsultantPerformanceDaily::query()->create([
        'user_id' => $consultant->id,
        'stat_date' => $monday->toDateString(),
        'leads_added' => 2,
        'phone_calls' => 1,
        'invites' => 0,
        'schedule_presentation' => 0,
        'actual_demo' => 0,
        'sales_closed' => 1,
    ]);

    ConsultantPerformanceDaily::query()->create([
        'user_id' => $consultant->id,
        'stat_date' => $monday->copy()->addDays(2)->toDateString(),
        'leads_added' => 3,
        'phone_calls' => 4,
        'invites' => 1,
        'schedule_presentation' => 0,
        'actual_demo' => 1,
        'sales_closed' => 0,
    ]);

    [$weekStart, $weekEnd] = $service->periodRange('week', $monday);
    $weekTotals = $service->totalsFor($consultant, $weekStart, $weekEnd);

    expect($weekTotals['leads_added'])->toBe(5)
        ->and($weekTotals['phone_calls'])->toBe(5)
        ->and($weekTotals['invites'])->toBe(1)
        ->and($weekTotals['actual_demo'])->toBe(1)
        ->and($weekTotals['sales_closed'])->toBe(1);

    [$monthStart, $monthEnd] = $service->periodRange('month', $monday);
    $monthTotals = $service->totalsFor($consultant, $monthStart, $monthEnd);

    expect($monthTotals['leads_added'])->toBe(5);
});

it('prevents a consultant from editing another consultants counters', function () {
    $consultant = performanceConsultant('Self');
    $other = performanceConsultant('Other');
    $service = app(ConsultantPerformanceService::class);

    expect($service->canManageSubject($consultant, $other))->toBeFalse();

    expect(fn () => $service->adjust($consultant, $other, 'leads_added', 1))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('allows a manager to edit a team members counters', function () {
    $manager = performanceManager();
    $member = performanceConsultant('Team Member');

    $team = Team::query()->create([
        'name' => 'Perf Team',
        'slug' => 'perf-team',
        'manager_id' => $manager->id,
    ]);
    $team->users()->attach([$manager->id => ['role' => 'lead'], $member->id => ['role' => 'member']]);

    $service = app(ConsultantPerformanceService::class);

    expect($service->canManageSubject($manager, $member))->toBeTrue();

    $service->adjust($manager, $member, 'sales_closed', 2);

    expect(ConsultantPerformanceDaily::query()
        ->where('user_id', $member->id)
        ->whereDate('stat_date', now()->toDateString())
        ->value('sales_closed'))->toBe(2);
});

it('renders the performance panel and supports date selection and increments', function () {
    $consultant = performanceConsultant();
    $past = now()->subDays(3)->toDateString();

    Livewire::actingAs($consultant)
        ->test(ConsultantPerformancePanel::class)
        ->assertSee('Performance counters')
        ->assertSee('Leads added')
        ->assertSee('Sales closed')
        ->set('focusDate', $past)
        ->call('increment', 'leads_added')
        ->call('increment', 'leads_added')
        ->call('decrement', 'leads_added')
        ->assertSet('focusDate', $past);

    expect(ConsultantPerformanceDaily::query()
        ->where('user_id', $consultant->id)
        ->whereDate('stat_date', $past)
        ->value('leads_added'))->toBe(1);
});

it('shows the performance panel on activities and calendar pages', function () {
    $consultant = performanceConsultant();

    Livewire::actingAs($consultant)
        ->test(ActivityManager::class)
        ->assertSeeLivewire(ConsultantPerformancePanel::class)
        ->assertSeeLivewire(ConsultantPerformanceSummary::class)
        ->assertSee('Performance counters')
        ->assertSee('Performance summary');

    Livewire::actingAs($consultant)
        ->test(CalendarDashboard::class)
        ->assertSeeLivewire(ConsultantPerformancePanel::class)
        ->assertSeeLivewire(ConsultantPerformanceSummary::class)
        ->assertSee('Performance counters')
        ->assertSee('Performance summary')
        ->assertSee('This week')
        ->assertSee('This month');
});

it('renders weekly and monthly performance summary cards', function () {
    $consultant = performanceConsultant();

    ConsultantPerformanceDaily::query()->create([
        'user_id' => $consultant->id,
        'stat_date' => now()->toDateString(),
        'leads_added' => 5,
        'phone_calls' => 2,
        'invites' => 1,
        'schedule_presentation' => 0,
        'actual_demo' => 0,
        'sales_closed' => 0,
    ]);

    Livewire::actingAs($consultant)
        ->test(ConsultantPerformanceSummary::class)
        ->assertSee('Performance summary')
        ->assertSee('This week')
        ->assertSee('This month')
        ->assertSee('Leads added')
        ->assertSee('Many leads this month with no closed sales');
});

it('navigates previous and next week and month on the summary', function () {
    $consultant = performanceConsultant();
    $lastWeek = now()->subWeek()->startOfWeek();

    ConsultantPerformanceDaily::query()->create([
        'user_id' => $consultant->id,
        'stat_date' => $lastWeek->toDateString(),
        'leads_added' => 7,
        'phone_calls' => 0,
        'invites' => 0,
        'schedule_presentation' => 0,
        'actual_demo' => 0,
        'sales_closed' => 0,
    ]);

    Livewire::actingAs($consultant)
        ->test(ConsultantPerformanceSummary::class)
        ->call('previousWeek')
        ->assertSee('7')
        ->assertDontSee('This week')
        ->call('nextWeek')
        ->assertSee('This week')
        ->call('previousMonth')
        ->assertDontSee('This month')
        ->call('nextMonth')
        ->assertSee('This month');
});

it('increments the selected date not today when a past date is chosen', function () {
    $consultant = performanceConsultant();
    $past = now()->subDays(10)->toDateString();

    Livewire::actingAs($consultant)
        ->test(ConsultantPerformancePanel::class)
        ->set('focusDate', $past)
        ->call('increment', 'invites');

    expect(ConsultantPerformanceDaily::query()
        ->where('user_id', $consultant->id)
        ->whereDate('stat_date', $past)
        ->value('invites'))->toBe(1)
        ->and(ConsultantPerformanceDaily::query()
            ->where('user_id', $consultant->id)
            ->whereDate('stat_date', now()->toDateString())
            ->exists())->toBeFalse();
});
