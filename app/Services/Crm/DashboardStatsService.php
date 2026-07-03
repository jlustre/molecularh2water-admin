<?php

namespace App\Services\Crm;

use App\Models\Crm\Activity;
use App\Models\Crm\Appointment;
use App\Models\Crm\Demonstration;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\Order;
use App\Models\Crm\Prospect;
use App\Models\Crm\Task;
use App\Models\Crm\TimelineEvent;
use App\Models\User;
use App\Support\Crm\CrmScope;
use App\Support\Crm\PipelineSummaryGrouper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DashboardStatsService
{
    /**
     * @return array{
     *     totalLeads: int,
     *     newLeads: int,
     *     hotProspects: int,
     *     followUpsDueToday: int,
     *     appointmentsToday: int,
     *     scheduledDemos: int,
     *     demosToday: int,
     *     activeProspects: int,
     *     pendingQuotes: int,
     *     pendingOrders: int,
     *     closedSales: int,
     *     conversionRate: float,
     *     funnelStages: Collection<int, FunnelStage>,
     *     groupedFunnelStages: list<array{label: string, stages: Collection<int, FunnelStage>}>,
     *     recentActivities: Collection<int, mixed>,
     *     upcomingTasks: Collection<int, Task>
     * }
     */
    public function get(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user || ! Schema::hasTable('leads')) {
            return $this->empty();
        }

        $ttl = (int) config('crm.dashboard_cache_ttl', 300);

        $scalars = Cache::remember(
            $this->cacheKey($user),
            $ttl,
            fn () => $this->computeScalars($user),
        );

        return array_merge($scalars, $this->computeCollections($user));
    }

    public function forget(?User $user = null): void
    {
        $user ??= auth()->user();

        if (! $user) {
            return;
        }

        Cache::forget($this->cacheKey($user));
    }

    public function notifyChanged(?User $user = null): void
    {
        $this->forget($user);
    }

    /**
     * @return Collection<int, Lead>
     */
    public function leadsInStage(int $stageId, ?User $user = null): Collection
    {
        $user ??= auth()->user();

        if (! $user || ! Schema::hasTable('leads')) {
            return collect();
        }

        return CrmScope::leads(Lead::query(), $user)
            ->where('funnel_stage_id', $stageId)
            ->with(['assignedUser'])
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @return array{
     *     totalLeads: int,
     *     newLeads: int,
     *     hotProspects: int,
     *     followUpsDueToday: int,
     *     appointmentsToday: int,
     *     scheduledDemos: int,
     *     demosToday: int,
     *     activeProspects: int,
     *     pendingQuotes: int,
     *     pendingOrders: int,
     *     closedSales: int,
     *     conversionRate: float
     * }
     */
    private function computeScalars(User $user): array
    {
        $leadQuery = fn () => CrmScope::contacts(Lead::query(), $user);
        $prospectQuery = fn () => CrmScope::contacts(Prospect::query(), $user);

        $totalLeads = $leadQuery()->count();
        $newLeads = $leadQuery()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $hotProspects = $prospectQuery()
            ->hot()
            ->count();
        $followUpsDueToday = $leadQuery()->followUpDueToday()->count()
            + $prospectQuery()->followUpDueToday()->count();
        $appointmentsToday = CrmScope::appointments(Appointment::query(), $user)
            ->whereDate('starts_at', now()->toDateString())
            ->count();

        $demoStageIds = FunnelStage::query()
            ->whereIn('slug', config('crm.demo_stage_slugs', []))
            ->pluck('id');

        $scheduledDemos = $leadQuery()
            ->whereIn('funnel_stage_id', $demoStageIds)
            ->count()
            + $prospectQuery()
                ->whereIn('funnel_stage_id', $demoStageIds)
                ->count();

        $demosToday = Schema::hasTable('demonstrations')
            ? Demonstration::query()
                ->whereDate('scheduled_at', now()->toDateString())
                ->forAccessibleContacts($user)
                ->count()
            : $scheduledDemos;

        $activeProspects = $prospectQuery()->count();

        $stageSlugs = config('crm.dashboard_stage_slugs', []);
        $pendingQuotes = $this->countLeadsInStages($leadQuery, $stageSlugs['pending_quotes'] ?? []);
        $pendingOrders = Schema::hasTable('orders')
            ? Order::query()
                ->forAccessibleContacts($user)
                ->whereIn('status', ['draft', 'submitted', 'confirmed'])
                ->count()
            : $this->countLeadsInStages($leadQuery, $stageSlugs['pending_orders'] ?? []);

        $wonStageIds = FunnelStage::query()->where('is_won', true)->pluck('id');
        $closedSales = $leadQuery()
            ->whereIn('funnel_stage_id', $wonStageIds)
            ->where('updated_at', '>=', now()->startOfMonth())
            ->count();

        $totalInFunnel = $leadQuery()->whereNotNull('funnel_stage_id')->count();
        $conversionRate = $totalInFunnel > 0
            ? round(($closedSales / max($totalInFunnel, 1)) * 100, 1)
            : 0;

        return [
            'totalLeads' => $totalLeads,
            'newLeads' => $newLeads,
            'hotProspects' => $hotProspects,
            'followUpsDueToday' => $followUpsDueToday,
            'appointmentsToday' => $appointmentsToday,
            'scheduledDemos' => $scheduledDemos,
            'demosToday' => $demosToday,
            'activeProspects' => $activeProspects,
            'pendingQuotes' => $pendingQuotes,
            'pendingOrders' => $pendingOrders,
            'closedSales' => $closedSales,
            'conversionRate' => $conversionRate,
        ];
    }

    /**
     * @param  callable(): \Illuminate\Database\Eloquent\Builder  $leadQuery
     * @param  list<string>  $slugs
     */
    private function countLeadsInStages(callable $leadQuery, array $slugs): int
    {
        if ($slugs === []) {
            return 0;
        }

        $stageIds = FunnelStage::query()->whereIn('slug', $slugs)->pluck('id');

        return $leadQuery()->whereIn('funnel_stage_id', $stageIds)->count();
    }

    /**
     * @return array{
     *     funnelStages: Collection<int, FunnelStage>,
     *     groupedFunnelStages: list<array{label: string, stages: Collection<int, FunnelStage>}>,
     *     recentActivities: Collection<int, mixed>,
     *     upcomingTasks: Collection<int, Task>,
     *     upcomingEvents: Collection<int, mixed>,
     *     upcomingCalendarTasks: Collection<int, mixed>
     * }
     */
    private function computeCollections(User $user): array
    {
        $funnelStages = $this->pipelineSummaryStages($user);

        $recentActivities = CrmScope::timelineEvents(TimelineEvent::query(), $user)
            ->with(['lead', 'user'])
            ->latest()
            ->limit(6)
            ->get();

        if ($recentActivities->isEmpty()) {
            $recentActivities = CrmScope::activities(Activity::query(), $user)
                ->with(['lead', 'user', 'type'])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Activity $activity) => (object) [
                    'title' => $activity->title,
                    'created_at' => $activity->created_at,
                    'lead' => $activity->lead,
                    'user' => $activity->user,
                ]);
        }

        $upcomingTasks = CrmScope::tasks(Task::query(), $user)
            ->with(['lead', 'user'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        $calendar = app(CalendarQueryService::class);

        $upcomingEvents = Schema::hasTable('calendar_events')
            ? $calendar->upcomingScheduledEvents(6, $user)
            : collect();

        $upcomingCalendarTasks = Schema::hasTable('tasks')
            ? $calendar->upcomingActionTasks(6, $user)
            : collect();

        return [
            'funnelStages' => $funnelStages,
            'groupedFunnelStages' => app(PipelineSummaryGrouper::class)->group($funnelStages),
            'recentActivities' => $recentActivities,
            'upcomingTasks' => $upcomingTasks,
            'upcomingEvents' => $upcomingEvents,
            'upcomingCalendarTasks' => $upcomingCalendarTasks,
        ];
    }

    /**
     * @return array{
     *     totalLeads: int,
     *     newLeads: int,
     *     hotProspects: int,
     *     followUpsDueToday: int,
     *     appointmentsToday: int,
     *     scheduledDemos: int,
     *     demosToday: int,
     *     activeProspects: int,
     *     pendingQuotes: int,
     *     pendingOrders: int,
     *     closedSales: int,
     *     conversionRate: float,
     *     funnelStages: Collection<int, FunnelStage>,
     *     groupedFunnelStages: list<array{label: string, stages: Collection<int, FunnelStage>}>,
     *     recentActivities: Collection<int, mixed>,
     *     upcomingTasks: Collection<int, Task>
     * }
     */
    private function empty(): array
    {
        return [
            'totalLeads' => 0,
            'newLeads' => 0,
            'hotProspects' => 0,
            'followUpsDueToday' => 0,
            'appointmentsToday' => 0,
            'scheduledDemos' => 0,
            'demosToday' => 0,
            'activeProspects' => 0,
            'pendingQuotes' => 0,
            'pendingOrders' => 0,
            'closedSales' => 0,
            'conversionRate' => 0,
            'funnelStages' => collect(),
            'groupedFunnelStages' => [],
            'recentActivities' => collect(),
            'upcomingTasks' => collect(),
            'upcomingEvents' => collect(),
            'upcomingCalendarTasks' => collect(),
        ];
    }

    private function cacheKey(User $user): string
    {
        $scope = CrmScope::userCanViewAll($user)
            ? 'all'
            : 'user.'.$user->id;

        $line = \App\Support\BusinessLineContext::current($user);

        return 'crm.dashboard.stats.'.$scope.'.'.$line;
    }

    /**
     * @return Collection<int, FunnelStage>
     */
    private function pipelineSummaryStages(User $user): Collection
    {
        $funnelSlugs = config(
            'crm.pipeline_summary_funnel_slugs',
            [config('crm.default_funnel_slug', 'sales-funnel')],
        );

        $stages = FunnelStage::query()
            ->whereHas('funnel', fn ($query) => $query->whereIn('slug', $funnelSlugs))
            ->with('funnel')
            ->withCount(['leads' => fn ($query) => CrmScope::leads($query, $user)])
            ->get();

        return $stages
            ->sortBy(function (FunnelStage $stage) use ($funnelSlugs) {
                $funnelIndex = array_search($stage->funnel?->slug, $funnelSlugs, true);

                return [
                    $funnelIndex !== false ? $funnelIndex : 999,
                    $stage->sort_order,
                ];
            })
            ->values();
    }
}
