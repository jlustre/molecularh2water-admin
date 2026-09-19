<?php

namespace App\Http\Controllers;

use App\Enums\InstallerAssignmentRejectionReason;
use App\Models\Installer;
use App\Models\InstallerInstallation;
use App\Services\Admin\InstallationQuestionnaireAssignment;
use App\Services\EmailMappingService;
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
        private readonly EmailMappingService $emailMappings,
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

    public function packet(InstallerInstallation $installation, Installer $installer): View
    {
        abort_unless($this->assignments->canViewPhotos($installation, $installer), 403);

        $installation->load([
            'installer',
            'questionnaire.seller',
        ]);

        $questionnaire = $installation->questionnaire;
        abort_unless($questionnaire, 404);

        $photoUrls = collect($questionnaire->sinkPhotoItems())
            ->map(fn (array $photo, int $index): array => [
                ...$photo,
                'url' => URL::temporarySignedRoute(
                    'installation-assignments.photos.show',
                    now()->addHour(),
                    [
                        'installation' => $installation,
                        'installer' => $installer,
                        'photo' => $index,
                    ],
                ),
            ])
            ->all();

        return view('installation-assignments.packet', [
            'installation' => $installation,
            'installer' => $installer,
            'questionnaire' => $questionnaire,
            'photoUrls' => $photoUrls,
            'completionUrl' => URL::temporarySignedRoute(
                'installation-assignments.complete',
                now()->addDays(14),
                [
                    'installation' => $installation,
                    'installer' => $installer,
                ],
            ),
        ]);
    }

    public function completionForm(InstallerInstallation $installation, Installer $installer): View
    {
        abort_unless($this->assignments->canViewPhotos($installation, $installer), 403);

        return view('installation-assignments.complete', [
            'installation' => $installation->loadMissing('questionnaire'),
            'installer' => $installer,
            'questionnaire' => $installation->questionnaire,
            'storeUrl' => URL::temporarySignedRoute(
                'installation-assignments.complete.store',
                now()->addDays(14),
                [
                    'installation' => $installation,
                    'installer' => $installer,
                ],
            ),
        ]);
    }

    public function complete(
        Request $request,
        InstallerInstallation $installation,
        Installer $installer,
    ): View {
        abort_unless($this->assignments->canViewPhotos($installation, $installer), 403);

        $attributes = $request->validate([
            'completion_installer_name' => ['required', 'string', 'max:120'],
            'installation_date' => ['required', 'date'],
            'completion_address' => ['required', 'string', 'max:1000'],
            'installed_product' => ['required', 'string', 'max:255'],
            'completion_details' => ['nullable', 'string', 'max:5000'],
            'installer_completion_notes' => ['nullable', 'string', 'max:5000'],
            'customer_name' => ['required', 'string', 'max:120'],
            'waiver_agreed' => ['accepted'],
            'signature_data' => ['required', 'string', 'max:700000'],
        ]);

        $signatureData = $attributes['signature_data'];
        if (! preg_match('/^data:image\/png;base64,(?<data>[A-Za-z0-9+\/=]+)$/', $signatureData, $matches)) {
            return back()->withErrors(['signature_data' => 'Please provide a valid signature.'])->withInput();
        }

        $signature = base64_decode($matches['data'], true);
        if ($signature === false || strlen($signature) < 100) {
            return back()->withErrors(['signature_data' => 'Please provide a valid signature.'])->withInput();
        }

        $oldSignature = $installation->customer_signature_path;
        $path = 'installation-completions/'.$installation->id.'/customer-signature-'.now()->timestamp.'.png';
        Storage::disk('public')->put($path, $signature);

        if ($oldSignature) {
            Storage::disk('public')->delete($oldSignature);
        }

        $installation->update([
            'status' => \App\Enums\InstallerInstallationStatus::Completed,
            'completed_at' => now(),
            'completion_installer_name' => trim($attributes['completion_installer_name']),
            'installation_date' => $attributes['installation_date'],
            'completion_address' => trim($attributes['completion_address']),
            'installed_product' => trim($attributes['installed_product']),
            'completion_details' => filled($attributes['completion_details'] ?? null) ? trim($attributes['completion_details']) : null,
            'installer_completion_notes' => filled($attributes['installer_completion_notes'] ?? null) ? trim($attributes['installer_completion_notes']) : null,
            'customer_signature_path' => $path,
            'customer_signature_name' => trim($attributes['customer_name']),
            'customer_signed_at' => now(),
        ]);

        $this->emailMappings->notifyInstallationCompletion($installation->fresh(['installer', 'questionnaire.seller']));

        return view('installation-assignments.complete-result', [
            'installation' => $installation->fresh(['questionnaire']),
            'installer' => $installer,
            'questionnaire' => $installation->questionnaire,
        ]);
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
            'packetUrl' => $state === 'accepted'
                ? URL::temporarySignedRoute(
                    'installation-assignments.packet',
                    now()->addDays(14),
                    [
                        'installation' => $installation,
                        'installer' => $installer,
                    ],
                )
                : null,
            'state' => $state,
        ]);
    }
}
