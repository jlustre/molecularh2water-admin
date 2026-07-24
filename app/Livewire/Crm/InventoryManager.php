<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\CrmProductKind;
use App\Enums\Crm\StockMovementType;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\StockMovement;
use App\Services\Crm\InventoryService;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryManager extends Component
{
    use UsesCrmLayout;
    use WithPagination;

    public string $search = '';

    public string $kindFilter = '';

    public string $stockFilter = '';

    public string $movementTypeFilter = '';

    public string $activeTab = 'stock';

    public bool $canManage = false;

    public bool $showReceiveModal = false;

    public bool $showAdjustModal = false;

    public bool $showWriteOffModal = false;

    public bool $showHistoryModal = false;

    public ?int $selectedProductId = null;

    public int $quantity = 1;

    public string $reason = '';

    public string $notes = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasPermission('products.view') || $user?->hasPermission('products.manage'), 403);
        $this->canManage = (bool) $user?->hasPermission('products.manage');
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['stock', 'movements', 'alerts'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openReceive(?int $productId = null): void
    {
        abort_unless($this->canManage, 403);
        $this->resetActionForm();
        $this->selectedProductId = $productId;
        $this->quantity = 1;
        $this->reason = 'Stock received';
        $this->showReceiveModal = true;
    }

    public function openAdjust(?int $productId = null): void
    {
        abort_unless($this->canManage, 403);
        $this->resetActionForm();
        $this->selectedProductId = $productId;
        $this->quantity = 1;
        $this->reason = 'Manual adjustment';
        $this->showAdjustModal = true;
    }

    public function openWriteOff(?int $productId = null): void
    {
        abort_unless($this->canManage, 403);
        $this->resetActionForm();
        $this->selectedProductId = $productId;
        $this->quantity = 1;
        $this->reason = 'Damaged / lost';
        $this->showWriteOffModal = true;
    }

    public function openHistory(int $productId): void
    {
        $this->selectedProductId = $productId;
        $this->showHistoryModal = true;
    }

    public function closeModals(): void
    {
        $this->showReceiveModal = false;
        $this->showAdjustModal = false;
        $this->showWriteOffModal = false;
        $this->showHistoryModal = false;
        $this->resetActionForm();
    }

    public function saveReceive(InventoryService $inventory): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate([
            'selectedProductId' => ['required', 'exists:crm_products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = CrmProduct::query()->findOrFail($data['selectedProductId']);
        $inventory->receive($product, (int) $data['quantity'], auth()->user(), $data['reason'] ?: null, $data['notes'] ?: null);

        session()->flash('status', "Received {$data['quantity']} unit(s) of {$product->name}.");
        $this->closeModals();
    }

    public function saveAdjust(InventoryService $inventory): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate([
            'selectedProductId' => ['required', 'exists:crm_products,id'],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = CrmProduct::query()->findOrFail($data['selectedProductId']);
        $inventory->adjust($product, (int) $data['quantity'], auth()->user(), $data['reason'] ?: null, $data['notes'] ?: null);

        session()->flash('status', "Adjusted stock for {$product->name}.");
        $this->closeModals();
    }

    public function saveWriteOff(InventoryService $inventory): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate([
            'selectedProductId' => ['required', 'exists:crm_products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = CrmProduct::query()->findOrFail($data['selectedProductId']);
        $inventory->writeOff($product, (int) $data['quantity'], auth()->user(), $data['reason'] ?: null, $data['notes'] ?: null);

        session()->flash('status', "Wrote off {$data['quantity']} unit(s) of {$product->name}.");
        $this->closeModals();
    }

    public function render()
    {
        $productsQuery = CrmProduct::query()
            ->with('productCategory')
            ->when($this->search, fn ($q) => $q->where(function ($inner) {
                $inner->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%");
            }))
            ->when($this->kindFilter, fn ($q) => $q->where('kind', $this->kindFilter))
            ->when($this->stockFilter === 'low', fn ($q) => $q->lowStock())
            ->when($this->stockFilter === 'out', fn ($q) => $q->where('inventory_quantity', '<=', 0))
            ->when($this->stockFilter === 'reserved', fn ($q) => $q->where('reserved_quantity', '>', 0))
            ->orderBy('name');

        $summary = [
            'skus' => CrmProduct::query()->where('is_active', true)->count(),
            'on_hand' => (int) CrmProduct::query()->sum('inventory_quantity'),
            'reserved' => (int) CrmProduct::query()->sum('reserved_quantity'),
            'low_stock' => CrmProduct::query()->active()->lowStock()->count(),
            'out_of_stock' => CrmProduct::query()->where('inventory_quantity', '<=', 0)->count(),
        ];

        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->when($this->movementTypeFilter, fn ($q) => $q->where('type', $this->movementTypeFilter))
            ->when($this->search && $this->activeTab === 'movements', function ($q) {
                $q->whereHas('product', fn ($product) => $product
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%"));
            })
            ->latest()
            ->paginate(20, pageName: 'movementsPage');

        $selectedProduct = $this->selectedProductId
            ? CrmProduct::query()->with(['stockMovements' => fn ($q) => $q->latest()->limit(25)])->find($this->selectedProductId)
            : null;

        return view('livewire.crm.inventory-manager', [
            'products' => $productsQuery->paginate(20),
            'alerts' => CrmProduct::query()->active()->lowStock()->orderBy('name')->limit(50)->get(),
            'movements' => $movements,
            'summary' => $summary,
            'selectedProduct' => $selectedProduct,
            'catalog' => CrmProduct::query()->active()->orderBy('name')->get(['id', 'name', 'sku', 'inventory_quantity', 'kind']),
            'kinds' => CrmProductKind::cases(),
            'movementTypes' => StockMovementType::cases(),
        ])->layout($this->crmLayout());
    }

    private function resetActionForm(): void
    {
        $this->reset(['selectedProductId', 'quantity', 'reason', 'notes']);
        $this->quantity = 1;
    }
}
