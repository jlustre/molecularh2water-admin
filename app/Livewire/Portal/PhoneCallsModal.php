<?php

namespace App\Livewire\Portal;

use App\Models\Crm\CalendarEvent;
use App\Services\Portal\PhoneCallService;
use App\Services\Portal\PortalDemoService;
use App\Support\Crm\CalendarScope;
use App\Support\Portal\PhoneCallReasons;
use App\Support\Portal\PhoneCallResults;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class PhoneCallsModal extends Component
{
    public bool $show = false;

    public string $contact_search = '';

    public string $contact_type = 'prospect';

    public ?int $contact_id = null;

    public string $other_name = '';

    public string $phone_number = '';

    public string $call_when = 'in_30';

    public string $call_date = '';

    public string $call_time = '';

    public string $call_reason = '';

    public string $notes = '';

    public bool $show_add_prospect_prompt = false;

    public bool $show_new_prospect_form = false;

    public string $new_first_name = '';

    public string $new_last_name = '';

    public string $new_email = '';

    public string $new_phone = '';

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

    public bool $showEdit = false;

    public ?int $editingEventId = null;

    public string $edit_call_when = 'in_30';

    public string $edit_call_date = '';

    public string $edit_call_time = '';

    public string $edit_phone_number = '';

    public string $edit_call_reason = '';

    public string $edit_notes = '';

    public string $edit_contact_type = 'prospect';

    #[On('open-phone-calls')]
    public function open(): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.view'), 403);

        $this->resetForm();
        $this->resetResults();
        $this->resetEdit();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetForm();
        $this->resetResults();
        $this->resetEdit();
    }

    public function updatedContactSearch(): void
    {
        $this->contact_id = null;
        $this->contact_type = 'prospect';
        $this->other_name = '';
        $this->phone_number = '';
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function selectContact(string $kind, int $contactId, PhoneCallService $phoneCalls): void
    {
        $contact = $phoneCalls->contactByKindAndId($kind, $contactId, Auth::user());

        if (! $contact) {
            return;
        }

        $this->applyContactSelection($contact);
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function clearContact(): void
    {
        $this->contact_search = '';
        $this->contact_id = null;
        $this->contact_type = 'prospect';
        $this->other_name = '';
        $this->phone_number = '';
        $this->call_reason = PhoneCallReasons::forContactKind('prospect')[0]['value'] ?? '';
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function schedule(PhoneCallService $phoneCalls): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        if ($this->contact_id) {
            $this->validate($this->scheduleFieldRules(), $this->whenFieldMessages('call_date', 'call_time'));
            $this->finalizeSchedule($phoneCalls);

            return;
        }

        if ($this->contact_type === 'other' && filled($this->other_name)) {
            $this->validate($this->scheduleFieldRules(), $this->whenFieldMessages('call_date', 'call_time'));
            $this->finalizeSchedule($phoneCalls);

            return;
        }

        $search = trim($this->contact_search);

        if ($search === '') {
            $this->addError('contact_search', 'Search for a contact, or type a new name.');

            return;
        }

        if ($match = $phoneCalls->findContactMatch($search, Auth::user())) {
            $this->applyContactSelection($match);
            $this->validate($this->scheduleFieldRules(), $this->whenFieldMessages('call_date', 'call_time'));
            $this->finalizeSchedule($phoneCalls);

            return;
        }

        $this->show_add_prospect_prompt = true;
        $this->show_new_prospect_form = false;
    }

    public function confirmAddProspect(PhoneCallService $phoneCalls): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.create'), 403);

        $parsed = $phoneCalls->parseContactName($this->contact_search);

        $this->new_first_name = $parsed['first_name'];
        $this->new_last_name = $parsed['last_name'];
        $this->new_email = '';
        $this->new_phone = '';
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = true;
        $this->resetValidation(['new_first_name', 'new_last_name', 'new_email', 'new_phone']);
    }

    public function cancelAddProspect(): void
    {
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function useOtherContact(): void
    {
        $this->contact_type = 'other';
        $this->contact_id = null;
        $this->other_name = trim($this->contact_search);
        $this->phone_number = '';
        $this->call_reason = PhoneCallReasons::forContactKind('other')[0]['value'] ?? '';
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
        $this->resetValidation(['contact_search', 'other_name', 'phone_number']);
    }

    public function createProspectAndSchedule(PhoneCallService $phoneCalls, PortalDemoService $demos): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.create'), 403);
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $validated = $this->validate(array_merge([
            'new_first_name' => ['required', 'string', 'max:120'],
            'new_last_name' => ['nullable', 'string', 'max:120'],
            'new_email' => ['nullable', 'email', 'max:255', 'required_without:new_phone'],
            'new_phone' => ['nullable', 'string', 'max:50', 'required_without:new_email'],
            'call_reason' => ['required', Rule::in(PhoneCallReasons::values())],
            'notes' => [Rule::requiredIf(fn () => $this->call_reason === 'other'), 'nullable', 'string', 'max:2000'],
        ], $this->whenFieldRules('call_when', 'call_date', 'call_time')), [
            'new_email.required_without' => 'Enter an email or phone number.',
            'new_phone.required_without' => 'Enter an email or phone number.',
            ...$this->whenFieldMessages('call_date', 'call_time'),
        ]);

        $lead = $demos->createProspect([
            'first_name' => $validated['new_first_name'],
            'last_name' => $validated['new_last_name'] ?? null,
            'email' => $validated['new_email'] ?? null,
            'phone' => $validated['new_phone'] ?? null,
        ], Auth::user());

        $this->applyContactSelection([
            'kind' => 'prospect',
            'id' => $lead->id,
            'label' => $lead->fullName(),
            'phone' => $lead->phone,
        ]);

        if (! filled($this->phone_number) && filled($validated['new_phone'] ?? null)) {
            $this->phone_number = (string) $validated['new_phone'];
        }

        $this->finalizeSchedule($phoneCalls, keepProspectForm: false);
    }

    public function beginCompleteCall(int $eventId, PhoneCallService $phoneCalls): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

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
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $validated = $this->validate(array_merge([
            'resultsEventId' => ['required', 'integer'],
            'call_result' => ['required', Rule::in(PhoneCallResults::values())],
            'result_comments' => ['nullable', 'string', 'max:2000'],
            'reschedule_enabled' => ['boolean'],
            'reschedule_reason' => [Rule::requiredIf(fn () => $this->reschedule_enabled), 'nullable', Rule::in(PhoneCallReasons::values())],
            'reschedule_notes' => [Rule::requiredIf(fn () => $this->reschedule_enabled && $this->reschedule_reason === 'other'), 'nullable', 'string', 'max:2000'],
        ], $this->whenFieldRules('reschedule_when', 'reschedule_date', 'reschedule_time', requiredIf: fn () => $this->reschedule_enabled)), [
            'call_result.required' => 'Select the call result.',
            'reschedule_when.required' => 'Choose when to reschedule the follow-up call.',
            'reschedule_reason.required' => 'Choose a reason for the follow-up call.',
            'reschedule_notes.required' => 'Please describe the reason in the notes field.',
            ...$this->whenFieldMessages('reschedule_date', 'reschedule_time'),
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
        $this->dispatch('phone-call-scheduled');
        session()->flash(
            'phone_call_status',
            ($validated['reschedule_enabled'] ?? false)
                ? 'Call results saved and follow-up call scheduled.'
                : 'Call results saved to your calendar and activity log.',
        );
    }

    public function cancelCallResults(): void
    {
        $this->resetResults();
    }

    public function openEditCall(int $eventId, PhoneCallService $phoneCalls): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $event = $this->findPhoneCallEvent($eventId);

        if ($event->status?->value === 'completed') {
            return;
        }

        $form = $phoneCalls->formDataFromEvent($event);

        $this->editingEventId = $event->id;
        $this->edit_call_when = $form['call_when'];
        $this->edit_call_date = $form['call_date'];
        $this->edit_call_time = $form['call_time'];
        $this->edit_phone_number = $form['phone_number'];
        $this->edit_call_reason = $form['call_reason'] ?: (PhoneCallReasons::forContactKind($form['contact_type'])[0]['value'] ?? '');
        $this->edit_notes = $form['notes'];
        $this->edit_contact_type = $form['contact_type'];
        $this->showEdit = true;
        $this->resetValidation(['edit_call_when', 'edit_call_date', 'edit_call_time', 'edit_phone_number', 'edit_call_reason', 'edit_notes']);
    }

    public function saveEditCall(PhoneCallService $phoneCalls): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $validated = $this->validate(array_merge([
            'editingEventId' => ['required', 'integer'],
            'edit_phone_number' => ['required', 'string', 'max:40'],
            'edit_call_reason' => ['required', Rule::in(PhoneCallReasons::values())],
            'edit_notes' => [Rule::requiredIf(fn () => $this->edit_call_reason === 'other'), 'nullable', 'string', 'max:2000'],
        ], $this->whenFieldRules('edit_call_when', 'edit_call_date', 'edit_call_time')), [
            'edit_phone_number.required' => 'Enter a phone number for this call.',
            'edit_notes.required' => 'Please describe the reason in the notes field.',
            ...$this->whenFieldMessages('edit_call_date', 'edit_call_time'),
        ]);

        $event = $this->findPhoneCallEvent($validated['editingEventId']);

        $phoneCalls->updateScheduledCall($event, [
            'call_when' => $validated['edit_call_when'],
            'call_date' => $validated['edit_call_date'] ?? null,
            'call_time' => $validated['edit_call_time'] ?? null,
            'phone_number' => $validated['edit_phone_number'],
            'call_reason' => $validated['edit_call_reason'],
            'notes' => $validated['edit_notes'] ?? null,
        ], Auth::user());

        $this->resetEdit();
        $this->dispatch('phone-call-scheduled');
        session()->flash('phone_call_status', 'Phone call updated.');
    }

    public function cancelEditCall(): void
    {
        $this->resetEdit();
    }

    public function render(PhoneCallService $phoneCalls)
    {
        return view('livewire.portal.phone-calls-modal', [
            'upcomingCalls' => $phoneCalls->upcomingCalls(),
            'contactResults' => $phoneCalls->searchContacts($this->contact_search),
            'showContactResults' => ! $this->contact_id
                && $this->contact_type !== 'other'
                && strlen(trim($this->contact_search)) >= 3
                && ! $this->show_add_prospect_prompt
                && ! $this->show_new_prospect_form,
            'reasonOptions' => PhoneCallReasons::forContactKind($this->contact_type),
            'editReasonOptions' => PhoneCallReasons::forContactKind($this->edit_contact_type),
            'rescheduleReasonOptions' => PhoneCallReasons::forContactKind($this->results_contact_type),
            'resultOptions' => PhoneCallResults::options(),
            'showPhoneField' => $this->contact_type === 'other' || filled($this->contact_id) || $this->show_new_prospect_form,
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

    /**
     * @param  array{kind: string, id: int, label: string, phone: ?string}  $contact
     */
    private function applyContactSelection(array $contact): void
    {
        $this->contact_type = $contact['kind'];
        $this->contact_id = $contact['id'];
        $this->contact_search = $contact['label'];
        $this->other_name = '';
        $this->phone_number = (string) ($contact['phone'] ?? '');
        $this->call_reason = PhoneCallReasons::forContactKind($this->contact_type)[0]['value'] ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduleFieldRules(): array
    {
        return array_merge([
            'contact_type' => ['required', 'in:prospect,customer,team,other'],
            'contact_id' => [Rule::requiredIf(fn () => $this->contact_type !== 'other'), 'nullable', 'integer'],
            'other_name' => [Rule::requiredIf(fn () => $this->contact_type === 'other'), 'nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:40'],
            'call_reason' => ['required', Rule::in(PhoneCallReasons::values())],
            'notes' => [Rule::requiredIf(fn () => $this->call_reason === 'other'), 'nullable', 'string', 'max:2000'],
        ], $this->whenFieldRules('call_when', 'call_date', 'call_time'));
    }

    /**
     * @param  (callable(): bool)|null  $requiredIf
     * @return array<string, mixed>
     */
    private function whenFieldRules(string $whenField, string $dateField, string $timeField, ?callable $requiredIf = null): array
    {
        $whenRequired = $requiredIf
            ? [Rule::requiredIf($requiredIf), 'nullable']
            : ['required'];

        return [
            $whenField => [...$whenRequired, 'in:in_15,in_30,in_60,today_14,today_16,tomorrow_10,custom'],
            $dateField => [
                Rule::requiredIf(fn () => ($requiredIf ? $requiredIf() : true) && $this->{$whenField} === 'custom'),
                'nullable',
                'date_format:Y-m-d',
            ],
            $timeField => [
                Rule::requiredIf(fn () => ($requiredIf ? $requiredIf() : true) && $this->{$whenField} === 'custom'),
                'nullable',
                'date_format:H:i',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function whenFieldMessages(string $dateField, string $timeField): array
    {
        return [
            "{$dateField}.required" => 'Choose a date for the call.',
            "{$timeField}.required" => 'Choose a time for the call.',
        ];
    }

    private function finalizeSchedule(PhoneCallService $phoneCalls, bool $keepProspectForm = false): void
    {
        $phoneCalls->schedule([
            'contact_kind' => $this->contact_type,
            'contact_id' => $this->contact_id,
            'other_name' => $this->other_name ?: null,
            'phone_number' => $this->phone_number,
            'call_when' => $this->call_when,
            'call_date' => $this->call_date ?: null,
            'call_time' => $this->call_time ?: null,
            'call_reason' => $this->call_reason,
            'notes' => $this->notes ?: null,
        ], Auth::user());

        if (! $keepProspectForm) {
            $this->resetForm(false);
        }

        $this->dispatch('phone-call-scheduled');
        session()->flash('phone_call_status', 'Phone call scheduled and added to your call list.');
    }

    private function resetForm(bool $resetContact = true): void
    {
        if ($resetContact) {
            $this->contact_search = '';
            $this->contact_id = null;
            $this->contact_type = 'prospect';
            $this->other_name = '';
            $this->phone_number = '';
        }

        $this->call_when = 'in_30';
        $this->call_date = '';
        $this->call_time = '';
        $this->call_reason = PhoneCallReasons::forContactKind($this->contact_type)[0]['value'] ?? '';
        $this->notes = '';
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
        $this->new_first_name = '';
        $this->new_last_name = '';
        $this->new_email = '';
        $this->new_phone = '';
        $this->resetValidation();
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

    private function resetEdit(): void
    {
        $this->showEdit = false;
        $this->editingEventId = null;
        $this->edit_call_when = 'in_30';
        $this->edit_call_date = '';
        $this->edit_call_time = '';
        $this->edit_phone_number = '';
        $this->edit_call_reason = '';
        $this->edit_notes = '';
        $this->edit_contact_type = 'prospect';
    }
}
