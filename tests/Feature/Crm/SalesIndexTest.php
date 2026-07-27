<?php

use App\Enums\Crm\MemberSaleStatus;
use App\Models\Crm\MemberSale;
use App\Models\Role;
use App\Models\User;
use App\Support\Navigation\AppNavigation;
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

it('places consultant sales products and inventory under system navigation for admins', function () {
    $admin = salesAdmin();
    $links = collect(AppNavigation::links($admin));

    expect($links->firstWhere('key', 'crm-sales'))->not->toBeNull()
        ->and($links->firstWhere('key', 'crm-sales')['section'])->toBe('system')
        ->and($links->firstWhere('key', 'crm-products')['section'])->toBe('system')
        ->and($links->firstWhere('key', 'crm-inventory')['section'])->toBe('system')
        ->and($links->firstWhere('key', 'crm-sales')['route'])->toBe('admin.crm.sales.index');
});

it('hides consultant sales products and inventory from portal navigation', function () {
    $agent = salesAgent();
    $labels = collect(PortalNavigation::links($agent))->pluck('label')->values()->all();

    expect($labels)->not->toContain('Consultant Sales')
        ->and($labels)->not->toContain('Products & Gifts')
        ->and($labels)->not->toContain('Inventory')
        ->and($labels)->toContain('Activities');
});

it('renders the consultant sales page for admins', function () {
    $admin = salesAdmin();

    $this->actingAs($admin)
        ->get(route('admin.crm.sales.index'))
        ->assertOk()
        ->assertSee('Consultant Sales')
        ->assertSee('Consultant')
        ->assertSee('Demo consultant')
        ->assertSee('Add Sale');
});

it('denies consultants consultant sales by default', function () {
    $agent = salesAgent();

    $this->actingAs($agent)
        ->get(route('portal.crm.sales.index'))
        ->assertForbidden();
});

it('allows a non-admin role when sales.view is granted and admin access exists', function () {
    $editor = User::factory()->create();
    $editorRole = Role::query()->where('slug', 'editor')->firstOrFail();
    $editorRole->update([
        'permissions' => array_values(array_unique(array_merge(
            $editorRole->permissions ?? [],
            ['sales.view', 'admin.dashboard.view'],
        ))),
    ]);
    $editor->roles()->attach($editorRole);

    $links = collect(AppNavigation::links($editor->fresh()));

    expect($links->firstWhere('key', 'crm-sales'))->not->toBeNull()
        ->and($links->firstWhere('key', 'crm-sales')['section'])->toBe('system');

    $this->actingAs($editor->fresh())
        ->get(route('admin.crm.sales.index'))
        ->assertOk()
        ->assertSee('Consultant Sales');
});

it('shows scoped consultant sales on the sales page', function () {
    $admin = salesAdmin();
    $agent = salesAgent();
    $other = salesAgent('Other Sales Agent');
    $demo = salesAgent('Demo Helper');

    MemberSale::query()->create([
        'user_id' => $agent->id,
        'demo_consultant_id' => $demo->id,
        'customer_name' => 'Mine Contact',
        'status' => MemberSaleStatus::Approved,
        'business_line' => 'both',
        'approved_at' => now(),
        'application_started_at' => now()->subDay(),
        'created_by' => $agent->id,
        'total' => 1200,
    ]);

    MemberSale::query()->create([
        'user_id' => $other->id,
        'customer_name' => 'Theirs Contact',
        'status' => MemberSaleStatus::ApplicationStarted,
        'business_line' => 'both',
        'application_started_at' => now(),
        'created_by' => $other->id,
        'total' => 500,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.crm.sales.index'))
        ->assertOk()
        ->assertSee('Mine Contact')
        ->assertSee($agent->name)
        ->assertSee($demo->name)
        ->assertSee('Theirs Contact');
});

it('denies access without sales.view permission', function () {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('slug', 'editor')->first());

    $this->actingAs($editor)
        ->get(route('admin.crm.sales.index'))
        ->assertForbidden();
});

it('redirects the legacy member-sales url to consultant sales', function () {
    $admin = salesAdmin();

    $this->actingAs($admin)
        ->get(route('admin.crm.member-sales.index'))
        ->assertRedirect(route('admin.crm.sales.index'));
});
