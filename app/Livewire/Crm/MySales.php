<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\MemberSaleStatus;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\MemberSale;
use App\Support\Crm\BusinessLineScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class MySales extends Component
{
    use UsesCrmLayout;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $creditFilter = 'all';

    public string $datePreset = 'month_to_date';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 12;

    public string $viewMode = 'cards';

    public ?int $selectedSaleId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('portal.dashboard.view'), 403);

        $this->syncCustomDatesFromPreset();
    }

    public function updatedDatePreset(): void
    {
        $this->syncCustomDatesFromPreset();
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->datePreset = 'custom';
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->datePreset = 'custom';
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCreditFilter(): void
    {
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['cards', 'list'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function selectStatus(?string $status = null): void
    {
        $this->statusFilter = $status ?? '';
        $this->resetPage();
    }

    public function openSale(int $saleId): void
    {
        $sale = $this->personalSalesQuery()->findOrFail($saleId);
        $this->selectedSaleId = $sale->id;
    }

    public function closeSale(): void
    {
        $this->selectedSaleId = null;
    }

    public function render()
    {
        [$rangeStart, $rangeEnd] = $this->dateRange();
        $userId = (int) auth()->id();
        $base = $this->personalSalesQuery($rangeStart, $rangeEnd);

        $summaryRows = (clone $base)->get(['id', 'user_id', 'demo_consultant_id', 'status', 'total', 'created_at']);
        $summary = $this->buildSummary($summaryRows, $userId);
        $pipeline = $this->buildPipeline($summaryRows);
        $monthlyTrend = $this->buildMonthlyTrend($userId);
        $selectedSale = $this->selectedSaleId
            ? $this->personalSalesQuery()->with(['consultant', 'demoConsultant', 'items'])->find($this->selectedSaleId)
            : null;

        $sales = $this->filteredSalesQuery($rangeStart, $rangeEnd)
            ->with(['consultant', 'demoConsultant', 'items'])
            ->latest()
            ->paginate(max(6, min(48, (int) $this->perPage)));

        return view('livewire.crm.my-sales', [
            'sales' => $sales,
            'summary' => $summary,
            'pipeline' => $pipeline,
            'monthlyTrend' => $monthlyTrend,
            'selectedSale' => $selectedSale,
            'statuses' => MemberSaleStatus::cases(),
            'datePresets' => $this->datePresetOptions(),
            'rangeLabel' => $this->rangeLabel($rangeStart, $rangeEnd),
            'statusStyles' => $this->statusStyles(),
        ])->layout($this->crmLayout(), ['header' => 'My Sales']);
    }

    /**
     * @param  Collection<int, MemberSale>  $rows
     * @return array<string, int|float>
     */
    private function buildSummary(Collection $rows, int $userId): array
    {
        $completed = $rows->filter(fn (MemberSale $sale) => $sale->status === MemberSaleStatus::Completed);
        $asConsultant = $rows->filter(fn (MemberSale $sale) => (int) $sale->user_id === $userId);
        $asDemo = $rows->filter(
            fn (MemberSale $sale) => (int) $sale->demo_consultant_id === $userId
                && (int) $sale->user_id !== $userId
        );

        $revenue = (float) $completed->sum(fn (MemberSale $sale) => (float) $sale->total);
        $pipelineValue = (float) $rows
            ->reject(fn (MemberSale $sale) => $sale->status === MemberSaleStatus::Completed)
            ->sum(fn (MemberSale $sale) => (float) $sale->total);

        return [
            'total_sales' => $rows->count(),
            'completed' => $completed->count(),
            'in_pipeline' => $rows->count() - $completed->count(),
            'revenue' => $revenue,
            'pipeline_value' => $pipelineValue,
            'avg_deal' => $completed->count() > 0 ? $revenue / $completed->count() : 0.0,
            'as_consultant' => $asConsultant->count(),
            'as_demo' => $asDemo->count(),
            'close_rate' => $rows->count() > 0
                ? round(($completed->count() / $rows->count()) * 100)
                : 0,
        ];
    }

    /**
     * @param  Collection<int, MemberSale>  $rows
     * @return list<array{status: MemberSaleStatus, count: int, total: float}>
     */
    private function buildPipeline(Collection $rows): array
    {
        return collect(MemberSaleStatus::cases())
            ->map(function (MemberSaleStatus $status) use ($rows) {
                $matched = $rows->filter(fn (MemberSale $sale) => $sale->status === $status);

                return [
                    'status' => $status,
                    'count' => $matched->count(),
                    'total' => (float) $matched->sum(fn (MemberSale $sale) => (float) $sale->total),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{label: string, count: int, revenue: float, height: int}>
     */
    private function buildMonthlyTrend(int $userId): array
    {
        $start = now()->subMonths(5)->startOfMonth();
        $sales = BusinessLineScope::apply(
            MemberSale::query()
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhere('demo_consultant_id', $userId);
                })
                ->where('created_at', '>=', $start)
        )->get(['status', 'total', 'created_at']);

        $months = collect(range(0, 5))->map(function (int $offset) use ($sales) {
            $month = now()->subMonths(5 - $offset)->startOfMonth();
            $inMonth = $sales->filter(
                fn (MemberSale $sale) => $sale->created_at?->isSameMonth($month)
            );
            $revenue = (float) $inMonth
                ->filter(fn (MemberSale $sale) => $sale->status === MemberSaleStatus::Completed)
                ->sum(fn (MemberSale $sale) => (float) $sale->total);

            return [
                'label' => $month->format('M'),
                'count' => $inMonth->count(),
                'revenue' => $revenue,
            ];
        });

        $maxRevenue = max(1.0, (float) $months->max('revenue'));

        return $months
            ->map(fn (array $month) => [
                ...$month,
                'height' => (int) max(8, round(($month['revenue'] / $maxRevenue) * 100)),
            ])
            ->all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<MemberSale>
     */
    private function personalSalesQuery(?Carbon $rangeStart = null, ?Carbon $rangeEnd = null)
    {
        $userId = (int) auth()->id();

        return BusinessLineScope::apply(
            MemberSale::query()
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhere('demo_consultant_id', $userId);
                })
                ->when($rangeStart, fn ($q) => $q->where('created_at', '>=', $rangeStart))
                ->when($rangeEnd, fn ($q) => $q->where('created_at', '<=', $rangeEnd))
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<MemberSale>
     */
    private function filteredSalesQuery(?Carbon $rangeStart, ?Carbon $rangeEnd)
    {
        $userId = (int) auth()->id();

        return $this->personalSalesQuery($rangeStart, $rangeEnd)
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('customer_name', 'like', "%{$this->search}%")
                        ->orWhere('customer_email', 'like', "%{$this->search}%")
                        ->orWhere('customer_phone', 'like', "%{$this->search}%")
                        ->orWhereHas('items', fn ($items) => $items->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->creditFilter === 'consultant', fn ($q) => $q->where('user_id', $userId))
            ->when($this->creditFilter === 'demo', function ($q) use ($userId) {
                $q->where('demo_consultant_id', $userId)
                    ->where(function ($inner) use ($userId) {
                        $inner->whereNull('user_id')->orWhere('user_id', '!=', $userId);
                    });
            });
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function dateRange(): array
    {
        return match ($this->datePreset) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'month_to_date' => [now()->startOfMonth(), now()->endOfDay()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'last_7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'last_30_days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'last_90_days' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'year_to_date' => [now()->startOfYear(), now()->endOfDay()],
            'custom' => [
                filled($this->dateFrom) ? Carbon::parse($this->dateFrom)->startOfDay() : null,
                filled($this->dateTo) ? Carbon::parse($this->dateTo)->endOfDay() : null,
            ],
            default => [null, null],
        };
    }

    private function syncCustomDatesFromPreset(): void
    {
        if ($this->datePreset === 'custom') {
            return;
        }

        [$start, $end] = $this->dateRange();
        $this->dateFrom = $start?->toDateString() ?? '';
        $this->dateTo = $end?->toDateString() ?? '';
    }

    /**
     * @return array<string, string>
     */
    private function datePresetOptions(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This week',
            'last_week' => 'Last week',
            'month_to_date' => 'Month to date',
            'last_month' => 'Last month',
            'last_7_days' => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            'last_90_days' => 'Last 90 days',
            'year_to_date' => 'Year to date',
            'all' => 'All time',
            'custom' => 'Custom range',
        ];
    }

    private function rangeLabel(?Carbon $start, ?Carbon $end): string
    {
        if (! $start && ! $end) {
            return 'All time';
        }

        if ($start && $end) {
            return $start->format('M j, Y').' – '.$end->format('M j, Y');
        }

        if ($start) {
            return 'From '.$start->format('M j, Y');
        }

        return 'Through '.$end->format('M j, Y');
    }

    /**
     * @return array<string, array{bg: string, text: string, ring: string, bar: string}>
     */
    private function statusStyles(): array
    {
        return [
            MemberSaleStatus::ApplicationStarted->value => [
                'bg' => 'bg-amber-50',
                'text' => 'text-amber-800',
                'ring' => 'ring-amber-200',
                'bar' => 'bg-amber-400',
            ],
            MemberSaleStatus::Financing->value => [
                'bg' => 'bg-sky-50',
                'text' => 'text-sky-800',
                'ring' => 'ring-sky-200',
                'bar' => 'bg-sky-500',
            ],
            MemberSaleStatus::Approved->value => [
                'bg' => 'bg-teal-50',
                'text' => 'text-teal-800',
                'ring' => 'ring-teal-200',
                'bar' => 'bg-teal-500',
            ],
            MemberSaleStatus::Delivered->value => [
                'bg' => 'bg-indigo-50',
                'text' => 'text-indigo-800',
                'ring' => 'ring-indigo-200',
                'bar' => 'bg-indigo-500',
            ],
            MemberSaleStatus::Completed->value => [
                'bg' => 'bg-emerald-50',
                'text' => 'text-emerald-800',
                'ring' => 'ring-emerald-200',
                'bar' => 'bg-emerald-500',
            ],
        ];
    }
}
