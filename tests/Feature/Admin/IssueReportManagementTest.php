<?php

use App\Enums\IssueReportSeverity;
use App\Enums\IssueReportSite;
use App\Enums\IssueReportSource;
use App\Enums\IssueReportStatus;
use App\Mail\IssueReportReceived;
use App\Mail\IssueReportStatusUpdated;
use App\Models\IssueReport;
use App\Models\Role;
use App\Models\User;
use App\Support\FrontendUrl;
use App\Support\Navigation\AppNavigation;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Mail;

function issueReportAdminPayload(array $overrides = []): array
{
    return array_merge([
        'reporter_name' => 'Casey Owner',
        'reporter_email' => 'casey@example.com',
        'reporter_phone' => '555-0199',
        'site' => IssueReportSite::Backend->value,
        'category' => 'error',
        'severity' => IssueReportSeverity::Critical->value,
        'title' => 'Installer page fails to load',
        'description' => 'Opening the installer detail page returns a 500 error.',
        'page_url' => 'http://localhost:8000/admin/installers/1',
        'status' => IssueReportStatus::New->value,
        'admin_notes' => 'Seen in staging logs.',
        'notify_reporter' => '1',
    ], $overrides);
}

it('allows a super-admin to manage issue reports and emails the reporter on status changes', function () {
    Mail::fake();

    config([
        'frontend.url' => 'http://localhost:8000',
        'frontend.environment_label' => 'Local',
    ]);

    $admin = superAdminUser(['name' => 'Admin User']);
    $assignee = User::factory()->create(['name' => 'Pat Assignee']);

    $this->actingAs($admin)
        ->get(route('admin.issue-reports.index'))
        ->assertOk()
        ->assertSee('Issue Reports')
        ->assertSee('http://localhost:8000/report-issue');

    $this->actingAs($admin)
        ->get(route('admin.issue-reports.create'))
        ->assertOk()
        ->assertSee('Log Issue');

    $this->actingAs($admin)
        ->post(route('admin.issue-reports.store'), issueReportAdminPayload([
            'assigned_to_user_id' => $assignee->id,
        ]))
        ->assertRedirect();

    $report = IssueReport::query()->first();

    expect($report)->not->toBeNull()
        ->and($report->source)->toBe(IssueReportSource::Admin)
        ->and($report->status)->toBe(IssueReportStatus::New)
        ->and($report->assigned_to_user_id)->toBe($assignee->id)
        ->and($report->admin_notes)->toBe('Seen in staging logs.');

    Mail::assertSent(IssueReportReceived::class, function (IssueReportReceived $mail) {
        return $mail->hasTo('casey@example.com');
    });

    $this->actingAs($admin)
        ->get(route('admin.issue-reports.show', $report))
        ->assertOk()
        ->assertSee($report->reference_code)
        ->assertSee('Casey Owner')
        ->assertSee('Installer page fails to load');

    $this->actingAs($admin)
        ->get(route('admin.issue-reports.edit', $report))
        ->assertOk()
        ->assertSee('Edit Issue Report');

    $this->actingAs($admin)
        ->put(route('admin.issue-reports.update', $report), issueReportAdminPayload([
            'status' => IssueReportStatus::Resolved->value,
            'assigned_to_user_id' => $assignee->id,
            'resolution_summary' => 'The installer query was fixed.',
        ]))
        ->assertRedirect(route('admin.issue-reports.show', $report));

    expect($report->fresh()->status)->toBe(IssueReportStatus::Resolved)
        ->and($report->fresh()->resolution_summary)->toBe('The installer query was fixed.');

    Mail::assertSent(IssueReportStatusUpdated::class, function (IssueReportStatusUpdated $mail) use ($report) {
        return $mail->hasTo('casey@example.com')
            && $mail->report->is($report)
            && $mail->report->status === IssueReportStatus::Resolved;
    });

    $this->actingAs($admin)
        ->from(route('admin.issue-reports.show', $report))
        ->put(route('admin.issue-reports.update', $report), issueReportAdminPayload([
            'status' => IssueReportStatus::Resolved->value,
            'assigned_to_user_id' => $assignee->id,
            'resolution_summary' => 'The installer query was fixed.',
            'admin_notes' => 'Internal follow-up only.',
        ]))
        ->assertRedirect(route('admin.issue-reports.show', $report));

    Mail::assertSent(IssueReportStatusUpdated::class, 1);

    $this->actingAs($admin)
        ->delete(route('admin.issue-reports.destroy', $report))
        ->assertRedirect(route('admin.issue-reports.index'));

    $this->assertDatabaseMissing('issue_reports', [
        'id' => $report->id,
    ]);
});

it('forbids non-super-admins from issue report CRUD', function () {
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->value('id'));

    $report = IssueReport::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.issue-reports.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.issue-reports.show', $report))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('admin.issue-reports.store'), issueReportAdminPayload())
        ->assertForbidden();
});

it('lets any authenticated portal user submit an issue report', function () {
    Mail::fake();
    $this->seed(RolesSeeder::class);

    $member = User::factory()->create([
        'name' => 'Morgan Member',
        'email' => 'morgan@example.com',
    ]);
    $member->roles()->attach(Role::query()->where('slug', 'member')->value('id'));

    $this->actingAs($member)
        ->get(route('issue-reports.create'))
        ->assertOk()
        ->assertSee('Report An Issue')
        ->assertSee('Morgan Member');

    $this->actingAs($member)
        ->post(route('issue-reports.store'), issueReportAdminPayload([
            'reporter_name' => 'Morgan Member',
            'reporter_email' => 'morgan@example.com',
            'site' => IssueReportSite::Backend->value,
        ]))
        ->assertRedirect(route('issue-reports.create'))
        ->assertSessionHas('status');

    $report = IssueReport::query()->first();

    expect($report->source)->toBe(IssueReportSource::Portal)
        ->and($report->user_id)->toBe($member->id)
        ->and($report->reporter_email)->toBe('morgan@example.com');

    Mail::assertSent(IssueReportReceived::class, function (IssueReportReceived $mail) {
        return $mail->hasTo('morgan@example.com');
    });
});

it('hides issue reports from regular admins and shows them to super-admins', function () {
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->value('id'));

    $superAdmin = superAdminUser();

    expect(collect(AppNavigation::links($admin))->pluck('key'))
        ->not->toContain('issue-reports')
        ->and(collect(AppNavigation::links($superAdmin))->pluck('key'))
        ->toContain('issue-reports')
        ->and(collect(AppNavigation::links($admin))->pluck('key'))
        ->toContain('report-issue');
});

it('resolves the public issue report url by environment defaults', function () {
    config(['frontend.url' => 'http://localhost:8000']);
    expect(FrontendUrl::issueReport())->toBe('http://localhost:8000/report-issue');

    config(['frontend.url' => 'https://www.molecularh2water.com']);
    expect(FrontendUrl::issueReport())->toBe('https://www.molecularh2water.com/report-issue');
});
