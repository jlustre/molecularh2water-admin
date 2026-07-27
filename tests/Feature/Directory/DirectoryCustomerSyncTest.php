<?php

use App\Enums\Crm\EngagementType;
use App\Enums\Crm\OrderStatus;
use App\Enums\Crm\PaymentStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Order;
use App\Models\Crm\OrderItem;
use App\Models\Crm\Prospect;
use App\Models\DirectoryCustomer;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\OrderService;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

it('moves a paid prospect into CRM customers once and derives consultant and products from that record', function () {
    $agent = User::factory()->create(['name' => 'Alex Consultant']);
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $prospect = Prospect::factory()->assignedTo($agent)->create([
        'funnel_id' => FunnelStage::query()->where('slug', 'order-submitted')->value('funnel_id'),
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'order-submitted')->value('id'),
        'first_name' => 'Jamie',
        'last_name' => 'Rivera',
        'email' => 'jamie.rivera@example.com',
        'phone' => '(310) 555-9000',
        'address' => '88 Sunset Blvd',
        'city' => 'Los Angeles',
        'state' => 'CA',
    ]);

    $order = Order::query()->create([
        'contact_type' => $prospect->getMorphClass(),
        'contact_id' => $prospect->id,
        'user_id' => $agent->id,
        'order_number' => 'O-SYNC-0001',
        'status' => OrderStatus::Submitted,
        'payment_status' => PaymentStatus::Pending,
        'total' => 2500,
        'subtotal' => 2500,
        'submitted_at' => now(),
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'description' => 'H2 Water Machine',
        'quantity' => 1,
        'unit_price' => 2500,
        'line_total' => 2500,
        'sort_order' => 1,
    ]);

    app(OrderService::class)->recordPayment($order, [
        'amount' => 2500,
        'payment_method' => 'Card',
    ], $agent);

    expect(Prospect::query()->where('email', 'jamie.rivera@example.com')->exists())->toBeFalse()
        ->and(DirectoryCustomer::query()->where('email', 'jamie.rivera@example.com')->exists())->toBeFalse();

    $crmCustomer = Customer::query()->where('email', 'jamie.rivera@example.com')->first();

    expect($crmCustomer)->not->toBeNull()
        ->and($crmCustomer->engagement_type)->toBe(EngagementType::Customer)
        ->and($crmCustomer->assigned_user_id)->toBe($agent->id)
        ->and($crmCustomer->stage?->slug)->toBe('payment-received')
        ->and($crmCustomer->productsSummaryLabel())->toBe('H2 Water Machine')
        ->and($crmCustomer->latestOrder()?->order_number)->toBe('O-SYNC-0001');

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    $this->actingAs($admin)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertSee('Jamie Rivera')
        ->assertSee('Alex Consultant')
        ->assertSee('H2 Water Machine')
        ->assertSee('O-SYNC-0001');
});
