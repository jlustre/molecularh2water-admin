<?php

namespace App\Livewire\Crm;

use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Services\Crm\ExecutiveAnalyticsService;
use App\Services\Crm\ReportService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ReportDashboard extends Component
{
    use UsesCrmLayout;

    public string $period = '30d';

    /** @var array<string, int|float> */
    public array $summary = [];

    /** @var Collection<int, object> */
    public Collection $leadSources;

    /** @var Collection<int, object> */
    public Collection $funnelStages;

    /** @var Collection<int, object> */
    public Collection $agentLeaderboard;

    /** @var Collection<int, object> */
    public Collection $monthlyTrend;

    /** @var Collection<int, object> */
    public Collection $landingPages;

    /** @var Collection<int, object> */
    public Collection $referralLeaderboard;

    /** @var array<string, mixed> */
    public array $executive = [];

    public function mount(ReportService $reports, ExecutiveAnalyticsService $analytics): void
    {
        abort_unless(auth()->user()?->hasPermission('reports.view'), 403);

        $this->leadSources = collect();
        $this->funnelStages = collect();
        $this->agentLeaderboard = collect();
        $this->referralLeaderboard = collect();
        $this->monthlyTrend = collect();
        $this->landingPages = collect();

        $this->loadReports($reports, $analytics);
    }

    public function updatedPeriod(ReportService $reports, ExecutiveAnalyticsService $analytics): void
    {
        $this->loadReports($reports, $analytics);
    }

    public function render()
    {
        return view('livewire.crm.report-dashboard')->layout($this->crmLayout());
    }

    private function loadReports(ReportService $reports, ExecutiveAnalyticsService $analytics): void
    {
        $this->summary = $reports->summary($this->period);
        $this->leadSources = $reports->leadSources($this->period);
        $this->funnelStages = $reports->funnelStages($this->period);
        $this->agentLeaderboard = $reports->agentLeaderboard($this->period);
        $this->referralLeaderboard = $reports->referralLeaderboard($this->period);
        $this->monthlyTrend = $reports->monthlyTrend();
        $this->landingPages = $reports->landingPages();
        $this->executive = $analytics->snapshot($this->period);
    }
}
