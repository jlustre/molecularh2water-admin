<?php

namespace App\Livewire\Portal;

use App\Enums\Crm\MemberSaleStatus;
use App\Models\Crm\MemberSale;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use App\Services\MemberOverviewAccess;
use App\Services\Portal\PortalDashboardService;
use App\Support\Crm\CrmRoutes;
use App\Support\Crm\MemberSaleScope;
use App\Support\Portal\Dashboard\PortalDashboardCard;
use App\Support\Portal\Dashboard\PortalDashboardSection;
use App\Support\Portal\PortalRoleLabel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class MemberOverview extends Component
{
    public User $member;

    public string $recruitsPeriod = 'week';

    public string $salesPeriod = 'week';

    public function mount(User $user, MemberOverviewAccess $access): void
    {
        $actor = auth()->user();
        abort_unless($access->canBrowse($actor), 403);
        $access->assertCanView($actor, $user);

        $this->member = $user->load(['roles', 'sponsor']);
    }

    public function setRecruitsPeriod(string $period): void
    {
        if (in_array($period, ['week', 'month', 'year'], true)) {
            $this->recruitsPeriod = $period;
        }
    }

    public function setSalesPeriod(string $period): void
    {
        if (in_array($period, ['week', 'month', 'year'], true)) {
            $this->salesPeriod = $period;
        }
    }

    public function getListeners(): array
    {
        return array_merge(parent::getListeners(), [
            'performance-counters-updated' => '$refresh',
            'crm-dashboard-refresh' => '$refresh',
        ]);
    }

    public function render(
        PortalDashboardService $dashboard,
        DashboardStatsService $crmStats,
        MemberOverviewAccess $access,
    ) {
        $actor = auth()->user();
        $member = $this->member;
        $access->assertCanView($actor, $member);

        $crmDetail = Schema::hasTable('leads')
            ? $crmStats->get($member)
            : null;

        $showPipeline = (bool) $actor?->hasPermission('pipeline.view');
        $showEvents = (bool) $actor?->hasPermission('calendar.view');
        $showTasks = (bool) $actor?->hasPermission('tasks.view');
        $showActivities = (bool) $actor?->hasPermission('activities.view');
        $showPerformance = (bool) (
            $actor?->hasPermission('activities.view')
            || $actor?->hasPermission('calendar.view')
            || $access->canBrowse($actor)
        );
        $showSales = (bool) (
            $actor?->hasPermission('sales.view')
            || $actor?->hasPermission('sales.manage')
        );

        [$recruitStart, $recruitEnd] = $this->periodRange($this->recruitsPeriod);
        $recruitCount = $member->sponsoredUsers()
            ->whereBetween('created_at', [$recruitStart, $recruitEnd])
            ->count();
        $recruits = $member->sponsoredUsers()
            ->whereBetween('created_at', [$recruitStart, $recruitEnd])
            ->orderByDesc('created_at')
            ->limit(12)
            ->get(['id', 'name', 'email', 'created_at']);

        return view('livewire.portal.member-overview', [
            'member' => $member,
            'roleLabel' => PortalRoleLabel::for($member),
            'sections' => $this->readOnlySections($dashboard->sections($member)),
            'crmDetail' => $crmDetail,
            'showPipeline' => $showPipeline,
            'showEvents' => $showEvents,
            'showTasks' => $showTasks,
            'showActivities' => $showActivities,
            'showPerformance' => $showPerformance,
            'showSales' => $showSales && Schema::hasTable('member_sales'),
            'salesSummary' => $showSales && Schema::hasTable('member_sales')
                ? $this->salesSummary($actor, $member)
                : null,
            'recruits' => $recruits,
            'recruitCount' => $recruitCount,
            'recruitsPeriodLabel' => $this->periodLabel($this->recruitsPeriod),
            'salesPeriodLabel' => $this->periodLabel($this->salesPeriod),
            'salesIndexUrl' => $actor?->hasPermission('sales.view')
                ? route(CrmRoutes::name('sales.index'))
                : null,
            'backUrl' => $actor?->hasPermission('sponsors.view-tree')
                ? route('portal.team')
                : route('dashboard'),
        ])->layout('layouts.portal', ['header' => 'Member Overview']);
    }

    /**
     * @param  list<PortalDashboardSection>  $sections
     * @return list<PortalDashboardSection>
     */
    private function readOnlySections(array $sections): array
    {
        return array_map(function (PortalDashboardSection $section) {
            $cards = array_map(
                fn (PortalDashboardCard $card) => new PortalDashboardCard(
                    label: $card->label,
                    value: $card->value,
                    hint: $card->hint,
                    route: null,
                    tone: $card->tone,
                    icon: $card->icon,
                    action: null,
                ),
                $section->cards,
            );

            return new PortalDashboardSection(
                key: $section->key,
                title: $section->title,
                description: $section->description,
                cards: $cards,
                priority: $section->priority,
            );
        }, $sections);
    }

    /**
     * @return array{count: int, total: float, completed: int, recent: \Illuminate\Support\Collection<int, MemberSale>}
     */
    private function salesSummary(?User $actor, User $member): array
    {
        [$start, $end] = $this->periodRange($this->salesPeriod);

        $base = MemberSaleScope::sales(MemberSale::query(), $actor)
            ->where(function ($query) use ($member) {
                $query->where('user_id', $member->id)
                    ->orWhere('demo_consultant_id', $member->id);
            })
            ->whereBetween('created_at', [$start, $end]);

        $count = (clone $base)->count();
        $total = (float) (clone $base)->sum('total');
        $completed = (clone $base)
            ->where('status', MemberSaleStatus::Completed)
            ->count();

        $recent = (clone $base)
            ->with(['consultant:id,name', 'demoConsultant:id,name'])
            ->latest()
            ->limit(8)
            ->get();

        return [
            'count' => $count,
            'total' => $total,
            'completed' => $completed,
            'recent' => $recent,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodRange(string $period): array
    {
        $focus = now();

        return match ($period) {
            'month' => [$focus->copy()->startOfMonth(), $focus->copy()->endOfMonth()],
            'year' => [$focus->copy()->startOfYear(), $focus->copy()->endOfYear()],
            default => [$focus->copy()->startOfWeek(), $focus->copy()->endOfWeek()],
        };
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            'month' => 'This month',
            'year' => 'This year',
            default => 'This week',
        };
    }
}
