<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IssueReportCategory;
use App\Enums\IssueReportSeverity;
use App\Enums\IssueReportSite;
use App\Enums\IssueReportSource;
use App\Enums\IssueReportStatus;
use App\Http\Controllers\Controller;
use App\Models\IssueReport;
use App\Models\User;
use App\Services\IssueReportService;
use App\Support\FrontendUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IssueReportController extends Controller
{
    public function __construct(
        private readonly IssueReportService $issueReports,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $query = IssueReport::query()
            ->with(['assignee', 'reporter'])
            ->latest();

        if ($request->filled('search')) {
            $query->search($request->string('search')->toString());
        }

        if ($request->filled('status') && array_key_exists($request->string('status')->toString(), IssueReportStatus::options())) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('site') && array_key_exists($request->string('site')->toString(), IssueReportSite::options())) {
            $query->where('site', $request->string('site')->toString());
        }

        if ($request->filled('severity') && array_key_exists($request->string('severity')->toString(), IssueReportSeverity::options())) {
            $query->where('severity', $request->string('severity')->toString());
        }

        if ($request->filled('category') && array_key_exists($request->string('category')->toString(), IssueReportCategory::options())) {
            $query->where('category', $request->string('category')->toString());
        }

        $reports = $query->paginate(15)->withQueryString();

        return view('admin.issue-reports.index', [
            'reports' => $reports,
            'statuses' => IssueReportStatus::options(),
            'sites' => IssueReportSite::options(),
            'severities' => IssueReportSeverity::options(),
            'categories' => IssueReportCategory::options(),
            'statusCounts' => IssueReport::query()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
            'openCount' => IssueReport::query()->open()->count(),
            'totalCount' => IssueReport::query()->count(),
            'publicUrl' => FrontendUrl::issueReport(),
        ]);
    }

    public function create(): View
    {
        $this->ensureSuperAdmin();

        return view('admin.issue-reports.create', $this->formData(new IssueReport([
            'status' => IssueReportStatus::New,
            'site' => IssueReportSite::Frontend,
            'category' => IssueReportCategory::Bug,
            'severity' => IssueReportSeverity::Medium,
            'source' => IssueReportSource::Admin,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate(IssueReportService::adminRules());
        $validated['assigned_to_user_id'] = $validated['assigned_to_user_id'] ?: null;

        $report = $this->issueReports->submit(
            $validated,
            IssueReportSource::Admin,
            $request->file('screenshot'),
            $request->user(),
        );

        $this->issueReports->update(
            $report,
            $validated,
            $request->user(),
            $request->boolean('notify_reporter', true),
        );

        return redirect()
            ->route('admin.issue-reports.show', $report)
            ->with('status', 'Issue report created.');
    }

    public function show(IssueReport $issue_report): View
    {
        $this->ensureSuperAdmin();

        $issue_report->load(['assignee', 'reporter', 'statusUpdates.actor']);

        return view('admin.issue-reports.show', [
            'report' => $issue_report,
        ]);
    }

    public function edit(IssueReport $issue_report): View
    {
        $this->ensureSuperAdmin();

        return view('admin.issue-reports.edit', $this->formData($issue_report));
    }

    public function update(Request $request, IssueReport $issue_report): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate(IssueReportService::adminRules());
        $validated['assigned_to_user_id'] = $validated['assigned_to_user_id'] ?: null;

        $this->issueReports->update(
            $issue_report,
            $validated,
            $request->user(),
            $request->boolean('notify_reporter', true),
            $request->file('screenshot'),
        );

        return redirect()
            ->route('admin.issue-reports.show', $issue_report)
            ->with('status', 'Issue report updated. The reporter was emailed if the status changed.');
    }

    public function destroy(IssueReport $issue_report): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $this->issueReports->delete($issue_report);

        return redirect()
            ->route('admin.issue-reports.index')
            ->with('status', 'Issue report deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(IssueReport $report): array
    {
        return [
            'report' => $report,
            'statuses' => IssueReportStatus::options(),
            'sites' => IssueReportSite::options(),
            'categories' => IssueReportCategory::options(),
            'severities' => IssueReportSeverity::options(),
            'assignees' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ];
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}
