<?php

namespace App\Services\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Enums\Crm\ReferralStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Referral;
use App\Models\User;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly DashboardStatsService $dashboardStats,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordReferral(Lead|Prospect|Customer|Recruit $referrer, array $data, User $user): Referral
    {
        return DB::transaction(function () use ($referrer, $data, $user) {
            $referralSource = LeadSource::query()->where('slug', 'referral')->first();
            $referralFunnel = Funnel::query()
                ->where('slug', config('crm.referral_funnel_slug', 'referral-funnel'))
                ->first();

            $entryStage = $referralFunnel
                ? FunnelStage::query()
                    ->where('funnel_id', $referralFunnel->id)
                    ->where('slug', config('crm.referral_entry_stage', 'referral-received'))
                    ->first()
                : null;

            $referred = Lead::query()->create([
                'lifecycle_id' => Lifecycle::idFor(LeadLifecycle::Lead),
                'status' => LeadStatus::New,
                'first_name' => trim((string) Arr::get($data, 'first_name')),
                'last_name' => Arr::get($data, 'last_name'),
                'email' => Arr::get($data, 'email'),
                'phone' => Arr::get($data, 'phone'),
                'message' => Arr::get($data, 'notes'),
                'referred_by_type' => $referrer->getMorphClass(),
                'referred_by_id' => $referrer->id,
                'lead_source_id' => $referralSource?->id,
                'funnel_id' => $referralFunnel?->id,
                'funnel_stage_id' => $entryStage?->id,
                'assigned_user_id' => $referrer->assigned_user_id ?? $user->id,
                'business_line' => $referrer->business_line,
            ]);

            $referral = Referral::query()->create([
                'referrer_type' => $referrer->getMorphClass(),
                'referrer_id' => $referrer->id,
                'referred_type' => $referred->getMorphClass(),
                'referred_id' => $referred->id,
                'user_id' => $user->id,
                'status' => ReferralStatus::Pending,
                'notes' => Arr::get($data, 'notes'),
            ]);

            $this->timeline->log(
                $referrer,
                'referral_logged',
                'Referral: '.$referred->fullName(),
                $referred->email ?? $referred->phone,
                ['referral_id' => $referral->id, 'referred_lead_id' => $referred->id],
                $user,
            );

            $this->timeline->log(
                $referred,
                'referral_received',
                'Referred by '.$referrer->fullName(),
                null,
                ['referral_id' => $referral->id, 'referrer_lead_id' => $referrer->id],
                $user,
            );

            $this->dashboardStats->forget($user);

            return $referral->fresh(['referrer', 'referred', 'loggedBy']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function markRewarded(Referral $referral, array $data, User $user): Referral
    {
        $referral->update([
            'status' => ReferralStatus::Rewarded,
            'reward_type' => Arr::get($data, 'reward_type'),
            'reward_amount' => filled(Arr::get($data, 'reward_amount'))
                ? (float) Arr::get($data, 'reward_amount')
                : null,
            'reward_notes' => Arr::get($data, 'reward_notes'),
            'rewarded_at' => now(),
        ]);

        $this->timeline->log(
            $referral->referrer,
            'referral_rewarded',
            'Referral reward issued',
            $referral->referred->fullName(),
            [
                'referral_id' => $referral->id,
                'reward_type' => $referral->reward_type,
                'reward_amount' => $referral->reward_amount,
            ],
            $user,
        );

        $this->dashboardStats->forget($user);

        return $referral->fresh(['referrer', 'referred']);
    }

    public function markConvertedForReferredLead(Lead|Prospect|Customer|Recruit $referredLead, User $user): void
    {
        $referral = Referral::query()
            ->where('referred_type', $referredLead->getMorphClass())
            ->where('referred_id', $referredLead->id)
            ->whereIn('status', [ReferralStatus::Pending, ReferralStatus::Contacted])
            ->first();

        if (! $referral) {
            return;
        }

        $referral->update(['status' => ReferralStatus::Converted]);

        $this->timeline->log(
            $referral->referrer,
            'referral_converted',
            'Referral converted to sale',
            $referredLead->fullName(),
            ['referral_id' => $referral->id, 'referred_lead_id' => $referredLead->id],
            $user,
        );

        $this->dashboardStats->forget($user);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Referral>
     */
    public function referralsForReferrer(Lead|Prospect|Customer|Recruit $referrer, int $limit = 20)
    {
        return Referral::query()
            ->where('referrer_type', $referrer->getMorphClass())
            ->where('referrer_id', $referrer->id)
            ->with(['referred.stage', 'loggedBy'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{name: string, referrals: int, converted: int, rewarded: int}>
     */
    public function leaderboard(?User $user = null, ?\Carbon\Carbon $start = null, int $limit = 10): \Illuminate\Support\Collection
    {
        $user ??= auth()->user();

        if (! $user) {
            return collect();
        }

        $rows = Referral::query()
            ->when($start, fn ($query) => $query->where('referrals.created_at', '>=', $start))
            ->select(
                'referrer_lead_id',
                DB::raw('count(*) as total'),
                DB::raw("sum(case when status in ('converted', 'rewarded') then 1 else 0 end) as converted"),
                DB::raw("sum(case when status = 'rewarded' then 1 else 0 end) as rewarded"),
            )
            ->groupBy('referrer_lead_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $referrers = Lead::query()
            ->whereIn('id', $rows->pluck('referrer_lead_id'))
            ->get()
            ->filter(fn (Lead $lead) => CrmScope::leadIsAccessible($lead, $user))
            ->keyBy('id');

        return $rows
            ->filter(fn ($row) => $referrers->has($row->referrer_lead_id))
            ->map(fn ($row) => (object) [
                'name' => $referrers[$row->referrer_lead_id]->fullName(),
                'referrals' => (int) $row->total,
                'converted' => (int) $row->converted,
                'rewarded' => (int) $row->rewarded,
            ])
            ->values();
    }
}
