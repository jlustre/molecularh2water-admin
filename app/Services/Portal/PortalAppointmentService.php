<?php

namespace App\Services\Portal;

use App\Enums\Crm\AppointmentStatus;
use App\Models\Crm\Appointment;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Services\Crm\AppointmentService;
use App\Services\Crm\DashboardStatsService;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PortalAppointmentService
{
    public function __construct(
        private readonly PhoneCallService $phoneCalls,
    ) {}

    /**
     * @return Collection<int, Appointment>
     */
    public function upcomingAppointments(?User $user = null, int $limit = 25): Collection
    {
        $user ??= auth()->user();

        if (! $user || ! Schema::hasTable('appointments')) {
            return collect();
        }

        return CrmScope::appointments(Appointment::query(), $user)
            ->with(['lead', 'user'])
            ->whereIn('status', [
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Confirmed->value,
            ])
            ->where('starts_at', '>=', now()->startOfDay())
            ->where('starts_at', '<=', now()->addDays(60))
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, array{kind: string, id: int, label: string, phone: ?string}>
     */
    public function searchContacts(string $query, ?User $user = null, int $limit = 8): Collection
    {
        return $this->phoneCalls->searchContacts($query, $user, $limit);
    }

    /**
     * @return array{kind: string, id: int, label: string, phone: ?string}|null
     */
    public function findContactMatch(string $name, ?User $user = null): ?array
    {
        return $this->phoneCalls->findContactMatch($name, $user);
    }

    public function contactById(int $leadId, ?User $user = null): Lead|Prospect|Customer|Recruit|null
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        return $this->findContactById($leadId, $user);
    }

    /**
     * @return array{first_name: string, last_name: string}
     */
    public function parseContactName(string $name): array
    {
        return app(PortalDemoService::class)->parseContactName($name);
    }

    /**
     * @param  array{
     *     lead_id?: int|null,
     *     title?: string|null,
     *     meeting_type: string,
     *     appointment_when: string,
     *     duration_minutes: int,
     *     location?: string|null,
     *     zoom_link?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function schedule(array $data, User $actor): Appointment
    {
        $contact = null;

        if (filled($data['contact_type'] ?? null) && filled($data['contact_id'] ?? null)) {
            $contact = $this->findContactByTypeAndId(
                (string) $data['contact_type'],
                (int) $data['contact_id'],
                $actor,
            );
        } elseif (filled($data['lead_id'] ?? null)) {
            $contact = $this->findContactById((int) $data['lead_id'], $actor);
        }

        if (filled($data['lead_id'] ?? null) && ! $contact) {
            throw ValidationException::withMessages([
                'lead_id' => 'Please choose a valid contact from the list.',
            ]);
        }

        [$startsAt, $durationMinutes] = $this->resolveWindow(
            $data['appointment_when'],
            (int) $data['duration_minutes'],
        );

        $title = filled($data['title'] ?? null) ? trim((string) $data['title']) : null;

        if (! $title && $contact) {
            $title = 'Appointment with '.$contact->fullName();
        }

        if (! $title) {
            $title = 'Appointment';
        }

        $appointment = app(AppointmentService::class)->create([
            'contact_type' => $contact?->getMorphClass(),
            'contact_id' => $contact?->id,
            'title' => $title,
            'meeting_type' => $data['meeting_type'],
            'location' => filled($data['location'] ?? null) ? trim((string) $data['location']) : null,
            'zoom_link' => filled($data['zoom_link'] ?? null) ? trim((string) $data['zoom_link']) : null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($durationMinutes),
            'reminder_notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
        ], $actor);

        app(DashboardStatsService::class)->notifyChanged($actor);

        return $appointment->fresh(['contact', 'user']);
    }

    public function meetingTypeLabel(?string $type): string
    {
        if (! $type) {
            return 'Appointment';
        }

        return config('crm.meeting_types.'.$type, ucfirst(str_replace('_', ' ', $type)));
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: int}
     */
    private function resolveWindow(string $preset, int $durationMinutes): array
    {
        $start = match ($preset) {
            'in_15' => now()->addMinutes(15)->ceilMinutes(15),
            'in_30' => now()->addMinutes(30)->ceilMinutes(15),
            'in_60' => now()->addHour()->ceilMinutes(15),
            'today_14' => now()->setTime(14, 0),
            'today_16' => now()->setTime(16, 0),
            'tomorrow_10' => now()->addDay()->setTime(10, 0),
            'tomorrow_14' => now()->addDay()->setTime(14, 0),
            'next_week' => now()->addWeek()->next('Monday')->setTime(10, 0),
            default => now()->addDay()->setTime(10, 0),
        };

        if ($start->isPast()) {
            $start = now()->addHour()->ceilMinutes(15);
        }

        return [$start, $durationMinutes];
    }

    private function findContactById(int $id, User $actor): Lead|Prospect|Customer|Recruit|null
    {
        foreach ([Lead::class, Prospect::class, Customer::class, Recruit::class] as $class) {
            $contact = CrmScope::contacts($class::query(), $actor)->find($id);

            if ($contact) {
                return $contact;
            }
        }

        return null;
    }

    private function findContactByTypeAndId(string $type, int $id, User $actor): Lead|Prospect|Customer|Recruit|null
    {
        $class = \App\Support\Crm\CrmContactResolver::modelClassForMorph($type);

        return CrmScope::contacts($class::query(), $actor)->find($id);
    }
}
