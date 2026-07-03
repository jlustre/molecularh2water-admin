<?php

namespace App\Services\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Funnel;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;

class AfterSalesService
{
    public function __construct(
        private readonly FunnelService $funnels,
        private readonly LeadService $leads,
        private readonly TimelineService $timeline,
    ) {}

    public function handleClosedWon(Lead|Prospect|Customer|Recruit $lead, User $user): Lead|Prospect|Customer|Recruit
    {
        app(ReferralService::class)->markConvertedForReferredLead($lead, $user);

        return $this->enrollInAfterSales($lead, $user);
    }

    public function enrollInAfterSales(Lead|Prospect|Customer|Recruit $lead, User $user): Lead|Prospect|Customer|Recruit
    {
        $afterSalesSlug = config('crm.after_sales_funnel_slug', 'after-sales-funnel');

        if ($lead->funnel?->slug === $afterSalesSlug) {
            return $lead;
        }

        if ($lead->lifecycle !== LeadLifecycle::Client) {
            $lead = $this->leads->convertLifecycle($lead, LeadLifecycle::Client, $user);
        }

        $stage = $this->funnels->findStageForFunnel(
            $afterSalesSlug,
            config('crm.after_sales_entry_stage', 'warranty-registration'),
        );

        if (! $stage) {
            return $lead;
        }

        $lead = $this->funnels->moveLead($lead, $stage, $user);

        $this->timeline->log(
            $lead,
            'after_sales_enrolled',
            'Enrolled in after-sales program',
            $lead->funnel?->name.' · '.$lead->stage?->name,
            ['funnel_id' => $lead->funnel_id, 'funnel_stage_id' => $lead->funnel_stage_id],
            $user,
        );

        return $lead->fresh(['stage', 'funnel']);
    }

    public function isEnrolled(Lead|Prospect|Customer|Recruit $lead): bool
    {
        return $lead->funnel?->slug === config('crm.after_sales_funnel_slug', 'after-sales-funnel');
    }

    public function afterSalesFunnel(): ?Funnel
    {
        return Funnel::query()
            ->where('slug', config('crm.after_sales_funnel_slug', 'after-sales-funnel'))
            ->first();
    }
}
