<?php

namespace App\Services\Crm;

use App\Enums\Crm\CalendarEventCategory;
use App\Enums\Crm\CalendarEventStatus;
use App\Enums\Crm\DemonstrationStatus;
use App\Enums\Crm\TaskStatus;
use App\Models\Crm\ActivityType;
use App\Models\Crm\Appointment;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Demonstration;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\Task;
use App\Models\User;
use App\Support\Crm\CalendarScope;
use App\Support\Crm\CrmScope;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use stdClass;

class CalendarQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, stdClass>
     */
    public function entries(Carbon $rangeStart, Carbon $rangeEnd, array $filters = [], ?User $user = null): Collection
    {
        $user ??= auth()->user();
        $entries = collect();

        if ($filters['show_events'] ?? true) {
            $entries = $entries->merge($this->calendarEvents($rangeStart, $rangeEnd, $filters, $user));
        }

        if ($filters['show_tasks'] ?? true) {
            $entries = $entries->merge($this->taskEntries($rangeStart, $rangeEnd, $filters, $user));
        }

        if ($filters['show_appointments'] ?? true) {
            $entries = $entries->merge($this->appointmentEntries($rangeStart, $rangeEnd, $filters, $user));
        }

        if (($filters['show_events'] ?? true) && empty($filters['show_category']) && empty($filters['event_type_id'])) {
            $entries = $this->mergeDemonstrationEntries($entries, $rangeStart, $rangeEnd, $user);
        }

        return $entries->sortBy('start_at')->values();
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function upcomingScheduledEvents(int $limit = 6, ?User $user = null): Collection
    {
        return $this->entries(now()->startOfDay(), now()->addDays(14), [
            'show_tasks' => false,
            'show_appointments' => true,
            'show_events' => true,
        ], $user)
            ->where('start_at', '>=', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereIn('kind', ['event', 'appointment', 'demonstration'])
            ->reject(fn (stdClass $entry) => $this->isTaskLinkedEntry($entry))
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function upcomingActionTasks(int $limit = 6, ?User $user = null): Collection
    {
        return $this->entries(now()->startOfDay(), now()->addDays(14), [
            'show_tasks' => true,
            'show_appointments' => false,
            'show_events' => false,
        ], $user)
            ->where('start_at', '>=', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function upcomingShowsAndDemos(int $limit = 6, ?User $user = null, array $filters = []): Collection
    {
        return $this->entries(now()->startOfDay(), now()->addDays(30), array_merge([
            'show_tasks' => false,
            'show_appointments' => false,
        ], $filters), $user)
            ->where('start_at', '>=', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->filter(fn (stdClass $entry) => $this->isShowOrDemoEntry($entry))
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function phoneCallsToday(?User $user = null, int $limit = 12): Collection
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
            ->whereBetween('start_at', [now()->startOfDay(), now()->endOfDay()])
            ->where('start_at', '>=', now())
            ->whereNotIn('status', [CalendarEventStatus::Completed->value, CalendarEventStatus::Cancelled->value])
            ->orderBy('start_at')
            ->limit($limit)
            ->get()
            ->map(fn (CalendarEvent $event) => $this->mapPhoneCallEntry($event));
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function overduePhoneCalls(?User $user = null, int $limit = 8): Collection
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
            ->where('start_at', '<', now())
            ->whereNotIn('status', [CalendarEventStatus::Completed->value, CalendarEventStatus::Cancelled->value])
            ->orderBy('start_at')
            ->limit($limit)
            ->get()
            ->map(fn (CalendarEvent $event) => $this->mapPhoneCallEntry($event));
    }

    /**
     * @return list<string>
     */
    private function phoneCallTypeSlugs(): array
    {
        return ['phone-call', 'follow-up', 'post-show-follow-up'];
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function overdueFollowUps(?User $user = null): Collection
    {
        $user ??= auth()->user();

        $leads = CrmScope::leads(Lead::query(), $user)
            ->followUpDueToday()
            ->where('next_follow_up_at', '<', now()->startOfDay())
            ->orderBy('next_follow_up_at')
            ->limit(8)
            ->get()
            ->map(function (Lead $lead) {
                $entry = new stdClass;
                $entry->kind = 'lead';
                $entry->id = $lead->id;
                $entry->contact_name = $lead->fullName();
                $entry->due_at = $lead->next_follow_up_at;
                $entry->phone = $lead->phone;
                $entry->reason = null;
                $entry->status = null;

                return $entry;
            });

        $phoneCalls = $this->overduePhoneCalls($user, 8)
            ->map(function (stdClass $entry) {
                $entry->kind = 'phone_call';
                $entry->due_at = $entry->start_at;

                return $entry;
            });

        return $leads
            ->concat($phoneCalls)
            ->sortBy(fn (stdClass $entry) => $entry->due_at?->timestamp ?? 0)
            ->take(8)
            ->values();
    }

    /**
     * @return Collection<int, Task>
     */
    public function tasksDueToday(?User $user = null): Collection
    {
        return CrmScope::tasks(Task::query(), $user)
            ->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value])
            ->whereDate('due_at', '<=', now()->toDateString())
            ->orderBy('due_at')
            ->limit(8)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, stdClass>
     */
    private function calendarEvents(Carbon $rangeStart, Carbon $rangeEnd, array $filters, ?User $user): Collection
    {
        return CalendarScope::events(CalendarEvent::query(), $user)
            ->with(['type', 'user', 'related'])
            ->where(function ($query) use ($rangeStart, $rangeEnd) {
                $query->whereBetween('start_at', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('end_at', [$rangeStart, $rangeEnd]);
            })
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['team_id'] ?? null, fn ($q, $id) => $q->where('team_id', $id))
            ->when($filters['event_type_id'] ?? null, fn ($q, $id) => $q->where('calendar_event_type_id', $id))
            ->when($filters['show_category'] ?? null, function ($q, $category) {
                $q->whereHas('type', fn ($typeQuery) => $typeQuery->where('category', $category));
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($q, $priority) => $q->where('priority', $priority))
            ->when($filters['lead_id'] ?? null, function ($q, $leadId) {
                $q->where('related_type', Lead::class)->where('related_id', $leadId);
            })
            ->when($filters['funnel_stage_id'] ?? null, function ($q, $stageId) {
                $q->where('related_type', FunnelStage::class)->where('related_id', $stageId);
            })
            ->orderBy('start_at')
            ->get()
            ->map(fn (CalendarEvent $event) => $this->mapEvent($event));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, stdClass>
     */
    private function taskEntries(Carbon $rangeStart, Carbon $rangeEnd, array $filters, ?User $user): Collection
    {
        if (($filters['status'] ?? null) && ! in_array($filters['status'], ['pending', 'in_progress'], true)) {
            return collect();
        }

        return CrmScope::tasks(Task::query(), $user)
            ->with(['lead', 'user'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$rangeStart, $rangeEnd])
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['lead_id'] ?? null, fn ($q, $id) => $q->whereLeadId($id))
            ->when($filters['priority'] ?? null, fn ($q, $priority) => $q->where('priority', $priority))
            ->get()
            ->map(function (Task $task) {
                $entry = new stdClass;
                $entry->key = 'task-'.$task->id;
                $entry->kind = 'task';
                $entry->id = $task->id;
                $entry->title = $task->title;
                $entry->start_at = $task->due_at;
                $entry->end_at = $task->due_at?->copy()->addHour();
                $entry->color = 'amber';
                $entry->status = $task->status?->value ?? 'pending';
                $entry->priority = $task->priority?->value ?? 'normal';
                $entry->lead_id = $task->contact_id;
                $entry->lead_name = $task->lead?->fullName();
                $entry->user_name = $task->user?->name;
                $entry->type_name = 'Task';

                return $entry;
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, stdClass>
     */
    private function appointmentEntries(Carbon $rangeStart, Carbon $rangeEnd, array $filters, ?User $user): Collection
    {
        if ($filters['event_type_id'] ?? null) {
            return collect();
        }

        return CrmScope::appointments(Appointment::query(), $user)
            ->with(['lead', 'user'])
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['lead_id'] ?? null, fn ($q, $id) => $q->whereLeadId($id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->get()
            ->map(function (Appointment $appointment) {
                $entry = new stdClass;
                $entry->key = 'appointment-'.$appointment->id;
                $entry->kind = 'appointment';
                $entry->id = $appointment->id;
                $entry->title = $appointment->title;
                $entry->start_at = $appointment->starts_at;
                $entry->end_at = $appointment->ends_at ?? $appointment->starts_at?->copy()->addHour();
                $entry->color = 'blue';
                $entry->status = $appointment->status?->value ?? 'scheduled';
                $entry->priority = 'normal';
                $entry->lead_id = $appointment->contact_id;
                $entry->lead_name = $appointment->lead?->fullName();
                $entry->user_name = $appointment->user?->name;
                $entry->type_name = 'Appointment';

                return $entry;
            });
    }

    /**
     * @param  Collection<int, stdClass>  $entries
     * @return Collection<int, stdClass>
     */
    private function mergeDemonstrationEntries(Collection $entries, Carbon $rangeStart, Carbon $rangeEnd, ?User $user): Collection
    {
        $linkedEventIds = $entries
            ->where('kind', 'event')
            ->pluck('id');

        $demonstrations = $this->demonstrationEntries($rangeStart, $rangeEnd, $user)
            ->reject(fn (stdClass $entry) => $entry->calendar_event_id && $linkedEventIds->contains($entry->calendar_event_id));

        return $entries->merge($demonstrations);
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function demonstrationEntries(Carbon $rangeStart, Carbon $rangeEnd, ?User $user): Collection
    {
        $user ??= auth()->user();

        if (! $user || ! Schema::hasTable('demonstrations')) {
            return collect();
        }

        return Demonstration::query()
            ->with(['contact', 'demonstrator'])
            ->forAccessibleContacts($user)
            ->whereIn('status', [
                DemonstrationStatus::Scheduled->value,
                DemonstrationStatus::Confirmed->value,
            ])
            ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd])
            ->orderBy('scheduled_at')
            ->get()
            ->map(function (Demonstration $demo) {
                $leadName = $demo->contact?->fullName();
                $typeLabel = $demo->type?->label() ?? 'Demo';

                $entry = new stdClass;
                $entry->key = 'demonstration-'.$demo->id;
                $entry->kind = 'demonstration';
                $entry->id = $demo->id;
                $entry->calendar_event_id = $demo->calendar_event_id;
                $entry->title = $leadName ? $typeLabel.' with '.$leadName : $typeLabel;
                $entry->start_at = $demo->scheduled_at;
                $entry->end_at = $demo->scheduled_at?->copy()->addMinutes($demo->duration_minutes ?? 60);
                $entry->color = 'violet';
                $entry->status = $demo->status?->value ?? 'scheduled';
                $entry->priority = 'normal';
                $entry->lead_id = $demo->contact_id;
                $entry->lead_name = $leadName;
                $entry->user_name = $demo->demonstrator?->name;
                $entry->type_name = $typeLabel;
                $entry->location = $demo->venue;
                $entry->meeting_link = $demo->type?->isOnline() ? $demo->venue : null;
                $entry->description = $demo->notes;

                return $entry;
            });
    }

    private function isTaskLinkedEntry(stdClass $entry): bool
    {
        if ($entry->kind === 'task') {
            return true;
        }

        return ! empty($entry->task_id)
            || ($entry->type_slug ?? null) === 'personal-task';
    }

    private function isShowOrDemoEntry(stdClass $entry): bool
    {
        if ($entry->kind === 'demonstration') {
            return true;
        }

        if ($entry->kind !== 'event') {
            return false;
        }

        return ($entry->is_show ?? false)
            || in_array($entry->type_category ?? null, [
                CalendarEventCategory::Show->value,
                CalendarEventCategory::Demo->value,
            ], true);
    }

    private function mapPhoneCallEntry(CalendarEvent $event): stdClass
    {
        $entry = $this->mapEvent($event);
        $entry->contact_name = $event->lead()?->fullName()
            ?? ($event->metadata['other_contact_name'] ?? null)
            ?? $event->attendees->first()?->user?->name
            ?? $event->title;
        $entry->phone = filled($event->metadata['phone_number'] ?? null)
            ? (string) $event->metadata['phone_number']
            : $event->lead()?->phone;
        $entry->reason = \App\Support\Portal\PhoneCallReasons::label(
            (string) ($event->metadata['phone_call_reason'] ?? ''),
        );

        return $entry;
    }

    private function mapEvent(CalendarEvent $event): stdClass
    {
        $entry = new stdClass;
        $entry->key = 'event-'.$event->id;
        $entry->kind = 'event';
        $entry->id = $event->id;
        $entry->title = $event->title;
        $entry->start_at = $event->start_at;
        $entry->end_at = $event->end_at ?? $event->start_at?->copy()->addHour();
        $entry->color = $event->type?->color ?? 'teal';
        $entry->status = $event->status?->value ?? 'scheduled';
        $entry->priority = $event->priority?->value ?? 'normal';
        $entry->lead_id = $event->related instanceof Lead ? $event->related->id : null;
        $entry->lead_name = $event->related instanceof Lead ? $event->related->fullName() : null;
        $entry->user_name = $event->user?->name;
        $entry->type_name = $event->type?->name ?? 'Event';
        $entry->type_slug = $event->type?->slug;
        $entry->task_id = $event->task_id;
        $entry->type_category = $event->type?->category?->value;
        $entry->is_show = $event->type?->isShow() ?? false;
        $entry->business_line = $event->business_line?->value;
        $entry->business_line_label = $event->business_line?->shortLabel();
        $entry->location = $event->location;
        $entry->meeting_link = $event->meeting_link;
        $entry->description = $event->description;

        return $entry;
    }
}
