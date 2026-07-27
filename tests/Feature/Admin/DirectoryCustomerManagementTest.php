<?php

use App\Enums\Crm\EngagementType;
use App\Models\Crm\Customer;
use App\Models\Installer;
use Database\Seeders\CrmSeeder;
use Database\Seeders\DirectoryCustomersSeeder;

beforeEach(function () {
    $this->seed(CrmSeeder::class);
});

it('allows an admin to manage CRM customers from customers management', function () {
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertSee('Customers Management')
        ->assertSee('Add Customer');

    $this->actingAs($admin)
        ->post(route('admin.customers.store'), [
            'name' => 'Taylor Customer',
            'email' => 'taylor.customer@example.com',
            'phone' => '(555) 444-1212',
            'street_address' => '12 Market St',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90012',
            'engagement_type' => EngagementType::Customer->value,
            'notes' => 'Installer-friendly contact.',
        ])
        ->assertRedirect(route('admin.customers.index'));

    $customer = Customer::query()->where('email', 'taylor.customer@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->fullName())->toBe('Taylor Customer')
        ->and($customer->state)->toBe('CA')
        ->and($customer->engagement_type)->toBe(EngagementType::Customer);

    $this->actingAs($admin)
        ->put(route('admin.customers.update', $customer), [
            'name' => 'Taylor Updated',
            'email' => 'taylor.customer@example.com',
            'phone' => '(555) 444-1212',
            'street_address' => '12 Market St',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90012',
            'engagement_type' => EngagementType::Both->value,
            'notes' => null,
        ])
        ->assertRedirect(route('admin.customers.index'));

    expect($customer->refresh()->fullName())->toBe('Taylor Updated')
        ->and($customer->engagement_type)->toBe(EngagementType::Both);
});

it('seeds CRM customers for installer dropdowns', function () {
    $this->seed([CrmSeeder::class, DirectoryCustomersSeeder::class]);

    expect(Customer::query()->count())->toBeGreaterThanOrEqual(8);
    expect(Customer::query()->where('state', 'CA')->exists())->toBeTrue();
});

it('autofills installer job fields from a selected CRM customer', function () {
    $admin = superAdminUser();
    $this->seed(DirectoryCustomersSeeder::class);

    $installer = Installer::factory()->create();
    $customer = Customer::query()->where('email', 'maria.gonzalez@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.installers.show', $installer))
        ->assertOk()
        ->assertSee('Select a customer')
        ->assertSee('Maria Gonzalez');

    $this->actingAs($admin)
        ->post(route('admin.installers.installations.store', $installer), [
            'crm_customer_id' => $customer->id,
            'status' => 'scheduled',
            'customer_name' => $customer->fullName(),
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'street_address' => $customer->address,
            'city' => $customer->city,
            'state' => $customer->state,
            'postal_code' => $customer->metadata['postal_code'] ?? null,
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ])
        ->assertRedirect(route('admin.installers.show', $installer));

    $this->assertDatabaseHas('installer_installations', [
        'installer_id' => $installer->id,
        'crm_customer_id' => $customer->id,
        'customer_name' => 'Maria Gonzalez',
        'city' => 'Santa Monica',
        'state' => 'CA',
    ]);
});
