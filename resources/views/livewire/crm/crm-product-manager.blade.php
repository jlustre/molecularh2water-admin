<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Sales Management</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Products, Gifts & Inventory</h1>
            <p class="mt-1 text-sm text-slate-500">
                @if ($canManage)
                    Manage catalog items, categories, and stock levels.
                @else
                    Read-only catalog view.
                @endif
            </p>
        </div>
        @if ($canManage)
            <div class="flex gap-2">
                @if ($activeTab === 'categories')
                    <button class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm" type="button" wire:click="openCategoryForm">Add Category</button>
                @else
                    <button class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm" type="button" wire:click="openProductForm">Add {{ $activeTab === 'gifts' ? 'Gift' : 'Product' }}</button>
                @endif
            </div>
        @endif
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['products' => 'Products', 'gifts' => 'Gifts', 'categories' => 'Categories'] as $tab => $label)
            <button
                @class([
                    'rounded-full px-4 py-2 text-sm font-semibold shadow-sm',
                    'bg-teal-600 text-white' => $activeTab === $tab,
                    'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' => $activeTab !== $tab,
                ])
                type="button"
                wire:click="setTab('{{ $tab }}')"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="mb-4">
        <input class="w-full max-w-md rounded-xl border-slate-200 shadow-sm" placeholder="Search..." type="search" wire:model.live.debounce.300ms="search" />
    </div>

    @if ($activeTab === 'categories')
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kind</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        @if ($canManage)
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr wire:key="cat-{{ $category->id }}">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $category->name }}</td>
                            <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ $category->kind->label() }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $category->products_count }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
                            @if ($canManage)
                                <td class="px-4 py-3 text-right text-sm">
                                    <button class="font-semibold text-teal-700" type="button" wire:click="openCategoryForm({{ $category->id }})">Edit</button>
                                    <button class="ml-3 font-semibold text-rose-600" type="button" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Delete this category?">Delete</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td class="px-4 py-10 text-center text-sm text-slate-500" colspan="{{ $canManage ? 5 : 4 }}">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $categories->links() }}</div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">On hand</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Available</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        @if ($canManage)
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td class="px-4 py-3 text-sm font-mono text-slate-700">{{ $item->sku }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $item->categoryLabel() }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900">${{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $item->inventory_quantity }}@if ($item->reserved_quantity) <span class="text-xs text-amber-700">({{ $item->reserved_quantity }} reserved)</span>@endif</td>
                            <td class="px-4 py-3 text-sm font-semibold {{ $item->isLowStock() ? 'text-rose-700' : 'text-emerald-700' }}">
                                {{ $item->availableQuantity() }}
                                @if ($item->isLowStock())
                                    <span class="ml-1 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-700">Low</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $item->is_active ? 'Active' : 'Inactive' }}</td>
                            @if ($canManage)
                                <td class="px-4 py-3 text-right text-sm">
                                    <button class="font-semibold text-teal-700" type="button" wire:click="openProductForm({{ $item->id }})">Edit</button>
                                    <button class="ml-3 font-semibold text-rose-600" type="button" wire:click="deleteProduct({{ $item->id }})" wire:confirm="Delete or deactivate this item?">Delete</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td class="px-4 py-10 text-center text-sm text-slate-500" colspan="{{ $canManage ? 8 : 7 }}">No items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    @endif

    @if ($showProductForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeProductForm"></div>
            <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-black text-slate-950">{{ $editingProductId ? 'Edit Item' : 'Add Item' }}</h3>
                </div>
                <form class="space-y-4 px-5 py-5" wire:submit="saveProduct">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">SKU</label>
                            <input class="block w-full rounded-xl border-slate-200 text-sm" type="text" wire:model="sku" required>
                            @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Kind</label>
                            <select class="block w-full rounded-xl border-slate-200 text-sm" wire:model="kind">
                                @foreach ($kinds as $kindCase)
                                    <option value="{{ $kindCase->value }}">{{ $kindCase->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Name</label>
                        <input class="block w-full rounded-xl border-slate-200 text-sm" type="text" wire:model="name" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Category</label>
                        <select class="block w-full rounded-xl border-slate-200 text-sm" wire:model="crm_product_category_id">
                            <option value="">None</option>
                            @foreach ($categoryOptions as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Unit price</label>
                            <input class="block w-full rounded-xl border-slate-200 text-sm" type="number" min="0" step="0.01" wire:model="unit_price" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Inventory qty</label>
                            <input class="block w-full rounded-xl border-slate-200 text-sm" type="number" min="0" wire:model="inventory_quantity" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Reorder level (low-stock alert)</label>
                            <input class="block w-full rounded-xl border-slate-200 text-sm" type="number" min="0" wire:model="reorder_level" required>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea class="block w-full rounded-xl border-slate-200 text-sm" rows="2" wire:model="description"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="is_active"> Active
                    </label>
                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" wire:click="closeProductForm">Cancel</button>
                        <x-primary-button type="submit">Save</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showCategoryForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeCategoryForm"></div>
            <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-black text-slate-950">{{ $editingCategoryId ? 'Edit Category' : 'Add Category' }}</h3>
                </div>
                <form class="space-y-4 px-5 py-5" wire:submit="saveCategory">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Name</label>
                        <input class="block w-full rounded-xl border-slate-200 text-sm" type="text" wire:model="category_name" required>
                        @error('category_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Slug</label>
                            <input class="block w-full rounded-xl border-slate-200 text-sm" type="text" wire:model="category_slug">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Kind</label>
                            <select class="block w-full rounded-xl border-slate-200 text-sm" wire:model="category_kind">
                                @foreach ($kinds as $kindCase)
                                    <option value="{{ $kindCase->value }}">{{ $kindCase->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea class="block w-full rounded-xl border-slate-200 text-sm" rows="2" wire:model="category_description"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="category_is_active"> Active
                    </label>
                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" wire:click="closeCategoryForm">Cancel</button>
                        <x-primary-button type="submit">Save</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
