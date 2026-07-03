<?php

namespace App\Services\Crm;

use App\Enums\Crm\AppointmentStatus;
use App\Models\Crm\Appointment;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Support\BusinessLineResolver;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Arr;

class AppointmentService
{
    public function __construct(
        private readonly TimelineService $timeline,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Appointment
    {
        $assigneeId = CrmScope::userCanViewAll($user)
            ? (int) (Arr::get($data, 'user_id') ?: $user->id)
            : $user->id;

        $contact = $this->resolveContact($data);

        $appointment = Appointment::query()->create([
            'contact_type' => $contact?->getMorphClass(),
            'contact_id' => $contact?->id,
            'user_id' => $assigneeId,
            'business_line' => BusinessLineResolver::forRelatedContact($data, $user, $contact),
            'title' => trim((string) Arr::get($data, 'title')),
            'meeting_type' => Arr::get($data, 'meeting_type'),
            'location' => Arr::get($data, 'location'),
            'zoom_link' => Arr::get($data, 'zoom_link'),
            'status' => Arr::get($data, 'status', AppointmentStatus::Scheduled->value),
            'starts_at' => Arr::get($data, 'starts_at'),
            'ends_at' => Arr::get($data, 'ends_at'),
            'reminder_notes' => Arr::get($data, 'reminder_notes'),
        ]);

        if ($contact) {
            $this->timeline->log(
                $contact,
                'appointment_scheduled',
                'Appointment scheduled',
                $appointment->title,
                ['appointment_id' => $appointment->id, 'starts_at' => $appointment->starts_at?->toIso8601String()],
                $user,
            );
        }

        return $appointment->fresh(['contact', 'user']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Appointment $appointment, array $data, User $user): Appointment
    {
        $contact = $this->resolveContact($data, $appointment);

        $appointment->update([
            'contact_type' => $contact?->getMorphClass(),
            'contact_id' => $contact?->id,
            'title' => trim((string) Arr::get($data, 'title', $appointment->title)),
            'meeting_type' => Arr::get($data, 'meeting_type', $appointment->meeting_type),
            'location' => Arr::get($data, 'location', $appointment->location),
            'zoom_link' => Arr::get($data, 'zoom_link', $appointment->zoom_link),
            'status' => Arr::get($data, 'status', $appointment->status?->value ?? AppointmentStatus::Scheduled->value),
            'starts_at' => Arr::get($data, 'starts_at', $appointment->starts_at),
            'ends_at' => Arr::get($data, 'ends_at', $appointment->ends_at),
            'reminder_notes' => Arr::get($data, 'reminder_notes', $appointment->reminder_notes),
        ]);

        return $appointment->fresh(['contact', 'user']);
    }

    public function cancel(Appointment $appointment, User $user): Appointment
    {
        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        if ($contact = $appointment->contact) {
            $this->timeline->log(
                $contact,
                'appointment_cancelled',
                'Appointment cancelled',
                $appointment->title,
                ['appointment_id' => $appointment->id],
                $user,
            );
        }

        return $appointment->fresh(['contact', 'user']);
    }

    public function delete(Appointment $appointment, User $user): void
    {
        if ($contact = $appointment->contact) {
            $this->timeline->log(
                $contact,
                'appointment_deleted',
                'Appointment removed',
                $appointment->title,
                ['appointment_id' => $appointment->id],
                $user,
            );
        }

        $appointment->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Lead|Prospect|Customer|Recruit|null
     */
    private function resolveContact(array $data, ?Appointment $existing = null): Lead|Prospect|Customer|Recruit|null
    {
        if (array_key_exists('contact_type', $data) || array_key_exists('contact_id', $data)) {
            if (blank($data['contact_type'] ?? null) || blank($data['contact_id'] ?? null)) {
                return null;
            }

            return CrmContactResolver::resolve((string) $data['contact_type'], (int) $data['contact_id']);
        }

        if (array_key_exists('lead_id', $data)) {
            if (blank($data['lead_id'])) {
                return null;
            }

            return CrmContactResolver::resolve('lead', (int) $data['lead_id']);
        }

        return $existing?->contact;
    }
}
