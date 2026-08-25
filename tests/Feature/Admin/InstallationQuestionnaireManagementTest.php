<?php

use App\Models\InstallationQuestionnaire;
use App\Support\FrontendUrl;

it('allows an admin to manage installation questionnaires', function () {
    config([
        'frontend.url' => 'http://localhost:8000',
        'frontend.environment_label' => 'Local',
    ]);

    $admin = superAdminUser(['name' => 'Admin User']);

    $questionnaire = InstallationQuestionnaire::create([
        'first_name' => 'Sam',
        'last_name' => 'Owner',
        'email' => 'sam@example.com',
        'phone' => '555-0199',
        'street_address' => '88 Lake Rd',
        'city' => 'Seattle',
        'state' => 'WA',
        'postal_code' => '98101',
        'country' => 'United States',
        'property_type' => 'Townhouse',
        'existing_equipment' => ['Water Softener'],
        'ownership' => 'rent',
        'water_source' => 'Well',
        'special_requirements' => 'Narrow hallway',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.index'))
        ->assertOk()
        ->assertSee('http://localhost:8000/installation')
        ->assertSee('Customer installation submissions')
        ->assertSee('Seller')
        ->assertSee('Sam Owner');

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.show', $questionnaire))
        ->assertOk()
        ->assertSee('Sam Owner')
        ->assertSee('88 Lake Rd');

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.edit', $questionnaire))
        ->assertOk()
        ->assertSee('Edit submission');

    $this->actingAs($admin)
        ->put(route('admin.installation-questionnaires.update', $questionnaire), [
            'first_name' => 'Samantha',
            'last_name' => 'Owner',
            'email' => 'sam@example.com',
            'phone' => '555-0199',
            'street_address' => '88 Lake Rd',
            'city' => 'Seattle',
            'state' => 'WA',
            'postal_code' => '98101',
            'country' => 'United States',
            'property_type' => 'Condo',
            'existing_equipment' => ['Water Softener'],
            'ownership' => 'own',
            'water_source' => 'Well',
            'special_requirements' => 'Narrow hallway',
            'additional_notes' => 'Updated note',
        ])
        ->assertRedirect(route('admin.installation-questionnaires.show', $questionnaire));

    expect($questionnaire->fresh()->first_name)->toBe('Samantha')
        ->and($questionnaire->fresh()->property_type)->toBe('Condo');

    $this->actingAs($admin)
        ->delete(route('admin.installation-questionnaires.destroy', $questionnaire))
        ->assertRedirect(route('admin.installation-questionnaires.index'));

    $this->assertDatabaseMissing('installation_questionnaires', [
        'id' => $questionnaire->id,
    ]);
});

it('resolves frontend installation urls by environment defaults', function () {
    config([
        'frontend.url' => 'http://localhost:8000',
    ]);

    expect(FrontendUrl::installationQuestionnaire())->toBe('http://localhost:8000/installation');

    config(['frontend.url' => 'https://staging.molecularh2water.com']);
    expect(FrontendUrl::installationQuestionnaire())->toBe('https://staging.molecularh2water.com/installation');

    config(['frontend.url' => 'https://www.molecularh2water.com']);
    expect(FrontendUrl::installationQuestionnaire())->toBe('https://www.molecularh2water.com/installation');
});

it('shows the seller on the installation questionnaire listing', function () {
    $admin = superAdminUser();
    $seller = \App\Models\User::factory()->create(['name' => 'Morgan Field Seller']);
    $seller->roles()->attach(
        \App\Models\Role::query()->where('slug', 'consultant')->value('id')
    );

    $questionnaire = InstallationQuestionnaire::create([
        'first_name' => 'Sam',
        'last_name' => 'Owner',
        'email' => 'sam@example.com',
        'phone' => '555-0199',
        'street_address' => '88 Lake Rd',
        'city' => 'Seattle',
        'state' => 'WA',
        'postal_code' => '98101',
        'country' => 'United States',
        'property_type' => 'Townhouse',
        'existing_equipment' => ['Water Softener'],
        'ownership' => 'own',
        'water_source' => 'Well',
        'seller_id' => $seller->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.index'))
        ->assertOk()
        ->assertSee('Morgan Field Seller')
        ->assertSee('Seller');

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.index', ['seller_id' => $seller->id]))
        ->assertOk()
        ->assertSee('Sam Owner')
        ->assertSee('Morgan Field Seller');

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.show', $questionnaire))
        ->assertOk()
        ->assertSee('Morgan Field Seller');

    $this->actingAs($admin)
        ->put(route('admin.installation-questionnaires.update', $questionnaire), [
            'first_name' => 'Sam',
            'last_name' => 'Owner',
            'email' => 'sam@example.com',
            'phone' => '555-0199',
            'street_address' => '88 Lake Rd',
            'city' => 'Seattle',
            'state' => 'WA',
            'postal_code' => '98101',
            'country' => 'United States',
            'property_type' => 'Townhouse',
            'existing_equipment' => ['Water Softener'],
            'ownership' => 'own',
            'water_source' => 'Well',
            'seller_id' => $admin->id,
        ])
        ->assertRedirect(route('admin.installation-questionnaires.show', $questionnaire));

    expect($questionnaire->fresh()->seller_id)->toBe($admin->id);
});

it('allows an admin to assign reassign and unassign an installer from a questionnaire', function () {
    Illuminate\Support\Facades\Mail::fake();

    $admin = superAdminUser();
    $nearbyInstaller = \App\Models\Installer::factory()->create([
        'name' => 'Jordan Nearby',
        'city' => 'Seattle',
        'state' => 'WA',
        'company' => 'Cascade Installs',
    ]);
    $otherInstaller = \App\Models\Installer::factory()->create([
        'name' => 'Riley Remote',
        'city' => 'Austin',
        'state' => 'TX',
    ]);
    $archivedInstaller = \App\Models\Installer::factory()->archived()->create([
        'name' => 'Archived Installer',
    ]);

    $questionnaire = InstallationQuestionnaire::create([
        'first_name' => 'Sam',
        'last_name' => 'Owner',
        'email' => 'sam@example.com',
        'phone' => '555-0199',
        'street_address' => '88 Lake Rd',
        'city' => 'Seattle',
        'state' => 'WA',
        'postal_code' => '98101',
        'country' => 'United States',
        'property_type' => 'Townhouse',
        'existing_equipment' => ['Water Softener'],
        'ownership' => 'own',
        'water_source' => 'Municipal (connected to the city)',
        'special_requirements' => 'Narrow hallway',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.index'))
        ->assertOk()
        ->assertSee('Unassigned')
        ->assertSee('Jordan Nearby');

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.show', $questionnaire))
        ->assertOk()
        ->assertSee('Assign an installer')
        ->assertSee('Nearby · Jordan Nearby')
        ->assertDontSee('Archived Installer');

    $this->actingAs($admin)
        ->post(route('admin.installation-questionnaires.assign-installer', $questionnaire), [
            'installer_id' => $nearbyInstaller->id,
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'notes' => 'Call before arrival.',
        ])
        ->assertRedirect(route('admin.installation-questionnaires.show', $questionnaire));

    $questionnaire->refresh();
    $job = \App\Models\InstallerInstallation::query()
        ->where('installation_questionnaire_id', $questionnaire->id)
        ->first();

    expect($questionnaire->installer_id)->toBe($nearbyInstaller->id)
        ->and($questionnaire->assignment_notes)->toBe('Call before arrival.')
        ->and($questionnaire->assigned_by_user_id)->toBe($admin->id)
        ->and($questionnaire->assigned_at)->not->toBeNull()
        ->and($job)->not->toBeNull()
        ->and($job->installer_id)->toBe($nearbyInstaller->id)
        ->and($job->customer_name)->toBe('Sam Owner')
        ->and($job->city)->toBe('Seattle')
        ->and($job->state)->toBe('WA')
        ->and($job->status)->toBe(\App\Enums\InstallerInstallationStatus::Scheduled)
        ->and($job->assignment_response)->toBe(\App\Enums\InstallerAssignmentResponse::Pending)
        ->and($questionnaire->assignment_response)->toBe(\App\Enums\InstallerAssignmentResponse::Pending)
        ->and($job->notes)->toContain('Call before arrival.')
        ->and($job->notes)->toContain('Narrow hallway');

    Illuminate\Support\Facades\Mail::assertSent(\App\Mail\InstallerAssignmentOffered::class, function ($mail) use ($nearbyInstaller) {
        return $mail->hasTo($nearbyInstaller->email);
    });

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.index', ['assignment' => 'assigned']))
        ->assertOk()
        ->assertSee('Jordan Nearby')
        ->assertDontSee('Choose installer');

    $this->actingAs($admin)
        ->post(route('admin.installation-questionnaires.assign-installer', $questionnaire), [
            'installer_id' => $archivedInstaller->id,
        ])
        ->assertSessionHasErrors('installer_id');

    $this->actingAs($admin)
        ->post(route('admin.installation-questionnaires.assign-installer', $questionnaire), [
            'installer_id' => $otherInstaller->id,
            'notes' => 'Coverage change.',
        ])
        ->assertRedirect(route('admin.installation-questionnaires.show', $questionnaire));

    $job->refresh();
    $questionnaire->refresh();

    expect($questionnaire->installer_id)->toBe($otherInstaller->id)
        ->and($job->installer_id)->toBe($otherInstaller->id)
        ->and(\App\Models\InstallerInstallation::query()->count())->toBe(1);

    $this->actingAs($admin)
        ->get(route('admin.installers.show', $otherInstaller))
        ->assertOk()
        ->assertSee('Sam Owner')
        ->assertSee('View installation questionnaire');

    $this->actingAs($admin)
        ->post(route('admin.installation-questionnaires.unassign-installer', $questionnaire))
        ->assertRedirect(route('admin.installation-questionnaires.show', $questionnaire));

    $questionnaire->refresh();
    $job->refresh();

    expect($questionnaire->installer_id)->toBeNull()
        ->and($questionnaire->assigned_at)->toBeNull()
        ->and($job->status)->toBe(\App\Enums\InstallerInstallationStatus::Cancelled);
});
