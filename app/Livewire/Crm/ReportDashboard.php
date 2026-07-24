<?php

namespace App\Livewire\Crm;

use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Services\Crm\ExecutiveAnalyticsService;
use App\Services\Crm\ReportService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function exportCsv(ReportService $reports, ExecutiveAnalyticsService $analytics): StreamedResponse
    {
        abort_unless(auth()->user()?->hasPermission('reports.view'), 403);

        $this->loadReports($reports, $analytics);
        $filename = sprintf('crm-reports-%s-%s.csv', $this->period, now()->format('Y-m-d'));

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['section', 'metric', 'value', 'period']);

            foreach ($this->summary as $key => $value) {
                fputcsv($handle, ['summary', $key, $value, $this->period]);
            }

            fputcsv($handle, ['executive', 'total_revenue', $this->executive['totalRevenue'] ?? 0, $this->period]);
            fputcsv($handle, ['executive', 'demo_success_rate', $this->executive['demoSuccess']['rate'] ?? 0, $this->period]);
            fputcsv($handle, ['executive', 'referral_conversion_rate', $this->executive['referralConversion']['rate'] ?? 0, $this->period]);
            fputcsv($handle, ['executive', 'completed_demos', $this->executive['demoSuccess']['completed'] ?? 0, $this->period]);

            foreach ($this->leadSources as $row) {
                fputcsv($handle, ['lead_sources', $row->label ?? 'Unknown', $row->count ?? 0, $this->period]);
            }

            foreach ($this->funnelStages as $row) {
                fputcsv($handle, ['funnel_stages', $row->name ?? 'Unknown', $row->count ?? 0, $this->period]);
            }

            foreach ($this->agentLeaderboard as $row) {
                fputcsv($handle, ['agent_leaderboard', ($row->name ?? 'Unknown').' leads', $row->leads ?? 0, $this->period]);
                fputcsv($handle, ['agent_leaderboard', ($row->name ?? 'Unknown').' closed', $row->closed ?? 0, $this->period]);
                fputcsv($handle, ['agent_leaderboard', ($row->name ?? 'Unknown').' activities', $row->activities ?? 0, $this->period]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
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
