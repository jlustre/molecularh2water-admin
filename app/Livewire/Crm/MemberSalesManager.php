<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\MemberSaleStatus;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\MemberSale;
use App\Models\User;
use App\Services\Crm\MemberSaleService;
use App\Support\Crm\MemberSaleScope;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class MemberSalesManager extends Component
{
    use UsesCrmLayout;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $sellerFilter = '';

    public string $datePreset = 'month_to_date';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 20;

    public bool $showForm = false;

    public ?int $editingSaleId = null;

    public ?int $user_id = null;

    public ?int $demo_consultant_id = null;

    public string $customer_name = '';

    public string $customer_phone = '';

    public string $customer_email = '';

    public string $status = 'application_started';

    public string $notes = '';

    /** @var list<array<string, mixed>> */
    public array $lineItems = [];

    public bool $canManage = false;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasPermission('sales.view') || $user?->hasPermission('sales.manage'), 403);

        $this->canManage = (bool) $user?->hasPermission('sales.manage');
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

    public function updatingSellerFilter(): void
    {
        $this->resetPage();
    }

    public function openForm(?int $saleId = null): void
    {
        abort_unless($this->canManage, 403);

        if ($saleId) {
            $sale = MemberSaleScope::sales(MemberSale::query())
                ->with('items')
                ->findOrFail($saleId);

            $this->editingSaleId = $sale->id;
            $this->user_id = $sale->user_id;
            $this->demo_consultant_id = $sale->demo_consultant_id;
            $this->customer_name = $sale->customer_name ?? '';
            $this->customer_phone = $sale->customer_phone ?? '';
            $this->customer_email = $sale->customer_email ?? '';
            $this->status = $sale->status->value;
            $this->notes = $sale->notes ?? '';
            $this->lineItems = $sale->items->map(fn ($item) => [
                'crm_product_id' => $item->crm_product_id,
                'item_kind' => $item->item_kind->value,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => (string) $item->unit_price,
            ])->values()->all();
        } else {
            $this->resetForm();
            $this->user_id = auth()->id();
            $this->lineItems = [$this->blankLineItem('product')];
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function addLineItem(string $kind = 'product'): void
    {
        abort_unless($this->canManage, 403);
        $this->lineItems[] = $this->blankLineItem($kind);
    }

    public function removeLineItem(int $index): void
    {
        abort_unless($this->canManage, 403);
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
    }

    public function updatedLineItems($value, ?string $key = null): void
    {
        if ($key === null || ! str_ends_with($key, '.crm_product_id')) {
            return;
        }

        $index = (int) explode('.', $key)[0];
        $productId = $this->lineItems[$index]['crm_product_id'] ?? null;

        if (! $productId) {
            return;
        }

        $product = CrmProduct::query()->active()->find($productId);

        if (! $product) {
            return;
        }

        $this->lineItems[$index]['name'] = $product->name;
        $this->lineItems[$index]['sku'] = $product->sku;
        $this->lineItems[$index]['item_kind'] = $product->kind->value;
        $this->lineItems[$index]['unit_price'] = (string) $product->unit_price;
    }

    public function save(MemberSaleService $sales): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate($this->rules());
        $items = $this->validateLineItems();

        $payload = [
            'user_id' => $data['user_id'],
            'demo_consultant_id' => $data['demo_consultant_id'] ?: null,
            'customer_name' => $data['customer_name'] ?: null,
            'customer_phone' => $data['customer_phone'] ?: null,
            'customer_email' => $data['customer_email'] ?: null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?: null,
        ];

        if ($this->editingSaleId) {
            $sale = MemberSaleScope::sales(MemberSale::query())->findOrFail($this->editingSaleId);
            $sales->update($sale, $payload, $items, auth()->user());
            session()->flash('status', 'Sale updated.');
        } else {
            $sales->create($payload, $items, auth()->user());
            session()->flash('status', 'Sale created.');
        }

        $this->closeForm();
    }

    public function deleteSale(int $saleId, MemberSaleService $sales): void
    {
        abort_unless($this->canManage, 403);

        $sale = MemberSaleScope::sales(MemberSale::query())->findOrFail($saleId);
        $sales->delete($sale, auth()->user());
        session()->flash('status', 'Sale deleted.');
    }

    public function render()
    {
        [$rangeStart, $rangeEnd] = $this->dateRange();
        $perPage = max(5, min(100, (int) $this->perPage));

        $sales = MemberSaleScope::sales(MemberSale::query())
            ->with(['consultant', 'demoConsultant', 'items'])
            ->when($rangeStart, fn ($q) => $q->where('created_at', '>=', $rangeStart))
            ->when($rangeEnd, fn ($q) => $q->where('created_at', '<=', $rangeEnd))
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('customer_name', 'like', "%{$this->search}%")
                        ->orWhere('customer_email', 'like', "%{$this->search}%")
                        ->orWhere('customer_phone', 'like', "%{$this->search}%")
                        ->orWhereHas('consultant', fn ($seller) => $seller->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('demoConsultant', fn ($seller) => $seller->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->sellerFilter, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('user_id', $this->sellerFilter)
                        ->orWhere('demo_consultant_id', $this->sellerFilter);
                });
            })
            ->latest()
            ->paginate($perPage);

        return view('livewire.crm.member-sales-manager', [
            'sales' => $sales,
            'statuses' => MemberSaleStatus::cases(),
            'consultants' => $this->canManage
                ? User::query()->orderBy('name')->get(['id', 'name', 'email'])
                : collect(),
            'products' => CrmProduct::query()->active()->products()->orderBy('sort_order')->get(['id', 'name', 'sku', 'unit_price']),
            'gifts' => CrmProduct::query()->active()->gifts()->orderBy('sort_order')->get(['id', 'name', 'sku', 'unit_price']),
            'datePresets' => $this->datePresetOptions(),
            'rangeLabel' => $this->rangeLabel($rangeStart, $rangeEnd),
        ])->layout($this->crmLayout());
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
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'demo_consultant_id' => ['nullable', 'exists:users,id', 'different:user_id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', Rule::in(array_column(MemberSaleStatus::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function validateLineItems(): array
    {
        $this->validate([
            'lineItems' => ['required', 'array', 'min:1'],
            'lineItems.*.item_kind' => ['required', Rule::in(['product', 'gift'])],
            'lineItems.*.name' => ['required', 'string', 'max:255'],
            'lineItems.*.quantity' => ['required', 'integer', 'min:1'],
            'lineItems.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lineItems.*.crm_product_id' => ['nullable', 'exists:crm_products,id'],
        ]);

        return $this->lineItems;
    }

    /**
     * @return array<string, mixed>
     */
    private function blankLineItem(string $kind): array
    {
        return [
            'crm_product_id' => null,
            'item_kind' => $kind,
            'name' => '',
            'sku' => '',
            'quantity' => 1,
            'unit_price' => '0.00',
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingSaleId',
            'user_id',
            'demo_consultant_id',
            'customer_name',
            'customer_phone',
            'customer_email',
            'notes',
            'lineItems',
        ]);
        $this->status = MemberSaleStatus::ApplicationStarted->value;
    }
}
