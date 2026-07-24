<?php

use App\Enums\NotifiableForm;
use App\Models\EmailMapping;
use Illuminate\Support\Facades\File;

it('allows an admin to manage email mappings', function () {
    $admin = superAdminUser(['name' => 'Admin User']);

    $mapping = EmailMapping::factory()->create([
        'form_key' => NotifiableForm::ContactUs,
        'email' => 'ops@example.com',
        'name' => 'Ops Desk',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.email-mappings.index'))
        ->assertOk()
        ->assertSee('Email Mappings')
        ->assertSee('ops@example.com')
        ->assertSee('Contact Us');

    $this->actingAs($admin)
        ->get(route('admin.email-mappings.create'))
        ->assertOk()
        ->assertSee('Add mapping');

    $this->actingAs($admin)
        ->post(route('admin.email-mappings.store'), [
            'form_key' => NotifiableForm::WarrantyRegistration->value,
            'emails' => [
                'warranty@example.com',
                'warranty.backup@example.com',
            ],
            'name' => 'Warranty Desk',
            'is_active' => '1',
            'notes' => 'Notify on warranty submissions',
        ])
        ->assertRedirect(route('admin.email-mappings.index'))
        ->assertSessionHas('status', '2 email mappings created.');

    $this->assertDatabaseHas('email_mappings', [
        'email' => 'warranty@example.com',
        'form_key' => NotifiableForm::WarrantyRegistration->value,
        'is_active' => 1,
    ]);
    $this->assertDatabaseHas('email_mappings', [
        'email' => 'warranty.backup@example.com',
        'form_key' => NotifiableForm::WarrantyRegistration->value,
        'name' => 'Warranty Desk',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.email-mappings.edit', $mapping))
        ->assertOk()
        ->assertSee('Edit mapping')
        ->assertSee('Add email');

    $this->actingAs($admin)
        ->put(route('admin.email-mappings.update', $mapping), [
            'form_key' => NotifiableForm::ContactUs->value,
            'emails' => [
                'ops.updated@example.com',
                'ops.backup@example.com',
            ],
            'name' => 'Ops Updated',
            'is_active' => '0',
            'notes' => 'Paused',
        ])
        ->assertRedirect(route('admin.email-mappings.index'))
        ->assertSessionHas('status', '2 email mappings updated.');

    $this->assertDatabaseHas('email_mappings', [
        'form_key' => NotifiableForm::ContactUs->value,
        'email' => 'ops.updated@example.com',
        'is_active' => 0,
    ]);
    $this->assertDatabaseHas('email_mappings', [
        'form_key' => NotifiableForm::ContactUs->value,
        'email' => 'ops.backup@example.com',
        'name' => 'Ops Updated',
    ]);
    $this->assertDatabaseMissing('email_mappings', [
        'email' => 'ops@example.com',
    ]);

    $updated = EmailMapping::query()
        ->where('email', 'ops.updated@example.com')
        ->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('admin.email-mappings.destroy', $updated))
        ->assertRedirect(route('admin.email-mappings.index'));

    $this->assertDatabaseMissing('email_mappings', [
        'id' => $updated->id,
    ]);
});

it('loads all form recipients when editing a mapping', function () {
    $admin = superAdminUser();

    $primary = EmailMapping::factory()->create([
        'form_key' => NotifiableForm::ContactUs,
        'email' => 'one@example.com',
    ]);
    EmailMapping::factory()->create([
        'form_key' => NotifiableForm::ContactUs,
        'email' => 'two@example.com',
    ]);
    EmailMapping::factory()->create([
        'form_key' => NotifiableForm::WarrantyRegistration,
        'email' => 'other-form@example.com',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.email-mappings.edit', $primary))
        ->assertOk()
        ->assertSee('one@example.com')
        ->assertSee('two@example.com')
        ->assertDontSee('other-form@example.com');
});

it('prevents duplicate email mappings for the same form', function () {
    $admin = superAdminUser();

    EmailMapping::factory()->create([
        'form_key' => NotifiableForm::InstallationQuestionnaire,
        'email' => 'shipping@happycooking.com',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.email-mappings.store'), [
            'form_key' => NotifiableForm::InstallationQuestionnaire->value,
            'emails' => ['shipping@happycooking.com'],
            'name' => 'Duplicate',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('emails.0');
});

it('creates multiple recipients for the same form in one submit', function () {
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.email-mappings.store'), [
            'form_key' => NotifiableForm::ContactUs->value,
            'emails' => [
                'alpha@example.com',
                'beta@example.com',
                'alpha@example.com',
            ],
            'name' => 'Contact Inbox',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.email-mappings.index'))
        ->assertSessionHas('status', '2 email mappings created.');

    expect(
        EmailMapping::query()
            ->where('form_key', NotifiableForm::ContactUs)
            ->whereIn('email', ['alpha@example.com', 'beta@example.com'])
            ->count()
    )->toBe(2);
});

it('updates the email mappings seeder from current records', function () {
    $admin = superAdminUser(['name' => 'Admin User']);

    $mapping = EmailMapping::factory()->create([
        'form_key' => NotifiableForm::InstallationQuestionnaire,
        'email' => 'shipping@happycooking.com',
        'name' => 'Shipping Team',
        'is_active' => true,
        'notes' => 'Seeder note.',
    ]);

    File::shouldReceive('put')
        ->once()
        ->withArgs(function (string $path, string $contents) use ($mapping) {
            expect($path)->toBe(database_path('seeders/EmailMappingsSeeder.php'));

            return str_contains($contents, 'class EmailMappingsSeeder')
                && str_contains($contents, "DB::table('email_mappings')->updateOrInsert")
                && str_contains($contents, "'id' => {$mapping->id}")
                && str_contains($contents, "'email' => 'shipping@happycooking.com'")
                && str_contains($contents, "'form_key' => 'installation_questionnaire'")
                && str_contains($contents, "'name' => 'Shipping Team'");
        })
        ->andReturn(1);

    $this->actingAs($admin)
        ->post(route('admin.email-mappings.update-seeder'))
        ->assertRedirect(route('admin.email-mappings.index'))
        ->assertSessionHas('status', 'EmailMappingsSeeder.php updated with 1 mapping.');
});
