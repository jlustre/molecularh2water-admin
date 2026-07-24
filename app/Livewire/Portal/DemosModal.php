<?php

namespace App\Livewire\Portal;

use App\Enums\Crm\DemonstrationType;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Services\Portal\PortalDemoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class DemosModal extends Component
{
    public bool $show = false;

    public string $contact_search = '';

    public ?int $lead_id = null;

    public string $demo_type = 'home';

    public string $demo_when = 'tomorrow_10';

    public int $duration_minutes = 60;

    public string $venue = '';

    public string $notes = '';

    public string $contact_email = '';

    public bool $show_add_prospect_prompt = false;

    public bool $show_new_prospect_form = false;

    public string $new_first_name = '';

    public string $new_last_name = '';

    public string $new_email = '';

    public string $new_phone = '';

    #[On('open-demos')]
    public function open(PortalDemoService $demos): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.view'), 403);

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

    public function selectContact(int $leadId, PortalDemoService $demos): void
    {
        $lead = $demos->contactById($leadId, Auth::user());

        if (! $lead) {
            return;
        }

        $this->lead_id = $lead->id;
        $this->contact_search = $lead->fullName();
        $this->contact_email = (string) ($lead->email ?? '');
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function clearContact(): void
    {
        $this->lead_id = null;
        $this->contact_search = '';
        $this->contact_email = '';
        $this->show_add_prospect_prompt = false;
        $this->show_new_prospect_form = false;
    }

    public function schedule(PortalDemoService $demos): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.update'), 403);

        $this->validate($this->demoFieldRules());

        if ($this->lead_id) {
            $this->finalizeSchedule($demos);

            return;
        }

        $search = trim($this->contact_search);

        if ($search === '') {
            $this->addError('contact_search', 'Search for a prospect or customer, or type a new name.');

            return;
        }

        if ($match = $demos->findContactMatch($search, Auth::user())) {
            $this->lead_id = $match->id;

            if (! filled($this->contact_email)) {
                $this->contact_email = (string) ($match->email ?? '');
            }

            $this->finalizeSchedule($demos);

            return;
        }

        $this->show_add_prospect_prompt = true;
        $this->show_new_prospect_form = false;
    }

    public function confirmAddProspect(PortalDemoService $demos): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.create'), 403);

        $parsed = $demos->parseContactName($this->contact_search);

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

    public function createProspectAndSchedule(PortalDemoService $demos): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.create'), 403);
        abort_unless(auth()->user()?->hasPermission('leads.update'), 403);

        $validated = $this->validate(array_merge($this->demoFieldRules(), [
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
        $this->contact_email = (string) ($lead->email ?? '');
        $this->finalizeSchedule($demos, keepProspectForm: false, contact: $lead);
    }

    public function render(PortalDemoService $demos)
    {
        return view('livewire.portal.demos-modal', [
            'upcomingDemos' => $demos->upcomingDemos(),
            'demoTypes' => DemonstrationType::cases(),
            'contactResults' => $demos->searchContacts($this->contact_search),
            'showContactResults' => ! $this->lead_id
                && strlen(trim($this->contact_search)) >= 3
                && ! $this->show_add_prospect_prompt
                && ! $this->show_new_prospect_form,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function demoFieldRules(): array
    {
        return [
            'demo_type' => ['required', Rule::enum(DemonstrationType::class)],
            'demo_when' => ['required', 'in:in_60,today_14,today_16,tomorrow_10,tomorrow_14,next_week'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'venue' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    private function finalizeSchedule(
        PortalDemoService $demos,
        bool $keepProspectForm = false,
        Lead|Prospect|Customer|Recruit|null $contact = null,
    ): void {
        $demos->schedule([
            'lead_id' => (int) $this->lead_id,
            'contact_type' => $contact?->getMorphClass(),
            'type' => $this->demo_type,
            'demo_when' => $this->demo_when,
            'duration_minutes' => $this->duration_minutes,
            'venue' => $this->venue ?: null,
            'notes' => $this->notes ?: null,
            'contact_email' => $this->contact_email ?: null,
        ], Auth::user());

        if (! $keepProspectForm) {
            $this->resetForm(false);
        }

        $this->dispatch('demo-scheduled');
        session()->flash('demo_status', filled($this->contact_email)
            ? 'Demo scheduled and confirmation email sent.'
            : 'Demo scheduled and added to your list.');
    }

    private function resetForm(bool $resetContact = true): void
    {
        if ($resetContact) {
            $this->contact_search = '';
            $this->lead_id = null;
            $this->contact_email = '';
        }

        $this->demo_type = DemonstrationType::Home->value;
        $this->demo_when = 'tomorrow_10';
        $this->duration_minutes = 60;
        $this->venue = '';
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
