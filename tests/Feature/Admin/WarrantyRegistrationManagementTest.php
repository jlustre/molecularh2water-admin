<?php

use App\Models\User;
use App\Models\WarrantyRegistration;
use App\Support\FrontendUrl;

it('allows an admin to manage warranty registrations', function () {
    config([
        'frontend.url' => 'http://localhost:8000',
        'frontend.environment_label' => 'Local',
    ]);

    $admin = User::factory()->create(['name' => 'Admin User']);
    $registration = WarrantyRegistration::create([
        'customer_name' => 'Jane Owner',
        'email' => 'jane@example.com',
        'phone' => '555-0100',
        'serial_number' => 'H2-ADMIN-001',
        'machine_model' => 'H2 Hydrogen Water Machine',
        'purchase_date' => '2026-06-01',
        'purchased_from' => 'H2Systems Demo',
        'notes' => 'Registered after delivery.',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.warranty-registrations.index'))
        ->assertOk()
        ->assertSee('Warranty Registrations')
        ->assertSee('http://localhost:8000/warranty')
        ->assertSee('Registration Records')
        ->assertSee('Customer warranty submissions')
        ->assertSee('Jane Owner')
        ->assertSee('H2-ADMIN-001');

    $this->actingAs($admin)
        ->get(route('admin.warranty-registrations.show', $registration))
        ->assertOk()
        ->assertSee('Jane Owner')
        ->assertSee('Registered after delivery.');

    $this->actingAs($admin)
        ->get(route('admin.warranty-registrations.edit', $registration))
        ->assertOk()
        ->assertSee('Edit registration')
        ->assertSee('H2-ADMIN-001');

    $this->actingAs($admin)
        ->put(route('admin.warranty-registrations.update', $registration), [
            'customer_name' => 'Jane Updated',
            'email' => 'jane.updated@example.com',
            'phone' => '555-0101',
            'serial_number' => 'H2-ADMIN-001',
            'machine_model' => 'H2 Hydrogen Water Machine',
            'purchase_date' => '2026-06-02',
            'purchased_from' => 'Updated Dealer',
            'notes' => 'Updated note.',
        ])
        ->assertRedirect(route('admin.warranty-registrations.show', $registration));

    $registration->refresh();

    expect($registration->customer_name)->toBe('Jane Updated');
    expect($registration->email)->toBe('jane.updated@example.com');
    expect($registration->notes)->toBe('Updated note.');

    $this->actingAs($admin)
        ->delete(route('admin.warranty-registrations.destroy', $registration))
        ->assertRedirect(route('admin.warranty-registrations.index'));

    $this->assertDatabaseMissing('warranty_registrations', [
        'id' => $registration->id,
    ]);
});

it('resolves frontend warranty urls by environment defaults', function () {
    config(['frontend.url' => 'http://localhost:8000', 'frontend.environment_label' => 'Local']);
    expect(FrontendUrl::warrantyRegistration())->toBe('http://localhost:8000/warranty');

    app()->detectEnvironment(fn () => 'local');
    expect(FrontendUrl::environmentKey())->toBe('local');

    app()->detectEnvironment(fn () => 'staging');
    config([
        'frontend.url' => 'https://staging.molecularh2water.com',
        'frontend.environment_label' => 'Staging',
    ]);
    expect(FrontendUrl::warrantyRegistration())->toBe('https://staging.molecularh2water.com/warranty');
    expect(FrontendUrl::environmentLabel())->toBe('Staging');
    expect(FrontendUrl::environmentKey())->toBe('staging');

    app()->detectEnvironment(fn () => 'production');
    config([
        'frontend.url' => 'https://www.molecularh2water.com',
        'frontend.environment_label' => 'Production',
    ]);
    expect(FrontendUrl::warrantyRegistration())->toBe('https://www.molecularh2water.com/warranty');
    expect(FrontendUrl::environmentKey())->toBe('production');
});
