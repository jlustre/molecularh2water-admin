<div class="p-4 sm:p-6 lg:p-8">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">CRM</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Orders & Quotes</h1>
            <p class="mt-1 text-sm text-slate-500">Orders and quotations in one list, with consultant credit.</p>
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
            <button
                class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                type="button"
                wire:click="exportCsv"
            >
                Export CSV
            </button>
        </div>
    </div>

    <p class="mb-4 text-sm text-slate-500">Showing <span class="font-semibold text-slate-700">{{ $rangeLabel }}</span></p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Orders', 'value' => number_format($summary['orders'] ?? 0), 'hint' => 'Created in selected range'],
            ['label' => 'Quotations', 'value' => number_format($summary['quotations'] ?? 0), 'hint' => 'Created in selected range'],
            ['label' => 'Open Quotes', 'value' => number_format($summary['open_quotations'] ?? 0), 'hint' => 'Draft / presented / viewed in range'],
            ['label' => 'Pending Payments', 'value' => number_format($summary['pending_payments'] ?? 0), 'hint' => 'Awaiting payment in range'],
            ['label' => 'Revenue', 'value' => '$'.number_format($summary['revenue'] ?? 0, 2), 'hint' => 'Paid in selected range'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-3">
        <input
            class="rounded-xl border-slate-200 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Search number, customer, consultant..."
            type="search"
            wire:model.live.debounce.300ms="search"
        />
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="recordType">
            <option value="">Orders & quotes</option>
            <option value="order">Orders only</option>
            <option value="quotation">Quotes only</option>
        </select>
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="statusFilter">
            <option value="">All statuses</option>
            <optgroup label="Order status">
                @foreach ($orderStatuses as $status)
                    <option value="{{ $status->value }}">Order · {{ $status->label() }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Quote status">
                @foreach ($quotationStatuses as $status)
                    <option value="{{ $status->value }}">Quote · {{ $status->label() }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Payment">
                <option value="pending">Payment · Pending</option>
                <option value="partial">Payment · Partial</option>
                <option value="paid">Payment · Paid</option>
                <option value="refunded">Payment · Refunded</option>
            </optgroup>
        </select>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-lg font-bold text-slate-900">Sales pipeline</h2>
            <p class="mt-0.5 text-sm text-slate-500">Orders and quotations identified by status, with consultant credit.</p>
        </div>
        @if (session('status'))
            <div class="border-b border-teal-100 bg-teal-50 px-5 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Record</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Consultant</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Demo consultant</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $row)
                        @php
                            $contactUrl = $this->contactUrl($row->contact);
                        @endphp
                        <tr class="hover:bg-teal-50/40" wire:key="{{ $row->key }}">
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                        'bg-emerald-50 text-emerald-700' => $row->type === 'order',
                                        'bg-violet-50 text-violet-700' => $row->type === 'quotation',
                                    ])>
                                        {{ $row->type_label }}
                                    </span>
                                    <p class="text-sm font-semibold text-slate-900">{{ $row->number }}</p>
                                </div>
                                <p class="mt-1 text-xs text-slate-400">{{ $row->created_at?->format('M j, Y') }}</p>
                                @if ($row->meta)
                                    <p class="text-xs text-slate-400">{{ $row->meta }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($contactUrl)
                                    <a class="text-sm font-medium text-teal-700 hover:text-teal-800 hover:underline" href="{{ $contactUrl }}">
                                        {{ $this->contactLabel($row->contact) }}
                                    </a>
                                @else
                                    <span class="text-sm text-slate-600">{{ $this->contactLabel($row->contact) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex w-fit rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                        {{ $row->status_label }}
                                    </span>
                                    @if ($row->payment_status_label)
                                        <span @class([
                                            'inline-flex w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            'bg-emerald-50 text-emerald-700' => $row->payment_status_value === 'paid',
                                            'bg-amber-50 text-amber-700' => in_array($row->payment_status_value, ['pending', 'partial'], true),
                                            'bg-slate-100 text-slate-600' => $row->payment_status_value === 'refunded',
                                        ])>
                                            {{ $row->payment_status_label }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $row->consultant?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $row->demo_consultant?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                ${{ number_format((float) $row->total, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <button class="font-semibold text-slate-700 hover:text-slate-900" type="button" wire:click="openView('{{ $row->type }}', {{ $row->id }})">View</button>
                                @if ($canManage)
                                    <button class="ml-3 font-semibold text-teal-700 hover:text-teal-900" type="button" wire:click="openEdit('{{ $row->type }}', {{ $row->id }})">Edit</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-10 text-center text-sm text-slate-500" colspan="7">
                                No orders or quotations yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                    <span>
                        @if ($totalItems === 0)
                            No records
                        @else
                            Showing {{ $fromItem }}–{{ $toItem }} of {{ number_format($totalItems) }}
                        @endif
                    </span>
                    <label class="inline-flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Per page</span>
                        <select class="rounded-lg border-slate-200 py-1 text-sm shadow-sm" wire:model.live="perPage">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </label>
                </div>
                <div>
                    {{ $records->links() }}
                </div>
            </div>
        </div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeModal"></div>
            <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">{{ $modalType === 'order' ? 'Order' : 'Quotation' }}</p>
                    <h3 class="mt-1 text-lg font-black text-slate-950">{{ $modalEditable ? 'Edit' : 'View' }} {{ $modalNumber }}</h3>
                </div>

                @if ($modalEditable)
                    <form class="space-y-5 px-5 py-5" wire:submit="saveModal">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Consultant</label>
                                <select class="block w-full rounded-xl border-slate-200 text-sm" wire:model="modal_user_id" required>
                                    @foreach ($consultants as $consultant)
                                        <option value="{{ $consultant->id }}">{{ $consultant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Demo consultant</label>
                                <select class="block w-full rounded-xl border-slate-200 text-sm" wire:model="modal_demo_consultant_id">
                                    <option value="">None</option>
                                    @foreach ($consultants as $consultant)
                                        <option value="{{ $consultant->id }}">{{ $consultant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Status</label>
                                <select class="block w-full rounded-xl border-slate-200 text-sm" wire:model="modalStatus" required>
                                    @foreach ($modalType === 'order' ? $orderStatuses : $quotationStatuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
                                <textarea class="block w-full rounded-xl border-slate-200 text-sm" rows="2" wire:model="modal_notes"></textarea>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <h4 class="text-sm font-bold text-slate-900">Line items</h4>
                                <button class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700" type="button" wire:click="addModalItem">+ Item</button>
                            </div>
                            <div class="space-y-3">
                                @foreach ($modalItems as $index => $item)
                                    <div class="rounded-xl border border-slate-200 p-3" wire:key="modal-item-{{ $index }}">
                                        <div class="grid gap-3 md:grid-cols-6">
                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-xs font-semibold text-slate-600">Catalog</label>
                                                <select class="block w-full rounded-lg border-slate-200 text-sm" wire:model.live="modalItems.{{ $index }}.crm_product_id">
                                                    <option value="">Custom</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-xs font-semibold text-slate-600">Description</label>
                                                <input class="block w-full rounded-lg border-slate-200 text-sm" type="text" wire:model="modalItems.{{ $index }}.description" required>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold text-slate-600">Qty</label>
                                                <input class="block w-full rounded-lg border-slate-200 text-sm" type="number" min="1" wire:model="modalItems.{{ $index }}.quantity" required>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold text-slate-600">Price</label>
                                                <input class="block w-full rounded-lg border-slate-200 text-sm" type="number" min="0" step="0.01" wire:model="modalItems.{{ $index }}.unit_price" required>
                                            </div>
                                        </div>
                                        @if (count($modalItems) > 1)
                                            <button class="mt-2 text-xs font-semibold text-rose-600" type="button" wire:click="removeModalItem({{ $index }})">Remove</button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                            <button class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" wire:click="closeModal">Cancel</button>
                            <x-primary-button type="submit">Save changes</x-primary-button>
                        </div>
                    </form>
                @else
                    <div class="space-y-4 px-5 py-5">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Consultant</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $consultants->firstWhere('id', $modal_user_id)?->name ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Demo consultant</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $modal_demo_consultant_id ? ($consultants->firstWhere('id', $modal_demo_consultant_id)?->name ?? '—') : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $modalStatus }}</p>
                            </div>
                            @if ($modal_notes)
                                <div class="sm:col-span-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $modal_notes }}</p>
                                </div>
                            @endif
                        </div>

                        <div>
                            <h4 class="mb-2 text-sm font-bold text-slate-900">Line items</h4>
                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Item</th>
                                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Qty</th>
                                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Price</th>
                                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse ($modalViewItems as $item)
                                            <tr>
                                                <td class="px-3 py-2 text-slate-800">{{ $item['description'] }}</td>
                                                <td class="px-3 py-2 text-right text-slate-600">{{ $item['quantity'] }}</td>
                                                <td class="px-3 py-2 text-right text-slate-600">${{ number_format((float) $item['unit_price'], 2) }}</td>
                                                <td class="px-3 py-2 text-right font-semibold text-slate-900">${{ number_format((float) $item['quantity'] * (float) $item['unit_price'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td class="px-3 py-6 text-center text-slate-500" colspan="4">No line items.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                            @if ($canManage)
                                <button class="rounded-full border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-800" type="button" wire:click="openEdit('{{ $modalType }}', {{ $modalId }})">Edit</button>
                            @endif
                            <button class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" wire:click="closeModal">Close</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
