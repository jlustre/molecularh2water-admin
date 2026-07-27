<?php

use App\Enums\Crm\MemberSaleStatus;
use App\Livewire\Crm\InventoryManager;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\StockMovement;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\InventoryService;
use App\Services\Crm\MemberSaleService;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function inventoryAdmin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

it('allows admins to receive and adjust inventory with movement history', function () {
    $admin = inventoryAdmin();
    $product = CrmProduct::query()->where('sku', 'H2-COMPACT')->firstOrFail();
    $before = (int) $product->inventory_quantity;

    Livewire::actingAs($admin)
        ->test(InventoryManager::class)
        ->assertOk()
        ->call('openReceive', $product->id)
        ->set('quantity', 5)
        ->set('reason', 'PO-100')
        ->call('saveReceive')
        ->assertHasNoErrors();

    expect((int) $product->fresh()->inventory_quantity)->toBe($before + 5)
        ->and(StockMovement::query()->where('crm_product_id', $product->id)->where('type', 'receive')->exists())->toBeTrue();
});

it('denies consultants inventory access by default', function () {
    $member = User::factory()->create();
    $member->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    Livewire::actingAs($member)
        ->test(InventoryManager::class)
        ->assertForbidden();
});

it('blocks view-only roles from inventory write actions', function () {
    $viewer = User::factory()->create();
    $role = Role::query()->where('slug', 'editor')->firstOrFail();
    $role->update([
        'permissions' => array_values(array_unique(array_merge(
            $role->permissions ?? [],
            ['products.view', 'admin.dashboard.view'],
        ))),
    ]);
    $viewer->roles()->attach($role);

    Livewire::actingAs($viewer->fresh())
        ->test(InventoryManager::class)
        ->assertOk()
        ->assertSet('canManage', false)
        ->call('openReceive')
        ->assertForbidden();
});

it('reserves stock when a sale reaches financing and deducts on delivered', function () {
    $admin = inventoryAdmin();
    $product = CrmProduct::query()->where('sku', 'COOKWARE-SET')->firstOrFail();
    $product->update(['inventory_quantity' => 10, 'reserved_quantity' => 0, 'reorder_level' => 2]);

    $sales = app(MemberSaleService::class);
    $sale = $sales->create([
        'user_id' => $admin->id,
        'customer_name' => 'Inventory Test',
        'status' => MemberSaleStatus::Financing->value,
    ], [[
        'crm_product_id' => $product->id,
        'item_kind' => 'product',
        'name' => $product->name,
        'quantity' => 2,
        'unit_price' => $product->unit_price,
    ]], $admin);

    expect($sale->fresh()->inventory_reserved)->toBeTrue()
        ->and((int) $product->fresh()->reserved_quantity)->toBe(2)
        ->and((int) $product->fresh()->inventory_quantity)->toBe(10);

    $sales->updateStatus($sale->fresh(), MemberSaleStatus::Delivered, $admin);

    expect($sale->fresh()->inventory_deducted)->toBeTrue()
        ->and((int) $product->fresh()->inventory_quantity)->toBe(8)
        ->and((int) $product->fresh()->reserved_quantity)->toBe(0);
});

it('marks low stock when available quantity is at or below reorder level', function () {
    $product = CrmProduct::query()->where('sku', 'FILTER-ANNUAL')->firstOrFail();
    $product->update([
        'inventory_quantity' => 3,
        'reserved_quantity' => 0,
        'reorder_level' => 5,
    ]);

    expect($product->fresh()->isLowStock())->toBeTrue();
});

it('registers the inventory route for admins', function () {
    $admin = inventoryAdmin();

    $this->actingAs($admin)
        ->get(route('admin.crm.inventory.index'))
        ->assertOk();
});
