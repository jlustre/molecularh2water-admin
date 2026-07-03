<?php

namespace App\Livewire\Crm;

use App\Services\Crm\DashboardStatsService;
use Livewire\Component;

class DashboardStats extends Component
{
    public function getListeners(): array
    {
        return array_merge(parent::getListeners(), [
            'business-line-changed' => '$refresh',
        ]);
    }

    public int $totalLeads = 0;

    public int $newLeads = 0;

    public int $hotProspects = 0;

    public int $followUpsDueToday = 0;

    public int $appointmentsToday = 0;

    public int $scheduledDemos = 0;

    public int $demosToday = 0;

    public int $activeProspects = 0;

    public int $pendingQuotes = 0;

    public int $pendingOrders = 0;

    public int $closedSales = 0;

    public float $conversionRate = 0;

    public function mount(DashboardStatsService $stats): void
    {
        $this->applyScalars($stats->get());
    }

    public function render(DashboardStatsService $stats)
    {
        $data = $stats->get();

        return view('livewire.crm.dashboard-stats', [
            'funnelStages' => $data['funnelStages'],
            'recentActivities' => $data['recentActivities'],
            'upcomingTasks' => $data['upcomingTasks'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyScalars(array $data): void
    {
        $this->totalLeads = $data['totalLeads'];
        $this->newLeads = $data['newLeads'];
        $this->hotProspects = $data['hotProspects'];
        $this->followUpsDueToday = $data['followUpsDueToday'];
        $this->appointmentsToday = $data['appointmentsToday'];
        $this->scheduledDemos = $data['scheduledDemos'];
        $this->demosToday = $data['demosToday'];
        $this->activeProspects = $data['activeProspects'];
        $this->pendingQuotes = $data['pendingQuotes'];
        $this->pendingOrders = $data['pendingOrders'];
        $this->closedSales = $data['closedSales'];
        $this->conversionRate = $data['conversionRate'];
    }
}
