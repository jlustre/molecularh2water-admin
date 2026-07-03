<?php

namespace App\Livewire\Crm;

use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Services\Crm\DashboardStatsService;
use App\Services\Crm\ExecutiveAnalyticsService;
use Livewire\Component;

class ExecutiveDashboard extends Component
{
    use UsesCrmLayout;

    public string $period = '30d';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('crm.dashboard.view'), 403);
    }

    public function render(DashboardStatsService $stats, ExecutiveAnalyticsService $analytics)
    {
        $executive = $analytics->snapshot($this->period);
        $quickStats = $stats->get();

        return view('livewire.crm.executive-dashboard', [
            'executive' => $executive,
            'quickStats' => $quickStats,
        ])->layout($this->crmLayout());
    }
}
