<?php

namespace App\Services\Portal;

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Services\Crm\CalendarEventService;
use App\Services\Crm\DashboardStatsService;
use App\Support\Crm\CalendarScope;
use App\Support\Crm\CrmScope;
use App\Support\Crm\TeamScope;
use App\Support\Portal\PhoneCallReasons;
use App\Support\Portal\PhoneCallResults;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PhoneCallService
{
    /**
     * @return list<string>
     */
    public function phoneCallTypeSlugs(): array
    {
        return ['phone-call', 'follow-up', 'post-show-follow-up'];
    }

    /**
     * @return Collection<int, CalendarEvent>
     */
    public function upcomingCalls(?User $user = null, int $limit = 25): Collection
    {
        $user ??= auth()->user();

        if (! $user || ! Schema::hasTable('calendar_events')) {
            return collect();
        }

        $typeIds = CalendarEventType::query()
            ->whereIn('slug', $this->phoneCallTypeSlugs())
            ->pluck('id');

        if ($typeIds->isEmpty()) {
            return collect();
        }

        return CalendarScope::events(CalendarEvent::query(), $user)
            ->with(['type', 'related', 'attendees.user'])
            ->whereIn('calendar_event_type_id', $typeIds)
            ->where('start_at', '>=', now()->startOfDay())
            ->where('start_at', '<=', now()->addDays(14))
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_at')
            ->limit($limit)
            ->get()
            ->sortBy([
                fn (CalendarEvent $call) => $call->status?->value === 'completed' ? 1 : 0,
                fn (CalendarEvent $call) => $call->start_at?->timestamp ?? 0,
            ])
            ->values();
    }

    /**
     * @return array{
     *     prospects: Collection<int, Lead>,
     *     customers: Collection<int, Lead>,
     *     team: Collection<int, User>
     * }
     */
    public function contactOptions(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [
                'prospects' => collect(),
                'customers' => collect(),
                'team' => collect(),
            ];
        }

        $columns = ['id', 'first_name', 'last_name', 'phone', 'email', 'lifecycle_id', 'company'];
        $prospects = collect([Lead::class, Prospect::class, Recruit::class])
            ->flatMap(fn (string $class) => CrmScope::contacts($class::query(), $user)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(250)
                ->get($columns))
            ->sortBy([
                ['first_name', 'asc'],
                ['last_name', 'asc'],
            ])
            ->values()
            ->take(250);

        $customers = CrmScope::contacts(Customer::query(), $user)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(250)
            ->get($columns);

        $teamIds = collect()
            ->when($user->sponsor_id, fn ($ids) => $ids->push((int) $user->sponsor_id))
            ->merge($user->sponsoredUsers()->pluck('id'))
            ->merge(TeamScope::memberUserIds($user))
            ->merge($user->teams()->with('users:id')->get()->flatMap(fn ($team) => $team->users->pluck('id')))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => $id === (int) $user->id)
            ->values();

        $team = $teamIds->isEmpty()
            ? collect()
            : User::query()
                ->whereIn('id', $teamIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

        return [
            'prospects' => $prospects,
            'customers' => $customers,
            'team' => $team,
        ];
    }

    /**
     * @return Collection<int, array{kind: string, id: int, label: string, phone: ?string}>
     */
    public function searchContacts(string $query, ?User $user = null, int $limit = 8): Collection
    {
        $user ??= auth()->user();
        $term = trim($query);

        if (! $user || strlen($term) < 3) {
            return collect();
        }

        $like = '%'.$term.'%';
        $results = collect();
        $nameFilter = function ($builder) use ($like) {
            $builder->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);
        };

        foreach ([
            [Prospect::class, 'prospect'],
            [Lead::class, 'prospect'],
            [Recruit::class, 'prospect'],
            [Customer::class, 'customer'],
        ] as [$class, $kind]) {
            if ($results->count() >= $limit) {
                break;
            }

            $remaining = $limit - $results->count();
            $contacts = CrmScope::contacts($class::query(), $user)
                ->where($nameFilter)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit($remaining)
                ->get(['id', 'first_name', 'last_name', 'phone', 'email', 'lifecycle_id']);

            foreach ($contacts as $contact) {
                $results->push([
                    'kind' => $kind,
                    'id' => $contact->id,
                    'label' => $contact->fullName(),
                    'phone' => $contact->phone,
                ]);
            }
        }

        $remaining = max(0, $limit - $results->count());

        if ($remaining > 0) {
            $teamIds = $this->contactOptions($user)['team']->pluck('id');

            if ($teamIds->isNotEmpty()) {
                $members = User::query()
                    ->whereIn('id', $teamIds)
                    ->where(function ($builder) use ($like) {
                        $builder->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })
                    ->orderBy('name')
                    ->limit($remaining)
                    ->get(['id', 'name', 'email']);

                foreach ($members as $member) {
                    $results->push([
                        'kind' => 'team',
                        'id' => $member->id,
                        'label' => $member->name,
                        'phone' => null,
                    ]);
                }
            }
        }

        return $results->take($limit)->values();
    }

    /**
     * @return array{kind: string, id: int, label: string, phone: ?string}|null
     */
    public function findContactMatch(string $name, ?User $user = null): ?array
    {
        $user ??= auth()->user();
        $normalized = strtolower(trim($name));

        if (! $user || $normalized === '') {
            return null;
        }

        foreach ([
            [Prospect::class, 'prospect'],
            [Lead::class, 'prospect'],
            [Recruit::class, 'prospect'],
            [Customer::class, 'customer'],
        ] as [$class, $kind]) {
            $contact = CrmScope::contacts($class::query(), $user)
                ->get(['id', 'first_name', 'last_name', 'phone', 'email', 'lifecycle_id'])
                ->first(fn (Model $row) => strtolower($row->fullName()) === $normalized);

            if ($contact) {
                return [
                    'kind' => $kind,
                    'id' => $contact->id,
                    'label' => $contact->fullName(),
                    'phone' => $contact->phone,
                ];
            }
        }

        $member = $this->contactOptions($user)['team']
            ->first(fn (User $teamMember) => strtolower($teamMember->name) === $normalized);

        if ($member) {
            return [
                'kind' => 'team',
                'id' => $member->id,
                'label' => $member->name,
                'phone' => null,
            ];
        }

        return null;
    }

    /**
     * @return array{kind: string, id: int, label: string, phone: ?string}|null
     */
    public function contactByKindAndId(string $kind, int $id, ?User $user = null): ?array
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        if ($kind === 'team') {
            $member = $this->contactOptions($user)['team']->firstWhere('id', $id);

            return $member ? [
                'kind' => 'team',
                'id' => $member->id,
                'label' => $member->name,
                'phone' => null,
            ] : null;
        }

        if (! in_array($kind, ['prospect', 'customer'], true)) {
            return null;
        }

        $contact = $this->resolveCrmContact($kind, $id, $user);

        if (! $contact) {
            return null;
        }

        return [
            'kind' => $kind,
            'id' => $contact->id,
            'label' => $contact->fullName(),
            'phone' => $contact->phone,
        ];
    }

    /**
     * @return array{first_name: string, last_name: string}
     */
    public function parseContactName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
        ];
    }

    public function phoneForContact(string $contactKind, int $contactId, User $actor): ?string
    {
        if ($contactKind === 'team') {
            return null;
        }

        return $this->resolveCrmContact($contactKind, $contactId, $actor)?->phone;
    }

    /**
     * @param  array{
     *     contact_kind: string,
     *     contact_id?: int|null,
     *     other_name?: string|null,
     *     phone_number?: string|null,
     *     call_when: string,
     *     call_date?: string|null,
     *     call_time?: string|null,
     *     call_reason: string,
     *     notes?: string|null
     * }  $data
     */
    public function schedule(array $data, User $actor): CalendarEvent
    {
        if ($data['contact_kind'] !== 'other') {
            $this->assertValidContact($data, $actor);
        }

        $type = CalendarEventType::query()->where('slug', 'phone-call')->first()
            ?? CalendarEventType::query()->orderBy('sort_order')->first();

        if (! $type) {
            throw ValidationException::withMessages([
                'contact_id' => 'Phone call event type is not configured. Run calendar seeders.',
            ]);
        }

        [$startAt, $endAt] = $this->resolveCallWindowFromData($data);
        $phone = filled($data['phone_number'] ?? null) ? trim((string) $data['phone_number']) : null;

        $payload = [
            'calendar_event_type_id' => $type->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'description' => $this->buildDescription($data, $phone),
            'reminder_minutes' => [15],
            'metadata' => array_filter([
                'phone_call_reason' => $data['call_reason'],
                'phone_number' => $phone,
                'contact_kind' => $data['contact_kind'],
                'schedule_notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
            ]),
        ];

        if ($data['contact_kind'] === 'other') {
            $name = trim((string) ($data['other_name'] ?? ''));
            $payload['title'] = 'Phone call with '.$name;
            $payload['metadata']['other_contact_name'] = $name;
        } elseif ($data['contact_kind'] === 'team') {
            $member = User::query()->findOrFail((int) $data['contact_id']);
            $payload['title'] = 'Phone call with '.$member->name;
            $payload['attendee_ids'] = [(int) $member->id];
        } else {
            $contact = $this->resolveCrmContact($data['contact_kind'], (int) $data['contact_id'], $actor);

            if (! $contact) {
                throw ValidationException::withMessages([
                    'contact_id' => 'Please choose a valid contact from the list.',
                ]);
            }

            $payload['related_type'] = $contact->getMorphClass();
            $payload['related_id'] = $contact->id;
            $payload['title'] = 'Phone call with '.$contact->fullName();
        }

        $event = app(CalendarEventService::class)->create($payload, $actor);

        app(DashboardStatsService::class)->notifyChanged($actor);

        return $event;
    }

    /**
     * @param  array{
     *     call_when: string,
     *     call_date?: string|null,
     *     call_time?: string|null,
     *     phone_number?: string|null,
     *     call_reason: string,
     *     notes?: string|null
     * }  $data
     */
    public function updateScheduledCall(CalendarEvent $event, array $data, User $actor): CalendarEvent
    {
        if (! $this->isPhoneCallEvent($event)) {
            throw ValidationException::withMessages([
                'event' => 'This calendar entry is not a phone call.',
            ]);
        }

        [$startAt, $endAt] = $this->resolveCallWindowFromData($data);
        $phone = filled($data['phone_number'] ?? null) ? trim((string) $data['phone_number']) : null;
        $metadata = array_merge($event->metadata ?? [], array_filter([
            'phone_call_reason' => $data['call_reason'],
            'phone_number' => $phone,
            'schedule_notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
        ]));

        return app(CalendarEventService::class)->update($event, [
            'start_at' => $startAt,
            'end_at' => $endAt,
            'description' => $this->buildDescription([
                'call_reason' => $data['call_reason'],
                'notes' => $data['notes'] ?? null,
            ], $phone),
            'metadata' => $metadata,
            'reminder_minutes' => $event->reminders->pluck('minutes_before')->all() ?: [15],
        ], $actor);
    }

    /**
     * @param  array{result: string, comments?: string|null}  $data
     */
    public function recordResults(CalendarEvent $event, array $data, User $actor): CalendarEvent
    {
        if (! $this->isPhoneCallEvent($event)) {
            throw ValidationException::withMessages([
                'event' => 'This calendar entry is not a phone call.',
            ]);
        }

        $comments = filled($data['comments'] ?? null) ? trim((string) $data['comments']) : null;
        $resultLabel = PhoneCallResults::label($data['result']) ?? $data['result'];
        $completionNotes = $comments
            ? $resultLabel.' — '.$comments
            : $resultLabel;

        $metadata = array_merge($event->metadata ?? [], [
            'phone_call_result' => $data['result'],
            'phone_call_result_comments' => $comments,
        ]);

        $event->update(['metadata' => $metadata]);

        return app(CalendarEventService::class)->complete(
            $event->fresh(),
            $actor,
            $completionNotes,
            $data['result'],
        );
    }

    /**
     * @param  array{
     *     call_when: string,
     *     call_date?: string|null,
     *     call_time?: string|null,
     *     call_reason: string,
     *     notes?: string|null
     * }  $data
     */
    public function scheduleFollowUpFromEvent(CalendarEvent $event, array $data, User $actor): CalendarEvent
    {
        $event->loadMissing(['related', 'attendees.user']);

        $contactKind = (string) ($event->metadata['contact_kind'] ?? '');
        $scheduleData = [
            'call_when' => $data['call_when'],
            'call_date' => $data['call_date'] ?? null,
            'call_time' => $data['call_time'] ?? null,
            'call_reason' => $data['call_reason'],
            'notes' => $data['notes'] ?? null,
            'phone_number' => $this->displayPhone($event),
        ];

        if ($contact = $event->crmContact()) {
            $scheduleData['contact_kind'] = in_array($contactKind, ['prospect', 'customer'], true)
                ? $contactKind
                : ($contact->lifecycleSlug() === LeadLifecycle::Client ? 'customer' : 'prospect');
            $scheduleData['contact_id'] = $contact->id;
        } elseif ($contactKind === 'team' && ($memberId = $event->attendees->first()?->user_id)) {
            $scheduleData['contact_kind'] = 'team';
            $scheduleData['contact_id'] = (int) $memberId;
        } else {
            $scheduleData['contact_kind'] = 'other';
            $scheduleData['other_name'] = (string) ($event->metadata['other_contact_name'] ?? $this->contactLabel($event));
        }

        $followUp = $this->schedule($scheduleData, $actor);

        if ($contact = $event->crmContact()) {
            $contact->update(['next_follow_up_at' => $followUp->start_at]);
        }

        return $followUp;
    }

    /**
     * @return array{
     *     call_when: string,
     *     call_date: string,
     *     call_time: string,
     *     phone_number: string,
     *     call_reason: string,
     *     notes: string,
     *     contact_type: string
     * }
     */
    public function formDataFromEvent(CalendarEvent $event): array
    {
        $preset = $this->guessCallWhenPreset($event->start_at);

        return [
            'call_when' => $preset ?? 'custom',
            'call_date' => $event->start_at?->format('Y-m-d') ?? '',
            'call_time' => $event->start_at?->format('H:i') ?? '',
            'phone_number' => $this->displayPhone($event) ?? '',
            'call_reason' => (string) ($event->metadata['phone_call_reason'] ?? ''),
            'notes' => (string) ($event->metadata['schedule_notes'] ?? ''),
            'contact_type' => (string) ($event->metadata['contact_kind'] ?? 'prospect'),
        ];
    }

    public function contactLabel(CalendarEvent $event): string
    {
        return $event->crmContact()?->fullName()
            ?? ($event->metadata['other_contact_name'] ?? null)
            ?? $event->attendees->first()?->user?->name
            ?? $event->title;
    }

    public function complete(CalendarEvent $event, User $actor): CalendarEvent
    {
        if (! $this->isPhoneCallEvent($event)) {
            throw ValidationException::withMessages([
                'event' => 'This calendar entry is not a phone call.',
            ]);
        }

        return app(CalendarEventService::class)->complete($event, $actor);
    }

    public function isPhoneCallEvent(CalendarEvent $event): bool
    {
        return in_array($event->type?->slug, $this->phoneCallTypeSlugs(), true);
    }

    public function displayPhone(CalendarEvent $event): ?string
    {
        $metadataPhone = $event->metadata['phone_number'] ?? null;

        if (filled($metadataPhone)) {
            return (string) $metadataPhone;
        }

        return $event->crmContact()?->phone;
    }

    private function resolveCrmContact(string $kind, int $id, User $user): Lead|Prospect|Customer|Recruit|null
    {
        $classes = match ($kind) {
            'customer' => [Customer::class],
            'prospect' => [Lead::class, Prospect::class, Recruit::class],
            default => [],
        };

        foreach ($classes as $class) {
            $contact = CrmScope::contacts($class::query(), $user)->find($id);

            if ($contact) {
                return $contact;
            }
        }

        return null;
    }

    /**
     * @param  array{call_reason: string, notes?: string|null}  $data
     */
    private function buildDescription(array $data, ?string $phone): ?string
    {
        $notes = filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null;

        if ($data['call_reason'] === 'other') {
            $parts = array_filter([$notes, $phone ? 'Phone: '.$phone : null]);

            return $parts !== [] ? implode(' · ', $parts) : null;
        }

        $reasonLabel = PhoneCallReasons::label($data['call_reason']) ?? 'Phone call';
        $parts = array_filter([
            $reasonLabel,
            $notes,
            $phone ? 'Phone: '.$phone : null,
        ]);

        return implode(' · ', $parts);
    }

    /**
     * @param  array{contact_kind: string, contact_id: int}  $data
     */
    private function assertValidContact(array $data, User $actor): void
    {
        $options = $this->contactOptions($actor);
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
     * @param  array{call_when: string, call_date?: string|null, call_time?: string|null}  $data
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveCallWindowFromData(array $data): array
    {
        return $this->resolveCallWindow(
            $data['call_when'],
            $data['call_date'] ?? null,
            $data['call_time'] ?? null,
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveCallWindow(string $preset, ?string $customDate = null, ?string $customTime = null): array
    {
        if ($preset === 'custom') {
            $date = trim((string) $customDate);
            $time = trim((string) $customTime);

            if ($date === '' || $time === '') {
                throw ValidationException::withMessages([
                    'call_date' => 'Choose a date and time for the call.',
                    'call_time' => 'Choose a date and time for the call.',
                ]);
            }

            try {
                $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time);
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'call_date' => 'Enter a valid date and time.',
                    'call_time' => 'Enter a valid date and time.',
                ]);
            }

            if (! $start) {
                throw ValidationException::withMessages([
                    'call_date' => 'Enter a valid date and time.',
                    'call_time' => 'Enter a valid date and time.',
                ]);
            }

            return [$start, $start->copy()->addMinutes(30)];
        }

        $start = match ($preset) {
            'in_15' => now()->addMinutes(15)->ceilMinutes(15),
            'in_30' => now()->addMinutes(30)->ceilMinutes(15),
            'in_60' => now()->addHour()->ceilMinutes(15),
            'today_14' => now()->setTime(14, 0),
            'today_16' => now()->setTime(16, 0),
            'tomorrow_10' => now()->addDay()->setTime(10, 0),
            default => now()->addMinutes(30)->ceilMinutes(15),
        };

        if ($start->isPast()) {
            $start = now()->addMinutes(15)->ceilMinutes(15);
        }

        return [$start, $start->copy()->addMinutes(30)];
    }

    private function guessCallWhenPreset(?\Illuminate\Support\Carbon $startAt): ?string
    {
        if (! $startAt) {
            return null;
        }

        $candidates = [
            'in_15' => now()->addMinutes(15)->ceilMinutes(15),
            'in_30' => now()->addMinutes(30)->ceilMinutes(15),
            'in_60' => now()->addHour()->ceilMinutes(15),
            'today_14' => now()->setTime(14, 0),
            'today_16' => now()->setTime(16, 0),
            'tomorrow_10' => now()->addDay()->setTime(10, 0),
        ];

        foreach ($candidates as $preset => $candidate) {
            if ($startAt->equalTo($candidate)) {
                return $preset;
            }
        }

        return null;
    }
}
