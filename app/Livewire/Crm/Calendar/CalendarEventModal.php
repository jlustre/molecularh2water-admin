<?php

namespace App\Livewire\Crm\Calendar;

use App\Enums\Crm\CalendarEventCategory;
use App\Enums\Crm\CalendarEventPriority;
use App\Enums\Crm\CalendarEventStatus;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\UserCalendar;
use App\Models\User;
use App\Services\Crm\CalendarEventService;
use App\Services\Crm\UserCalendarService;
use App\Support\Crm\CalendarRecurrence;
use App\Support\Crm\CalendarScope;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
class CalendarEventModal extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?int $editingEventId = null;

    /** Composite key: "{morph}:{id}" e.g. "lead:12", "prospect:4". */
    public ?string $related_key = null;

    public ?int $calendar_event_type_id = null;

    public ?int $user_id = null;

    public ?int $user_calendar_id = null;

    public string $title = '';

    public string $description = '';

    public string $start_at = '';

    public string $end_at = '';

    public bool $is_all_day = false;

    public string $recurrence = 'none';

    public int $recurrence_count = 8;

    public ?string $recurrenceSummary = null;

    public string $timezone = '';

    public string $location = '';

    public string $meeting_link = '';

    public string $status = 'scheduled';

    public string $priority = 'normal';

    public bool $reminder_enabled = true;

    /** @var list<int> */
    public array $reminder_minutes = [15];

    /** @var list<int> */
    public array $attendee_ids = [];

    public function mount(): void
    {
        $this->timezone = config('calendar.default_timezone', config('app.timezone'));
        $this->user_id = auth()->id();
    }

    #[On('open-calendar-create')]
    public function openCreate(?string $date = null, ?int $lead = null): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $this->resetEventForm();
        $this->editingEventId = null;

        if ($lead) {
            $this->related_key = 'lead:'.$lead;
        }

        $start = $date
            ? Carbon::parse($date)->setHour(10)->setMinute(0)
            : now()->addHour()->startOfHour();

        $this->is_all_day = false;
        $this->start_at = $start->format('Y-m-d\TH:i');
        $this->end_at = $start->copy()->addHour()->format('Y-m-d\TH:i');
        $this->applyFormDefaultsForContext($lead !== null);
        $this->show = true;
    }

    #[On('open-calendar-create-show')]
    public function openCreateShow(string $slug, ?string $date = null): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $type = CalendarEventType::query()->active()->where('slug', $slug)->firstOrFail();

        $this->resetEventForm();
        $this->editingEventId = null;
        $start = $date
            ? Carbon::parse($date)->setHour(10)->setMinute(0)
            : now()->addDay()->setHour(10)->setMinute(0);
        $durationHours = (int) config('calendar.show_default_duration_hours', 2);

        $this->calendar_event_type_id = $type->id;
        $this->title = $type->name;
        $this->is_all_day = false;
        $this->start_at = $start->format('Y-m-d\TH:i');
        $this->end_at = $start->copy()->addHours($durationHours)->format('Y-m-d\TH:i');
        $this->show = true;
    }

    #[On('open-calendar-edit')]
    public function openEdit(int $eventId): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $event = CalendarScope::events(\App\Models\Crm\CalendarEvent::query())
            ->with(['reminders', 'attendees'])
            ->findOrFail($eventId);

        $this->editingEventId = $event->id;
        $this->calendar_event_type_id = $event->calendar_event_type_id;
        $this->user_id = $event->user_id;
        $this->user_calendar_id = $event->user_calendar_id;
        $this->related_key = $this->contactKey($event->crmContact());
        $this->title = $event->title;
        $this->description = (string) $event->description;
        $this->is_all_day = (bool) $event->is_all_day;
        $this->start_at = $this->is_all_day
            ? ($event->start_at?->format('Y-m-d') ?? '')
            : ($event->start_at?->format('Y-m-d\TH:i') ?? '');
        $this->end_at = $this->is_all_day
            ? ($event->end_at?->format('Y-m-d') ?? $event->start_at?->format('Y-m-d') ?? '')
            : ($event->end_at?->format('Y-m-d\TH:i') ?? '');
        $this->recurrence = 'none';
        $this->recurrence_count = 8;
        $this->recurrenceSummary = $this->summarizeRecurrence($event->metadata ?? []);
        $this->timezone = $event->timezone ?? $this->timezone;
        $this->location = (string) $event->location;
        $this->meeting_link = (string) $event->meeting_link;
        $this->status = $event->status?->value ?? 'scheduled';
        $this->priority = $event->priority?->value ?? 'normal';
        $this->reminder_enabled = $event->reminder_enabled;
        $this->reminder_minutes = $event->reminders->pluck('minutes_before')->map(fn ($m) => (int) $m)->all() ?: [15];
        $this->attendee_ids = $event->attendees->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->all();
        $this->show = true;
    }

    public function updatedUserCalendarId(mixed $value): void
    {
        $this->applyFormDefaultsForContext(filled($this->related_key));
    }

    public function updatedCalendarEventTypeId(mixed $value): void
    {
        $this->syncFieldsForEventType();
    }

    public function updatedIsAllDay(mixed $value): void
    {
        if ($this->is_all_day) {
            $start = $this->start_at !== '' ? Carbon::parse($this->start_at) : now();
            $end = $this->end_at !== '' ? Carbon::parse($this->end_at) : $start->copy();
            $this->start_at = $start->format('Y-m-d');
            $this->end_at = $end->format('Y-m-d');

            return;
        }

        $start = $this->start_at !== ''
            ? Carbon::parse($this->start_at)->setHour(10)->setMinute(0)
            : now()->addHour()->startOfHour();
        $end = $this->end_at !== ''
            ? Carbon::parse($this->end_at)->setHour(11)->setMinute(0)
            : $start->copy()->addHour();

        if ($end->lte($start)) {
            $end = $start->copy()->addHour();
        }

        $this->start_at = $start->format('Y-m-d\TH:i');
        $this->end_at = $end->format('Y-m-d\TH:i');
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetEventForm();
    }

    public function save(CalendarEventService $events): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $this->syncFieldsForEventType();

        $data = $this->validate($this->eventRules());
        $related = $this->usesCrmFields()
            ? $this->parseRelatedKey($data['related_key'] ?? null)
            : null;

        if ($related) {
            $contact = CrmScope::contacts(
                CrmContactResolver::modelClassForMorph($related['type'])::query()
            )->findOrFail($related['id']);
            $this->authorize('view', $contact);
        }

        $payload = array_merge($data, [
            'related_type' => $related['type'] ?? null,
            'related_id' => $related['id'] ?? null,
            'is_all_day' => $this->is_all_day,
            'recurrence' => $this->editingEventId ? 'none' : $this->recurrence,
            'recurrence_count' => $this->recurrence_count,
            'reminder_minutes' => $this->reminder_minutes,
            'attendee_ids' => $this->usesCrmFields() ? $this->attendee_ids : [],
            'user_id' => $this->usesCrmFields() ? ($data['user_id'] ?? auth()->id()) : auth()->id(),
            'meeting_link' => $this->usesCrmFields() ? ($data['meeting_link'] ?? null) : null,
        ]);
        unset($payload['related_key']);

        if ($this->editingEventId) {
            $event = CalendarScope::events(\App\Models\Crm\CalendarEvent::query())->findOrFail($this->editingEventId);
            $events->update($event, $payload, auth()->user());
            $message = 'Event updated.';
        } else {
            $created = $events->createSeries($payload, auth()->user());
            $message = $created->count() > 1
                ? "Scheduled {$created->count()} recurring events."
                : 'Event scheduled.';
        }

        $this->close();
        $this->dispatch('calendar-status', message: $message);
        $this->dispatch('calendar-updated')->to(CalendarGrid::class);
        $this->dispatch('calendar-updated')->to(CalendarWidgets::class);
    }

    public function render(UserCalendarService $calendars)
    {
        $user = auth()->user();
        $userCalendars = collect();

        if ($user && Schema::hasTable('user_calendars')) {
            $userCalendars = $calendars->ensureDefaults($user);
            if (! $this->user_calendar_id) {
                $this->user_calendar_id = $calendars->defaultCalendarId($user);
            }
        }

        $eventTypesQuery = CalendarEventType::query()->active()->orderBy('sort_order');

        if ($this->isPersonalCalendar()) {
            $eventTypesQuery->where('category', CalendarEventCategory::Internal->value);
        }

        $eventTypes = $eventTypesQuery->get();

        return view('livewire.crm.calendar.calendar-event-modal', [
            'eventTypes' => $eventTypes,
            'eventTypesByCategory' => $eventTypes
                ->groupBy(fn (CalendarEventType $type) => $type->category?->label() ?? 'Other'),
            'statuses' => CalendarEventStatus::cases(),
            'priorities' => CalendarEventPriority::cases(),
            'reminderPresets' => config('calendar.reminder_presets', []),
            'contacts' => $this->usesCrmFields() ? $this->contactOptions() : collect(),
            'assignableUsers' => $this->assignableUsers(),
            'userCalendars' => $userCalendars,
            'canAssign' => $this->usesCrmFields()
                && (CalendarScope::userCanViewAll() || CalendarScope::userCanViewTeam()),
            'usesCrmFields' => $this->usesCrmFields(),
            'isPersonalCalendar' => $this->isPersonalCalendar(),
            'canManage' => auth()->user()?->hasPermission('calendar.manage'),
            'recurrenceOptions' => CalendarRecurrence::options(),
            'recurrenceCounts' => CalendarRecurrence::counts(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventRules(): array
    {
        return [
            'calendar_event_type_id' => ['required', 'exists:calendar_event_types,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'user_calendar_id' => ['nullable', 'integer', 'exists:user_calendars,id'],
            'related_key' => ['nullable', 'string', 'regex:/^(lead|prospect|customer|recruit):\d+$/'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_at' => ['required', 'date'],
            'end_at' => [
                'nullable',
                'date',
                $this->is_all_day ? 'after_or_equal:start_at' : 'after:start_at',
            ],
            'is_all_day' => ['boolean'],
            'recurrence' => ['required', Rule::in(CalendarRecurrence::ruleValues())],
            'recurrence_count' => [
                Rule::requiredIf(fn () => ! $this->editingEventId && $this->recurrence !== 'none'),
                'integer',
                Rule::in(CalendarRecurrence::counts()),
            ],
            'timezone' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'status' => ['required', Rule::in(array_column(CalendarEventStatus::cases(), 'value'))],
            'priority' => ['required', Rule::in(array_column(CalendarEventPriority::cases(), 'value'))],
            'reminder_enabled' => ['boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function summarizeRecurrence(array $metadata): ?string
    {
        $rule = $metadata['recurrence_rule'] ?? null;
        $index = $metadata['recurrence_index'] ?? null;
        $total = $metadata['recurrence_total'] ?? null;

        if (! $rule || $rule === 'none' || ! $index || ! $total) {
            return null;
        }

        return sprintf(
            'Occurrence %d of %d · %s. Edits apply to this occurrence only.',
            (int) $index,
            (int) $total,
            CalendarRecurrence::labelFor((string) $rule),
        );
    }

    private function usesCrmFields(): bool
    {
        $type = $this->selectedEventType();

        if (! $type) {
            // Personal calendars only allow internal types; default to simplified form.
            return ! $this->isPersonalCalendar();
        }

        return $type->category !== CalendarEventCategory::Internal;
    }

    private function isPersonalCalendar(): bool
    {
        if (! $this->user_calendar_id || ! Schema::hasTable('user_calendars')) {
            return false;
        }

        return UserCalendar::query()->whereKey($this->user_calendar_id)->value('kind') === 'personal';
    }

    private function selectedEventType(): ?CalendarEventType
    {
        if (! $this->calendar_event_type_id) {
            return null;
        }

        return CalendarEventType::query()->find($this->calendar_event_type_id);
    }

    private function syncFieldsForEventType(): void
    {
        if ($this->usesCrmFields()) {
            return;
        }

        $this->related_key = null;
        $this->meeting_link = '';
        $this->user_id = auth()->id();
        $this->attendee_ids = [];
    }

    private function applyFormDefaultsForContext(bool $keepRelated = false): void
    {
        if ($keepRelated && $this->isPersonalCalendar()) {
            // CRM-linked creates belong on Work (or another non-personal) calendar.
            $this->preferNonPersonalCalendar();
        }

        if ($this->isPersonalCalendar()) {
            $this->calendar_event_type_id = CalendarEventType::query()
                ->active()
                ->where('category', CalendarEventCategory::Internal->value)
                ->where('slug', 'personal-task')
                ->value('id')
                ?? CalendarEventType::query()
                    ->active()
                    ->where('category', CalendarEventCategory::Internal->value)
                    ->orderBy('sort_order')
                    ->value('id');

            $this->syncFieldsForEventType();

            return;
        }

        $type = $this->selectedEventType();

        // Work / custom calendars default to a CRM type when none is set, or when
        // the current type is Internal (typical after leaving the Personal calendar).
        if (! $type || $type->category === CalendarEventCategory::Internal) {
            $this->calendar_event_type_id = CalendarEventType::query()->active()->shows()->orderBy('sort_order')->value('id')
                ?? CalendarEventType::query()
                    ->active()
                    ->where('category', '!=', CalendarEventCategory::Internal->value)
                    ->orderBy('sort_order')
                    ->value('id')
                ?? CalendarEventType::query()->active()->orderBy('sort_order')->value('id');
        }

        $this->syncFieldsForEventType();
    }

    private function preferNonPersonalCalendar(): void
    {
        if (! Schema::hasTable('user_calendars') || ! auth()->user()) {
            return;
        }

        $calendars = app(UserCalendarService::class)->ensureDefaults(auth()->user());
        $preferred = $calendars->first(fn (UserCalendar $calendar) => $calendar->kind === 'work')
            ?? $calendars->first(fn (UserCalendar $calendar) => $calendar->kind !== 'personal');

        if ($preferred) {
            $this->user_calendar_id = $preferred->id;
        }
    }

    private function resetEventForm(): void
    {
        $this->reset([
            'editingEventId',
            'related_key',
            'title',
            'description',
            'start_at',
            'end_at',
            'location',
            'meeting_link',
            'user_calendar_id',
        ]);
        $this->is_all_day = false;
        $this->recurrence = 'none';
        $this->recurrence_count = 8;
        $this->recurrenceSummary = null;
        $this->status = CalendarEventStatus::Scheduled->value;
        $this->priority = CalendarEventPriority::Normal->value;
        $this->reminder_enabled = true;
        $this->reminder_minutes = config('calendar.default_reminders', [15]);
        $this->attendee_ids = [];
        $this->user_id = auth()->id();

        if (auth()->user() && Schema::hasTable('user_calendars')) {
            $calendars = app(UserCalendarService::class);
            $calendars->ensureDefaults(auth()->user());
            $this->user_calendar_id = $calendars->defaultCalendarId(auth()->user());
        }
    }

    /**
     * @return Collection<int, Model>
     */
    private function contactOptions(): Collection
    {
        $columns = ['id', 'first_name', 'last_name', 'email', 'lifecycle_id'];

        return collect([Lead::class, Prospect::class, Customer::class, Recruit::class])
            ->flatMap(fn (string $class) => CrmScope::contacts($class::query())
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(200)
                ->get($columns))
            ->sortBy([
                ['first_name', 'asc'],
                ['last_name', 'asc'],
            ])
            ->values()
            ->take(200);
    }

    private function contactKey(?Model $contact): ?string
    {
        if (! $contact) {
            return null;
        }

        return $contact->getMorphClass().':'.$contact->id;
    }

    /**
     * @return array{type: string, id: int}|null
     */
    private function parseRelatedKey(?string $key): ?array
    {
        if (! $key || ! preg_match('/^(lead|prospect|customer|recruit):(\d+)$/', $key, $matches)) {
            return null;
        }

        return [
            'type' => $matches[1],
            'id' => (int) $matches[2],
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function assignableUsers(): Collection
    {
        if (! CalendarScope::userCanViewAll() && ! CalendarScope::userCanViewTeam()) {
            return collect([auth()->user()])->filter();
        }

        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['consultant', 'manager', 'team-admin', 'admin']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
