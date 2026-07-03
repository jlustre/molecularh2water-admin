<?php

namespace App\Services\Portal;

use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Lead;
use App\Models\User;
use App\Services\Crm\CalendarEventService;
use App\Services\Crm\DashboardStatsService;
use App\Support\Crm\CalendarScope;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MeetingService
{
    public function __construct(
        private readonly PhoneCallService $phoneCalls,
    ) {}

    /**
     * @return list<string>
     */
    public function meetingTypeSlugs(): array
    {
        return ['in-person-meeting', 'zoom-meeting'];
    }

    /**
     * @return Collection<int, CalendarEvent>
     */
    public function upcomingMeetings(?User $user = null, int $limit = 25): Collection
    {
        $user ??= auth()->user();

        if (! $user || ! Schema::hasTable('calendar_events')) {
            return collect();
        }

        $typeIds = CalendarEventType::query()
            ->whereIn('slug', $this->meetingTypeSlugs())
            ->pluck('id');

        if ($typeIds->isEmpty()) {
            return collect();
        }

        return CalendarScope::events(CalendarEvent::query(), $user)
            ->with(['type', 'related', 'attendees.user'])
            ->whereIn('calendar_event_type_id', $typeIds)
            ->where('start_at', '>=', now()->startOfDay())
            ->where('start_at', '<=', now()->addDays(60))
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_at')
            ->limit($limit)
            ->get()
            ->sortBy([
                fn (CalendarEvent $meeting) => $meeting->status?->value === 'completed' ? 1 : 0,
                fn (CalendarEvent $meeting) => $meeting->start_at?->timestamp ?? 0,
            ])
            ->values();
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

    /**
     * @return array{kind: string, id: int, label: string, phone: ?string}|null
     */
    public function contactByKindAndId(string $kind, int $id, ?User $user = null): ?array
    {
        return $this->phoneCalls->contactByKindAndId($kind, $id, $user);
    }

    /**
     * @return array{first_name: string, last_name: string}
     */
    public function parseContactName(string $name): array
    {
        return $this->phoneCalls->parseContactName($name);
    }

    public function contactLabel(CalendarEvent $event): string
    {
        return $this->phoneCalls->contactLabel($event);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function inviteeGroupOptions(): array
    {
        return config('portal.meeting_invitee_groups', []);
    }

    /**
     * @return list<int>
     */
    public function resolveInviteeUserIds(string $group, User $actor, ?int $excludeUserId = null): array
    {
        if ($group === '') {
            return [];
        }

        $ids = match ($group) {
            'team_members' => $this->phoneCalls->contactOptions($actor)['team']->pluck('id'),
            'managers' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('slug', 'manager'))
                ->pluck('id'),
            'office_staff' => User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['admin', 'team-admin']))
                ->pluck('id'),
            'consultants' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('slug', 'consultant'))
                ->pluck('id'),
            default => collect(),
        };

        return $ids
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) $actor->id || ($excludeUserId !== null && $id === $excludeUserId))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array{
     *     contact_kind: string,
     *     contact_id?: int|null,
     *     other_name?: string|null,
     *     meeting_format: string,
     *     meeting_when: string,
     *     duration_minutes: int,
     *     title?: string|null,
     *     location?: string|null,
     *     meeting_link?: string|null,
     *     notes?: string|null,
     *     recurrence?: string,
     *     recurrence_count?: int,
     *     invitee_group?: string
     * }  $data
     * @return Collection<int, CalendarEvent>
     */
    public function schedule(array $data, User $actor): Collection
    {
        if ($data['contact_kind'] !== 'other') {
            $this->assertValidContact($data, $actor);
        }

        $typeSlug = $data['meeting_format'] === 'online' ? 'zoom-meeting' : 'in-person-meeting';
        $type = CalendarEventType::query()->where('slug', $typeSlug)->first()
            ?? CalendarEventType::query()->where('slug', 'in-person-meeting')->first()
            ?? CalendarEventType::query()->orderBy('sort_order')->first();

        if (! $type) {
            throw ValidationException::withMessages([
                'meeting_format' => 'Meeting event type is not configured. Run calendar seeders.',
            ]);
        }

        [$startAt, $durationMinutes] = $this->resolveMeetingWindow(
            $data['meeting_when'],
            (int) $data['duration_minutes'],
        );
        $endAt = $startAt->copy()->addMinutes($durationMinutes);

        $recurrence = $data['recurrence'] ?? 'none';
        $recurrenceCount = (int) ($data['recurrence_count'] ?? 8);
        $occurrences = $this->buildOccurrences($startAt, $endAt, $recurrence, $recurrenceCount);
        $groupId = (string) Str::uuid();

        $title = filled($data['title'] ?? null) ? trim((string) $data['title']) : null;
        $location = filled($data['location'] ?? null) ? trim((string) $data['location']) : null;
        $meetingLink = filled($data['meeting_link'] ?? null) ? trim((string) $data['meeting_link']) : null;
        $notes = filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null;
        $inviteeGroup = (string) ($data['invitee_group'] ?? '');
        $excludeAttendeeId = $data['contact_kind'] === 'team' ? (int) $data['contact_id'] : null;
        $attendeeIds = $this->resolveInviteeUserIds($inviteeGroup, $actor, $excludeAttendeeId);

        $payloadBase = [
            'calendar_event_type_id' => $type->id,
            'description' => $notes,
            'reminder_minutes' => [15, 60],
            'attendee_ids' => $attendeeIds,
            'metadata' => [
                'contact_kind' => $data['contact_kind'],
                'meeting_format' => $data['meeting_format'],
                'recurrence_rule' => $recurrence,
                'recurrence_group_id' => $groupId,
                'invitee_group' => $inviteeGroup !== '' ? $inviteeGroup : null,
            ],
        ];

        if ($data['contact_kind'] === 'other') {
            $name = trim((string) ($data['other_name'] ?? ''));
            $payloadBase['title'] = $title ?: 'Meeting with '.$name;
            $payloadBase['metadata']['other_contact_name'] = $name;
        } elseif ($data['contact_kind'] === 'team') {
            $member = User::query()->findOrFail((int) $data['contact_id']);
            $payloadBase['title'] = $title ?: 'Meeting with '.$member->name;
            $payloadBase['attendee_ids'] = collect($attendeeIds)
                ->push((int) $member->id)
                ->unique()
                ->values()
                ->all();
        } else {
            $lead = CrmScope::leads(Lead::query(), $actor)->findOrFail((int) $data['contact_id']);
            $payloadBase['lead_id'] = $lead->id;
            $payloadBase['title'] = $title ?: 'Meeting with '.$lead->fullName();
        }

        if ($data['meeting_format'] === 'online') {
            $payloadBase['meeting_link'] = $meetingLink;
        } else {
            $payloadBase['location'] = $location;
        }

        $events = collect();
        $calendar = app(CalendarEventService::class);

        foreach ($occurrences as $index => [$occurrenceStart, $occurrenceEnd]) {
            $metadata = array_merge($payloadBase['metadata'], [
                'recurrence_index' => $index + 1,
                'recurrence_total' => count($occurrences),
            ]);

            $events->push($calendar->create(array_merge($payloadBase, [
                'start_at' => $occurrenceStart,
                'end_at' => $occurrenceEnd,
                'metadata' => $metadata,
            ]), $actor));
        }

        app(DashboardStatsService::class)->notifyChanged($actor);

        return $events;
    }

    /**
     * @param  array{contact_kind: string, contact_id: int}  $data
     */
    private function assertValidContact(array $data, User $actor): void
    {
        $options = $this->phoneCalls->contactOptions($actor);
        $collection = match ($data['contact_kind']) {
            'prospect' => $options['prospects'],
            'customer' => $options['customers'],
            'team' => $options['team'],
            default => collect(),
        };

        if (! $collection->contains('id', (int) $data['contact_id'])) {
            throw ValidationException::withMessages([
                'contact_id' => 'Please choose a valid contact from the list.',
            ]);
        }
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: int}
     */
    private function resolveMeetingWindow(string $preset, int $durationMinutes): array
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

    /**
     * @return list<array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}>
     */
    private function buildOccurrences(
        \Illuminate\Support\Carbon $startAt,
        \Illuminate\Support\Carbon $endAt,
        string $recurrence,
        int $recurrenceCount,
    ): array {
        $occurrences = [[$startAt->copy(), $endAt->copy()]];

        if ($recurrence === 'none') {
            return $occurrences;
        }

        $total = max(2, min($recurrenceCount, 52));
        $durationMinutes = $startAt->diffInMinutes($endAt);
        $currentStart = $startAt->copy();

        for ($index = 1; $index < $total; $index++) {
            $currentStart = match ($recurrence) {
                'weekly' => $currentStart->copy()->addWeek(),
                'biweekly' => $currentStart->copy()->addWeeks(2),
                'monthly' => $currentStart->copy()->addMonth(),
                default => null,
            };

            if (! $currentStart) {
                break;
            }

            $occurrences[] = [
                $currentStart->copy(),
                $currentStart->copy()->addMinutes($durationMinutes),
            ];
        }

        return $occurrences;
    }
}
