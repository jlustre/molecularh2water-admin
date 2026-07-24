<?php

use App\Enums\Crm\MemberSaleStatus;
use App\Models\Crm\MemberSale;
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

it('places a single consultant sales link after activities in portal navigation', function () {
    $agent = salesAgent();
    $labels = collect(PortalNavigation::links($agent))->pluck('label')->values()->all();

    expect($labels)->toContain('Consultant Sales')
        ->and($labels)->not->toContain('Orders & Quotes')
        ->and(collect($labels)->filter(fn ($label) => $label === 'Consultant Sales')->count())->toBe(1)
        ->and(array_search('Consultant Sales', $labels, true))
        ->toBeGreaterThan(array_search('Activities', $labels, true));
});

it('renders the consultant sales page for admins and agents', function () {
    $admin = salesAdmin();
    $agent = salesAgent();

    $this->actingAs($admin)
        ->get(route('admin.crm.sales.index'))
        ->assertOk()
        ->assertSee('Consultant Sales')
        ->assertSee('Consultant')
        ->assertSee('Demo consultant')
        ->assertSee('Add Sale');

    $this->actingAs($agent)
        ->get(route('portal.crm.sales.index'))
        ->assertOk()
        ->assertSee('Consultant Sales')
        ->assertDontSee('Add Sale');
});

it('shows scoped consultant sales on the sales page', function () {
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

    $this->actingAs($agent)
        ->get(route('portal.crm.sales.index'))
        ->assertOk()
        ->assertSee('Mine Contact')
        ->assertSee($agent->name)
        ->assertSee($demo->name)
        ->assertDontSee('Theirs Contact');
});

it('denies access without sales.view permission', function () {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('slug', 'editor')->first());

    $this->actingAs($editor)
        ->get(route('portal.crm.sales.index'))
        ->assertForbidden();
});

it('redirects the legacy member-sales url to consultant sales', function () {
    $admin = salesAdmin();

    $this->actingAs($admin)
        ->get(route('admin.crm.member-sales.index'))
        ->assertRedirect(route('admin.crm.sales.index'));
});
