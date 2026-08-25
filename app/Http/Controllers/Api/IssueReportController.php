<?php

namespace App\Http\Controllers\Api;

use App\Enums\IssueReportSource;
use App\Http\Controllers\Controller;
use App\Models\IssueReport;
use App\Services\IssueReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueReportController extends Controller
{
    public function __construct(
        private readonly IssueReportService $issueReports,
    ) {}

    public function store(Request $request): JsonResponse
    {
        if ($request->filled(config('crm.capture.honeypot_field', 'company_website'))) {
            return response()->json([
                'message' => 'Thank you. Your issue report has been received.',
                'data' => [
                    'reference_code' => 'IR-00000',
                ],
            ], 201);
        }

        $validated = $request->validate(IssueReportService::submissionRules());

        $report = $this->issueReports->submit(
            $validated,
            IssueReportSource::PublicWebsite,
            $request->file('screenshot'),
        );

        return response()->json([
            'message' => 'Thank you. Your issue report has been received and we emailed a confirmation to '.$report->reporter_email.'.',
            'data' => [
                'id' => $report->id,
                'reference_code' => $report->reference_code,
                'status' => $report->status->value,
                'status_label' => $report->status->label(),
            ],
        ], 201);
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference_code' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $report = IssueReport::query()
            ->where('reference_code', strtoupper(trim($validated['reference_code'])))
            ->whereRaw('LOWER(reporter_email) = ?', [strtolower(trim($validated['email']))])
            ->first();

        if (! $report) {
            return response()->json([
                'message' => 'No issue report matched that reference number and email.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'reference_code' => $report->reference_code,
                'title' => $report->title,
                'status' => $report->status->value,
                'status_label' => $report->status->label(),
                'status_message' => $report->status->reporterMessage(),
                'site' => $report->site->label(),
                'category' => $report->category->label(),
                'severity' => $report->severity->label(),
                'resolution_summary' => $report->resolution_summary,
                'submitted_at' => $report->created_at?->toIso8601String(),
                'updated_at' => $report->status_changed_at?->toIso8601String(),
            ],
        ]);
    }
}
