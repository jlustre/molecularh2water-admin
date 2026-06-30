<?php

use App\Mail\WarrantyRegistrationConfirmation;
use App\Models\WarrantyRegistration;
use Illuminate\Support\Facades\Mail;

it('stores a warranty registration from the public api', function () {
    Mail::fake();

    $payload = [
        'customer_name' => 'Jane Owner',
        'email' => 'jane@example.com',
        'phone' => '555-0100',
        'serial_number' => 'H2-12345-ABCD',
        'machine_model' => 'H2 Hydrogen Water Machine',
        'purchase_date' => '2026-06-01',
        'purchased_from' => 'H2Systems Demo',
        'notes' => 'Registered after delivery.',
    ];

    $this->postJson('/api/warranty-registrations', $payload)
        ->assertCreated()
        ->assertJsonPath('message', 'Your machine has been registered for warranty coverage.')
        ->assertJsonPath('data.serial_number', 'H2-12345-ABCD');

    $this->assertDatabaseHas('warranty_registrations', [
        'customer_name' => 'Jane Owner',
        'email' => 'jane@example.com',
        'serial_number' => 'H2-12345-ABCD',
    ]);

    Mail::assertSent(WarrantyRegistrationConfirmation::class, function (WarrantyRegistrationConfirmation $mail) {
        return $mail->hasTo('jane@example.com')
            && $mail->registration->serial_number === 'H2-12345-ABCD'
            && $mail->registration->customer_name === 'Jane Owner'
            && $mail->registration->purchased_from === 'H2Systems Demo';
    });
});

it('sends a warranty confirmation email with registration details', function () {
    Mail::fake();

    $payload = [
        'customer_name' => 'Jane Owner',
        'email' => 'jane@example.com',
        'phone' => '555-0100',
        'serial_number' => 'H2-EMAIL-001',
        'machine_model' => 'H2 Hydrogen Water Machine',
        'purchase_date' => '2026-06-01',
        'purchased_from' => 'H2Systems Demo',
    ];

    $this->postJson('/api/warranty-registrations', $payload)
        ->assertCreated();

    Mail::assertSent(WarrantyRegistrationConfirmation::class, function (WarrantyRegistrationConfirmation $mail) {
        $rendered = $mail->render();

        return $mail->hasTo('jane@example.com')
            && str_contains($rendered, 'Jane Owner')
            && str_contains($rendered, 'H2-EMAIL-001')
            && str_contains($rendered, 'June 1, 2026')
            && str_contains($rendered, 'H2Systems Demo')
            && str_contains($rendered, 'Date Of Registration');
    });
});

it('rejects duplicate serial numbers for warranty registration', function () {
    WarrantyRegistration::create([
        'customer_name' => 'Existing Owner',
        'email' => 'existing@example.com',
        'phone' => '555-0199',
        'serial_number' => 'H2-DUPLICATE',
        'machine_model' => 'H2 Hydrogen Water Machine',
        'purchase_date' => '2026-05-01',
    ]);

    $this->postJson('/api/warranty-registrations', [
        'customer_name' => 'Another Owner',
        'email' => 'another@example.com',
        'phone' => '555-0200',
        'serial_number' => 'H2-DUPLICATE',
        'machine_model' => 'H2 Hydrogen Water Machine',
        'purchase_date' => '2026-06-02',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['serial_number']);
});

it('rejects duplicate serial numbers regardless of letter casing', function () {
    WarrantyRegistration::create([
        'customer_name' => 'Existing Owner',
        'email' => 'existing@example.com',
        'phone' => '555-0199',
        'serial_number' => 'h2-case-test',
        'machine_model' => 'H2 Hydrogen Water Machine',
        'purchase_date' => '2026-05-01',
    ]);

    $this->postJson('/api/warranty-registrations', [
        'customer_name' => 'Another Owner',
        'email' => 'another@example.com',
        'phone' => '555-0200',
        'serial_number' => ' H2-CASE-TEST ',
        'machine_model' => 'H2 Hydrogen Water Machine',
        'purchase_date' => '2026-06-02',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['serial_number']);
});

it('checks whether a serial number is available before registration', function () {
    WarrantyRegistration::create([
        'customer_name' => 'Existing Owner',
        'email' => 'existing@example.com',
        'phone' => '555-0199',
        'serial_number' => 'H2-CHECK-001',
        'machine_model' => 'H2 Hydrogen Water Machine',
        'purchase_date' => '2026-05-01',
    ]);

    $this->getJson('/api/warranty-registrations/check-serial?serial_number=H2-NEW-001')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('serial_number', 'H2-NEW-001')
        ->assertJsonPath('message', 'This serial number is available for registration.');

    $this->getJson('/api/warranty-registrations/check-serial?serial_number=h2-check-001')
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('serial_number', 'H2-CHECK-001')
        ->assertJsonPath('message', 'This serial number has already been registered for warranty.');
});

it('validates required warranty registration fields', function () {
    $this->postJson('/api/warranty-registrations', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'customer_name',
            'email',
            'phone',
            'serial_number',
            'machine_model',
            'purchase_date',
        ]);
});
