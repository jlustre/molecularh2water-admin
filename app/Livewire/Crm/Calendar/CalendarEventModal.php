<?php

namespace App\Livewire\Crm\Calendar;

use App\Enums\Crm\CalendarEventPriority;
use App\Enums\Crm\CalendarEventStatus;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Services\Crm\CalendarEventService;
use App\Support\Crm\CalendarScope;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
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

    public string $title = '';

    public string $description = '';

    public string $start_at = '';

    public string $end_at = '';

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

        $this->start_at = $start->format('Y-m-d\TH:i');
        $this->end_at = $start->copy()->addHour()->format('Y-m-d\TH:i');
        $this->calendar_event_type_id = CalendarEventType::query()->active()->shows()->orderBy('sort_order')->value('id')
            ?? CalendarEventType::query()->active()->orderBy('sort_order')->value('id');
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
        $this->related_key = $this->contactKey($event->crmContact());
        $this->title = $event->title;
        $this->description = (string) $event->description;
        $this->start_at = $event->start_at?->format('Y-m-d\TH:i') ?? '';
        $this->end_at = $event->end_at?->format('Y-m-d\TH:i') ?? '';
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

    public function close(): void
    {
        $this->show = false;
        $this->resetEventForm();
    }

    public function save(CalendarEventService $events): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $data = $this->validate($this->eventRules());
        $related = $this->parseRelatedKey($data['related_key'] ?? null);

        if ($related) {
            $contact = CrmScope::contacts(
                CrmContactResolver::modelClassForMorph($related['type'])::query()
            )->findOrFail($related['id']);
            $this->authorize('view', $contact);
        }

        $payload = array_merge($data, [
            'related_type' => $related['type'] ?? null,
            'related_id' => $related['id'] ?? null,
            'reminder_minutes' => $this->reminder_minutes,
            'attendee_ids' => $this->attendee_ids,
        ]);
        unset($payload['related_key']);

        if ($this->editingEventId) {
            $event = CalendarScope::events(\App\Models\Crm\CalendarEvent::query())->findOrFail($this->editingEventId);
            $events->update($event, $payload, auth()->user());
            $message = 'Event updated.';
        } else {
            $events->create($payload, auth()->user());
            $message = 'Event scheduled.';
        }

        $this->close();
        $this->dispatch('calendar-status', message: $message);
        $this->dispatch('calendar-updated');
    }

    public function render()
    {
        return view('livewire.crm.calendar.calendar-event-modal', [
            'eventTypes' => CalendarEventType::query()->active()->orderBy('sort_order')->get(),
            'eventTypesByCategory' => CalendarEventType::query()
                ->active()
                ->orderBy('sort_order')
                ->get()
                ->groupBy(fn (CalendarEventType $type) => $type->category?->label() ?? 'Other'),
            'statuses' => CalendarEventStatus::cases(),
            'priorities' => CalendarEventPriority::cases(),
            'reminderPresets' => config('calendar.reminder_presets', []),
            'contacts' => $this->contactOptions(),
            'assignableUsers' => $this->assignableUsers(),
            'canAssign' => CalendarScope::userCanViewAll() || CalendarScope::userCanViewTeam(),
            'canManage' => auth()->user()?->hasPermission('calendar.manage'),
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
            'related_key' => ['nullable', 'string', 'regex:/^(lead|prospect|customer|recruit):\d+$/'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'status' => ['required', Rule::in(array_column(CalendarEventStatus::cases(), 'value'))],
            'priority' => ['required', Rule::in(array_column(CalendarEventPriority::cases(), 'value'))],
            'reminder_enabled' => ['boolean'],
        ];
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
        ]);
        $this->status = CalendarEventStatus::Scheduled->value;
        $this->priority = CalendarEventPriority::Normal->value;
        $this->reminder_enabled = true;
        $this->reminder_minutes = config('calendar.default_reminders', [15]);
        $this->attendee_ids = [];
        $this->user_id = auth()->id();
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
