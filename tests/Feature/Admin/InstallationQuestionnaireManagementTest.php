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
