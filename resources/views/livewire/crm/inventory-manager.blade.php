<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Sales Management</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Inventory</h1>
            <p class="mt-1 text-sm text-slate-500">
                Track on-hand stock, reservations, receive/adjust movements, and low-stock alerts.
            </p>
        </div>
        @if ($canManage)
            <div class="flex flex-wrap gap-2">
                <button class="rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm" type="button" wire:click="openReceive">Receive Stock</button>
                <button class="rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm" type="button" wire:click="openAdjust">Adjust</button>
                <button class="rounded-full border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm" type="button" wire:click="openWriteOff">Write Off</button>
            </div>
        @endif
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active SKUs</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['skus'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">On hand</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($summary['on_hand']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reserved</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($summary['reserved']) }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Low stock</p>
            <p class="mt-1 text-2xl font-bold text-amber-900">{{ $summary['low_stock'] }}</p>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Out of stock</p>
            <p class="mt-1 text-2xl font-bold text-rose-900">{{ $summary['out_of_stock'] }}</p>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['stock' => 'Stock levels', 'movements' => 'Movement history', 'alerts' => 'Low-stock alerts'] as $tab => $label)
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

    @if ($activeTab !== 'alerts')
        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
            <input class="rounded-xl border-slate-200 shadow-sm" placeholder="Search SKU or name..." type="search" wire:model.live.debounce.300ms="search" />
            @if ($activeTab === 'stock')
                <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="kindFilter">
                    <option value="">All kinds</option>
                    @foreach ($kinds as $kind)
                        <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                    @endforeach
                </select>
                <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="stockFilter">
                    <option value="">All stock</option>
                    <option value="low">Low stock</option>
                    <option value="out">Out of stock</option>
                    <option value="reserved">Has reserved</option>
                </select>
            @else
                <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="movementTypeFilter">
                    <option value="">All movement types</option>
                    @foreach ($movementTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    @endif

    @if ($activeTab === 'stock')
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">On hand</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reserved</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Available</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reorder at</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr wire:key="inv-{{ $product->id }}">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ $product->sku }} · {{ $product->kind->label() }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $product->inventory_quantity }}</td>
                            <td class="px-4 py-3 text-sm text-amber-700">{{ $product->reserved_quantity }}</td>
                            <td class="px-4 py-3 text-sm font-semibold {{ $product->isLowStock() ? 'text-rose-700' : 'text-emerald-700' }}">
                                {{ $product->availableQuantity() }}
                                @if ($product->isLowStock())
                                    <span class="ml-1 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-700">Low</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $product->reorder_level }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <button class="font-semibold text-slate-600 hover:text-slate-900" type="button" wire:click="openHistory({{ $product->id }})">History</button>
                                @if ($canManage)
                                    <button class="ml-3 font-semibold text-teal-700" type="button" wire:click="openReceive({{ $product->id }})">Receive</button>
                                    <button class="ml-3 font-semibold text-slate-700" type="button" wire:click="openAdjust({{ $product->id }})">Adjust</button>
                                    <button class="ml-3 font-semibold text-rose-600" type="button" wire:click="openWriteOff({{ $product->id }})">Write off</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-10 text-center text-sm text-slate-500" colspan="6">No inventory items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    @elseif ($activeTab === 'movements')
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">When</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Delta</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movements as $movement)
                        <tr wire:key="mv-{{ $movement->id }}">
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $movement->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $movement->product?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $movement->type->label() }}</td>
                            <td @class(['px-4 py-3 text-sm font-semibold', 'text-emerald-700' => $movement->quantity_delta > 0, 'text-rose-700' => $movement->quantity_delta < 0])>
                                {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $movement->quantity_before }} → {{ $movement->quantity_after }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $movement->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-10 text-center text-sm text-slate-500" colspan="6">No stock movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $movements->links() }}</div>
    @else
        <div class="overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-amber-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-amber-800">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-amber-800">Available</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-amber-800">Reorder level</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-amber-800">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($alerts as $product)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ $product->sku }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-rose-700">{{ $product->availableQuantity() }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $product->reorder_level }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($canManage)
                                    <button class="rounded-full bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white" type="button" wire:click="openReceive({{ $product->id }})">Receive stock</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-10 text-center text-sm text-slate-500" colspan="4">No low-stock items. Inventory looks healthy.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($showReceiveModal || $showAdjustModal || $showWriteOffModal)
        @php
            $title = $showReceiveModal ? 'Receive Stock' : ($showAdjustModal ? 'Adjust Stock' : 'Write Off Stock');
            $submit = $showReceiveModal ? 'saveReceive' : ($showAdjustModal ? 'saveAdjust' : 'saveWriteOff');
            $hint = $showAdjustModal ? 'Use a positive number to increase, negative to decrease.' : ($showWriteOffModal ? 'Removes units from on-hand stock.' : 'Adds units to on-hand stock.');
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeModals"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-black text-slate-950">{{ $title }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
                </div>
                <form class="space-y-4 px-5 py-5" wire:submit="{{ $submit }}">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Product / Gift</label>
                        <select class="block w-full rounded-xl border-slate-200 text-sm" wire:model="selectedProductId" required>
                            <option value="">Select item…</option>
                            @foreach ($catalog as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->sku }}) — on hand {{ $item->inventory_quantity }}</option>
                            @endforeach
                        </select>
                        @error('selectedProductId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Quantity</label>
                        <input class="block w-full rounded-xl border-slate-200 text-sm" type="number" wire:model="quantity" required {{ $showAdjustModal ? '' : 'min=1' }}>
                        @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Reason</label>
                        <input class="block w-full rounded-xl border-slate-200 text-sm" type="text" wire:model="reason">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
                        <textarea class="block w-full rounded-xl border-slate-200 text-sm" rows="2" wire:model="notes"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" wire:click="closeModals">Cancel</button>
                        <x-primary-button type="submit">Save</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showHistoryModal && $selectedProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeModals"></div>
            <div class="relative max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-black text-slate-950">{{ $selectedProduct->name }}</h3>
                    <p class="text-xs text-slate-500">Recent stock movements</p>
                </div>
                <div class="divide-y divide-slate-100 px-5 py-2">
                    @forelse ($selectedProduct->stockMovements as $movement)
                        <div class="py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $movement->type->label() }}</p>
                                    <p class="text-xs text-slate-500">{{ $movement->reason }} · {{ $movement->created_at?->format('M j, Y g:i A') }}</p>
                                </div>
                                <p @class(['text-sm font-bold', 'text-emerald-700' => $movement->quantity_delta > 0, 'text-rose-700' => $movement->quantity_delta < 0])>
                                    {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No movements recorded for this item.</p>
                    @endforelse
                </div>
                <div class="border-t border-slate-100 px-5 py-4 text-right">
                    <button class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" wire:click="closeModals">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
