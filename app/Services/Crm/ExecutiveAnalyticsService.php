<?php

namespace App\Services\Crm;

use App\Enums\Crm\DemonstrationOutcome;
use App\Enums\Crm\DemonstrationStatus;
use App\Enums\Crm\PaymentStatus;
use App\Enums\Crm\ReferralStatus;
use App\Models\Crm\Demonstration;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Order;
use App\Models\Crm\OrderItem;
use App\Models\Crm\PipelineStageHistory;
use App\Models\Crm\Referral;
use App\Models\User;
use App\Support\Crm\CrmScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExecutiveAnalyticsService
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
     * @return array<string, mixed>
     */
    public function snapshot(string $period = '30d', ?User $user = null): array
    {
        $user ??= auth()->user();

        return [
            'demoSuccess' => $this->demoSuccessRate($period, $user),
            'stageDurations' => $this->stageDurations($period, $user),
            'referralConversion' => $this->referralConversion($period, $user),
            'revenueByProduct' => $this->revenueByProduct($period, $user),
            'revenueByAgent' => $this->revenueByAgent($period, $user),
            'revenueTrend' => $this->revenueTrend($period, $user),
            'totalRevenue' => $this->totalRevenue($period, $user),
        ];
    }

    /**
     * @return array{completed: int, successful: int, rate: float}
     */
    public function demoSuccessRate(string $period = '30d', ?User $user = null): array
    {
        if (! Schema::hasTable('demonstrations')) {
            return ['completed' => 0, 'successful' => 0, 'rate' => 0.0];
        }

        $start = $this->periodStart($period);

        $query = Demonstration::query()
            ->where('status', DemonstrationStatus::Completed)
            ->forAccessibleContacts($user)
            ->when($start, fn ($q) => $q->where('updated_at', '>=', $start));

        $completed = (clone $query)->count();
        $successful = (clone $query)
            ->whereIn('outcome', [DemonstrationOutcome::Interested, DemonstrationOutcome::Sold])
            ->count();

        return [
            'completed' => $completed,
            'successful' => $successful,
            'rate' => $completed > 0 ? round(($successful / $completed) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return Collection<int, object{stage: string, avg_days: float, moves: int}>
     */
    public function stageDurations(string $period = '30d', ?User $user = null): Collection
    {
        if (! Schema::hasTable('pipeline_stage_histories')) {
            return collect();
        }

        $start = $this->periodStart($period);

        $rows = PipelineStageHistory::query()
            ->forAccessibleContacts($user)
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->whereNotNull('from_stage_id')
            ->whereNotNull('duration_in_previous_stage_seconds')
            ->select('from_stage_id', DB::raw('avg(duration_in_previous_stage_seconds) as avg_seconds'), DB::raw('count(*) as moves'))
            ->groupBy('from_stage_id')
            ->orderByDesc('moves')
            ->limit(12)
            ->get();

        $stages = FunnelStage::query()
            ->whereIn('id', $rows->pluck('from_stage_id'))
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($stages) {
            $stage = $stages[$row->from_stage_id] ?? null;

            return (object) [
                'stage' => $stage?->name ?? 'Unknown',
                'avg_days' => round(((float) $row->avg_seconds) / 86400, 1),
                'moves' => (int) $row->moves,
            ];
        });
    }

    /**
     * @return array{total: int, converted: int, rate: float}
     */
    public function referralConversion(string $period = '30d', ?User $user = null): array
    {
        if (! Schema::hasTable('referrals')) {
            return ['total' => 0, 'converted' => 0, 'rate' => 0.0];
        }

        $start = $this->periodStart($period);

        $query = Referral::query()
            ->whereHasMorph(
                'referrer',
                ['lead', 'prospect', 'customer', 'recruit'],
                fn ($contactQuery) => CrmScope::contacts($contactQuery, $user),
            )
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start));

        $total = (clone $query)->count();
        $converted = (clone $query)
            ->whereIn('status', [ReferralStatus::Converted, ReferralStatus::Rewarded])
            ->count();

        return [
            'total' => $total,
            'converted' => $converted,
            'rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return Collection<int, object{label: string, revenue: float, quantity: int}>
     */
    public function revenueByProduct(string $period = '30d', ?User $user = null): Collection
    {
        if (! Schema::hasTable('order_items')) {
            return collect();
        }

        $start = $this->periodStart($period);

        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.id', Order::query()->forAccessibleContacts($user)->select('id'))
            ->when($start, fn ($q) => $q->where('orders.paid_at', '>=', $start))
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->select(
                'order_items.description',
                DB::raw('sum(order_items.line_total) as revenue'),
                DB::raw('sum(order_items.quantity) as quantity'),
            )
            ->groupBy('order_items.description')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return $rows->map(fn ($row) => (object) [
            'label' => $row->description,
            'revenue' => round((float) $row->revenue, 2),
            'quantity' => (int) $row->quantity,
        ]);
    }

    /**
     * @return Collection<int, object{name: string, revenue: float, orders: int}>
     */
    public function revenueByAgent(string $period = '30d', ?User $user = null): Collection
    {
        if (! Schema::hasTable('orders')) {
            return collect();
        }

        if (! CrmScope::userCanViewAll($user)) {
            return collect();
        }

        $start = $this->periodStart($period);

        $orders = Order::query()
            ->with('contact')
            ->forAccessibleContacts($user)
            ->when($start, fn ($q) => $q->where('paid_at', '>=', $start))
            ->where('payment_status', PaymentStatus::Paid->value)
            ->get(['id', 'contact_type', 'contact_id', 'amount_paid']);

        $grouped = $orders
            ->filter(fn (Order $order) => filled($order->contact?->assigned_user_id))
            ->groupBy(fn (Order $order) => (int) $order->contact->assigned_user_id)
            ->map(fn (Collection $group) => (object) [
                'assigned_user_id' => (int) $group->first()->contact->assigned_user_id,
                'revenue' => (float) $group->sum('amount_paid'),
                'orders' => $group->count(),
            ])
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        $users = User::query()
            ->whereIn('id', $grouped->pluck('assigned_user_id'))
            ->get()
            ->keyBy('id');

        return $grouped->map(fn ($row) => (object) [
            'name' => $users[$row->assigned_user_id]->name ?? 'Unknown',
            'revenue' => round((float) $row->revenue, 2),
            'orders' => (int) $row->orders,
        ]);
    }

    /**
     * @return Collection<int, object{label: string, revenue: float}>
     */
    public function revenueTrend(string $period = '30d', ?User $user = null, int $months = 6): Collection
    {
        if (! Schema::hasTable('orders')) {
            return collect();
        }

        $start = now()->subMonths($months - 1)->startOfMonth();

        $rows = Order::query()
            ->forAccessibleContacts($user)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->where('paid_at', '>=', $start)
            ->get(['paid_at', 'amount_paid'])
            ->groupBy(fn (Order $order) => $order->paid_at?->format('Y-m'))
            ->map(fn ($group) => round((float) $group->sum('amount_paid'), 2));

        $trend = collect();

        for ($i = 0; $i < $months; $i++) {
            $month = now()->subMonths($months - 1 - $i)->startOfMonth();
            $key = $month->format('Y-m');
            $trend->push((object) [
                'label' => $month->format('M Y'),
                'revenue' => (float) ($rows[$key] ?? 0),
            ]);
        }

        return $trend;
    }

    public function totalRevenue(string $period = '30d', ?User $user = null): float
    {
        if (! Schema::hasTable('orders')) {
            return 0.0;
        }

        $start = $this->periodStart($period);

        return round((float) Order::query()
            ->forAccessibleContacts($user)
            ->when($start, fn ($q) => $q->where('paid_at', '>=', $start))
            ->where('payment_status', PaymentStatus::Paid->value)
            ->sum('amount_paid'), 2);
    }
}
