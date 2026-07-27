<?php

namespace App\Services\Crm;

use App\Enums\Crm\CalendarEventStatus;
use App\Models\Crm\ActivityType;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Customer;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Task;
use App\Models\User;
use App\Support\BusinessLineResolver;
use App\Support\Crm\CalendarRecurrence;
use App\Support\Crm\CalendarScope;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CalendarEventService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly ActivityService $activities,
    ) {}

    /**
     * Create a single event, or a materialized recurring series when recurrence is set.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, CalendarEvent>
     */
    public function createSeries(array $data, User $actor): Collection
    {
        $rule = (string) Arr::get($data, 'recurrence', 'none');
        $count = (int) Arr::get($data, 'recurrence_count', 8);
        $schedule = $this->normalizeSchedule($data);

        $occurrences = CalendarRecurrence::buildOccurrences(
            $schedule['start_at'],
            $schedule['end_at'] ?? $schedule['start_at']->copy()->addHour(),
            $rule,
            $count,
            $schedule['is_all_day'],
        );

        $groupId = $rule === 'none' ? null : CalendarRecurrence::newGroupId();
        $baseMetadata = Arr::get($data, 'metadata', []) ?? [];

        return collect($occurrences)->values()->map(function (array $window, int $index) use ($data, $actor, $rule, $groupId, $baseMetadata, $occurrences, $schedule) {
            [$start, $end] = $window;

            $metadata = $baseMetadata;
            if ($rule !== 'none' && $groupId) {
                $metadata = array_merge($metadata, [
                    'recurrence_rule' => $rule,
                    'recurrence_group_id' => $groupId,
                    'recurrence_index' => $index + 1,
                    'recurrence_total' => count($occurrences),
                ]);
            }

            return $this->create(array_merge($data, [
                'start_at' => $start,
                'end_at' => $end,
                'is_all_day' => $schedule['is_all_day'],
                'metadata' => $metadata,
                'recurrence' => 'none',
            ]), $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CalendarEvent
    {
        $type = CalendarEventType::query()->findOrFail((int) $data['calendar_event_type_id']);
        $assigneeId = $this->resolveAssigneeId($data, $actor);
        $contact = $this->resolveContact($data, $actor);
        $related = $this->resolveRelated($data, $contact);
        $schedule = $this->normalizeSchedule($data);

        $event = CalendarEvent::query()->create([
            'user_id' => $assigneeId,
            'team_id' => Arr::get($data, 'team_id', $contact?->team_id),
            'business_line' => $contact
                ? BusinessLineResolver::forRelatedContact($data, $actor, $contact)
                : BusinessLineResolver::forCalendarEvent($data, $actor, null, $type),
            'related_type' => $related['type'],
            'related_id' => $related['id'],
            'calendar_event_type_id' => $type->id,
            'user_calendar_id' => $this->resolveUserCalendarId($data, $actor),
            'task_id' => Arr::get($data, 'task_id'),
            'title' => trim((string) Arr::get($data, 'title')),
            'description' => Arr::get($data, 'description'),
            'start_at' => $schedule['start_at'],
            'end_at' => $schedule['end_at'],
            'is_all_day' => $schedule['is_all_day'],
            'timezone' => Arr::get($data, 'timezone', config('calendar.default_timezone')),
            'location' => Arr::get($data, 'location'),
            'meeting_link' => Arr::get($data, 'meeting_link'),
            'status' => Arr::get($data, 'status', CalendarEventStatus::Scheduled->value),
            'priority' => Arr::get($data, 'priority', 'normal'),
            'reminder_enabled' => (bool) Arr::get($data, 'reminder_enabled', true),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'metadata' => Arr::get($data, 'metadata'),
        ]);

        $this->syncReminders($event, Arr::get($data, 'reminder_minutes', config('calendar.default_reminders', [15])));
        $this->syncAttendees($event, Arr::get($data, 'attendee_ids', []));

        if ($contact) {
            $this->timeline->log(
                $contact,
                'calendar_event_scheduled',
                'Calendar event scheduled',
                $event->title,
                ['calendar_event_id' => $event->id, 'start_at' => $event->start_at?->toIso8601String()],
                $actor,
            );
        }

        return $event->fresh(['type', 'user', 'related', 'reminders', 'attendees']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CalendarEvent $event, array $data, User $actor): CalendarEvent
    {
        $this->ensureAccessible($event, $actor);

        $contact = $this->resolveContact($data, $actor, $event->crmContact());
        $related = $this->resolveRelated($data, $contact, $event);
        $schedule = $this->normalizeSchedule(array_merge([
            'start_at' => $event->start_at,
            'end_at' => $event->end_at,
            'is_all_day' => $event->is_all_day,
        ], $data));

        $event->update([
            'user_id' => $this->resolveAssigneeId($data, $actor, $event->user_id),
            'team_id' => Arr::get($data, 'team_id', $event->team_id),
            'related_type' => $related['type'],
            'related_id' => $related['id'],
            'calendar_event_type_id' => Arr::get($data, 'calendar_event_type_id', $event->calendar_event_type_id),
            'user_calendar_id' => array_key_exists('user_calendar_id', $data)
                ? $this->resolveUserCalendarId($data, $actor, $event->user_calendar_id)
                : $event->user_calendar_id,
            'title' => trim((string) Arr::get($data, 'title', $event->title)),
            'description' => Arr::get($data, 'description', $event->description),
            'start_at' => $schedule['start_at'],
            'end_at' => $schedule['end_at'],
            'is_all_day' => $schedule['is_all_day'],
            'timezone' => Arr::get($data, 'timezone', $event->timezone),
            'location' => Arr::get($data, 'location', $event->location),
            'meeting_link' => Arr::get($data, 'meeting_link', $event->meeting_link),
            'status' => Arr::get($data, 'status', $event->status?->value),
            'priority' => Arr::get($data, 'priority', $event->priority?->value),
            'reminder_enabled' => (bool) Arr::get($data, 'reminder_enabled', $event->reminder_enabled),
            'updated_by' => $actor->id,
        ]);

        if (array_key_exists('reminder_minutes', $data)) {
            $this->syncReminders($event, $data['reminder_minutes'] ?? []);
        }

        if (array_key_exists('attendee_ids', $data)) {
            $this->syncAttendees($event, $data['attendee_ids'] ?? []);
        }

        if (array_key_exists('metadata', $data)) {
            $event->update([
                'metadata' => $data['metadata'],
            ]);
        }

        return $event->fresh(['type', 'user', 'related', 'reminders', 'attendees']);
    }

    public function reschedule(CalendarEvent $event, string $startAt, ?string $endAt, User $actor): CalendarEvent
    {
        return $this->update($event, [
            'start_at' => $startAt,
            'end_at' => $endAt,
            'reminder_minutes' => $event->reminders->pluck('minutes_before')->all(),
        ], $actor);
    }

    public function complete(CalendarEvent $event, User $actor, ?string $completionNotes = null, ?string $outcome = null): CalendarEvent
    {
        $this->ensureAccessible($event, $actor);

        $event->update([
            'status' => CalendarEventStatus::Completed,
            'completed_at' => now(),
            'completion_notes' => $completionNotes,
            'updated_by' => $actor->id,
        ]);

        $contact = $event->crmContact();

        if ($contact) {
            $this->timeline->log(
                $contact,
                'calendar_event_completed',
                'Calendar event completed',
                $event->title,
                ['calendar_event_id' => $event->id, 'outcome' => $outcome],
                $actor,
            );

            $this->createActivityFromEvent($event, $contact, $actor, $outcome);
        }

        return $event->fresh(['type', 'user', 'related']);
    }

    public function markMissed(CalendarEvent $event, User $actor): CalendarEvent
    {
        $this->ensureAccessible($event, $actor);
        $event->update([
            'status' => CalendarEventStatus::Missed,
            'updated_by' => $actor->id,
        ]);

        return $event->fresh();
    }

    public function cancel(CalendarEvent $event, User $actor): CalendarEvent
    {
        $this->ensureAccessible($event, $actor);

        $event->update([
            'status' => CalendarEventStatus::Cancelled,
            'cancelled_at' => now(),
            'updated_by' => $actor->id,
        ]);

        if ($contact = $event->crmContact()) {
            $this->timeline->log(
                $contact,
                'calendar_event_cancelled',
                'Calendar event cancelled',
                $event->title,
                ['calendar_event_id' => $event->id],
                $actor,
            );
        }

        return $event->fresh();
    }

    public function delete(CalendarEvent $event, User $actor): void
    {
        $this->ensureAccessible($event, $actor);

        if ($contact = $event->crmContact()) {
            $this->timeline->log(
                $contact,
                'calendar_event_deleted',
                'Calendar event removed',
                $event->title,
                ['calendar_event_id' => $event->id],
                $actor,
            );
        }

        $event->delete();
    }

    public function createFromTask(Task $task, User $actor, array $overrides = []): CalendarEvent
    {
        $type = CalendarEventType::query()->where('slug', 'personal-task')->first()
            ?? CalendarEventType::query()->orderBy('sort_order')->first();

        return $this->create(array_merge([
            'calendar_event_type_id' => $type?->id,
            'title' => $task->title,
            'description' => $task->description,
            'lead_id' => $task->lead_id,
            'task_id' => $task->id,
            'user_id' => $task->user_id,
            'start_at' => $task->due_at ?? now()->addHour(),
            'end_at' => $task->due_at?->copy()->addHour(),
            'priority' => $task->priority?->value ?? 'normal',
        ], $overrides), $actor);
    }

    /**
     * @return array{event_type_slug: string, title: string}|null
     */
    public function suggestForStage(FunnelStage $stage): ?array
    {
        $action = config('calendar.funnel_stage_actions.'.$stage->slug);

        if (! $action) {
            return null;
        }

        return [
            'event_type_slug' => $action['event_type'],
            'title' => $action['title'],
        ];
    }

    private function createActivityFromEvent(CalendarEvent $event, Model $contact, User $actor, ?string $outcome = null): void
    {
        $type = $event->type;

        if (! $type?->creates_activity || ! $type->activity_type_slug) {
            return;
        }

        $activityType = ActivityType::query()->where('slug', $type->activity_type_slug)->first();

        if (! $activityType) {
            return;
        }

        $this->activities->log([
            'activity_type_id' => $activityType->id,
            'contact_type' => $contact->getMorphClass(),
            'contact_id' => $contact->id,
            'title' => $event->title,
            'description' => $event->completion_notes ?: $event->description,
            'completed_at' => now(),
            'outcome' => $outcome ?? 'completed',
            'metadata' => [
                'calendar_event_id' => $event->id,
                'phone_call_result' => $outcome,
            ],
        ], $actor);
    }

    /**
     * @param  list<int>  $minutesList
     */
    private function syncReminders(CalendarEvent $event, array $minutesList): void
    {
        $event->reminders()->delete();

        if (! $event->reminder_enabled || ! $event->start_at) {
            return;
        }

        foreach (collect($minutesList)->filter()->unique() as $minutes) {
            $event->reminders()->create([
                'channel' => 'database',
                'minutes_before' => (int) $minutes,
                'remind_at' => $event->start_at->copy()->subMinutes((int) $minutes),
            ]);
        }
    }

    /**
     * @param  list<int>  $userIds
     */
    private function syncAttendees(CalendarEvent $event, array $userIds): void
    {
        $event->attendees()->delete();

        foreach (collect($userIds)->filter()->unique() as $userId) {
            $event->attendees()->create([
                'user_id' => (int) $userId,
                'response' => 'pending',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{type: ?string, id: ?int}
     */
    private function resolveRelated(array $data, ?Model $contact, ?CalendarEvent $existing = null): array
    {
        if ($contact) {
            return ['type' => $contact->getMorphClass(), 'id' => $contact->id];
        }

        if ($stageId = Arr::get($data, 'funnel_stage_id')) {
            return ['type' => (new FunnelStage)->getMorphClass(), 'id' => (int) $stageId];
        }

        // Explicit related_type/related_id (including null) means the caller cleared the link.
        if (Arr::has($data, 'related_type') || Arr::has($data, 'related_id')) {
            return ['type' => null, 'id' => null];
        }

        return [
            'type' => $existing?->related_type,
            'id' => $existing?->related_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveContact(array $data, User $actor, ?Model $fallback = null): Lead|Prospect|Customer|Recruit|null
    {
        if (Arr::has($data, 'related_type') || Arr::has($data, 'related_id')) {
            $type = (string) Arr::get($data, 'related_type', '');
            $id = (int) Arr::get($data, 'related_id', 0);

            if ($type === '' || $id <= 0) {
                return null;
            }

            $class = in_array($type, ['lead', 'prospect', 'customer', 'recruit'], true)
                ? CrmContactResolver::modelClassForMorph($type)
                : $type;

            if (is_subclass_of($class, Model::class)) {
                return CrmScope::contacts($class::query(), $actor)->findOrFail($id);
            }

            return null;
        }

        if (Arr::has($data, 'lead_id')) {
            $leadId = Arr::get($data, 'lead_id');

            if (blank($leadId)) {
                return null;
            }

            return CrmScope::contacts(Lead::query(), $actor)->findOrFail((int) $leadId);
        }

        return $fallback instanceof Lead
            || $fallback instanceof Prospect
            || $fallback instanceof Customer
            || $fallback instanceof Recruit
            ? $fallback
            : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{start_at: Carbon, end_at: ?Carbon, is_all_day: bool}
     */
    private function normalizeSchedule(array $data): array
    {
        $isAllDay = (bool) Arr::get($data, 'is_all_day', false);
        $start = Carbon::parse(Arr::get($data, 'start_at'));
        $endRaw = Arr::get($data, 'end_at');

        if ($isAllDay) {
            $end = Carbon::parse($endRaw ?: $start)->endOfDay();
            $start = $start->copy()->startOfDay();

            if ($end->lt($start)) {
                $end = $start->copy()->endOfDay();
            }

            return [
                'start_at' => $start,
                'end_at' => $end,
                'is_all_day' => true,
            ];
        }

        $end = $endRaw ? Carbon::parse($endRaw) : null;

        return [
            'start_at' => $start,
            'end_at' => $end,
            'is_all_day' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveAssigneeId(array $data, User $actor, ?int $fallback = null): int
    {
        if (CalendarScope::userCanViewAll($actor) || CalendarScope::userCanViewTeam($actor)) {
            return (int) (Arr::get($data, 'user_id') ?: $fallback ?: $actor->id);
        }

        return $actor->id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveUserCalendarId(array $data, User $actor, ?int $fallback = null): ?int
    {
        $calendars = app(UserCalendarService::class);
        $accessible = $calendars->accessibleCalendarIds($actor);

        if (Arr::has($data, 'user_calendar_id')) {
            $calendarId = Arr::get($data, 'user_calendar_id');

            if (blank($calendarId)) {
                return null;
            }

            $calendarId = (int) $calendarId;

            if (! in_array($calendarId, $accessible, true)) {
                throw ValidationException::withMessages([
                    'user_calendar_id' => 'You do not have access to that calendar.',
                ]);
            }

            return $calendarId;
        }

        if ($fallback && in_array($fallback, $accessible, true)) {
            return $fallback;
        }

        $calendars->ensureDefaults($actor);

        return $calendars->defaultCalendarId($actor);
    }

    private function ensureAccessible(CalendarEvent $event, User $actor): void
    {
        if (! CalendarScope::eventIsAccessible($event, $actor)) {
            throw ValidationException::withMessages([
                'event' => 'You do not have permission to manage this calendar event.',
            ]);
        }
    }
}
