<?php

namespace App\Http\Controllers;

use App\Enums\IssueReportCategory;
use App\Enums\IssueReportSeverity;
use App\Enums\IssueReportSite;
use App\Enums\IssueReportSource;
use App\Services\IssueReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IssueReportController extends Controller
{
    public function __construct(
        private readonly IssueReportService $issueReports,
    ) {}

    public function create(Request $request): View
    {
        $user = $request->user();

        return view('issue-reports.create', [
            'title' => 'Report An Issue',
            'header' => 'Report An Issue',
            'sites' => IssueReportSite::options(),
            'categories' => IssueReportCategory::options(),
            'severities' => IssueReportSeverity::options(),
            'defaults' => [
                'reporter_name' => $user?->name,
                'reporter_email' => $user?->email,
                'site' => IssueReportSite::Backend->value,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(IssueReportService::submissionRules());

        $report = $this->issueReports->submit(
            $validated,
            IssueReportSource::Portal,
            $request->file('screenshot'),
            $request->user(),
        );

        return redirect()
            ->route('issue-reports.create')
            ->with('status', 'Thanks. Your issue was logged as '.$report->reference_code.'. A confirmation email was sent to '.$report->reporter_email.'.');
    }
}
