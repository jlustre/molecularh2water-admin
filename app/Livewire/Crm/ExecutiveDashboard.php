<?php

namespace App\Livewire\Crm;

use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Services\Crm\DashboardStatsService;
use App\Services\Crm\ExecutiveAnalyticsService;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutiveDashboard extends Component
{
    use UsesCrmLayout;

    public string $period = '30d';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('crm.dashboard.view'), 403);
    }

    public function exportCsv(DashboardStatsService $stats, ExecutiveAnalyticsService $analytics): StreamedResponse
    {
        abort_unless(auth()->user()?->hasPermission('crm.dashboard.view'), 403);

        $executive = $analytics->snapshot($this->period);
        $quickStats = $stats->get();
        $filename = sprintf('crm-executive-%s-%s.csv', $this->period, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($executive, $quickStats) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['section', 'metric', 'value', 'period']);
            fputcsv($handle, ['executive', 'total_revenue', $executive['totalRevenue'] ?? 0, $this->period]);
            fputcsv($handle, ['executive', 'demo_success_rate', $executive['demoSuccess']['rate'] ?? 0, $this->period]);
            fputcsv($handle, ['executive', 'demo_completed', $executive['demoSuccess']['completed'] ?? 0, $this->period]);
            fputcsv($handle, ['executive', 'demo_successful', $executive['demoSuccess']['successful'] ?? 0, $this->period]);
            fputcsv($handle, ['executive', 'referral_conversion_rate', $executive['referralConversion']['rate'] ?? 0, $this->period]);

            foreach ($quickStats as $key => $value) {
                if (is_scalar($value)) {
                    fputcsv($handle, ['quick_stats', $key, $value, $this->period]);
                }
            }

            foreach ($executive['revenueByAgent'] ?? [] as $row) {
                fputcsv($handle, ['revenue_by_agent', $row->name ?? 'Unknown', $row->revenue ?? 0, $this->period]);
            }

            foreach ($executive['stageDurations'] ?? [] as $row) {
                fputcsv($handle, ['stage_duration_days', $row->stage ?? 'Unknown', $row->avg_days ?? 0, $this->period]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
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
