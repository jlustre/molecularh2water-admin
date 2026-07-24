<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Sales Management</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Consultant Sales</h1>
            <p class="mt-1 text-sm text-slate-500">
                @if ($canManage)
                    Enter and manage sales for any consultant, including dual credit when another consultant runs the demo.
                @else
                    Read-only view of sales credited to you (as consultant or demo consultant).
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date range</label>
                <select class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" wire:model.live="datePreset">
                    @foreach ($datePresets as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                <input class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" type="date" wire:model.live="dateFrom">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                <input class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" type="date" wire:model.live="dateTo">
            </div>
            @if ($canManage)
                <button class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm" type="button" wire:click="openForm">
                    Add Sale
                </button>
            @endif
        </div>
    </div>

    <p class="mb-4 text-sm text-slate-500">Showing <span class="font-semibold text-slate-700">{{ $rangeLabel }}</span></p>

    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
        <input class="rounded-xl border-slate-200 shadow-sm" placeholder="Search customer or consultant..." type="search" wire:model.live.debounce.300ms="search" />
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="statusFilter">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
        @if ($canManage)
            <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="sellerFilter">
                <option value="">All consultants</option>
                @foreach ($consultants as $consultant)
                    <option value="{{ $consultant->id }}">{{ $consultant->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Consultant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Demo consultant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Products sold</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Updated</th>
                    @if ($canManage)
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($sales as $sale)
                    <tr wire:key="sale-{{ $sale->id }}">
                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $sale->consultant?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $sale->demoConsultant?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            <p class="font-medium">{{ $sale->displayCustomerName() }}</p>
                            @if ($sale->customer_phone)
                                <p class="text-xs text-slate-500">{{ $sale->customer_phone }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            @if ($sale->items->isEmpty())
                                <span class="text-slate-400">—</span>
                            @else
                                <ul class="space-y-0.5">
                                    @foreach ($sale->items as $item)
                                        <li>
                                            <span class="font-medium text-slate-900">{{ $item->quantity }}×</span>
                                            {{ $item->name }}
                                            @if ($item->item_kind?->value === 'gift')
                                                <span class="text-xs text-slate-400">(gift)</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $sale->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">${{ number_format((float) $sale->total, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $sale->updated_at?->format('M j, Y') }}</td>
                        @if ($canManage)
                            <td class="px-4 py-3 text-right text-sm">
                                <button class="font-semibold text-teal-700 hover:text-teal-900" type="button" wire:click="openForm({{ $sale->id }})">Edit</button>
                                <button class="ml-3 font-semibold text-rose-600 hover:text-rose-800" type="button" wire:click="deleteSale({{ $sale->id }})" wire:confirm="Delete this sale?">Delete</button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-10 text-center text-sm text-slate-500" colspan="{{ $canManage ? 8 : 7 }}">No sales found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Per page</span>
            <select class="rounded-lg border-slate-200 py-1 text-sm shadow-sm" wire:model.live="perPage">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </label>
        <div>{{ $sales->links() }}</div>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeForm"></div>
            <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-black text-slate-950">{{ $editingSaleId ? 'Edit Sale' : 'Add Sale' }}</h3>
                </div>
                <form class="space-y-5 px-5 py-5" wire:submit="save">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Consultant</label>
                            <select class="block w-full rounded-xl border-slate-200 text-sm shadow-sm" wire:model="user_id" required>
                                @foreach ($consultants as $consultant)
                                    <option value="{{ $consultant->id }}">{{ $consultant->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Primary credit — often the learning consultant.</p>
                            @error('user_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Demo consultant <span class="font-normal text-slate-400">(optional)</span></label>
                            <select class="block w-full rounded-xl border-slate-200 text-sm shadow-sm" wire:model="demo_consultant_id">
                                <option value="">Same as consultant / none</option>
                                @foreach ($consultants as $consultant)
                                    <option value="{{ $consultant->id }}">{{ $consultant->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Who ran the demo when assisting a learning consultant.</p>
                            @error('demo_consultant_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Status</label>
                            <select class="block w-full rounded-xl border-slate-200 text-sm shadow-sm" wire:model="status" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Customer name</label>
                            <input class="block w-full rounded-xl border-slate-200 text-sm shadow-sm" type="text" wire:model="customer_name">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Customer phone</label>
                            <input class="block w-full rounded-xl border-slate-200 text-sm shadow-sm" type="text" wire:model="customer_phone">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Customer email</label>
                            <input class="block w-full rounded-xl border-slate-200 text-sm shadow-sm" type="email" wire:model="customer_email">
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-sm font-bold text-slate-900">Products & Gifts</h4>
                            <div class="flex gap-2">
                                <button class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700" type="button" wire:click="addLineItem('product')">+ Product</button>
                                <button class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700" type="button" wire:click="addLineItem('gift')">+ Gift</button>
                            </div>
                        </div>
                        <div class="space-y-3">
                            @foreach ($lineItems as $index => $item)
                                <div class="rounded-xl border border-slate-200 p-3" wire:key="line-{{ $index }}">
                                    <div class="grid gap-3 md:grid-cols-6">
                                        <div class="md:col-span-2">
                                            <label class="mb-1 block text-xs font-semibold text-slate-600">Catalog item</label>
                                            <select class="block w-full rounded-lg border-slate-200 text-sm" wire:model.live="lineItems.{{ $index }}.crm_product_id">
                                                <option value="">Custom line</option>
                                                @foreach (($item['item_kind'] ?? 'product') === 'gift' ? $gifts : $products as $catalogItem)
                                                    <option value="{{ $catalogItem->id }}">{{ $catalogItem->name }} (${{ number_format((float) $catalogItem->unit_price, 2) }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="mb-1 block text-xs font-semibold text-slate-600">Name</label>
                                            <input class="block w-full rounded-lg border-slate-200 text-sm" type="text" wire:model="lineItems.{{ $index }}.name" required>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-slate-600">Qty</label>
                                            <input class="block w-full rounded-lg border-slate-200 text-sm" type="number" min="1" wire:model="lineItems.{{ $index }}.quantity" required>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-slate-600">Price</label>
                                            <input class="block w-full rounded-lg border-slate-200 text-sm" type="number" min="0" step="0.01" wire:model="lineItems.{{ $index }}.unit_price" required>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ ($item['item_kind'] ?? 'product') === 'gift' ? 'Gift' : 'Product' }}</span>
                                        @if (count($lineItems) > 1)
                                            <button class="text-xs font-semibold text-rose-600" type="button" wire:click="removeLineItem({{ $index }})">Remove</button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
                        <textarea class="block w-full rounded-xl border-slate-200 text-sm shadow-sm" rows="3" wire:model="notes"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" wire:click="closeForm">Cancel</button>
                        <x-primary-button type="submit">Save Sale</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
