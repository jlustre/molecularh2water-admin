<?php

use App\Enums\Crm\OrderStatus;
use App\Enums\Crm\PaymentStatus;
use App\Enums\Crm\QuotationStatus;
use App\Models\Crm\Order;
use App\Models\Crm\Prospect;
use App\Models\Crm\Quotation;
use App\Models\Role;
use App\Models\User;
use App\Support\Portal\PortalNavigation;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function salesAdmin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

function salesAgent(string $name = 'Sales Agent'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('places sales after activities in portal navigation', function () {
    $agent = salesAgent();
    $labels = collect(PortalNavigation::links($agent))->pluck('label')->values()->all();

    expect($labels)->toContain('Sales')
        ->and(array_search('Sales', $labels, true))
        ->toBe(array_search('Activities', $labels, true) + 1);
});

it('renders the sales page for admins and agents', function () {
    $admin = salesAdmin();
    $agent = salesAgent();

    $this->actingAs($admin)
        ->get(route('admin.crm.sales.index'))
        ->assertOk()
        ->assertSee('Sales')
        ->assertSee('Recent Orders')
        ->assertSee('Recent Quotations')
        ->assertSee('Revenue');

    $this->actingAs($agent)
        ->get(route('portal.crm.sales.index'))
        ->assertOk()
        ->assertSee('Sales')
        ->assertSee('Recent Orders');
});

it('shows scoped orders and quotations on the sales page', function () {
    $agent = salesAgent();
    $other = salesAgent('Other Sales Agent');

    $mine = Prospect::factory()->assignedTo($agent)->create([
        'first_name' => 'Mine',
        'last_name' => 'Contact',
    ]);
    $theirs = Prospect::factory()->assignedTo($other)->create([
        'first_name' => 'Theirs',
        'last_name' => 'Contact',
    ]);

    Order::query()->create([
        'contact_type' => $mine->getMorphClass(),
        'contact_id' => $mine->id,
        'user_id' => $agent->id,
        'order_number' => 'O-SALES-MINE',
        'status' => OrderStatus::Submitted,
        'payment_status' => PaymentStatus::Paid,
        'subtotal' => 1200,
        'total' => 1200,
        'amount_paid' => 1200,
        'paid_at' => now(),
    ]);

    Quotation::query()->create([
        'contact_type' => $mine->getMorphClass(),
        'contact_id' => $mine->id,
        'user_id' => $agent->id,
        'quote_number' => 'Q-SALES-MINE',
        'status' => QuotationStatus::Presented,
        'subtotal' => 900,
        'total' => 900,
    ]);

    Order::query()->create([
        'contact_type' => $theirs->getMorphClass(),
        'contact_id' => $theirs->id,
        'user_id' => $other->id,
        'order_number' => 'O-SALES-THEIRS',
        'status' => OrderStatus::Submitted,
        'payment_status' => PaymentStatus::Pending,
        'subtotal' => 500,
        'total' => 500,
    ]);

    Quotation::query()->create([
        'contact_type' => $theirs->getMorphClass(),
        'contact_id' => $theirs->id,
        'user_id' => $other->id,
        'quote_number' => 'Q-SALES-THEIRS',
        'status' => QuotationStatus::Draft,
        'subtotal' => 400,
        'total' => 400,
    ]);

    $this->actingAs($agent)
        ->get(route('portal.crm.sales.index'))
        ->assertOk()
        ->assertSee('O-SALES-MINE')
        ->assertSee('Q-SALES-MINE')
        ->assertSee('Mine Contact')
        ->assertDontSee('O-SALES-THEIRS')
        ->assertDontSee('Q-SALES-THEIRS')
        ->assertDontSee('Theirs Contact');
});

it('denies access without sales.view permission', function () {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('slug', 'editor')->first());

    $this->actingAs($editor)
        ->get(route('portal.crm.sales.index'))
        ->assertForbidden();
});
