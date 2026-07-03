<?php

namespace App\Livewire\Portal;

use App\Services\Portal\MeetingService;
use App\Services\Portal\PortalDemoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class MeetingsModal extends Component
{
    public bool $show = false;

    public string $contact_search = '';

    public string $contact_type = 'prospect';

    public ?int $contact_id = null;

    public string $other_name = '';

    public string $meeting_format = 'in_person';

    public string $meeting_when = 'tomorrow_10';

    public int $duration_minutes = 60;

    public string $title = '';

    public string $location = '';

    public string $meeting_link = '';

    public string $notes = '';

    public string $recurrence = 'none';

    public int $recurrence_count = 8;

    public string $invitee_group = '';

    public bool $show_add_prospect_prompt = false;

    public bool $show_new_prospect_form = false;

    public string $new_first_name = '';

    public string $new_last_name = '';

    public string $new_email = '';

    public string $new_phone = '';

    #[On('open-meetings')]
    public function open(): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.view'), 403);

        $this->resetForm();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    public function updatedContactSearch(): void
    {
        $this->contact_id = null;
        $this->contact_type = 'prospect';
        $this->other_name = '';
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function updatedMeetingFormat(): void
    {
        $this->resetValidation(['location', 'meeting_link']);
    }

    public function updatedRecurrence(): void
    {
        if ($this->recurrence === 'none') {
            $this->recurrence_count = 8;
        }
    }

    public function selectContact(string $kind, int $contactId, MeetingService $meetings): void
    {
        $contact = $meetings->contactByKindAndId($kind, $contactId, Auth::user());

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
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function schedule(MeetingService $meetings): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        if ($this->contact_id) {
            $this->validate($this->scheduleFieldRules());
            $this->finalizeSchedule($meetings);

            return;
        }

        if ($this->contact_type === 'other' && filled($this->other_name)) {
            $this->validate($this->scheduleFieldRules());
            $this->finalizeSchedule($meetings);

            return;
        }

        $search = trim($this->contact_search);

        if ($search === '') {
            $this->addError('contact_search', 'Search for a contact, or type a new name.');

            return;
        }

        if ($match = $meetings->findContactMatch($search, Auth::user())) {
            $this->applyContactSelection($match);
            $this->validate($this->scheduleFieldRules());
            $this->finalizeSchedule($meetings);

            return;
        }

        $this->show_add_prospect_prompt = true;
        $this->show_new_prospect_form = false;
    }

    public function confirmAddProspect(MeetingService $meetings): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.create'), 403);

        $parsed = $meetings->parseContactName($this->contact_search);

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
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
        $this->resetValidation(['contact_search', 'other_name']);
    }

    public function createProspectAndSchedule(MeetingService $meetings, PortalDemoService $demos): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.create'), 403);
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $validated = $this->validate(array_merge($this->scheduleFieldRules(), [
            'new_first_name' => ['required', 'string', 'max:120'],
            'new_last_name' => ['nullable', 'string', 'max:120'],
            'new_email' => ['nullable', 'email', 'max:255', 'required_without:new_phone'],
            'new_phone' => ['nullable', 'string', 'max:50', 'required_without:new_email'],
        ], [
            'new_email.required_without' => 'Enter an email or phone number.',
            'new_phone.required_without' => 'Enter an email or phone number.',
        ]));

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

        $this->finalizeSchedule($meetings, keepProspectForm: false);
    }

    public function render(MeetingService $meetings)
    {
        $actor = Auth::user();

        return view('livewire.portal.meetings-modal', [
            'upcomingMeetings' => $meetings->upcomingMeetings(),
            'contactResults' => $meetings->searchContacts($this->contact_search),
            'meetingFormats' => config('portal.meeting_formats', []),
            'recurrenceOptions' => config('portal.meeting_recurrence', []),
            'recurrenceCounts' => config('portal.meeting_recurrence_counts', [4, 8, 12, 26]),
            'inviteeGroups' => $meetings->inviteeGroupOptions(),
            'inviteeCount' => $this->invitee_group !== '' && $actor
                ? count($meetings->resolveInviteeUserIds(
                    $this->invitee_group,
                    $actor,
                    $this->contact_type === 'team' ? $this->contact_id : null,
                ))
                : 0,
            'showContactResults' => ! $this->contact_id
                && $this->contact_type !== 'other'
                && strlen(trim($this->contact_search)) >= 3
                && ! $this->show_add_prospect_prompt
                && ! $this->show_new_prospect_form,
        ]);
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
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduleFieldRules(): array
    {
        return [
            'meeting_format' => ['required', Rule::in(array_column(config('portal.meeting_formats', []), 'value'))],
            'meeting_when' => ['required', 'in:in_15,in_30,in_60,today_14,today_16,tomorrow_10,tomorrow_14,next_week'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'title' => ['nullable', 'string', 'max:255'],
            'location' => [Rule::requiredIf(fn () => $this->meeting_format === 'in_person'), 'nullable', 'string', 'max:255'],
            'meeting_link' => [Rule::requiredIf(fn () => $this->meeting_format === 'online'), 'nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'recurrence' => ['required', Rule::in(array_column(config('portal.meeting_recurrence', []), 'value'))],
            'recurrence_count' => [
                Rule::requiredIf(fn () => $this->recurrence !== 'none'),
                'integer',
                'min:2',
                'max:52',
            ],
            'invitee_group' => ['nullable', Rule::in(array_column(config('portal.meeting_invitee_groups', []), 'value'))],
            'other_name' => [Rule::requiredIf(fn () => $this->contact_type === 'other'), 'nullable', 'string', 'max:255'],
        ];
    }

    private function finalizeSchedule(MeetingService $meetings, bool $keepProspectForm = false): void
    {
        $events = $meetings->schedule([
            'contact_kind' => $this->contact_type,
            'contact_id' => $this->contact_id,
            'other_name' => $this->other_name ?: null,
            'meeting_format' => $this->meeting_format,
            'meeting_when' => $this->meeting_when,
            'duration_minutes' => $this->duration_minutes,
            'title' => $this->title ?: null,
            'location' => $this->location ?: null,
            'meeting_link' => $this->meeting_link ?: null,
            'notes' => $this->notes ?: null,
            'recurrence' => $this->recurrence,
            'recurrence_count' => $this->recurrence_count,
            'invitee_group' => $this->invitee_group,
        ], Auth::user());

        if (! $keepProspectForm) {
            $this->resetForm(false);
        }

        $count = $events->count();
        $this->dispatch('meeting-scheduled');
        session()->flash('meeting_status', $count > 1
            ? "{$count} meetings scheduled and synced to your calendar."
            : 'Meeting scheduled and synced to your calendar.');
    }

    private function resetForm(bool $resetContact = true): void
    {
        if ($resetContact) {
            $this->contact_search = '';
            $this->contact_id = null;
            $this->contact_type = 'prospect';
            $this->other_name = '';
        }

        $this->meeting_format = 'in_person';
        $this->meeting_when = 'tomorrow_10';
        $this->duration_minutes = 60;
        $this->title = '';
        $this->location = '';
        $this->meeting_link = '';
        $this->notes = '';
        $this->recurrence = 'none';
        $this->recurrence_count = 8;
        $this->invitee_group = '';
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
        $this->new_first_name = '';
        $this->new_last_name = '';
        $this->new_email = '';
        $this->new_phone = '';
        $this->resetValidation();
    }
}
