<?php

namespace App\Services\Crm;

use App\Models\Crm\Activity;
use App\Models\Crm\Customer;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\LandingPage;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Prospect;
use App\Models\User;
use App\Services\Crm\ReferralService;
use App\Support\Crm\CrmScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function periodStart(string $period): ?Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7)->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            '90d' => now()->subDays(90)->startOfDay(),
            default => null,
        };
    }

    /**
     * @return array<string, int|float>
     */
    public function summary(string $period = '30d'): array
    {
        $start = $this->periodStart($period);
        $leads = $this->scopedLeads($start);
        $prospectsQuery = $this->scopedContacts(Prospect::query(), $start);
        $clientsQuery = $this->scopedContacts(Customer::query(), $start);
        $wonStageIds = FunnelStage::query()->where('is_won', true)->pluck('id');

        $total = (clone $leads)->count()
            + (clone $prospectsQuery)->count()
            + (clone $clientsQuery)->count();
        $prospects = (clone $prospectsQuery)->count();
        $clients = (clone $clientsQuery)->count();
        $closed = (clone $leads)->whereIn('funnel_stage_id', $wonStageIds)->count()
            + (clone $prospectsQuery)->whereIn('funnel_stage_id', $wonStageIds)->count()
            + (clone $clientsQuery)->whereIn('funnel_stage_id', $wonStageIds)->count();
        $activities = CrmScope::activities(Activity::query())
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->count();

        return [
            'total_records' => $total,
            'prospects' => $prospects,
            'clients' => $clients,
            'closed_won' => $closed,
            'conversion_rate' => $total > 0 ? round(($closed / $total) * 100, 1) : 0,
            'activities_logged' => $activities,
        ];
    }

    /**
     * @return Collection<int, object{label: string, count: int, percentage: float}>
     */
    public function leadSources(string $period = '30d'): Collection
    {
        $start = $this->periodStart($period);

        $rows = $this->scopedLeads($start)
            ->select('lead_source_id', DB::raw('count(*) as total'))
            ->groupBy('lead_source_id')
            ->get();

        $total = max($rows->sum('total'), 1);
        $sources = LeadSource::query()->whereIn('id', $rows->pluck('lead_source_id'))->get()->keyBy('id');

        return $rows
            ->map(function ($row) use ($sources, $total) {
                $label = $sources[$row->lead_source_id]->name ?? 'Unknown';

                return (object) [
                    'label' => $label,
                    'count' => (int) $row->total,
                    'percentage' => round(((int) $row->total / $total) * 100, 1),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    /**
     * @return Collection<int, object{name: string, count: int, percentage: float, is_won: bool, is_lost: bool}>
     */
    public function funnelStages(string $period = '30d'): Collection
    {
        $start = $this->periodStart($period);

        $rows = $this->scopedLeads($start)
            ->select('funnel_stage_id', DB::raw('count(*) as total'))
            ->whereNotNull('funnel_stage_id')
            ->groupBy('funnel_stage_id')
            ->get();

        $total = max($rows->sum('total'), 1);
        $stages = FunnelStage::query()->whereIn('id', $rows->pluck('funnel_stage_id'))->get()->keyBy('id');

        return FunnelStage::query()
            ->orderBy('sort_order')
            ->get()
            ->map(function (FunnelStage $stage) use ($rows, $total) {
                $count = (int) ($rows->firstWhere('funnel_stage_id', $stage->id)?->total ?? 0);

                return (object) [
                    'name' => $stage->name,
                    'count' => $count,
                    'percentage' => round(($count / $total) * 100, 1),
                    'is_won' => $stage->is_won,
                    'is_lost' => $stage->is_lost,
                ];
            });
    }

    /**
     * @return Collection<int, object{user_id: int, name: string, leads: int, closed: int, activities: int}>
     */
    public function agentLeaderboard(string $period = '30d'): Collection
    {
        if (! CrmScope::userCanViewAll()) {
            return collect();
        }

        $start = $this->periodStart($period);
        $wonStageIds = FunnelStage::query()->where('is_won', true)->pluck('id');

        $leadCounts = Lead::query()
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->whereNotNull('assigned_user_id')
            ->select('assigned_user_id', DB::raw('count(*) as total'))
            ->groupBy('assigned_user_id')
            ->pluck('total', 'assigned_user_id');

        $closedCounts = Lead::query()
            ->when($start, fn ($q) => $q->where('updated_at', '>=', $start))
            ->whereIn('funnel_stage_id', $wonStageIds)
            ->whereNotNull('assigned_user_id')
            ->select('assigned_user_id', DB::raw('count(*) as total'))
            ->groupBy('assigned_user_id')
            ->pluck('total', 'assigned_user_id');

        $activityCounts = Activity::query()
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('count(*) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $userIds = $leadCounts->keys()
            ->merge($closedCounts->keys())
            ->merge($activityCounts->keys())
            ->unique()
            ->filter();

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => (object) [
                'user_id' => $user->id,
                'name' => $user->name,
                'leads' => (int) ($leadCounts[$user->id] ?? 0),
                'closed' => (int) ($closedCounts[$user->id] ?? 0),
                'activities' => (int) ($activityCounts[$user->id] ?? 0),
            ])
            ->sortByDesc('closed')
            ->values();
    }

    /**
     * @return Collection<int, object{name: string, referrals: int, converted: int, rewarded: int}>
     */
    public function referralLeaderboard(string $period = '30d'): Collection
    {
        if (! CrmScope::userCanViewAll()) {
            return collect();
        }

        return app(ReferralService::class)->leaderboard(
            auth()->user(),
            $this->periodStart($period),
        );
    }

    /**
     * @return Collection<int, object{label: string, count: int}>
     */
    public function monthlyTrend(int $months = 6): Collection
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $counts = $this->scopedLeads($start)
            ->get(['created_at'])
            ->groupBy(fn (Lead $lead) => $lead->created_at->format('Y-m'))
            ->map->count();

        $trend = collect();

        for ($i = 0; $i < $months; $i++) {
            $month = now()->subMonths($months - 1 - $i)->startOfMonth();
            $key = $month->format('Y-m');
            $trend->push((object) [
                'label' => $month->format('M Y'),
                'count' => (int) ($counts[$key] ?? 0),
            ]);
        }

        return $trend;
    }

    /**
     * @return Collection<int, object{title: string, slug: string, conversions: int, published: bool}>
     */
    public function landingPages(): Collection
    {
        if (! CrmScope::userCanViewAll()) {
            return collect();
        }

        return LandingPage::query()
            ->orderByDesc('conversion_count')
            ->get()
            ->map(fn (LandingPage $page) => (object) [
                'title' => $page->title,
                'slug' => $page->slug,
                'conversions' => $page->conversion_count,
                'published' => $page->is_published,
            ]);
    }

    private function scopedLeads(?Carbon $start = null)
    {
        return $this->scopedContacts(Lead::query(), $start);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    private function scopedContacts($query, ?Carbon $start = null)
    {
        return CrmScope::contacts($query)
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start));
    }
}
