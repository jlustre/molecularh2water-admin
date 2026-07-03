<?php

namespace App\Livewire\Portal;

use App\Services\Portal\PortalAppointmentService;
use App\Services\Portal\PortalDemoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class AppointmentsModal extends Component
{
    public bool $show = false;

    public string $contact_search = '';

    public ?int $lead_id = null;

    public string $title = '';

    public string $meeting_type = 'home_demo';

    public string $appointment_when = 'tomorrow_10';

    public int $duration_minutes = 60;

    public string $location = '';

    public string $zoom_link = '';

    public string $notes = '';

    public bool $show_add_prospect_prompt = false;

    public bool $show_new_prospect_form = false;

    public string $new_first_name = '';

    public string $new_last_name = '';

    public string $new_email = '';

    public string $new_phone = '';

    #[On('open-appointments')]
    public function open(): void
    {
        abort_unless(auth()->user()?->hasPermission('appointments.view'), 403);

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
        $this->lead_id = null;
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function selectContact(int $leadId, PortalAppointmentService $appointments): void
    {
        $lead = $appointments->contactById($leadId, Auth::user());

        if (! $lead) {
            return;
        }

        $this->lead_id = $lead->id;
        $this->contact_search = $lead->fullName();
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function clearContact(): void
    {
        $this->contact_search = '';
        $this->lead_id = null;
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function schedule(PortalAppointmentService $appointments): void
    {
        abort_unless(auth()->user()?->hasPermission('appointments.manage'), 403);

        if ($this->lead_id) {
            $this->finalizeSchedule($appointments);

            return;
        }

        $search = trim($this->contact_search);

        if ($search === '') {
            $this->validate($this->fieldRules());
            $this->finalizeSchedule($appointments);

            return;
        }

        if ($match = $appointments->findContactMatch($search, Auth::user())) {
            $this->lead_id = $match['id'];
            $this->contact_search = $match['label'];
            $this->finalizeSchedule($appointments);

            return;
        }

        $this->validate($this->fieldRules());
        $this->show_add_prospect_prompt = true;
        $this->show_new_prospect_form = false;
    }

    public function confirmAddProspect(PortalAppointmentService $appointments): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.create'), 403);

        $parsed = $appointments->parseContactName($this->contact_search);

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

    public function createProspectAndSchedule(
        PortalAppointmentService $appointments,
        PortalDemoService $demos,
    ): void {
        abort_unless(auth()->user()?->hasPermission('leads.create'), 403);
        abort_unless(auth()->user()?->hasPermission('appointments.manage'), 403);

        $validated = $this->validate(array_merge($this->fieldRules(), [
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

        $this->lead_id = $lead->id;
        $this->contact_search = $lead->fullName();
        $this->finalizeSchedule($appointments, keepProspectForm: false);
    }

    public function render(PortalAppointmentService $appointments)
    {
        return view('livewire.portal.appointments-modal', [
            'upcomingAppointments' => $appointments->upcomingAppointments(),
            'meetingTypes' => config('crm.meeting_types', []),
            'contactResults' => $appointments->searchContacts($this->contact_search),
            'showContactResults' => ! $this->lead_id
                && strlen(trim($this->contact_search)) >= 3
                && ! $this->show_add_prospect_prompt
                && ! $this->show_new_prospect_form,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldRules(): array
    {
        return [
            'meeting_type' => ['required', Rule::in(array_keys(config('crm.meeting_types', [])))],
            'appointment_when' => ['required', 'in:in_15,in_30,in_60,today_14,today_16,tomorrow_10,tomorrow_14,next_week'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'title' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'zoom_link' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function finalizeSchedule(PortalAppointmentService $appointments, bool $keepProspectForm = false): void
    {
        $this->validate($this->fieldRules());

        $appointments->schedule([
            'lead_id' => $this->lead_id,
            'title' => $this->title ?: null,
            'meeting_type' => $this->meeting_type,
            'appointment_when' => $this->appointment_when,
            'duration_minutes' => $this->duration_minutes,
            'location' => $this->location ?: null,
            'zoom_link' => $this->zoom_link ?: null,
            'notes' => $this->notes ?: null,
        ], Auth::user());

        if (! $keepProspectForm) {
            $this->resetForm(false);
        }

        $this->dispatch('appointment-scheduled');
        session()->flash('appointment_status', 'Appointment scheduled and added to your list.');
    }

    private function resetForm(bool $resetContact = true): void
    {
        if ($resetContact) {
            $this->contact_search = '';
            $this->lead_id = null;
        }

        $this->title = '';
        $this->meeting_type = 'home_demo';
        $this->appointment_when = 'tomorrow_10';
        $this->duration_minutes = 60;
        $this->location = '';
        $this->zoom_link = '';
        $this->notes = '';
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
        $this->new_first_name = '';
        $this->new_last_name = '';
        $this->new_email = '';
        $this->new_phone = '';
        $this->resetValidation();
    }
}
