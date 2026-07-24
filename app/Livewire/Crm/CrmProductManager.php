<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\CrmProductKind;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\CrmProductCategory;
use App\Services\Crm\CrmProductCategoryService;
use App\Services\Crm\CrmProductService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CrmProductManager extends Component
{
    use UsesCrmLayout;
    use WithPagination;

    public string $activeTab = 'products';

    public string $search = '';

    public string $kindFilter = '';

    public bool $showProductForm = false;

    public bool $showCategoryForm = false;

    public ?int $editingProductId = null;

    public ?int $editingCategoryId = null;

    public string $sku = '';

    public string $name = '';

    public string $kind = 'product';

    public ?int $crm_product_category_id = null;

    public string $description = '';

    public string $unit_price = '0.00';

    public int $inventory_quantity = 0;

    public int $reorder_level = 5;

    public bool $is_active = true;

    public int $sort_order = 0;

    public string $category_name = '';

    public string $category_slug = '';

    public string $category_kind = 'product';

    public string $category_description = '';

    public bool $category_is_active = true;

    public int $category_sort_order = 0;

    public bool $canManage = false;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasPermission('products.view') || $user?->hasPermission('products.manage'), 403);

        $this->canManage = (bool) $user?->hasPermission('products.manage');
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['products', 'gifts', 'categories'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
        $this->kindFilter = $tab === 'gifts' ? 'gift' : ($tab === 'products' ? 'product' : '');
    }

    public function openProductForm(?int $productId = null): void
    {
        abort_unless($this->canManage, 403);

        if ($productId) {
            $product = CrmProduct::query()->findOrFail($productId);
            $this->editingProductId = $product->id;
            $this->sku = $product->sku;
            $this->name = $product->name;
            $this->kind = $product->kind->value;
            $this->crm_product_category_id = $product->crm_product_category_id;
            $this->description = $product->description ?? '';
            $this->unit_price = (string) $product->unit_price;
            $this->inventory_quantity = (int) $product->inventory_quantity;
            $this->reorder_level = (int) ($product->reorder_level ?? 5);
            $this->is_active = (bool) $product->is_active;
            $this->sort_order = (int) $product->sort_order;
        } else {
            $this->resetProductForm();
            $this->kind = $this->activeTab === 'gifts' ? 'gift' : 'product';
        }

        $this->showProductForm = true;
    }

    public function closeProductForm(): void
    {
        $this->showProductForm = false;
        $this->resetProductForm();
    }

    public function saveProduct(CrmProductService $products): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate($this->productRules());

        if ($this->editingProductId) {
            $product = CrmProduct::query()->findOrFail($this->editingProductId);
            $products->update($product, $data, auth()->user());
            session()->flash('status', 'Item updated.');
        } else {
            $products->create($data, auth()->user());
            session()->flash('status', 'Item created.');
        }

        $this->closeProductForm();
    }

    public function deleteProduct(int $productId, CrmProductService $products): void
    {
        abort_unless($this->canManage, 403);

        $product = CrmProduct::query()->findOrFail($productId);
        $products->delete($product, auth()->user());
        session()->flash('status', $product->isReferenced() ? 'Item deactivated because it is referenced.' : 'Item deleted.');
    }

    public function openCategoryForm(?int $categoryId = null): void
    {
        abort_unless($this->canManage, 403);

        if ($categoryId) {
            $category = CrmProductCategory::query()->findOrFail($categoryId);
            $this->editingCategoryId = $category->id;
            $this->category_name = $category->name;
            $this->category_slug = $category->slug;
            $this->category_kind = $category->kind->value;
            $this->category_description = $category->description ?? '';
            $this->category_is_active = (bool) $category->is_active;
            $this->category_sort_order = (int) $category->sort_order;
        } else {
            $this->resetCategoryForm();
        }

        $this->showCategoryForm = true;
    }

    public function closeCategoryForm(): void
    {
        $this->showCategoryForm = false;
        $this->resetCategoryForm();
    }

    public function saveCategory(CrmProductCategoryService $categories): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate($this->categoryRules());

        $payload = [
            'name' => $data['category_name'],
            'slug' => $data['category_slug'] ?: null,
            'kind' => $data['category_kind'],
            'description' => $data['category_description'] ?: null,
            'is_active' => $data['category_is_active'],
            'sort_order' => $data['category_sort_order'],
        ];

        if ($this->editingCategoryId) {
            $category = CrmProductCategory::query()->findOrFail($this->editingCategoryId);
            $categories->update($category, $payload, auth()->user());
            session()->flash('status', 'Category updated.');
        } else {
            $categories->create($payload, auth()->user());
            session()->flash('status', 'Category created.');
        }

        $this->closeCategoryForm();
    }

    public function deleteCategory(int $categoryId, CrmProductCategoryService $categories): void
    {
        abort_unless($this->canManage, 403);

        $category = CrmProductCategory::query()->findOrFail($categoryId);
        $categories->delete($category, auth()->user());
        session()->flash('status', 'Category deleted.');
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
            ->orderBy('sort_order')
            ->orderBy('name');

        $categories = CrmProductCategory::query()
            ->withCount('products')
            ->when($this->search && $this->activeTab === 'categories', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20, pageName: 'categoriesPage');

        return view('livewire.crm.crm-product-manager', [
            'items' => in_array($this->activeTab, ['products', 'gifts'], true)
                ? $productsQuery->paginate(20)
                : collect(),
            'categories' => $categories,
            'categoryOptions' => CrmProductCategory::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'kinds' => CrmProductKind::cases(),
        ])->layout($this->crmLayout());
    }

    /**
     * @return array<string, mixed>
     */
    private function productRules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::unique('crm_products', 'sku')->ignore($this->editingProductId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(array_column(CrmProductKind::cases(), 'value'))],
            'crm_product_category_id' => ['nullable', 'exists:crm_product_categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'inventory_quantity' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryRules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255'],
            'category_slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('crm_product_categories', 'slug')->ignore($this->editingCategoryId),
            ],
            'category_kind' => ['required', Rule::in(array_column(CrmProductKind::cases(), 'value'))],
            'category_description' => ['nullable', 'string', 'max:5000'],
            'category_is_active' => ['boolean'],
            'category_sort_order' => ['integer', 'min:0'],
        ];
    }

    private function resetProductForm(): void
    {
        $this->reset([
            'editingProductId',
            'sku',
            'name',
            'crm_product_category_id',
            'description',
            'unit_price',
            'inventory_quantity',
            'reorder_level',
            'sort_order',
        ]);
        $this->kind = 'product';
        $this->is_active = true;
        $this->inventory_quantity = 0;
        $this->reorder_level = 5;
        $this->unit_price = '0.00';
    }

    private function resetCategoryForm(): void
    {
        $this->reset([
            'editingCategoryId',
            'category_name',
            'category_slug',
            'category_description',
            'category_sort_order',
        ]);
        $this->category_kind = 'product';
        $this->category_is_active = true;
    }
}
