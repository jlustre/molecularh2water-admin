<?php

use App\Enums\Crm\MemberSaleStatus;
use App\Livewire\Crm\CrmProductManager;
use App\Livewire\Crm\MemberSalesManager;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\MemberSale;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function salesAdminUser(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

function salesMemberUser(string $name = 'Sales Member'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

function salesManagerUser(): User
{
    $user = User::factory()->create(['name' => 'Sales Manager']);
    $user->roles()->attach(Role::query()->where('slug', 'manager')->first());

    return $user;
}

it('allows admins to manage member sales', function () {
    $admin = salesAdminUser();

    Livewire::actingAs($admin)
        ->test(MemberSalesManager::class)
        ->assertOk()
        ->assertSet('canManage', true)
        ->call('openForm')
        ->set('user_id', $admin->id)
        ->set('customer_name', 'Test Customer')
        ->set('status', MemberSaleStatus::ApplicationStarted->value)
        ->set('lineItems', [[
            'crm_product_id' => null,
            'item_kind' => 'product',
            'name' => 'Test Product',
            'sku' => 'TEST-1',
            'quantity' => 1,
            'unit_price' => '100.00',
        ]])
        ->call('save')
        ->assertHasNoErrors();

    expect(MemberSale::query()->where('customer_name', 'Test Customer')->exists())->toBeTrue();
});

it('blocks members from creating sales', function () {
    $member = salesMemberUser();

    Livewire::actingAs($member)
        ->test(MemberSalesManager::class)
        ->assertOk()
        ->assertSet('canManage', false)
        ->call('openForm')
        ->assertForbidden();
});

it('scopes consultant sales to the logged-in consultant', function () {
    $memberA = salesMemberUser('Consultant A');
    $memberB = salesMemberUser('Consultant B');

    MemberSale::query()->create([
        'user_id' => $memberA->id,
        'customer_name' => 'A Customer',
        'status' => MemberSaleStatus::ApplicationStarted,
        'business_line' => 'both',
        'application_started_at' => now(),
        'created_by' => $memberA->id,
    ]);

    MemberSale::query()->create([
        'user_id' => $memberB->id,
        'customer_name' => 'B Customer',
        'status' => MemberSaleStatus::ApplicationStarted,
        'business_line' => 'both',
        'application_started_at' => now(),
        'created_by' => $memberB->id,
    ]);

    Livewire::actingAs($memberA)
        ->test(MemberSalesManager::class)
        ->assertSee('A Customer')
        ->assertDontSee('B Customer');
});

it('shows sales to the demo consultant as well as the primary consultant', function () {
    $learner = salesMemberUser('Learning Consultant');
    $mentor = salesMemberUser('Demo Mentor');

    MemberSale::query()->create([
        'user_id' => $learner->id,
        'demo_consultant_id' => $mentor->id,
        'customer_name' => 'Shared Credit Customer',
        'status' => MemberSaleStatus::ApplicationStarted,
        'business_line' => 'both',
        'application_started_at' => now(),
        'created_by' => $mentor->id,
    ]);

    Livewire::actingAs($mentor)
        ->test(MemberSalesManager::class)
        ->assertSee('Shared Credit Customer')
        ->assertSee('Learning Consultant')
        ->assertSee('Demo Mentor')
        ->assertSee('Demo consultant');
});


it('allows managers to view team member sales read-only', function () {
    $manager = salesManagerUser();
    $member = salesMemberUser('Team Member');

    MemberSale::query()->create([
        'user_id' => $member->id,
        'customer_name' => 'Team Sale',
        'status' => MemberSaleStatus::Approved,
        'business_line' => 'both',
        'approved_at' => now(),
        'application_started_at' => now()->subDay(),
        'created_by' => $member->id,
    ]);

    Livewire::actingAs($manager)
        ->test(MemberSalesManager::class)
        ->assertSet('canManage', false);
});

it('allows admins to manage products gifts and categories', function () {
    $admin = salesAdminUser();

    Livewire::actingAs($admin)
        ->test(CrmProductManager::class)
        ->assertOk()
        ->assertSet('canManage', true)
        ->call('openCategoryForm')
        ->set('category_name', 'Demo Category')
        ->set('category_kind', 'product')
        ->call('saveCategory')
        ->assertHasNoErrors();

    Livewire::actingAs($admin)
        ->test(CrmProductManager::class)
        ->call('openProductForm')
        ->set('sku', 'DEMO-SKU-1')
        ->set('name', 'Demo Product')
        ->set('kind', 'product')
        ->set('unit_price', '99.99')
        ->set('inventory_quantity', 5)
        ->call('saveProduct')
        ->assertHasNoErrors();

    expect(CrmProduct::query()->where('sku', 'DEMO-SKU-1')->exists())->toBeTrue();
});

it('blocks members from product management actions', function () {
    $member = salesMemberUser();

    Livewire::actingAs($member)
        ->test(CrmProductManager::class)
        ->assertOk()
        ->assertSet('canManage', false)
        ->call('openProductForm')
        ->assertForbidden();
});

it('filters consultant sales by date preset', function () {
    $admin = salesAdminUser();

    MemberSale::query()->create([
        'user_id' => $admin->id,
        'customer_name' => 'This Week Sale',
        'status' => MemberSaleStatus::ApplicationStarted,
        'business_line' => 'both',
        'application_started_at' => now(),
        'created_by' => $admin->id,
    ]);

    $old = MemberSale::query()->create([
        'user_id' => $admin->id,
        'customer_name' => 'Old Sale',
        'status' => MemberSaleStatus::Completed,
        'business_line' => 'both',
        'application_started_at' => now()->subMonths(2),
        'completed_at' => now()->subMonths(2),
        'created_by' => $admin->id,
    ]);
    $old->forceFill([
        'created_at' => now()->subMonths(2),
        'updated_at' => now()->subMonths(2),
    ])->saveQuietly();

    Livewire::actingAs($admin)
        ->test(MemberSalesManager::class)
        ->assertSee('Date range')
        ->set('datePreset', 'this_week')
        ->assertSee('This Week Sale')
        ->assertDontSee('Old Sale')
        ->set('datePreset', 'all')
        ->assertSee('This Week Sale')
        ->assertSee('Old Sale');
});

it('registers consultant sales and products routes', function () {
    $admin = salesAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.crm.sales.index'))
        ->assertOk()
        ->assertSee('Consultant Sales');

    $this->actingAs($admin)
        ->get(route('admin.crm.products.index'))
        ->assertOk();
});
