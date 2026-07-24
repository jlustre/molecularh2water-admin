<?php

namespace App\Livewire\Portal;

use App\Services\Portal\PortalReferralService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class ReferralsModal extends Component
{
    public bool $show = false;

    public string $referrer_search = '';

    public ?int $referrer_lead_id = null;

    public ?string $referrer_type = null;

    public bool $referrer_is_external = false;

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
        $this->referrer_type = null;
        $this->referrer_is_external = false;
    }

    public function selectReferrer(string $type, int $leadId, PortalReferralService $referrals): void
    {
        $referrer = $referrals->referrerByKey($type, $leadId, Auth::user());

        if (! $referrer) {
            return;
        }

        $this->referrer_type = $referrer->getMorphClass();
        $this->referrer_lead_id = $referrer->id;
        $this->referrer_search = $referrer->fullName();
        $this->referrer_is_external = false;
    }

    public function useTypedReferrer(): void
    {
        $name = trim($this->referrer_search);

        if (strlen($name) < 2) {
            return;
        }

        $this->referrer_lead_id = null;
        $this->referrer_type = null;
        $this->referrer_is_external = true;
        $this->referrer_search = $name;
        $this->resetValidation(['referrer_lead_id', 'referrer_search']);
    }

    public function clearReferrer(): void
    {
        $this->referrer_search = '';
        $this->referrer_lead_id = null;
        $this->referrer_type = null;
        $this->referrer_is_external = false;
    }

    public function create(PortalReferralService $referrals): void
    {
        abort_unless(auth()->user()?->hasPermission('leads.update'), 403);

        $this->validate([
            'referrer_search' => ['required', 'string', 'min:2', 'max:255'],
            'referrer_lead_id' => ['nullable', 'integer'],
            'referrer_type' => ['nullable', 'string', Rule::in(['lead', 'prospect', 'customer', 'recruit'])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'referrer_search.required' => 'Enter or select the referring person.',
            'referrer_search.min' => 'Enter or select the referring person.',
        ]);

        if ($this->referrer_lead_id && ! $this->referrer_type) {
            $this->addError('referrer_lead_id', 'Select a valid referring person.');

            return;
        }

        $referrals->record([
            'referrer_lead_id' => $this->referrer_lead_id,
            'referrer_type' => $this->referrer_type,
            'referrer_name' => $this->referrer_lead_id ? null : trim($this->referrer_search),
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
        $typedName = trim($this->referrer_search);

        return view('livewire.portal.referrals-modal', [
            'recentReferrals' => $referrals->recentReferrals(),
            'referrerResults' => $referrals->searchReferrers($this->referrer_search),
            'showReferrerResults' => ! $this->referrer_lead_id
                && ! $this->referrer_is_external
                && strlen($typedName) >= 3,
            'canUseTypedReferrer' => ! $this->referrer_lead_id
                && strlen($typedName) >= 2,
        ]);
    }

    private function resetForm(): void
    {
        $this->referrer_search = '';
        $this->referrer_lead_id = null;
        $this->referrer_type = null;
        $this->referrer_is_external = false;
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
        $this->notes = '';
        $this->resetValidation();
    }
}
