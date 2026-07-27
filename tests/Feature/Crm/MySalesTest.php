<?php

use App\Enums\Crm\MemberSaleStatus;
use App\Livewire\Crm\MySales;
use App\Models\Crm\MemberSale;
use App\Models\Role;
use App\Models\User;
use App\Support\Navigation\AppNavigation;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function mySalesConsultant(string $name = 'My Sales Consultant'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('places My Sales under workspace navigation', function () {
    $consultant = mySalesConsultant();
    $links = collect(AppNavigation::links($consultant));

    expect($links->firstWhere('key', 'crm-my-sales'))->not->toBeNull()
        ->and($links->firstWhere('key', 'crm-my-sales')['section'])->toBe('workspace')
        ->and($links->firstWhere('key', 'crm-my-sales')['label'])->toBe('My Sales')
        ->and($links->firstWhere('key', 'crm-my-sales')['route'])->toBe('portal.crm.my-sales.index');
});

it('renders the personal My Sales page for consultants', function () {
    $consultant = mySalesConsultant();

    $this->actingAs($consultant)
        ->get(route('portal.crm.my-sales.index'))
        ->assertOk()
        ->assertSee('My Sales')
        ->assertSee('Your personal sales board')
        ->assertSee('Deal stages');
});

it('shows only sales credited to the signed-in consultant', function () {
    $consultant = mySalesConsultant('Owner Consultant');
    $other = mySalesConsultant('Other Consultant');

    MemberSale::query()->create([
        'user_id' => $consultant->id,
        'customer_name' => 'Mine Customer',
        'status' => MemberSaleStatus::Approved,
        'business_line' => 'h2s',
        'total' => 1200,
        'subtotal' => 1200,
        'gifts_total' => 0,
        'created_by' => $consultant->id,
        'application_started_at' => now()->subDays(3),
        'approved_at' => now()->subDay(),
    ]);

    MemberSale::query()->create([
        'user_id' => $other->id,
        'demo_consultant_id' => $consultant->id,
        'customer_name' => 'Demo Assist Customer',
        'status' => MemberSaleStatus::Completed,
        'business_line' => 'h2s',
        'total' => 900,
        'subtotal' => 900,
        'gifts_total' => 0,
        'created_by' => $other->id,
        'application_started_at' => now()->subDays(5),
        'completed_at' => now()->subDay(),
    ]);

    MemberSale::query()->create([
        'user_id' => $other->id,
        'customer_name' => 'Hidden Customer',
        'status' => MemberSaleStatus::Completed,
        'business_line' => 'h2s',
        'total' => 500,
        'subtotal' => 500,
        'gifts_total' => 0,
        'created_by' => $other->id,
        'application_started_at' => now()->subDays(2),
        'completed_at' => now(),
    ]);

    Livewire::actingAs($consultant)
        ->test(MySales::class)
        ->set('datePreset', 'all')
        ->assertSee('Mine Customer')
        ->assertSee('Demo Assist Customer')
        ->assertDontSee('Hidden Customer')
        ->call('openSale', MemberSale::query()->where('customer_name', 'Mine Customer')->value('id'))
        ->assertSet('selectedSaleId', MemberSale::query()->where('customer_name', 'Mine Customer')->value('id'))
        ->assertSee('Deal detail');
});
