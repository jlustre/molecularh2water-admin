<?php

namespace App\Livewire\Crm\Calendar;

use App\Models\Crm\CalendarEvent;
use App\Models\Crm\Task;
use App\Services\Crm\CalendarQueryService;
use App\Services\Crm\TaskService;
use App\Services\Portal\PhoneCallService;
use App\Support\Crm\CalendarScope;
use App\Support\Crm\CrmScope;
use App\Support\Portal\PhoneCallReasons;
use App\Support\Portal\PhoneCallResults;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
class CalendarWidgets extends Component
{
    /** @var array<string, mixed> */
    public array $filters = [];

    public bool $canManage = false;

    public bool $showResults = false;

    public ?int $resultsEventId = null;

    public string $resultsContactLabel = '';

    public string $call_result = '';

    public string $result_comments = '';

    public bool $reschedule_enabled = false;

    public string $reschedule_when = 'tomorrow_10';

    public string $reschedule_date = '';

    public string $reschedule_time = '';

    public string $reschedule_reason = 'general_follow_up';

    public string $reschedule_notes = '';

    public string $results_contact_type = 'prospect';

    public function mount(array $filters = [], bool $canManage = false): void
    {
        $this->filters = $filters;
        $this->canManage = $canManage;
    }

    #[On('calendar-updated')]
    public function refreshWidgets(): void
    {
        // Re-render with current props.
    }

    #[On('business-line-changed')]
    public function refreshWidgetsOnBusinessLine(): void
    {
        // Re-render with current business line scope.
    }

    public function openDetails(string $kind, int $id): void
    {
        $this->dispatch('open-calendar-details', kind: $kind, id: $id);
    }

    public function completeTask(int $taskId, TaskService $tasks): void
    {
        abort_unless(auth()->user()?->hasPermission('tasks.manage'), 403);

        $task = CrmScope::tasks(Task::query())->findOrFail($taskId);
        $tasks->complete($task, auth()->user());

        $this->dispatch('calendar-status', message: 'Task completed.');
        $this->dispatch('calendar-updated');
    }

    public function beginCompleteCall(int $eventId, PhoneCallService $phoneCalls): void
    {
        abort_unless($this->canManage, 403);

        $event = $this->findPhoneCallEvent($eventId);

        if ($event->status?->value === 'completed') {
            return;
        }

        $this->resultsEventId = $event->id;
        $this->resultsContactLabel = $phoneCalls->contactLabel($event);
        $this->results_contact_type = $phoneCalls->formDataFromEvent($event)['contact_type'] ?: 'prospect';
        $this->call_result = (string) ($event->metadata['phone_call_result'] ?? '');
        $this->result_comments = (string) ($event->metadata['phone_call_result_comments'] ?? '');
        $this->reschedule_enabled = false;
        $this->reschedule_when = 'tomorrow_10';
        $this->reschedule_date = '';
        $this->reschedule_time = '';
        $this->reschedule_reason = PhoneCallReasons::forContactKind($this->results_contact_type)[0]['value'] ?? 'general_follow_up';
        $this->reschedule_notes = '';
        $this->showResults = true;
        $this->resetValidation(['call_result', 'result_comments', 'reschedule_when', 'reschedule_date', 'reschedule_time', 'reschedule_reason', 'reschedule_notes']);
    }

    public function updatedCallResult(): void
    {
        if (in_array($this->call_result, ['follow_up_needed', 'voicemail', 'no_answer'], true)) {
            $this->reschedule_enabled = true;

            if ($this->call_result === 'voicemail' || $this->call_result === 'no_answer') {
                $this->reschedule_reason = 'left_voicemail';
            }
        }
    }

    public function saveCallResults(PhoneCallService $phoneCalls): void
    {
        abort_unless($this->canManage, 403);

        $validated = $this->validate([
            'resultsEventId' => ['required', 'integer'],
            'call_result' => ['required', Rule::in(PhoneCallResults::values())],
            'result_comments' => ['nullable', 'string', 'max:2000'],
            'reschedule_enabled' => ['boolean'],
            'reschedule_when' => [Rule::requiredIf(fn () => $this->reschedule_enabled), 'nullable', 'in:in_15,in_30,in_60,today_14,today_16,tomorrow_10,custom'],
            'reschedule_date' => [Rule::requiredIf(fn () => $this->reschedule_enabled && $this->reschedule_when === 'custom'), 'nullable', 'date_format:Y-m-d'],
            'reschedule_time' => [Rule::requiredIf(fn () => $this->reschedule_enabled && $this->reschedule_when === 'custom'), 'nullable', 'date_format:H:i'],
            'reschedule_reason' => [Rule::requiredIf(fn () => $this->reschedule_enabled), 'nullable', Rule::in(PhoneCallReasons::values())],
            'reschedule_notes' => [Rule::requiredIf(fn () => $this->reschedule_enabled && $this->reschedule_reason === 'other'), 'nullable', 'string', 'max:2000'],
        ], [
            'call_result.required' => 'Select the call result.',
            'reschedule_when.required' => 'Choose when to reschedule the follow-up call.',
            'reschedule_date.required' => 'Choose a date for the call.',
            'reschedule_time.required' => 'Choose a time for the call.',
            'reschedule_reason.required' => 'Choose a reason for the follow-up call.',
            'reschedule_notes.required' => 'Please describe the reason in the notes field.',
        ]);

        $event = $this->findPhoneCallEvent($validated['resultsEventId']);

        $phoneCalls->recordResults($event, [
            'result' => $validated['call_result'],
            'comments' => $validated['result_comments'] ?? null,
        ], Auth::user());

        if ($validated['reschedule_enabled'] ?? false) {
            $phoneCalls->scheduleFollowUpFromEvent($event->fresh(), [
                'call_when' => $validated['reschedule_when'],
                'call_date' => $validated['reschedule_date'] ?? null,
                'call_time' => $validated['reschedule_time'] ?? null,
                'call_reason' => $validated['reschedule_reason'],
                'notes' => $validated['reschedule_notes'] ?? null,
            ], Auth::user());
        }

        $this->resetResults();
        $this->dispatch('calendar-status', message: ($validated['reschedule_enabled'] ?? false)
            ? 'Call results saved and follow-up call scheduled.'
            : 'Call results saved to your calendar and activity log.');
        $this->dispatch('calendar-updated');
    }

    public function cancelCallResults(): void
    {
        $this->resetResults();
    }

    public function render(CalendarQueryService $calendar)
    {
        $user = auth()->user();

        return view('livewire.crm.calendar.calendar-widgets', [
            'upcoming' => $calendar->upcomingShowsAndDemos(6, $user, $this->filters),
            'callListsToday' => $calendar->phoneCallsToday($user),
            'overdueFollowUps' => $calendar->overdueFollowUps($user),
            'tasksDueToday' => $calendar->tasksDueToday(),
            'typeColors' => config('calendar.type_colors', []),
            'resultOptions' => PhoneCallResults::options(),
            'rescheduleReasonOptions' => PhoneCallReasons::forContactKind($this->results_contact_type),
        ]);
    }

    private function findPhoneCallEvent(int $eventId): CalendarEvent
    {
        $event = CalendarScope::events(CalendarEvent::query())
            ->with(['type', 'related', 'attendees.user', 'reminders'])
            ->findOrFail($eventId);

        abort_unless(app(PhoneCallService::class)->isPhoneCallEvent($event), 404);

        return $event;
    }

    private function resetResults(): void
    {
        $this->showResults = false;
        $this->resultsEventId = null;
        $this->resultsContactLabel = '';
        $this->call_result = '';
        $this->result_comments = '';
        $this->reschedule_enabled = false;
        $this->reschedule_when = 'tomorrow_10';
        $this->reschedule_date = '';
        $this->reschedule_time = '';
        $this->reschedule_reason = 'general_follow_up';
        $this->reschedule_notes = '';
        $this->results_contact_type = 'prospect';
    }
}
