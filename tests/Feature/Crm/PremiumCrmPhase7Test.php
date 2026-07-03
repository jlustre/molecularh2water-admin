<?php

use App\Enums\Crm\DemonstrationOutcome;
use App\Enums\Crm\DemonstrationStatus;
use App\Enums\Crm\DemonstrationType;
use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\PaymentStatus;
use App\Enums\Crm\ReferralStatus;
use App\Livewire\Crm\ExecutiveDashboard;
use App\Livewire\Crm\ReportDashboard;
use App\Models\Crm\Demonstration;
use App\Models\Crm\Order;
use App\Models\Crm\OrderItem;
use App\Models\Crm\PipelineStageHistory;
use App\Models\Crm\Referral;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function phase7ExecAdmin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

function phase7ExecAgent(string $name = 'Phase 7 Agent'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('allows authorized users to view the executive dashboard', function () {
    $admin = phase7ExecAdmin();

    $this->actingAs($admin)
        ->get(route('admin.crm.dashboard.index'))
        ->assertOk()
        ->assertSee('CRM Dashboard')
        ->assertSee('Demo Success')
        ->assertSee('Revenue Trend');
});

it('denies users without crm dashboard permission', function () {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('slug', 'editor')->first());

    $this->actingAs($editor)
        ->get(route('admin.crm.dashboard.index'))
        ->assertForbidden();
});

it('calculates demo success rate from completed demonstrations', function () {
    $agent = phase7ExecAgent();
    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create();

    Demonstration::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'type' => DemonstrationType::Home,
        'status' => DemonstrationStatus::Completed,
        'outcome' => DemonstrationOutcome::Interested,
        'scheduled_at' => now()->subDay(),
    ]);

    Demonstration::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'type' => DemonstrationType::Home,
        'status' => DemonstrationStatus::Completed,
        'outcome' => DemonstrationOutcome::NotInterested,
        'scheduled_at' => now()->subDay(),
    ]);

    $stats = app(\App\Services\Crm\ExecutiveAnalyticsService::class)->demoSuccessRate('all', $agent);

    expect($stats['completed'])->toBe(2)
        ->and($stats['successful'])->toBe(1)
        ->and($stats['rate'])->toBe(50.0);
});

it('aggregates paid revenue by product', function () {
    $agent = phase7ExecAgent();
    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->client()->create();

    $order = Order::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'order_number' => 'O-PH7-0001',
        'status' => 'submitted',
        'payment_status' => PaymentStatus::Paid,
        'total' => 4500,
        'subtotal' => 4500,
        'amount_paid' => 4500,
        'paid_at' => now(),
        'submitted_at' => now(),
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'description' => 'H2 Ultra Pro',
        'quantity' => 1,
        'unit_price' => 4500,
        'line_total' => 4500,
    ]);

    $rows = app(\App\Services\Crm\ExecutiveAnalyticsService::class)->revenueByProduct('30d', phase7ExecAdmin());

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->label)->toBe('H2 Ultra Pro')
        ->and($rows->first()->revenue)->toBe(4500.0);
});

it('reports referral conversion rate', function () {
    $agent = phase7ExecAgent();
    $client = \App\Models\Crm\Lead::factory()->assignedTo($agent)->create([
        'lifecycle' => LeadLifecycle::Client,
    ]);

    $referred = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'referred_by_lead_id' => $client->id,
    ]);

    Referral::query()->create([
        'referrer_lead_id' => $client->id,
        'referred_lead_id' => $referred->id,
        'status' => ReferralStatus::Converted,
        'user_id' => $agent->id,
    ]);

    Referral::query()->create([
        'referrer_lead_id' => $client->id,
        'referred_lead_id' => \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
            'referred_by_lead_id' => $client->id,
        ])->id,
        'status' => ReferralStatus::Pending,
        'user_id' => $agent->id,
    ]);

    $stats = app(\App\Services\Crm\ExecutiveAnalyticsService::class)->referralConversion('all', $agent);

    expect($stats['total'])->toBe(2)
        ->and($stats['converted'])->toBe(1)
        ->and($stats['rate'])->toBe(50.0);
});

it('includes executive metrics on the reports dashboard', function () {
    $admin = phase7ExecAdmin();

    Livewire::actingAs($admin)
        ->test(ReportDashboard::class)
        ->assertSee('Total Revenue')
        ->assertSee('Demo Success Rate')
        ->assertSee('Revenue by Product');
});

it('shows average stage duration from pipeline history', function () {
    $agent = phase7ExecAgent();
    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create();
    $fromStage = \App\Models\Crm\FunnelStage::query()->where('slug', 'demo-scheduled')->first();
    $toStage = \App\Models\Crm\FunnelStage::query()->where('slug', 'demo-completed')->first();

    PipelineStageHistory::query()->create([
        'lead_id' => $lead->id,
        'funnel_id' => $fromStage->funnel_id,
        'from_stage_id' => $fromStage->id,
        'to_stage_id' => $toStage->id,
        'user_id' => $agent->id,
        'duration_in_previous_stage_seconds' => 172800,
    ]);

    $rows = app(\App\Services\Crm\ExecutiveAnalyticsService::class)->stageDurations('all', $agent);

    expect($rows)->not->toBeEmpty()
        ->and($rows->first()->stage)->toBe($fromStage->name)
        ->and($rows->first()->avg_days)->toBe(2.0);
});

it('renders executive dashboard livewire component for consultants', function () {
    $agent = phase7ExecAgent();

    Livewire::actingAs($agent)
        ->test(ExecutiveDashboard::class)
        ->assertSee('CRM Dashboard')
        ->assertSet('period', '30d');
});
