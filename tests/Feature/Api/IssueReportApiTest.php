<?php

use App\Enums\IssueReportCategory;
use App\Enums\IssueReportSeverity;
use App\Enums\IssueReportSite;
use App\Enums\IssueReportSource;
use App\Enums\IssueReportStatus;
use App\Enums\NotifiableForm;
use App\Mail\FormSubmissionAlert;
use App\Mail\IssueReportReceived;
use App\Models\EmailMapping;
use App\Models\IssueReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function issueReportPayload(array $overrides = []): array
{
    return array_merge([
        'reporter_name' => 'Jordan Visitor',
        'reporter_email' => 'jordan@example.com',
        'reporter_phone' => '555-0142',
        'site' => IssueReportSite::Frontend->value,
        'category' => IssueReportCategory::Bug->value,
        'severity' => IssueReportSeverity::High->value,
        'title' => 'Checkout button does nothing',
        'description' => 'Clicking the checkout button on the warranty page does not submit the form.',
        'page_url' => 'http://localhost:5174/warranty',
        'steps_to_reproduce' => 'Open warranty, fill the form, click submit.',
        'expected_behavior' => 'The form should submit.',
        'actual_behavior' => 'Nothing happens.',
        'browser' => 'Chrome',
        'device' => 'Windows desktop',
    ], $overrides);
}

it('stores a public issue report, emails the reporter, and notifies mapped staff', function () {
    Mail::fake();
    Storage::fake('public');

    EmailMapping::factory()->create([
        'form_key' => NotifiableForm::IssueReport,
        'email' => 'support-desk@example.com',
        'is_active' => true,
    ]);

    EmailMapping::factory()->inactive()->create([
        'form_key' => NotifiableForm::IssueReport,
        'email' => 'inactive@example.com',
    ]);

    $this->post('/api/issue-reports', issueReportPayload([
        'screenshot' => UploadedFile::fake()->image('broken-button.jpg'),
    ]), [
        'Accept' => 'application/json',
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', IssueReportStatus::New->value)
        ->assertJsonPath('data.status_label', 'New');

    $report = IssueReport::query()->first();

    expect($report)->not->toBeNull()
        ->and($report->reference_code)->toStartWith('IR-'.now()->format('Y').'-')
        ->and($report->reporter_email)->toBe('jordan@example.com')
        ->and($report->source)->toBe(IssueReportSource::PublicWebsite)
        ->and($report->status)->toBe(IssueReportStatus::New)
        ->and($report->screenshot_path)->not->toBeNull();

    Storage::disk('public')->assertExists($report->screenshot_path);

    Mail::assertSent(IssueReportReceived::class, function (IssueReportReceived $mail) use ($report) {
        return $mail->hasTo('jordan@example.com')
            && $mail->report->is($report);
    });

    Mail::assertSent(FormSubmissionAlert::class, function (FormSubmissionAlert $mail) {
        return $mail->hasTo('support-desk@example.com')
            && ! $mail->hasTo('inactive@example.com')
            && str_contains($mail->subjectLine, 'IR-');
    });
});

it('silently accepts honeypot issue report submissions', function () {
    Mail::fake();

    $this->postJson('/api/issue-reports', issueReportPayload([
        'company_website' => 'https://spam.test',
    ]))->assertCreated();

    $this->assertDatabaseCount('issue_reports', 0);
    Mail::assertNothingSent();
});

it('validates required issue report fields', function () {
    $this->postJson('/api/issue-reports', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'reporter_name',
            'reporter_email',
            'site',
            'category',
            'severity',
            'title',
            'description',
        ]);
});

it('looks up a public issue report by reference code and email', function () {
    $report = IssueReport::factory()->create([
        'reporter_email' => 'jordan@example.com',
        'title' => 'Checkout button does nothing',
        'status' => IssueReportStatus::InProgress,
    ]);

    $this->getJson('/api/issue-reports/lookup?'.http_build_query([
        'reference_code' => strtolower($report->reference_code),
        'email' => 'Jordan@example.com',
    ]))
        ->assertOk()
        ->assertJsonPath('data.reference_code', $report->reference_code)
        ->assertJsonPath('data.status', IssueReportStatus::InProgress->value)
        ->assertJsonPath('data.status_label', 'In Progress')
        ->assertJsonPath('data.title', 'Checkout button does nothing');
});

it('does not look up an issue report with the wrong email', function () {
    $report = IssueReport::factory()->create([
        'reporter_email' => 'jordan@example.com',
    ]);

    $this->getJson('/api/issue-reports/lookup?'.http_build_query([
        'reference_code' => $report->reference_code,
        'email' => 'other@example.com',
    ]))->assertNotFound();
});
