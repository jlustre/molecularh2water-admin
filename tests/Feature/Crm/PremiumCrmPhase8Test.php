<?php

use App\Enums\Crm\DemonstrationOutcome;
use App\Enums\Crm\DemonstrationStatus;
use App\Enums\Crm\DemonstrationType;
use App\Enums\Crm\DeliveryStatus;
use App\Enums\Crm\PaymentStatus;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\Delivery;
use App\Models\Crm\FollowupSequenceEnrollment;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Order;
use App\Models\Crm\Task;
use App\Notifications\Crm\DemoScheduledNotification;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmMarketingSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    config([
        'crm.automation.enabled' => true,
        'crm.automation.sync' => true,
    ]);
    $this->seed([RolesSeeder::class, CrmSeeder::class, CalendarSeeder::class, CrmMarketingSeeder::class]);
});

function phase8Agent(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('creates calendar event and notifies assignee when demo is scheduled', function () {
    Notification::fake();
    Mail::fake();

    $agent = phase8Agent();
    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'funnel_id' => FunnelStage::query()->where('slug', 'qualified')->value('funnel_id'),
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'qualified')->value('id'),
    ]);

    $demo = app(\App\Services\Crm\DemonstrationService::class)->schedule($lead, [
        'type' => DemonstrationType::Home->value,
        'scheduled_at' => now()->addDays(2),
        'duration_minutes' => 60,
        'venue' => 'Client home',
    ], $agent);

    expect(CalendarEvent::query()->where('title', 'like', '%'.$lead->fullName().'%')->exists())->toBeTrue()
        ->and($demo->fresh()->calendar_event_id)->not->toBeNull()
        ->and($lead->fresh()->stage?->slug)->toBe('demo-scheduled');

    Notification::assertSentTo($agent, DemoScheduledNotification::class);
});

it('creates a post-demo follow-up task when demo is completed', function () {
    $agent = phase8Agent();
    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create();

    $demo = app(\App\Services\Crm\DemonstrationService::class)->schedule($lead, [
        'type' => DemonstrationType::Home->value,
        'scheduled_at' => now()->addDay(),
    ], $agent);

    app(\App\Services\Crm\DemonstrationService::class)->complete($demo, [
        'outcome' => DemonstrationOutcome::Interested->value,
        'attended' => true,
    ], $agent);

    $task = Task::query()->where('lead_id', $lead->id)->where('title', 'like', 'Post-demo follow-up%')->first();

    expect($task)->not->toBeNull()
        ->and($task->priority?->value)->toBe('high');
});

it('enrolls prospect in nurture sequence on capture', function () {
    Mail::fake();

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    $lead = app(\App\Services\Crm\ProspectCaptureService::class)->capture([
        'first_name' => 'Auto',
        'last_name' => 'Prospect',
        'email' => 'auto.prospect@example.com',
        'consent_given' => true,
    ]);

    $enrollment = FollowupSequenceEnrollment::query()
        ->where('lead_id', $lead->id)
        ->where('trigger_event', 'prospect_captured')
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->status)->toBe('completed');
});

it('creates delivery scheduling task when order is fully paid', function () {
    $agent = phase8Agent();
    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create();

    $order = Order::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'order_number' => 'O-PH8-0001',
        'status' => 'submitted',
        'payment_status' => PaymentStatus::Pending,
        'total' => 1500,
        'subtotal' => 1500,
        'submitted_at' => now(),
    ]);

    app(\App\Services\Crm\OrderService::class)->recordPayment($order, [
        'amount' => 1500,
        'payment_method' => 'Card',
    ], $agent);

    $task = Task::query()->where('lead_id', $lead->id)->where('title', 'like', 'Schedule delivery%')->first();

    expect($task)->not->toBeNull();
});

it('creates orientation task when delivery is completed', function () {
    $agent = phase8Agent();
    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->client()->create();

    $order = Order::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'order_number' => 'O-PH8-0002',
        'status' => 'submitted',
        'payment_status' => PaymentStatus::Paid,
        'total' => 1500,
        'subtotal' => 1500,
        'amount_paid' => 1500,
        'paid_at' => now(),
        'submitted_at' => now(),
    ]);

    $delivery = Delivery::query()->create([
        'order_id' => $order->id,
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'status' => DeliveryStatus::Scheduled,
        'scheduled_at' => now(),
    ]);

    app(\App\Services\Crm\DeliveryService::class)->complete($delivery, [], $agent);

    $task = Task::query()->where('lead_id', $lead->id)->where('title', 'like', 'Schedule customer orientation%')->first();

    expect($task)->not->toBeNull();
});

it('creates referral campaign task when lead moves to referral requested', function () {
    $agent = phase8Agent();
    $stage = FunnelStage::query()->where('slug', 'referral-requested')->first();

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->client()->create([
        'funnel_id' => $stage->funnel_id,
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'customer-orientation')->where('funnel_id', $stage->funnel_id)->value('id'),
    ]);

    app(\App\Services\Crm\FunnelService::class)->moveLead($lead, $stage, $agent);

    $task = Task::query()->where('lead_id', $lead->id)->where('title', 'like', 'Referral campaign%')->first();

    expect($task)->not->toBeNull();
});

it('renders template variables for automation messages', function () {
    $lead = \App\Models\Crm\Lead::factory()->create([
        'first_name' => 'Taylor',
        'last_name' => 'Swift',
        'email' => 'taylor@example.com',
    ]);

    $rendered = app(\App\Services\Crm\CrmTemplateRenderer::class)->render(
        'Hello {{first_name}} {{last_name}} ({{lead_name}})',
        $lead,
    );

    expect($rendered)->toBe('Hello Taylor Swift (Taylor Swift)');
});

it('enrolls demo reminder sequence when demonstration is scheduled', function () {
    Mail::fake();

    $agent = phase8Agent();
    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'email' => 'demo.sequence@example.com',
    ]);

    app(\App\Services\Crm\DemonstrationService::class)->schedule($lead, [
        'type' => DemonstrationType::Home->value,
        'scheduled_at' => now()->addDay(),
    ], $agent);

    $enrollment = FollowupSequenceEnrollment::query()
        ->where('lead_id', $lead->id)
        ->where('trigger_event', 'demonstration.scheduled')
        ->first();

    expect($enrollment)->not->toBeNull();
});
