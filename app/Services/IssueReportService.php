<?php

namespace App\Services;

use App\Enums\IssueReportCategory;
use App\Enums\IssueReportSeverity;
use App\Enums\IssueReportSite;
use App\Enums\IssueReportSource;
use App\Enums\IssueReportStatus;
use App\Mail\IssueReportReceived;
use App\Mail\IssueReportStatusUpdated;
use App\Models\IssueReport;
use App\Models\IssueReportStatusUpdate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class IssueReportService
{
    public function __construct(
        private readonly EmailMappingService $emailMappings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function submissionRules(): array
    {
        return [
            'reporter_name' => ['required', 'string', 'max:120'],
            'reporter_email' => ['required', 'email', 'max:255'],
            'reporter_phone' => ['nullable', 'string', 'max:50'],
            'site' => ['required', Rule::enum(IssueReportSite::class)],
            'category' => ['required', Rule::enum(IssueReportCategory::class)],
            'severity' => ['required', Rule::enum(IssueReportSeverity::class)],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:8000'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'steps_to_reproduce' => ['nullable', 'string', 'max:5000'],
            'expected_behavior' => ['nullable', 'string', 'max:2000'],
            'actual_behavior' => ['nullable', 'string', 'max:2000'],
            'browser' => ['nullable', 'string', 'max:120'],
            'device' => ['nullable', 'string', 'max:120'],
            'screenshot' => ['nullable', 'image', 'max:10240'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminRules(): array
    {
        return array_merge(self::submissionRules(), [
            'status' => ['required', Rule::enum(IssueReportStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:8000'],
            'resolution_summary' => ['nullable', 'string', 'max:4000'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notify_reporter' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function submit(
        array $attributes,
        IssueReportSource $source,
        ?UploadedFile $screenshot = null,
        ?User $user = null,
    ): IssueReport {
        $report = IssueReport::create([
            ...Arr::only($attributes, [
                'reporter_name',
                'reporter_email',
                'reporter_phone',
                'site',
                'category',
                'severity',
                'title',
                'description',
                'page_url',
                'steps_to_reproduce',
                'expected_behavior',
                'actual_behavior',
                'browser',
                'device',
            ]),
            'user_id' => $user?->id,
            'source' => $source,
            'status' => IssueReportStatus::New,
            'screenshot_path' => $this->storeScreenshot($screenshot),
        ]);

        $this->recordStatusChange(
            $report,
            from: null,
            to: IssueReportStatus::New,
            actor: $user,
            note: 'Issue report submitted.',
            notifyReporter: true,
        );

        Mail::to($report->reporter_email)->send(new IssueReportReceived($report->fresh()));
        $this->emailMappings->notifyIssueReport($report->fresh());

        $report->forceFill(['last_reporter_notified_at' => now()])->save();

        return $report->fresh(['reporter', 'assignee', 'statusUpdates.actor']) ?? $report;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        IssueReport $report,
        array $attributes,
        User $actor,
        bool $notifyReporter = true,
        ?UploadedFile $screenshot = null,
    ): IssueReport {
        $currentStatus = $report->status;
        $nextStatus = $attributes['status'] instanceof IssueReportStatus
            ? $attributes['status']
            : IssueReportStatus::from((string) $attributes['status']);

        if ($screenshot) {
            $report->deleteScreenshot();
            $attributes['screenshot_path'] = $this->storeScreenshot($screenshot);
        }

        unset($attributes['screenshot'], $attributes['notify_reporter']);

        $report->fill(Arr::only($attributes, [
            'reporter_name',
            'reporter_email',
            'reporter_phone',
            'site',
            'category',
            'severity',
            'title',
            'description',
            'page_url',
            'steps_to_reproduce',
            'expected_behavior',
            'actual_behavior',
            'browser',
            'device',
            'screenshot_path',
            'status',
            'admin_notes',
            'resolution_summary',
            'assigned_to_user_id',
        ]));

        if ($currentStatus !== $nextStatus) {
            $report->status_changed_at = now();
            $report->resolved_at = $nextStatus === IssueReportStatus::Resolved ? now() : null;
            $report->closed_at = in_array($nextStatus, [IssueReportStatus::Closed, IssueReportStatus::WontFix], true)
                ? now()
                : null;
        }

        $report->save();

        if ($currentStatus !== $nextStatus) {
            $statusUpdate = $this->recordStatusChange(
                $report,
                from: $currentStatus,
                to: $nextStatus,
                actor: $actor,
                note: $report->resolution_summary,
                notifyReporter: $notifyReporter,
            );

            if ($notifyReporter) {
                Mail::to($report->reporter_email)->send(
                    new IssueReportStatusUpdated($report->fresh(), $statusUpdate),
                );
                $report->forceFill(['last_reporter_notified_at' => now()])->save();
            }
        }

        return $report->fresh(['reporter', 'assignee', 'statusUpdates.actor']) ?? $report;
    }

    public function delete(IssueReport $report): void
    {
        $report->delete();
    }

    private function storeScreenshot(?UploadedFile $screenshot): ?string
    {
        if (! $screenshot) {
            return null;
        }

        return $screenshot->store('issue-reports', 'public');
    }

    private function recordStatusChange(
        IssueReport $report,
        ?IssueReportStatus $from,
        IssueReportStatus $to,
        ?User $actor,
        ?string $note,
        bool $notifyReporter,
    ): IssueReportStatusUpdate {
        return $report->statusUpdates()->create([
            'user_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'notified_reporter' => $notifyReporter,
        ]);
    }
}
