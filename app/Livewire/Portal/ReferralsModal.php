<?php

namespace App\Livewire\Portal;

use App\Services\Portal\PortalReferralService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ReferralsModal extends Component
{
    public bool $show = false;

    public string $referrer_search = '';

    public ?int $referrer_lead_id = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    #[On('open-referrals')]
    public function open(): void
    {
        abort_unless(auth()->user()?->hasPermission('clients.view'), 403);

        $this->resetForm();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    public function updatedReferrerSearch(): void
    {
        $this->referrer_lead_id = null;
    }

    public function selectReferrer(int $leadId, PortalReferralService $referrals): void
    {
        $referrer = $referrals->referrerById($leadId, Auth::user());

        if (! $referrer) {
            return;
        }

        $this->referrer_lead_id = $referrer->id;
        $this->referrer_search = $referrer->fullName();
    }

    public function clearReferrer(): void
    {
        $this->referrer_search = '';
        $this->referrer_lead_id = null;
    }

    public function create(PortalReferralService $referrals): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.update'), 403);

        $this->validate([
            'referrer_lead_id' => ['required', 'integer'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'referrer_lead_id.required' => 'Select the referring person.',
        ]);

        $referrals->record([
            'referrer_lead_id' => $this->referrer_lead_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name ?: null,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'notes' => $this->notes ?: null,
        ], Auth::user());

        $this->resetForm();
        $this->dispatch('referral-created');
        session()->flash('referral_status', 'Referral logged. The referred person was added to your leads and can be converted to a prospect when ready.');
    }

    public function render(PortalReferralService $referrals)
    {
        return view('livewire.portal.referrals-modal', [
            'recentReferrals' => $referrals->recentReferrals(),
            'referrerResults' => $referrals->searchReferrers($this->referrer_search),
            'showReferrerResults' => ! $this->referrer_lead_id
                && strlen(trim($this->referrer_search)) >= 3,
        ]);
    }

    private function resetForm(): void
    {
        $this->referrer_search = '';
        $this->referrer_lead_id = null;
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
        $this->notes = '';
        $this->resetValidation();
    }
}
