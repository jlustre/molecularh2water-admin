<?php

use App\Enums\InstallerInstallationStatus;
use App\Enums\InstallerStatus;
use App\Models\Installer;
use App\Models\InstallerInstallation;

it('allows an admin to manage installers and installation history', function () {
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.installers.index'))
        ->assertOk()
        ->assertSee('Installer Management')
        ->assertSee('Add Installer');

    $this->actingAs($admin)
        ->get(route('admin.installers.create'))
        ->assertOk()
        ->assertSee('Add installer')
        ->assertSee('CA — California', false)
        ->assertSee('value="CA"', false);

    $this->actingAs($admin)
        ->post(route('admin.installers.store'), [
            'name' => 'Alex Installer',
            'email' => 'alex.installer@example.com',
            'phone' => '(555) 222-3333',
            'company' => 'H2 Fit Crew',
            'city' => 'Spokane',
            'state' => 'WA',
            'status' => InstallerStatus::Active->value,
            'notes' => 'Serves eastern Washington.',
        ])
        ->assertRedirect();

    $installer = Installer::query()->where('email', 'alex.installer@example.com')->first();

    expect($installer)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.installers.show', $installer))
        ->assertOk()
        ->assertSee('Alex Installer')
        ->assertSee('(555) 222-3333')
        ->assertSee('Installation records');

    $this->actingAs($admin)
        ->post(route('admin.installers.installations.store', $installer), [
            'status' => InstallerInstallationStatus::Scheduled->value,
            'customer_name' => 'Pat Customer',
            'customer_email' => 'pat@example.com',
            'customer_phone' => '(555) 111-0000',
            'street_address' => '100 Main St',
            'city' => 'Spokane',
            'state' => 'WA',
            'postal_code' => '99201',
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'notes' => 'Bring spare fittings.',
        ])
        ->assertRedirect(route('admin.installers.show', $installer));

    $job = InstallerInstallation::query()->where('installer_id', $installer->id)->first();

    expect($job)->not->toBeNull()
        ->and($job->status)->toBe(InstallerInstallationStatus::Scheduled)
        ->and($job->customer_name)->toBe('Pat Customer');

    $this->actingAs($admin)
        ->put(route('admin.installers.installations.update', [$installer, $job]), [
            'status' => InstallerInstallationStatus::Completed->value,
            'customer_name' => 'Pat Customer',
            'customer_email' => 'pat@example.com',
            'customer_phone' => '(555) 111-0000',
            'street_address' => '100 Main St',
            'city' => 'Spokane',
            'state' => 'WA',
            'postal_code' => '99201',
            'scheduled_at' => $job->scheduled_at?->format('Y-m-d H:i:s'),
            'notes' => 'Finished cleanly.',
        ])
        ->assertRedirect(route('admin.installers.show', $installer));

    $job->refresh();

    expect($job->status)->toBe(InstallerInstallationStatus::Completed)
        ->and($job->completed_at)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.installers.show', [
            'installer' => $installer,
            'history_status' => InstallerInstallationStatus::Completed->value,
        ]))
        ->assertOk()
        ->assertSee('Pat Customer')
        ->assertSee('Completed');
});

it('archives restores and deletes an installer with history', function () {
    $admin = superAdminUser();
    $installer = Installer::factory()->create([
        'name' => 'Casey Installer',
        'email' => 'casey@example.com',
    ]);
    InstallerInstallation::factory()->completed()->create([
        'installer_id' => $installer->id,
        'customer_name' => 'History Customer',
    ]);
    InstallerInstallation::factory()->cancelled()->create([
        'installer_id' => $installer->id,
    ]);
    InstallerInstallation::factory()->rescheduled()->create([
        'installer_id' => $installer->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.installers.archive', $installer))
        ->assertRedirect(route('admin.installers.index'));

    $installer->refresh();

    expect($installer->status)->toBe(InstallerStatus::Archived)
        ->and($installer->archived_at)->not->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.installers.restore', $installer))
        ->assertRedirect(route('admin.installers.show', $installer));

    $installer->refresh();

    expect($installer->status)->toBe(InstallerStatus::Active)
        ->and($installer->archived_at)->toBeNull();

    $this->actingAs($admin)
        ->delete(route('admin.installers.destroy', $installer))
        ->assertRedirect(route('admin.installers.index'));

    $this->assertDatabaseMissing('installers', ['id' => $installer->id]);
    $this->assertDatabaseMissing('installer_installations', ['installer_id' => $installer->id]);
});

it('filters the installer directory by search and status', function () {
    $admin = superAdminUser();

    Installer::factory()->create([
        'name' => 'Active Tech',
        'phone' => '(509) 555-1000',
        'status' => InstallerStatus::Active,
    ]);
    Installer::factory()->archived()->create([
        'name' => 'Archived Tech',
        'phone' => '(509) 555-2000',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.installers.index', [
            'search' => 'Active Tech',
            'status' => InstallerStatus::Active->value,
        ]))
        ->assertOk()
        ->assertSee('Active Tech')
        ->assertDontSee('Archived Tech');
});
