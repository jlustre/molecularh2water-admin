<?php

namespace App\Http\Controllers;

use App\Enums\InstallerAssignmentRejectionReason;
use App\Models\Installer;
use App\Models\InstallerInstallation;
use App\Services\Admin\InstallationQuestionnaireAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InstallationAssignmentResponseController extends Controller
{
    public function __construct(
        private readonly InstallationQuestionnaireAssignment $assignments,
    ) {}

    public function accept(InstallerInstallation $installation, Installer $installer): View
    {
        $state = $this->assignments->accept($installation, $installer);

        return $this->resultView($installation->fresh(['questionnaire']) ?? $installation, $installer, $state);
    }

    public function rejectForm(InstallerInstallation $installation, Installer $installer): View
    {
        $state = $this->assignments->offerState($installation, $installer);

        if ($state !== 'offered') {
            return $this->resultView($installation, $installer, $state);
        }

        return view('installation-assignments.reject', [
            'installation' => $installation,
            'installer' => $installer,
            'questionnaire' => $installation->questionnaire,
            'reasons' => InstallerAssignmentRejectionReason::options(),
            'storeUrl' => URL::temporarySignedRoute(
                'installation-assignments.reject.store',
                now()->addDays(14),
                [
                    'installation' => $installation,
                    'installer' => $installer,
                ],
            ),
        ]);
    }

    public function reject(
        Request $request,
        InstallerInstallation $installation,
        Installer $installer,
    ): View {
        $attributes = $request->validate([
            'reason' => ['required', Rule::enum(InstallerAssignmentRejectionReason::class)],
            'notes' => ['nullable', 'string', 'max:2000', 'required_if:reason,other'],
        ]);

        $state = $this->assignments->reject(
            $installation,
            $installer,
            InstallerAssignmentRejectionReason::from($attributes['reason']),
            filled($attributes['notes'] ?? null) ? trim($attributes['notes']) : null,
        );

        return $this->resultView($installation->fresh(['questionnaire']) ?? $installation, $installer, $state);
    }

    public function photo(
        InstallerInstallation $installation,
        Installer $installer,
        int $photo,
    ): BinaryFileResponse {
        abort_unless($this->assignments->canViewPhotos($installation, $installer), 403);

        $questionnaire = $installation->questionnaire;
        abort_unless($questionnaire, 404);

        $photos = $questionnaire->sinkPhotoItems();
        abort_unless(isset($photos[$photo]), 404);

        $path = $photos[$photo]['path'];
        abort_unless(Storage::disk('public')->exists($path), 404);

        $fileName = $photos[$photo]['original_name'] ?: basename($path);

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    private function resultView(
        InstallerInstallation $installation,
        Installer $installer,
        string $state,
    ): View {
        $copy = match ($state) {
            'accepted' => [
                'title' => 'Assignment accepted',
                'message' => 'Thanks. You are confirmed for this installation. The team has been notified.',
            ],
            'rejected' => [
                'title' => 'Assignment declined',
                'message' => 'Thanks for letting us know. This job has been released so another installer can be assigned.',
            ],
            default => [
                'title' => 'Assignment no longer available',
                'message' => 'This job was reassigned, cancelled, or the link is no longer valid.',
            ],
        };

        return view('installation-assignments.result', [
            ...$copy,
            'installation' => $installation,
            'installer' => $installer,
            'questionnaire' => $installation->questionnaire,
            'state' => $state,
        ]);
    }
}
