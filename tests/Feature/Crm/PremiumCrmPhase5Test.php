<?php

use App\Models\Crm\CrmProduct;
use App\Models\Crm\Delivery;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Installation;
use App\Models\Crm\Order;
use App\Models\Crm\Quotation;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

it('creates and submits an order from a quotation', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'funnel_id' => FunnelStage::query()->where('slug', 'quote-presented')->value('funnel_id'),
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'quote-presented')->value('id'),
    ]);

    $quotation = app(\App\Services\Crm\QuotationService::class)->create(
        $lead,
        ['valid_until' => now()->addDays(30)],
        [[
            'crm_product_id' => CrmProduct::query()->value('id'),
            'description' => 'H2 Ultra Pro',
            'quantity' => 1,
            'unit_price' => 3499,
        ]],
        $agent,
    );

    app(\App\Services\Crm\QuotationService::class)->present($quotation, $agent);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadQuotationsPanel::class, ['lead' => $lead->fresh()])
        ->call('createOrder', $quotation->id)
        ->assertHasNoErrors();

    $order = Order::query()->where('lead_id', $lead->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->status->value)->toBe('submitted')
        ->and($order->items)->toHaveCount(1)
        ->and($lead->fresh()->stage?->slug)->toBe('order-submitted');
});

it('records payment and moves lead to payment received', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'funnel_id' => FunnelStage::query()->where('slug', 'order-submitted')->value('funnel_id'),
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'order-submitted')->value('id'),
    ]);

    $order = Order::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'order_number' => 'O-TEST-0001',
        'status' => 'submitted',
        'payment_status' => 'pending',
        'total' => 1000,
        'subtotal' => 1000,
        'submitted_at' => now(),
    ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadOrdersPanel::class, ['lead' => $lead])
        ->set('payment_amount', '1000')
        ->set('payment_method', 'Card')
        ->call('recordPayment', $order->id)
        ->assertHasNoErrors();

    expect($order->fresh()->payment_status->value)->toBe('paid')
        ->and($lead->fresh()->stage?->slug)->toBe('payment-received');
});

it('schedules delivery and moves lead to delivery scheduled', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'funnel_id' => FunnelStage::query()->where('slug', 'payment-received')->value('funnel_id'),
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'payment-received')->value('id'),
        'address' => '123 Main St',
    ]);

    $order = Order::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'order_number' => 'O-TEST-0002',
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'total' => 500,
        'subtotal' => 500,
        'amount_paid' => 500,
        'paid_at' => now(),
    ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadOrdersPanel::class, ['lead' => $lead])
        ->set('delivery_scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('scheduleDelivery', $order->id)
        ->assertHasNoErrors();

    expect(Delivery::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and($lead->fresh()->stage?->slug)->toBe('delivery-scheduled');
});

it('completes installation and moves lead to delivered installed', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'funnel_id' => FunnelStage::query()->where('slug', 'delivery-scheduled')->value('funnel_id'),
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'delivery-scheduled')->value('id'),
    ]);

    $order = Order::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'order_number' => 'O-TEST-0003',
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'total' => 500,
        'subtotal' => 500,
        'amount_paid' => 500,
    ]);

    $installation = Installation::query()->create([
        'order_id' => $order->id,
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'status' => 'scheduled',
        'scheduled_at' => now(),
        'checklist' => collect(config('crm.installation_checklist'))->mapWithKeys(fn ($label, $slug) => [$slug => true])->all(),
    ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadOrdersPanel::class, ['lead' => $lead])
        ->call('startCompleteInstallation', $installation->id)
        ->call('completeInstallation')
        ->assertHasNoErrors();

    expect($installation->fresh()->status->value)->toBe('completed')
        ->and($order->fresh()->status->value)->toBe('fulfilled')
        ->and($lead->fresh()->stage?->slug)->toBe('delivered-installed');
});

it('prevents duplicate orders from the same quotation', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create();
    $quotation = Quotation::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'quote_number' => 'Q-TEST-0001',
        'status' => 'presented',
        'total' => 100,
        'subtotal' => 100,
    ]);

    app(\App\Services\Crm\OrderService::class)->createFromQuotation($quotation, $agent);

    expect(fn () => app(\App\Services\Crm\OrderService::class)->createFromQuotation($quotation->fresh(), $agent))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});
