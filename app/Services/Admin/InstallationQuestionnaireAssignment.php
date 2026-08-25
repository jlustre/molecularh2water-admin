<?php

namespace App\Services\Admin;

use App\Enums\InstallerAssignmentRejectionReason;
use App\Enums\InstallerAssignmentResponse;
use App\Enums\InstallerInstallationStatus;
use App\Enums\InstallerStatus;
use App\Mail\InstallerAssignmentOffered;
use App\Mail\InstallerAssignmentResponded;
use App\Models\InstallationQuestionnaire;
use App\Models\Installer;
use App\Models\InstallerInstallation;
use App\Models\User;
use App\Support\UsStates;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class InstallationQuestionnaireAssignment
{
    public function assign(
        InstallationQuestionnaire $questionnaire,
        Installer $installer,
        User $actor,
        ?Carbon $scheduledAt = null,
        ?string $notes = null,
    ): InstallerInstallation {
        if ($installer->status !== InstallerStatus::Active) {
            throw ValidationException::withMessages([
                'installer_id' => 'Choose an active installer. Archived installers cannot take new jobs.',
            ]);
        }

        $job = $this->mutableJobFor($questionnaire);
        $payload = $this->jobPayload($questionnaire, $installer, $scheduledAt, $notes);

        if ($job) {
            $job->update($payload);
        } else {
            $job = InstallerInstallation::query()->create($payload);
        }

        $questionnaire->update([
            'installer_id' => $installer->id,
            'assigned_by_user_id' => $actor->id,
            'assigned_at' => now(),
            'assignment_notes' => $notes,
            'assignment_response' => InstallerAssignmentResponse::Pending,
            'assignment_responded_at' => null,
            'assignment_rejection_reason' => null,
            'assignment_rejection_notes' => null,
        ]);

        return $job->fresh(['installer', 'questionnaire']) ?? $job;
    }

    public function sendOfferEmail(InstallerInstallation $installation): bool
    {
        $installation->loadMissing(['installer', 'questionnaire.seller', 'questionnaire.assignedBy']);

        $installer = $installation->installer;
        $questionnaire = $installation->questionnaire;

        if (! $installer || ! $questionnaire || ! filled($installer->email)) {
            return false;
        }

        try {
            Mail::to($installer->email)->send(
                InstallerAssignmentOffered::make($installation, $questionnaire, $installer),
            );
        } catch (Throwable $exception) {
            Log::warning('Failed to send installer assignment email.', [
                'installation_id' => $installation->id,
                'installer_id' => $installer->id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    public function unassign(InstallationQuestionnaire $questionnaire): void
    {
        $job = $this->mutableJobFor($questionnaire);

        if ($job) {
            $job->update([
                'status' => InstallerInstallationStatus::Cancelled,
                'cancelled_at' => $job->cancelled_at ?? now(),
            ]);
        }

        $questionnaire->update([
            'installer_id' => null,
            'assigned_by_user_id' => null,
            'assigned_at' => null,
            'assignment_notes' => null,
            'assignment_response' => null,
            'assignment_responded_at' => null,
            'assignment_rejection_reason' => null,
            'assignment_rejection_notes' => null,
        ]);
    }

    public function accept(InstallerInstallation $installation, Installer $installer): string
    {
        $state = $this->offerState($installation, $installer);

        if ($state === 'accepted') {
            return $state;
        }

        if ($state !== 'offered') {
            return $state;
        }

        $questionnaire = $installation->questionnaire;
        $questionnaire?->loadMissing(['assignedBy']);
        $assignor = $questionnaire?->assignedBy;

        $now = now();
        $installation->update([
            'assignment_response' => InstallerAssignmentResponse::Accepted,
            'assignment_responded_at' => $now,
            'assignment_rejection_reason' => null,
            'assignment_rejection_notes' => null,
        ]);

        $questionnaire?->update([
            'assignment_response' => InstallerAssignmentResponse::Accepted,
            'assignment_responded_at' => $now,
            'assignment_rejection_reason' => null,
            'assignment_rejection_notes' => null,
        ]);

        $this->notifyAssignor(
            $installation->fresh(['installer', 'questionnaire']) ?? $installation,
            $assignor,
        );

        return 'accepted';
    }

    public function reject(
        InstallerInstallation $installation,
        Installer $installer,
        InstallerAssignmentRejectionReason $reason,
        ?string $notes = null,
    ): string {
        $state = $this->offerState($installation, $installer);

        if ($state === 'rejected') {
            return $state;
        }

        if ($state !== 'offered') {
            return $state;
        }

        $questionnaire = $installation->questionnaire;
        $questionnaire?->loadMissing(['assignedBy']);
        $assignor = $questionnaire?->assignedBy;

        $now = now();

        $installation->update([
            'status' => InstallerInstallationStatus::Cancelled,
            'cancelled_at' => $installation->cancelled_at ?? $now,
            'assignment_response' => InstallerAssignmentResponse::Rejected,
            'assignment_responded_at' => $now,
            'assignment_rejection_reason' => $reason,
            'assignment_rejection_notes' => $notes,
        ]);

        $questionnaire?->update([
            'installer_id' => null,
            'assigned_by_user_id' => null,
            'assigned_at' => null,
            'assignment_notes' => null,
            'assignment_response' => InstallerAssignmentResponse::Rejected,
            'assignment_responded_at' => $now,
            'assignment_rejection_reason' => $reason,
            'assignment_rejection_notes' => $notes,
        ]);

        $this->notifyAssignor(
            $installation->fresh(['installer', 'questionnaire']) ?? $installation,
            $assignor,
        );

        return 'rejected';
    }

    public function offerState(InstallerInstallation $installation, Installer $installer): string
    {
        if ((int) $installation->installer_id !== (int) $installer->id) {
            return 'unavailable';
        }

        if ($installation->assignment_response === InstallerAssignmentResponse::Accepted) {
            return 'accepted';
        }

        if ($installation->assignment_response === InstallerAssignmentResponse::Rejected) {
            return 'rejected';
        }

        $questionnaire = $installation->questionnaire;

        if (! $questionnaire || (int) $questionnaire->installer_id !== (int) $installer->id) {
            return 'unavailable';
        }

        if (! in_array($installation->status, [
            InstallerInstallationStatus::Scheduled,
            InstallerInstallationStatus::Rescheduled,
        ], true)) {
            return 'unavailable';
        }

        return 'offered';
    }

    public function canViewPhotos(InstallerInstallation $installation, Installer $installer): bool
    {
        return (int) $installation->installer_id === (int) $installer->id
            && $installation->questionnaire !== null;
    }

    private function notifyAssignor(InstallerInstallation $installation, ?User $assignor = null): void
    {
        $installation->loadMissing(['installer', 'questionnaire.assignedBy']);

        $questionnaire = $installation->questionnaire;
        $installer = $installation->installer;
        $assignor ??= $questionnaire?->assignedBy;

        if (! $questionnaire || ! $installer) {
            return;
        }

        if (! filled($assignor?->email)) {
            Log::warning('Installer responded, but the assignor has no email address.', [
                'installation_id' => $installation->id,
                'questionnaire_id' => $questionnaire->id,
            ]);

            return;
        }

        try {
            Mail::to($assignor->email)->send(new InstallerAssignmentResponded(
                $installation,
                $questionnaire,
                $installer,
                route('admin.installation-questionnaires.show', $questionnaire),
                $assignor,
            ));
        } catch (Throwable $exception) {
            Log::warning('Failed to send installer assignment response email to the assignor.', [
                'installation_id' => $installation->id,
                'assignor_id' => $assignor->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function mutableJobFor(InstallationQuestionnaire $questionnaire): ?InstallerInstallation
    {
        $job = $questionnaire->currentInstallerInstallation();

        if (! $job) {
            return null;
        }

        return in_array($job->status, [
            InstallerInstallationStatus::Scheduled,
            InstallerInstallationStatus::Rescheduled,
        ], true) ? $job : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function jobPayload(
        InstallationQuestionnaire $questionnaire,
        Installer $installer,
        ?Carbon $scheduledAt,
        ?string $notes,
    ): array {
        $street = collect([
            $questionnaire->street_address,
            $questionnaire->street_address_2,
        ])->filter()->implode(', ');

        return [
            'installer_id' => $installer->id,
            'installation_questionnaire_id' => $questionnaire->id,
            'status' => InstallerInstallationStatus::Scheduled,
            'assignment_response' => InstallerAssignmentResponse::Pending,
            'assignment_responded_at' => null,
            'assignment_rejection_reason' => null,
            'assignment_rejection_notes' => null,
            'customer_name' => $questionnaire->full_name,
            'customer_email' => $questionnaire->email,
            'customer_phone' => $questionnaire->phone,
            'street_address' => $street !== '' ? $street : null,
            'city' => $questionnaire->city,
            'state' => UsStates::abbreviation($questionnaire->state),
            'postal_code' => $questionnaire->postal_code,
            'scheduled_at' => $scheduledAt,
            'cancelled_at' => null,
            'notes' => $this->jobNotes($questionnaire, $notes),
        ];
    }

    private function jobNotes(InstallationQuestionnaire $questionnaire, ?string $notes): ?string
    {
        $parts = collect([
            filled($notes) ? trim($notes) : null,
            filled($questionnaire->special_requirements)
                ? 'Special requirements: '.$questionnaire->special_requirements
                : null,
        ])->filter();

        return $parts->isEmpty() ? null : $parts->implode("\n\n");
    }
}
