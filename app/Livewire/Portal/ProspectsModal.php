<?php

namespace App\Livewire\Portal;

use App\Services\Portal\PortalProspectService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ProspectsModal extends Component
{
    public bool $show = false;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $company = '';

    public string $notes = '';

    #[On('open-prospects')]
    public function open(): void
    {
        abort_unless(auth()->user()?->hasPermission('prospects.view'), 403);

        $this->resetForm();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    public function create(PortalProspectService $prospects): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.create'), 403);

        $this->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:50', 'required_without:email'],
            'company' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'email.required_without' => 'Enter an email or phone number.',
            'phone.required_without' => 'Enter an email or phone number.',
        ]);

        $prospects->create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name ?: null,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'company' => $this->company ?: null,
            'notes' => $this->notes ?: null,
        ], Auth::user());

        $this->resetForm();
        $this->dispatch('prospect-created');
        session()->flash('prospect_status', 'Lead added to your pipeline.');
    }

    public function render(PortalProspectService $prospects)
    {
        return view('livewire.portal.prospects-modal', [
            'recentProspects' => $prospects->recentProspects(),
        ]);
    }

    private function resetForm(): void
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
        $this->company = '';
        $this->notes = '';
        $this->resetValidation();
    }
}
