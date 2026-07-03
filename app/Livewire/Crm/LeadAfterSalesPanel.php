<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Services\Crm\AfterSalesService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class LeadAfterSalesPanel extends Component
{
    use AuthorizesRequests;

    public Lead|Prospect|Customer|Recruit $lead;

    public function mount(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead;
    }

    public function enroll(AfterSalesService $afterSales): void
    {
        $this->authorize('update', $this->lead);

        $afterSales->enrollInAfterSales($this->lead, auth()->user());
        $this->lead->refresh();
    }

    public function render(AfterSalesService $afterSales)
    {
        $funnel = $afterSales->afterSalesFunnel();

        return view('livewire.crm.lead-after-sales-panel', [
            'isEnrolled' => $afterSales->isEnrolled($this->lead),
            'afterSalesFunnel' => $funnel,
            'stages' => $funnel?->stages()->orderBy('sort_order')->get() ?? collect(),
            'showPanel' => $this->lead->lifecycle === LeadLifecycle::Client || $afterSales->isEnrolled($this->lead),
        ]);
    }
}
