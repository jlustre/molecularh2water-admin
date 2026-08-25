<?php

use App\Enums\InstallerAssignmentRejectionReason;
use App\Enums\InstallerAssignmentResponse;
use App\Enums\InstallerInstallationStatus;
use App\Mail\InstallerAssignmentOffered;
use App\Mail\InstallerAssignmentResponded;
use App\Models\InstallationQuestionnaire;
use App\Models\Installer;
use App\Models\InstallerInstallation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

function assignedInstallationForEmailTests(?Installer $installer = null): array
{
    Mail::fake();
    Storage::fake('public');

    $admin = superAdminUser([
        'name' => 'Dispatch Admin',
        'email' => 'dispatch@example.com',
    ]);
    $seller = User::factory()->create([
        'name' => 'Casey Seller',
        'email' => 'casey.seller@example.com',
    ]);
    $installer ??= Installer::factory()->create([
        'name' => 'Jordan Nearby',
        'email' => 'jordan.installer@example.com',
        'city' => 'Seattle',
        'state' => 'WA',
    ]);

    Storage::disk('public')->put('installation-questionnaires/sink.jpg', 'photo-bytes');

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
        'additional_notes' => 'Dog in yard',
        'seller_id' => $seller->id,
        'sink_photos' => [[
            'path' => 'installation-questionnaires/sink.jpg',
            'original_name' => 'sink.jpg',
        ]],
    ]);

    test()->actingAs($admin)
        ->post(route('admin.installation-questionnaires.assign-installer', $questionnaire), [
            'installer_id' => $installer->id,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'notes' => 'Gate code 1234',
        ])
        ->assertRedirect(route('admin.installation-questionnaires.show', $questionnaire));

    $questionnaire->refresh();
    $job = InstallerInstallation::query()
        ->where('installation_questionnaire_id', $questionnaire->id)
        ->firstOrFail();

    return compact('admin', 'seller', 'installer', 'questionnaire', 'job');
}

it('emails the installer a compact assignment with accept and decline links', function () {
    [
        'installer' => $installer,
        'questionnaire' => $questionnaire,
        'job' => $job,
        'admin' => $admin,
    ] = assignedInstallationForEmailTests();

    Mail::assertSent(InstallerAssignmentOffered::class, function (InstallerAssignmentOffered $mail) use ($installer, $questionnaire, $job) {
        $html = $mail->render();

        return $mail->hasTo($installer->email)
            && $mail->questionnaire->is($questionnaire)
            && $mail->installation->is($job)
            && str_contains($html, 'Accept assignment')
            && str_contains($html, 'Decline with reason')
            && str_contains($html, 'Sam Owner')
            && str_contains($html, 'Casey Seller')
            && str_contains($html, '88 Lake Rd')
            && str_contains($html, 'Water Softener')
            && str_contains($html, 'Narrow hallway')
            && str_contains($html, 'Gate code 1234')
            && str_contains($html, 'sink.jpg')
            && str_contains($html, 'installation-assignments/'.$job->id.'/installers/'.$installer->id.'/accept')
            && str_contains($html, 'installation-assignments/'.$job->id.'/installers/'.$installer->id.'/reject');
    });

    test()->actingAs($admin)
        ->get(route('admin.installation-questionnaires.show', $questionnaire))
        ->assertOk()
        ->assertSee('Awaiting reply');
});

it('lets the installer accept the assignment from the signed email link', function () {
    [
        'admin' => $admin,
        'installer' => $installer,
        'questionnaire' => $questionnaire,
        'job' => $job,
    ] = assignedInstallationForEmailTests();

    $acceptUrl = URL::temporarySignedRoute('installation-assignments.accept', now()->addDay(), [
        'installation' => $job,
        'installer' => $installer,
    ]);

    $this->get($acceptUrl)
        ->assertOk()
        ->assertSee('Assignment accepted')
        ->assertSee('Sam Owner');

    $questionnaire->refresh();
    $job->refresh();

    expect($questionnaire->installer_id)->toBe($installer->id)
        ->and($questionnaire->assignment_response)->toBe(InstallerAssignmentResponse::Accepted)
        ->and($job->assignment_response)->toBe(InstallerAssignmentResponse::Accepted)
        ->and($job->status)->toBe(InstallerInstallationStatus::Scheduled);

    Mail::assertSent(InstallerAssignmentResponded::class, function (InstallerAssignmentResponded $mail) use ($admin) {
        $html = $mail->render();

        return $mail->hasTo($admin->email)
            && $mail->assignor?->is($admin)
            && str_contains($mail->envelope()->subject, 'Installer accepted')
            && str_contains($html, 'Hi Dispatch Admin')
            && str_contains($html, 'Accepted');
    });
    Mail::assertNotSent(InstallerAssignmentResponded::class, fn (InstallerAssignmentResponded $mail) => $mail->hasTo('casey.seller@example.com'));

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.show', $questionnaire))
        ->assertOk()
        ->assertSee('Accepted');

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.index'))
        ->assertOk()
        ->assertSee('Accepted');
});

it('lets the installer reject the assignment with a reason and unassigns the job', function () {
    [
        'admin' => $admin,
        'installer' => $installer,
        'questionnaire' => $questionnaire,
        'job' => $job,
    ] = assignedInstallationForEmailTests();

    $rejectUrl = URL::temporarySignedRoute('installation-assignments.reject', now()->addDay(), [
        'installation' => $job,
        'installer' => $installer,
    ]);

    $this->get($rejectUrl)
        ->assertOk()
        ->assertSee('Decline this job')
        ->assertSee('Schedule conflict');

    $storeUrl = URL::temporarySignedRoute('installation-assignments.reject.store', now()->addDay(), [
        'installation' => $job,
        'installer' => $installer,
    ]);

    $this->from($rejectUrl)
        ->post($storeUrl, [
            'reason' => InstallerAssignmentRejectionReason::ScheduleConflict->value,
            'notes' => 'Already booked that morning.',
        ])
        ->assertOk()
        ->assertSee('Assignment declined');

    $questionnaire->refresh();
    $job->refresh();

    expect($questionnaire->installer_id)->toBeNull()
        ->and($questionnaire->assignment_response)->toBe(InstallerAssignmentResponse::Rejected)
        ->and($questionnaire->assignment_rejection_reason)->toBe(InstallerAssignmentRejectionReason::ScheduleConflict)
        ->and($questionnaire->assignment_rejection_notes)->toBe('Already booked that morning.')
        ->and($job->status)->toBe(InstallerInstallationStatus::Cancelled)
        ->and($job->assignment_response)->toBe(InstallerAssignmentResponse::Rejected);

    Mail::assertSent(InstallerAssignmentResponded::class, function (InstallerAssignmentResponded $mail) use ($admin) {
        $html = $mail->render();

        return $mail->hasTo($admin->email)
            && $mail->assignor?->is($admin)
            && str_contains($mail->envelope()->subject, 'Installer declined')
            && str_contains($html, 'Hi Dispatch Admin')
            && str_contains($html, 'Declined')
            && str_contains($html, 'Schedule conflict');
    });
    Mail::assertNotSent(InstallerAssignmentResponded::class, fn (InstallerAssignmentResponded $mail) => $mail->hasTo('casey.seller@example.com'));

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.show', $questionnaire))
        ->assertOk()
        ->assertSee('Declined')
        ->assertSee('Last installer declined this job')
        ->assertSee('Schedule conflict');

    $this->actingAs($admin)
        ->get(route('admin.installation-questionnaires.index'))
        ->assertOk()
        ->assertSee('Declined');
});

it('requires notes when the installer rejects for another reason', function () {
    ['installer' => $installer, 'job' => $job] = assignedInstallationForEmailTests();

    $storeUrl = URL::temporarySignedRoute('installation-assignments.reject.store', now()->addDay(), [
        'installation' => $job,
        'installer' => $installer,
    ]);

    $this->from(URL::temporarySignedRoute('installation-assignments.reject', now()->addDay(), [
        'installation' => $job,
        'installer' => $installer,
    ]))
        ->post($storeUrl, [
            'reason' => InstallerAssignmentRejectionReason::Other->value,
        ])
        ->assertSessionHasErrors('notes');

    expect($job->fresh()->assignment_response)->toBe(InstallerAssignmentResponse::Pending)
        ->and($job->fresh()->questionnaire->installer_id)->toBe($installer->id);
});

it('rejects unsigned assignment links and ignores old links after reassignment', function () {
    [
        'admin' => $admin,
        'installer' => $installer,
        'questionnaire' => $questionnaire,
        'job' => $job,
    ] = assignedInstallationForEmailTests();

    $this->get(route('installation-assignments.accept', [
        'installation' => $job,
        'installer' => $installer,
    ]))->assertForbidden();

    $oldAcceptUrl = URL::temporarySignedRoute('installation-assignments.accept', now()->addDay(), [
        'installation' => $job,
        'installer' => $installer,
    ]);

    $replacement = Installer::factory()->create([
        'name' => 'Riley Remote',
        'email' => 'riley.remote@example.com',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.installation-questionnaires.assign-installer', $questionnaire), [
            'installer_id' => $replacement->id,
        ])
        ->assertRedirect();

    $this->get($oldAcceptUrl)
        ->assertOk()
        ->assertSee('Assignment no longer available');

    expect($job->fresh()->installer_id)->toBe($replacement->id)
        ->and($questionnaire->fresh()->assignment_response)->toBe(InstallerAssignmentResponse::Pending);
});

it('serves questionnaire photos from a signed installer link', function () {
    ['installer' => $installer, 'job' => $job] = assignedInstallationForEmailTests();

    $this->get(route('installation-assignments.photos.show', [
        'installation' => $job,
        'installer' => $installer,
        'photo' => 0,
    ]))->assertForbidden();

    $photoUrl = URL::temporarySignedRoute('installation-assignments.photos.show', now()->addDay(), [
        'installation' => $job,
        'installer' => $installer,
        'photo' => 0,
    ]);

    $this->get($photoUrl)
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename="sink.jpg"');
});

it('skips the assignment email when the installer has no email address', function () {
    Mail::fake();

    $admin = superAdminUser();
    $installer = Installer::factory()->create([
        'name' => 'No Email Installer',
        'email' => null,
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
        'existing_equipment' => [],
        'ownership' => 'own',
        'water_source' => 'Well',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.installation-questionnaires.assign-installer', $questionnaire), [
            'installer_id' => $installer->id,
        ])
        ->assertRedirect(route('admin.installation-questionnaires.show', $questionnaire))
        ->assertSessionHas('status', function ($status) {
            return str_contains($status, 'no email address');
        });

    Mail::assertNothingSent();
    expect($questionnaire->fresh()->installer_id)->toBe($installer->id);
});
