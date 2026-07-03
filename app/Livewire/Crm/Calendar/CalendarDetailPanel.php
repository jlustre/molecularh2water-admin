<?php



namespace App\Livewire\Crm\Calendar;



use App\Enums\Crm\TaskPriority;

use App\Models\Crm\CalendarEvent;

use App\Models\Crm\Demonstration;

use App\Models\Crm\Lead;

use App\Models\Crm\Task;

use App\Services\Crm\CalendarEventService;

use App\Services\Crm\DemonstrationService;

use App\Services\Crm\LeadService;

use App\Services\Crm\TaskService;

use App\Services\Portal\PhoneCallService;

use App\Support\Crm\CalendarScope;

use App\Support\Crm\CrmScope;

use App\Support\Portal\PhoneCallReasons;

use Illuminate\Validation\Rule;

use Livewire\Attributes\Isolate;

use Livewire\Attributes\On;

use Livewire\Component;



#[Isolate]

class CalendarDetailPanel extends Component

{

    public bool $show = false;



    public ?int $selectedEntryKey = null;



    public string $selectedEntryKind = 'event';



    public string $completion_notes = '';



    public string $task_title = '';



    public string $task_description = '';



    public string $task_priority = 'normal';



    public string $task_due_at = '';



    public string $lead_follow_up_at = '';



    public string $phone_call_when = 'in_30';



    public string $phone_call_date = '';



    public string $phone_call_time = '';



    public string $phone_number = '';



    public string $phone_call_reason = '';



    public string $phone_call_notes = '';



    public string $phone_contact_type = 'prospect';



    public string $demo_scheduled_at = '';



    public string $demo_venue = '';



    public string $demo_notes = '';



    public string $demo_duration_minutes = '60';



    #[On('open-calendar-details')]

    public function openDetails(string $kind, int $id): void

    {

        $this->selectedEntryKind = $kind;

        $this->selectedEntryKey = $id;

        $this->completion_notes = '';

        $this->resetValidation();

        $this->hydrateEditFields();

        $this->show = true;

    }



    public function close(): void

    {

        $this->show = false;

        $this->selectedEntryKey = null;

        $this->completion_notes = '';

        $this->resetValidation();

    }



    public function openEdit(int $eventId): void

    {

        $this->close();

        $this->dispatch('open-calendar-edit', eventId: $eventId);

    }



    public function save(PhoneCallService $phoneCalls, TaskService $tasks, LeadService $leads, DemonstrationService $demos, CalendarEventService $events): void

    {

        match ($this->resolvedKind()) {

            'task' => $this->saveTask($tasks, $events),

            'lead' => $this->saveLeadFollowUp($leads),

            'phone_call' => $this->savePhoneCall($phoneCalls),

            'demonstration' => $this->saveDemonstration($demos, $events),

            default => null,

        };

    }



    public function completeEvent(int $eventId, CalendarEventService $events): void

    {

        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);



        $event = CalendarScope::events(CalendarEvent::query())->findOrFail($eventId);

        $events->complete($event, auth()->user(), $this->completion_notes ?: null);



        $this->close();

        $this->dispatch('calendar-status', message: 'Event marked complete.');

        $this->dispatch('calendar-updated');

    }



    public function cancelEvent(int $eventId, CalendarEventService $events): void

    {

        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);



        $events->cancel(CalendarScope::events(CalendarEvent::query())->findOrFail($eventId), auth()->user());



        $this->close();

        $this->dispatch('calendar-status', message: 'Event cancelled.');

        $this->dispatch('calendar-updated');

    }



    public function deleteEvent(int $eventId, CalendarEventService $events): void

    {

        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);



        $events->delete(CalendarScope::events(CalendarEvent::query())->findOrFail($eventId), auth()->user());



        $this->close();

        $this->dispatch('calendar-status', message: 'Event deleted.');

        $this->dispatch('calendar-updated');

    }



    public function render(PhoneCallService $phoneCalls)

    {

        $selectedEvent = null;

        $selectedDemonstration = null;

        $selectedTask = null;

        $selectedLead = null;

        $isPhoneCall = false;



        if ($this->show && $this->selectedEntryKey) {

            match ($this->resolvedKind()) {

                'event', 'phone_call' => $selectedEvent = $this->findEvent($this->selectedEntryKey),

                'demonstration' => $selectedDemonstration = $this->findDemonstration($this->selectedEntryKey),

                'task' => $selectedTask = $this->findTask($this->selectedEntryKey),

                'lead' => $selectedLead = $this->findLead($this->selectedEntryKey),

                default => null,

            };

        }



        if ($selectedEvent) {

            $isPhoneCall = $phoneCalls->isPhoneCallEvent($selectedEvent);

        }



        return view('livewire.crm.calendar.calendar-detail-panel', [

            'selectedEvent' => $selectedEvent,

            'selectedDemonstration' => $selectedDemonstration,

            'selectedTask' => $selectedTask,

            'selectedLead' => $selectedLead,

            'isPhoneCall' => $isPhoneCall,

            'resolvedKind' => $this->resolvedKind(),

            'canManageCalendar' => auth()->user()?->hasPermission('calendar.manage'),

            'canManageTasks' => auth()->user()?->hasPermission('tasks.manage'),

            'canManageLeads' => auth()->user()?->hasPermission('leads.update'),

            'taskPriorities' => TaskPriority::cases(),

            'phoneCallWhenOptions' => $this->phoneCallWhenOptions(),

            'phoneReasonOptions' => PhoneCallReasons::forContactKind($this->phone_contact_type),

        ]);

    }



    private function resolvedKind(): string

    {

        if ($this->selectedEntryKind === 'event' && $this->selectedEntryKey) {

            $event = CalendarScope::events(CalendarEvent::query())->find($this->selectedEntryKey);



            if ($event && app(PhoneCallService::class)->isPhoneCallEvent($event)) {

                return 'phone_call';

            }

        }



        return $this->selectedEntryKind;

    }



    private function hydrateEditFields(): void

    {

        if (! $this->selectedEntryKey) {

            return;

        }



        match ($this->resolvedKind()) {

            'task' => $this->hydrateTaskFields($this->findTask($this->selectedEntryKey)),

            'lead' => $this->hydrateLeadFields($this->findLead($this->selectedEntryKey)),

            'phone_call' => $this->hydratePhoneCallFields($this->findEvent($this->selectedEntryKey)),

            'demonstration' => $this->hydrateDemonstrationFields($this->findDemonstration($this->selectedEntryKey)),

            default => null,

        };

    }



    private function hydrateTaskFields(?Task $task): void

    {

        if (! $task) {

            return;

        }



        $this->task_title = $task->title;

        $this->task_description = (string) $task->description;

        $this->task_priority = $task->priority?->value ?? 'normal';

        $this->task_due_at = $task->due_at?->format('Y-m-d\TH:i') ?? '';

    }



    private function hydrateLeadFields(?Lead $lead): void

    {

        if (! $lead) {

            return;

        }



        $this->lead_follow_up_at = $lead->next_follow_up_at?->format('Y-m-d\TH:i') ?? '';

    }



    private function hydratePhoneCallFields(?CalendarEvent $event): void

    {

        if (! $event) {

            return;

        }



        $form = app(PhoneCallService::class)->formDataFromEvent($event);



        $this->phone_call_when = $form['call_when'];

        $this->phone_call_date = $form['call_date'];

        $this->phone_call_time = $form['call_time'];

        $this->phone_number = $form['phone_number'];

        $this->phone_call_reason = $form['call_reason'] ?: (PhoneCallReasons::forContactKind($form['contact_type'])[0]['value'] ?? '');

        $this->phone_call_notes = $form['notes'];

        $this->phone_contact_type = $form['contact_type'];

    }



    private function hydrateDemonstrationFields(?Demonstration $demo): void

    {

        if (! $demo) {

            return;

        }



        $this->demo_scheduled_at = $demo->scheduled_at?->format('Y-m-d\TH:i') ?? '';

        $this->demo_venue = (string) $demo->venue;

        $this->demo_notes = (string) $demo->notes;

        $this->demo_duration_minutes = (string) ($demo->duration_minutes ?? 60);

    }



    private function saveTask(TaskService $tasks, CalendarEventService $events): void

    {

        abort_unless(auth()->user()?->hasPermission('tasks.manage'), 403);



        $validated = $this->validate([

            'task_title' => ['required', 'string', 'max:255'],

            'task_description' => ['nullable', 'string', 'max:5000'],

            'task_priority' => ['required', Rule::in(array_column(TaskPriority::cases(), 'value'))],

            'task_due_at' => ['required', 'date'],

        ]);



        $task = $this->findTask($this->selectedEntryKey);

        abort_unless($task, 404);



        $tasks->update($task, [

            'title' => $validated['task_title'],

            'description' => $validated['task_description'] ?: null,

            'priority' => $validated['task_priority'],

            'due_at' => $validated['task_due_at'],

        ], auth()->user());



        $calendarEvent = $task->calendarEvents()->first();



        if ($calendarEvent) {

            $dueAt = $task->fresh()->due_at;

            $events->update($calendarEvent, [

                'title' => $task->fresh()->title,

                'description' => $task->fresh()->description,

                'start_at' => $dueAt,

                'end_at' => $dueAt?->copy()->addHour(),

            ], auth()->user());

        }



        $this->finishSave('Task updated.');

    }



    private function saveLeadFollowUp(LeadService $leads): void

    {

        abort_unless(auth()->user()?->hasPermission('leads.update'), 403);



        $validated = $this->validate([

            'lead_follow_up_at' => ['required', 'date'],

        ]);



        $lead = $this->findLead($this->selectedEntryKey);

        abort_unless($lead, 404);



        $leads->update($lead, [

            'next_follow_up_at' => $validated['lead_follow_up_at'],

        ], auth()->user());



        $this->finishSave('Follow-up rescheduled.');

    }



    private function savePhoneCall(PhoneCallService $phoneCalls): void

    {

        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);



        $validated = $this->validate([

            'phone_call_when' => ['required', 'in:in_15,in_30,in_60,today_14,today_16,tomorrow_10,custom'],

            'phone_call_date' => [Rule::requiredIf(fn () => $this->phone_call_when === 'custom'), 'nullable', 'date_format:Y-m-d'],

            'phone_call_time' => [Rule::requiredIf(fn () => $this->phone_call_when === 'custom'), 'nullable', 'date_format:H:i'],

            'phone_number' => ['required', 'string', 'max:40'],

            'phone_call_reason' => ['required', Rule::in(PhoneCallReasons::values())],

            'phone_call_notes' => [Rule::requiredIf(fn () => $this->phone_call_reason === 'other'), 'nullable', 'string', 'max:2000'],

        ], [

            'phone_number.required' => 'Enter a phone number for this call.',

            'phone_call_notes.required' => 'Please describe the reason in the notes field.',

            'phone_call_date.required' => 'Choose a date for the call.',

            'phone_call_time.required' => 'Choose a time for the call.',

        ]);



        $event = $this->findEvent($this->selectedEntryKey);

        abort_unless($event, 404);



        $phoneCalls->updateScheduledCall($event, [

            'call_when' => $validated['phone_call_when'],

            'call_date' => $validated['phone_call_date'] ?? null,

            'call_time' => $validated['phone_call_time'] ?? null,

            'phone_number' => $validated['phone_number'],

            'call_reason' => $validated['phone_call_reason'],

            'notes' => $validated['phone_call_notes'] ?? null,

        ], auth()->user());



        $this->finishSave('Phone call rescheduled.');

    }



    private function saveDemonstration(DemonstrationService $demos, CalendarEventService $events): void

    {

        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);



        $validated = $this->validate([

            'demo_scheduled_at' => ['required', 'date'],

            'demo_venue' => ['nullable', 'string', 'max:255'],

            'demo_notes' => ['nullable', 'string', 'max:5000'],

            'demo_duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],

        ]);



        $demo = $this->findDemonstration($this->selectedEntryKey);

        abort_unless($demo, 404);



        $demos->update($demo, [

            'scheduled_at' => $validated['demo_scheduled_at'],

            'venue' => $validated['demo_venue'] ?: null,

            'notes' => $validated['demo_notes'] ?: null,

            'duration_minutes' => (int) $validated['demo_duration_minutes'],

        ], auth()->user());



        if ($demo->calendar_event_id) {

            $scheduledAt = $demo->fresh()->scheduled_at;

            $events->update(

                CalendarScope::events(CalendarEvent::query())->findOrFail($demo->calendar_event_id),

                [

                    'start_at' => $scheduledAt,

                    'end_at' => $scheduledAt?->copy()->addMinutes((int) $validated['demo_duration_minutes']),

                ],

                auth()->user(),

            );

        }



        $this->finishSave('Demo rescheduled.');

    }



    private function finishSave(string $message): void

    {

        $this->close();

        $this->dispatch('calendar-status', message: $message);

        $this->dispatch('calendar-updated');

    }



    private function findEvent(int $id): ?CalendarEvent

    {

        return CalendarScope::events(CalendarEvent::query())

            ->with(['type', 'user', 'related', 'reminders', 'attendees.user', 'notes.user'])

            ->find($id);

    }



    private function findDemonstration(int $id): ?Demonstration

    {

        return Demonstration::query()

            ->with(['contact', 'demonstrator', 'calendarEvent.type'])

            ->forAccessibleContacts()

            ->find($id);

    }



    private function findTask(int $id): ?Task

    {

        return CrmScope::tasks(Task::query())

            ->with(['lead', 'user', 'calendarEvents'])

            ->find($id);

    }



    private function findLead(int $id): ?Lead

    {

        return CrmScope::leads(Lead::query())

            ->with(['assignedUser'])

            ->find($id);

    }



    /**

     * @return array<string, string>

     */

    private function phoneCallWhenOptions(): array

    {

        return [

            'in_15' => 'In 15 minutes',

            'in_30' => 'In 30 minutes',

            'in_60' => 'In 1 hour',

            'today_14' => 'Today at 2:00 PM',

            'today_16' => 'Today at 4:00 PM',

            'tomorrow_10' => 'Tomorrow at 10:00 AM',

            'custom' => 'Pick date & time',

        ];

    }

}

