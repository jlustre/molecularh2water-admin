<?php

namespace App\Livewire\Crm;

use App\Models\Crm\ConsultantPerformanceDaily;
use App\Models\User;
use App\Services\Crm\ConsultantPerformanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ConsultantPerformanceSummary extends Component
{
    public ?int $subjectUserId = null;

    public bool $lockSubject = false;

    public string $weekFocusDate = '';

    public string $monthFocusDate = '';

    public function mount(?int $subjectUserId = null, bool $lockSubject = false): void
    {
        $actor = auth()->user();

        abort_unless(
            $actor?->hasPermission('activities.view')
                || $actor?->hasPermission('calendar.view')
                || ($lockSubject && app(\App\Services\MemberOverviewAccess::class)->canBrowse($actor)),
            403,
        );

        $this->lockSubject = $lockSubject;
        $this->subjectUserId = $subjectUserId ?: Auth::id();
        $this->weekFocusDate = now()->toDateString();
        $this->monthFocusDate = now()->toDateString();
    }

    public function previousWeek(): void
    {
        $this->weekFocusDate = Carbon::parse($this->weekFocusDate ?: now())->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $candidate = Carbon::parse($this->weekFocusDate ?: now())->addWeek()->startOfWeek();

        if ($candidate->gt(now()->startOfWeek())) {
            $this->weekFocusDate = now()->toDateString();

            return;
        }

        $this->weekFocusDate = $candidate->toDateString();
    }

    public function previousMonth(): void
    {
        $this->monthFocusDate = Carbon::parse($this->monthFocusDate ?: now())->subMonthNoOverflow()->toDateString();
    }

    public function nextMonth(): void
    {
        $candidate = Carbon::parse($this->monthFocusDate ?: now())->addMonthNoOverflow()->startOfMonth();

        if ($candidate->gt(now()->startOfMonth())) {
            $this->monthFocusDate = now()->toDateString();

            return;
        }

        $this->monthFocusDate = $candidate->toDateString();
    }

    #[On('performance-subject-changed')]
    public function syncSubject(?int $subjectUserId): void
    {
        if ($this->lockSubject) {
            return;
        }

        if ($subjectUserId) {
            $this->subjectUserId = $subjectUserId;
        }
    }

    #[On('performance-counters-updated')]
    public function refreshSummary(): void
    {
        // Re-render with latest totals.
    }

    public function render(ConsultantPerformanceService $performance)
    {
        $actor = Auth::user();
        $subject = $this->resolveSubject();
        $performance->assertCanViewSubject($actor, $subject);

        $weekFocus = Carbon::parse($this->weekFocusDate ?: now()->toDateString());
        $monthFocus = Carbon::parse($this->monthFocusDate ?: now()->toDateString());

        [$weekStart, $weekEnd] = $performance->periodRange('week', $weekFocus);
        [$monthStart, $monthEnd] = $performance->periodRange('month', $monthFocus);

        $weekTotals = $performance->totalsFor($subject, $weekStart, $weekEnd);
        $monthTotals = $performance->totalsFor($subject, $monthStart, $monthEnd);
        $labels = ConsultantPerformanceDaily::metricLabels();

        $isCurrentWeek = $weekStart->equalTo(now()->startOfWeek());
        $isCurrentMonth = $monthStart->equalTo(now()->startOfMonth());

        return view('livewire.crm.consultant-performance-summary', [
            'weekTotals' => $weekTotals,
            'monthTotals' => $monthTotals,
            'weekLabel' => $weekStart->format('M j').' – '.$weekEnd->format('M j, Y'),
            'monthLabel' => $monthStart->format('F Y'),
            'weekTitle' => $isCurrentWeek ? 'This week' : 'Week',
            'monthTitle' => $isCurrentMonth ? 'This month' : 'Month',
            'canGoNextWeek' => ! $isCurrentWeek,
            'canGoNextMonth' => ! $isCurrentMonth,
            'labels' => $labels,
            'subject' => $subject,
            'insight' => $this->insight($weekTotals, $monthTotals),
        ]);
    }

    /**
     * @param  array<string, int>  $week
     * @param  array<string, int>  $month
     */
    private function insight(array $week, array $month): ?string
    {
        $monthLeads = $month['leads_added'] ?? 0;
        $monthSales = $month['sales_closed'] ?? 0;
        $monthDemos = $month['actual_demo'] ?? 0;
        $weekCalls = $week['phone_calls'] ?? 0;

        if ($monthLeads >= 5 && $monthSales === 0) {
            return 'Many leads this month with no closed sales — review follow-up and closing process.';
        }

        if ($monthDemos >= 3 && $monthSales === 0) {
            return 'Demos are happening without closes — coach presentation-to-close conversion.';
        }

        if ($weekCalls === 0 && $monthLeads > 0) {
            return 'Leads on the books but no calls logged this week — prioritize outreach.';
        }

        if ($monthSales > 0 && $monthLeads > 0) {
            $rate = round(($monthSales / max($monthLeads, 1)) * 100);

            return "Month close rate vs leads: {$rate}%. Keep reinforcing what is working.";
        }

        return 'Track activity daily so weekly and monthly trends stay visible for coaching.';
    }

    private function resolveSubject(): User
    {
        $actor = Auth::user();
        $id = $this->subjectUserId ?: $actor?->id;

        return User::query()->findOrFail($id);
    }
}
