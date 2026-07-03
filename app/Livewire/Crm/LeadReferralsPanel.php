<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Referral;
use App\Services\Crm\ReferralService;
use App\Support\Crm\CrmRoutes;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LeadReferralsPanel extends Component
{
    use AuthorizesRequests;

    public Lead|Prospect|Customer|Recruit $lead;

    public bool $showForm = false;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    public ?int $rewardingReferralId = null;

    public string $reward_type = 'gift_card';

    public string $reward_amount = '';

    public string $reward_notes = '';

    public function mount(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead;
    }

    public function toggleForm(): void
    {
        $this->authorize('update', $this->lead);
        $this->showForm = ! $this->showForm;
    }

    public function saveReferral(ReferralService $referrals): void
    {
        $this->authorize('update', $this->lead);

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $referrals->recordReferral($this->lead, $validated, auth()->user());

        $this->reset(['showForm', 'first_name', 'last_name', 'email', 'phone', 'notes']);
        $this->lead->refresh();
    }

    public function startReward(int $referralId): void
    {
        $this->authorize('update', $this->lead);
        $this->rewardingReferralId = $referralId;
        $this->reward_type = array_key_first(config('crm.referral_reward_types', [])) ?: 'gift_card';
        $this->reward_amount = '';
        $this->reward_notes = '';
    }

    public function issueReward(ReferralService $referrals): void
    {
        $this->authorize('update', $this->lead);

        $this->validate([
            'rewardingReferralId' => ['required', 'integer'],
            'reward_type' => ['required', Rule::in(array_keys(config('crm.referral_reward_types', [])))],
            'reward_amount' => ['nullable', 'numeric', 'min:0'],
            'reward_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $referral = Referral::query()
            ->where('referrer_lead_id', $this->lead->id)
            ->findOrFail($this->rewardingReferralId);

        $referrals->markRewarded($referral, [
            'reward_type' => $this->reward_type,
            'reward_amount' => $this->reward_amount,
            'reward_notes' => $this->reward_notes,
        ], auth()->user());

        $this->rewardingReferralId = null;
        $this->lead->refresh();
    }

    public function referredProfileUrl(Lead $referred): string
    {
        return match ($referred->lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.show', ['lead' => $referred]),
            LeadLifecycle::Client => CrmRoutes::url('customers.show', ['lead' => $referred]),
            default => CrmRoutes::url('leads.show', ['lead' => $referred]),
        };
    }

    public function render(ReferralService $referrals)
    {
        return view('livewire.crm.lead-referrals-panel', [
            'referrals' => $referrals->referralsForReferrer($this->lead),
            'rewardTypes' => config('crm.referral_reward_types', []),
            'showPanel' => $this->lead->lifecycle === LeadLifecycle::Client,
        ]);
    }
}
