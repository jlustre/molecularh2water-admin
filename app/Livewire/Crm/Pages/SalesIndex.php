<?php

namespace App\Livewire\Crm\Pages;

use App\Enums\Crm\OrderStatus;
use App\Enums\Crm\PaymentStatus;
use App\Enums\Crm\QuotationStatus;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\Order;
use App\Models\Crm\Quotation;
use App\Models\User;
use App\Services\Crm\OrderService;
use App\Services\Crm\QuotationService;
use App\Support\Crm\CrmRoutes;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;
use stdClass;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesIndex extends Component
{
    use UsesCrmLayout;
    use WithPagination;

    public string $datePreset = 'month_to_date';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 20;

    public string $search = '';

    public string $recordType = '';

    public string $statusFilter = '';

    /** @var array<string, int|float> */
    public array $summary = [];

    public bool $canManage = false;

    public bool $showModal = false;

    public bool $modalEditable = false;

    public string $modalType = '';

    public ?int $modalId = null;

    public string $modalNumber = '';

    public string $modalStatus = '';

    public ?int $modal_user_id = null;

    public ?int $modal_demo_consultant_id = null;

    public string $modal_notes = '';

    /** @var list<array<string, mixed>> */
    public array $modalItems = [];

    /** @var list<array<string, mixed>> */
    public array $modalViewItems = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.view'), 403);

        $this->canManage = (bool) auth()->user()?->hasPermission('sales.manage');
        $this->syncCustomDatesFromPreset();
        $this->loadSummary();
    }

    public function updatedDatePreset(): void
    {
        $this->syncCustomDatesFromPreset();
        $this->resetPage('salesPage');
        $this->loadSummary();
    }

    public function updatedDateFrom(): void
    {
        $this->datePreset = 'custom';
        $this->resetPage('salesPage');
        $this->loadSummary();
    }

    public function updatedDateTo(): void
    {
        $this->datePreset = 'custom';
        $this->resetPage('salesPage');
        $this->loadSummary();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage('salesPage');
    }

    public function updatingSearch(): void
    {
        $this->resetPage('salesPage');
    }

    public function updatingRecordType(): void
    {
        $this->resetPage('salesPage');
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage('salesPage');
    }

    public function contactUrl(?Model $contact): ?string
    {
        if (! $contact) {
            return null;
        }

        return match ($contact->getMorphClass()) {
            'lead' => CrmRoutes::url('leads.show', ['lead' => $contact]),
            'prospect' => CrmRoutes::url('prospects.show', ['lead' => $contact]),
            'customer' => CrmRoutes::url('customers.show', ['lead' => $contact]),
            'recruit' => CrmRoutes::url('recruits.show', ['lead' => $contact]),
            default => null,
        };
    }

    public function contactLabel(?Model $contact): string
    {
        if (! $contact) {
            return 'Unknown contact';
        }

        if (method_exists($contact, 'fullName')) {
            return $contact->fullName() ?: 'Unnamed contact';
        }

        return 'Unknown contact';
    }

    public function openView(string $type, int $id): void
    {
        $this->loadModalRecord($type, $id, editable: false);
    }

    public function openEdit(string $type, int $id): void
    {
        abort_unless($this->canManage, 403);
        $this->loadModalRecord($type, $id, editable: true);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->modalEditable = false;
        $this->modalType = '';
        $this->modalId = null;
        $this->modalNumber = '';
        $this->modalStatus = '';
        $this->modal_user_id = null;
        $this->modal_demo_consultant_id = null;
        $this->modal_notes = '';
        $this->modalItems = [];
        $this->modalViewItems = [];
    }

    public function addModalItem(): void
    {
        abort_unless($this->canManage && $this->modalEditable, 403);
        $this->modalItems[] = [
            'crm_product_id' => null,
            'description' => '',
            'quantity' => 1,
            'unit_price' => '0.00',
        ];
    }

    public function removeModalItem(int $index): void
    {
        abort_unless($this->canManage && $this->modalEditable, 403);
        unset($this->modalItems[$index]);
        $this->modalItems = array_values($this->modalItems);
    }

    public function updatedModalItems($value, ?string $key = null): void
    {
        if ($key === null || ! str_ends_with($key, '.crm_product_id')) {
            return;
        }

        $index = (int) explode('.', $key)[0];
        $productId = $this->modalItems[$index]['crm_product_id'] ?? null;

        if (! $productId) {
            return;
        }

        $product = CrmProduct::query()->find($productId);

        if (! $product) {
            return;
        }

        $this->modalItems[$index]['description'] = $product->name;
        $this->modalItems[$index]['unit_price'] = (string) $product->unit_price;
    }

    public function saveModal(OrderService $orders, QuotationService $quotations): void
    {
        abort_unless($this->canManage && $this->modalEditable && $this->modalId, 403);

        $data = $this->validate([
            'modal_user_id' => ['required', 'exists:users,id'],
            'modal_demo_consultant_id' => ['nullable', 'exists:users,id', 'different:modal_user_id'],
            'modalStatus' => ['required', 'string'],
            'modal_notes' => ['nullable', 'string', 'max:5000'],
            'modalItems' => ['required', 'array', 'min:1'],
            'modalItems.*.description' => ['required', 'string', 'max:255'],
            'modalItems.*.quantity' => ['required', 'integer', 'min:1'],
            'modalItems.*.unit_price' => ['required', 'numeric', 'min:0'],
            'modalItems.*.crm_product_id' => ['nullable', 'exists:crm_products,id'],
        ]);

        $payload = [
            'user_id' => $data['modal_user_id'],
            'demo_consultant_id' => $data['modal_demo_consultant_id'] ?: null,
            'notes' => $data['modal_notes'] ?: null,
            'status' => $data['modalStatus'],
        ];

        if ($this->modalType === 'order') {
            $order = $this->findAccessibleOrder($this->modalId);
            $orders->update($order, $payload, $this->modalItems, auth()->user());
            session()->flash('status', 'Order updated.');
        } else {
            $quotation = $this->findAccessibleQuotation($this->modalId);
            $quotations->update($quotation, $payload, $this->modalItems, auth()->user());
            session()->flash('status', 'Quotation updated.');
        }

        $this->closeModal();
    }

    public function exportCsv(): StreamedResponse
    {
        abort_unless(auth()->user()?->hasPermission('sales.view'), 403);

        $this->loadSummary();
        $user = auth()->user();
        $filename = sprintf('sales-%s-%s.csv', $this->datePreset, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($user) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['metric', 'value', 'date_preset', 'date_from', 'date_to']);
            foreach ($this->summary as $key => $value) {
                fputcsv($handle, [$key, $value, $this->datePreset, $this->dateFrom, $this->dateTo]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'type',
                'number',
                'contact',
                'status',
                'payment_status',
                'consultant',
                'demo_consultant',
                'total',
                'created_at',
            ]);

            $this->mergedRows($user)->each(function (stdClass $row) use ($handle) {
                fputcsv($handle, [
                    $row->type,
                    $row->number,
                    $this->contactLabel($row->contact),
                    $row->status_label,
                    $row->payment_status_label,
                    $row->consultant?->name,
                    $row->demo_consultant?->name,
                    $row->total,
                    $row->created_at?->toDateTimeString(),
                ]);
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render(): View
    {
        $user = auth()->user();
        $page = max(1, (int) $this->getPage('salesPage'));
        $perPage = max(5, min(100, (int) $this->perPage));
        $rows = $this->mergedRows($user);
        $total = $rows->count();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        $records = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'pageName' => 'salesPage',
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        [$rangeStart, $rangeEnd] = $this->dateRange();

        return view('livewire.crm.pages.sales-index', [
            'records' => $records,
            'orderStatuses' => OrderStatus::cases(),
            'quotationStatuses' => QuotationStatus::cases(),
            'consultants' => User::query()->orderBy('name')->get(['id', 'name']),
            'products' => CrmProduct::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'sku', 'unit_price']),
            'datePresets' => $this->datePresetOptions(),
            'rangeLabel' => $this->rangeLabel($rangeStart, $rangeEnd),
            'fromItem' => $total === 0 ? 0 : (($page - 1) * $perPage) + 1,
            'toItem' => min($page * $perPage, $total),
            'totalItems' => $total,
        ])->layout($this->crmLayout());
    }

    private function loadModalRecord(string $type, int $id, bool $editable): void
    {
        abort_unless(in_array($type, ['order', 'quotation'], true), 404);

        if ($type === 'order') {
            $record = $this->findAccessibleOrder($id)->load(['items', 'consultant', 'demoConsultant', 'contact']);
            $this->modalNumber = $record->order_number;
            $this->modalStatus = $record->status?->value ?? (string) $record->status;
            $this->modalItems = $record->items->map(fn ($item) => [
                'crm_product_id' => $item->crm_product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => (string) $item->unit_price,
            ])->values()->all();
        } else {
            $record = $this->findAccessibleQuotation($id)->load(['items', 'consultant', 'demoConsultant', 'contact']);
            $this->modalNumber = $record->quote_number;
            $this->modalStatus = $record->status?->value ?? (string) $record->status;
            $this->modalItems = $record->items->map(fn ($item) => [
                'crm_product_id' => $item->crm_product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => (string) $item->unit_price,
            ])->values()->all();
        }

        $this->modalType = $type;
        $this->modalId = $id;
        $this->modal_user_id = $record->user_id;
        $this->modal_demo_consultant_id = $record->demo_consultant_id;
        $this->modal_notes = $record->notes ?? '';
        $this->modalViewItems = $this->modalItems;
        $this->modalEditable = $editable;
        $this->showModal = true;
    }

    private function findAccessibleOrder(int $id): Order
    {
        return Order::query()
            ->forAccessibleContacts(auth()->user())
            ->findOrFail($id);
    }

    private function findAccessibleQuotation(int $id): Quotation
    {
        return Quotation::query()
            ->forAccessibleContacts(auth()->user())
            ->findOrFail($id);
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function mergedRows(?\App\Models\User $user): Collection
    {
        $includeOrders = $this->recordType === '' || $this->recordType === 'order';
        $includeQuotes = $this->recordType === '' || $this->recordType === 'quotation';

        $orders = collect();
        $quotations = collect();

        if ($includeOrders && Schema::hasTable('orders')) {
            $orders = $this->ordersQuery($user)
                ->with(['contact', 'consultant', 'demoConsultant'])
                ->latest()
                ->get()
                ->map(fn (Order $order) => $this->mapOrderRow($order));
        }

        if ($includeQuotes && Schema::hasTable('quotations')) {
            $quotations = $this->quotationsQuery($user)
                ->with(['contact', 'consultant', 'demoConsultant', 'order'])
                ->latest()
                ->get()
                ->map(fn (Quotation $quotation) => $this->mapQuotationRow($quotation));
        }

        return $orders
            ->concat($quotations)
            ->when($this->statusFilter !== '', function (Collection $rows) {
                return $rows->filter(function (stdClass $row) {
                    return $row->status_value === $this->statusFilter
                        || $row->payment_status_value === $this->statusFilter;
                })->values();
            })
            ->sortByDesc(fn (stdClass $row) => $row->created_at?->timestamp ?? 0)
            ->values();
    }

    private function mapOrderRow(Order $order): stdClass
    {
        $row = new stdClass;
        $row->key = 'order-'.$order->id;
        $row->id = $order->id;
        $row->type = 'order';
        $row->type_label = 'Order';
        $row->number = $order->order_number;
        $row->contact = $order->contact;
        $row->status_value = $order->status?->value ?? (string) $order->status;
        $row->status_label = $order->status?->label() ?? (string) $order->status;
        $row->payment_status_value = $order->payment_status?->value;
        $row->payment_status_label = $order->payment_status?->label();
        $row->consultant = $order->consultant;
        $row->demo_consultant = $order->demoConsultant;
        $row->total = $order->total;
        $row->created_at = $order->created_at;
        $row->meta = null;

        return $row;
    }

    private function mapQuotationRow(Quotation $quotation): stdClass
    {
        $row = new stdClass;
        $row->key = 'quotation-'.$quotation->id;
        $row->id = $quotation->id;
        $row->type = 'quotation';
        $row->type_label = 'Quote';
        $row->number = $quotation->quote_number;
        $row->contact = $quotation->contact;
        $row->status_value = $quotation->status?->value ?? (string) $quotation->status;
        $row->status_label = $quotation->status?->label() ?? (string) $quotation->status;
        $row->payment_status_value = null;
        $row->payment_status_label = null;
        $row->consultant = $quotation->consultant;
        $row->demo_consultant = $quotation->demoConsultant;
        $row->total = $quotation->total;
        $row->created_at = $quotation->created_at;
        $row->meta = $quotation->order?->order_number
            ? 'Order '.$quotation->order->order_number
            : ($quotation->valid_until ? 'Valid until '.$quotation->valid_until->format('M j, Y') : null);

        return $row;
    }

    private function ordersQuery(?\App\Models\User $user)
    {
        return Order::query()
            ->forAccessibleContacts($user)
            ->tap(fn ($query) => $this->applyDateFilter($query))
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($inner) use ($term) {
                    $inner->where('order_number', 'like', $term)
                        ->orWhereHas('consultant', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('demoConsultant', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHasMorph('contact', ['lead', 'prospect', 'customer', 'recruit'], function ($contactQuery) use ($term) {
                            $contactQuery->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            });
    }

    private function quotationsQuery(?\App\Models\User $user)
    {
        return Quotation::query()
            ->forAccessibleContacts($user)
            ->tap(fn ($query) => $this->applyDateFilter($query))
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($inner) use ($term) {
                    $inner->where('quote_number', 'like', $term)
                        ->orWhereHas('consultant', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('demoConsultant', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHasMorph('contact', ['lead', 'prospect', 'customer', 'recruit'], function ($contactQuery) use ($term) {
                            $contactQuery->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            });
    }

    private function applyDateFilter($query): void
    {
        [$start, $end] = $this->dateRange();

        $query
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('created_at', '<=', $end));
    }

    private function loadSummary(): void
    {
        $user = auth()->user();
        [$start, $end] = $this->dateRange();

        $ordersQuery = Schema::hasTable('orders')
            ? Order::query()->forAccessibleContacts($user)
            : null;

        $quotationsQuery = Schema::hasTable('quotations')
            ? Quotation::query()->forAccessibleContacts($user)
            : null;

        $ordersInRange = $ordersQuery
            ? (clone $ordersQuery)
                ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
                ->when($end, fn ($q) => $q->where('created_at', '<=', $end))
            : null;

        $quotesInRange = $quotationsQuery
            ? (clone $quotationsQuery)
                ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
                ->when($end, fn ($q) => $q->where('created_at', '<=', $end))
            : null;

        $revenue = $ordersQuery
            ? (float) (clone $ordersQuery)
                ->where('payment_status', PaymentStatus::Paid->value)
                ->when($start, fn ($q) => $q->where('paid_at', '>=', $start))
                ->when($end, fn ($q) => $q->where('paid_at', '<=', $end))
                ->sum('amount_paid')
            : 0;

        $openQuotations = $quotesInRange
            ? (clone $quotesInRange)
                ->whereIn('status', [
                    QuotationStatus::Draft->value,
                    QuotationStatus::Presented->value,
                    QuotationStatus::Viewed->value,
                ])
                ->count()
            : 0;

        $pendingPayments = $ordersInRange
            ? (clone $ordersInRange)
                ->whereIn('payment_status', [
                    PaymentStatus::Pending->value,
                    PaymentStatus::Partial->value,
                ])
                ->whereNotIn('status', [OrderStatus::Cancelled->value])
                ->count()
            : 0;

        $this->summary = [
            'orders' => $ordersInRange?->count() ?? 0,
            'quotations' => $quotesInRange?->count() ?? 0,
            'open_quotations' => $openQuotations,
            'pending_payments' => $pendingPayments,
            'revenue' => round($revenue, 2),
        ];
    }

    /**
     * @return array{0: ?\Illuminate\Support\Carbon, 1: ?\Illuminate\Support\Carbon}
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
                filled($this->dateFrom) ? \Illuminate\Support\Carbon::parse($this->dateFrom)->startOfDay() : null,
                filled($this->dateTo) ? \Illuminate\Support\Carbon::parse($this->dateTo)->endOfDay() : null,
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

    private function rangeLabel(?\Illuminate\Support\Carbon $start, ?\Illuminate\Support\Carbon $end): string
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
}
