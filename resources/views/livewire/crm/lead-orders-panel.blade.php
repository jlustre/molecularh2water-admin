<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-gradient-to-r from-emerald-50 to-slate-100 px-4 py-2.5">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Orders & Fulfillment</h2>
            <p class="text-[11px] text-slate-500">Payments, delivery scheduling, installation, and completion photos.</p>
        </div>
    </div>

    <div class="space-y-4 bg-slate-100 p-4">
        <div class="space-y-3">
            @forelse ($orders as $order)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $order->order_number }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $order->created_at->format('M j, Y') }}
                                @if ($order->quotation)
                                    · from {{ $order->quotation->quote_number }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-700">
                                {{ $order->status->label() }}
                            </span>
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-800">
                                {{ $order->payment_status->label() }}
                            </span>
                            <span class="text-sm font-bold text-slate-900">${{ number_format($order->total, 2) }}</span>
                        </div>
                    </div>

                    <ul class="mt-3 space-y-1 text-xs text-slate-600">
                        @foreach ($order->items as $item)
                            <li>{{ $item->quantity }}× {{ $item->description }} — ${{ number_format($item->line_total, 2) }}</li>
                        @endforeach
                    </ul>

                    @can('update', $lead)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($order->status->value === 'draft')
                                <button
                                    class="rounded-full bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700"
                                    type="button"
                                    wire:click="submitOrder({{ $order->id }})"
                                    wire:confirm="Submit this order and move prospect to Order Submitted?"
                                >
                                    Submit Order
                                </button>
                            @endif
                            <button
                                class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                type="button"
                                wire:click="setActiveOrder({{ $order->id }})"
                            >
                                {{ $activeOrderId === $order->id ? 'Close' : 'Manage' }}
                            </button>
                        </div>

                        @if ($activeOrderId === $order->id)
                            <div class="mt-4 space-y-4 rounded-lg border border-slate-100 bg-slate-50 p-4">
                                @if ($order->payment_status->value !== 'paid')
                                    <form class="grid gap-3 sm:grid-cols-3" wire:submit="recordPayment({{ $order->id }})">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Payment Amount</label>
                                            <input class="w-full rounded-lg border-slate-200 text-sm" min="0" step="0.01" type="number" wire:model="payment_amount" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Method</label>
                                            <input class="w-full rounded-lg border-slate-200 text-sm" placeholder="Cash, card, financing..." wire:model="payment_method" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Reference</label>
                                            <input class="w-full rounded-lg border-slate-200 text-sm" wire:model="payment_reference" />
                                        </div>
                                        <div class="sm:col-span-3 flex justify-end">
                                            <button class="rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700" type="submit">
                                                Record Payment
                                            </button>
                                        </div>
                                    </form>
                                @endif

                                @if ($order->payment_status->value === 'paid' && $order->deliveries->where('status.value', '!=', 'delivered')->isEmpty() && $order->deliveries->isEmpty())
                                    <form class="grid gap-3 sm:grid-cols-2" wire:submit="scheduleDelivery({{ $order->id }})">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Delivery Date</label>
                                            <input class="w-full rounded-lg border-slate-200 text-sm" type="datetime-local" wire:model="delivery_scheduled_at" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Contact Phone</label>
                                            <input class="w-full rounded-lg border-slate-200 text-sm" wire:model="delivery_contact_phone" />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Delivery Address</label>
                                            <input class="w-full rounded-lg border-slate-200 text-sm" wire:model="delivery_address" />
                                        </div>
                                        <div class="sm:col-span-2 flex justify-end">
                                            <button class="rounded-full bg-cyan-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-cyan-700" type="submit">
                                                Schedule Delivery
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @endcan

                    @foreach ($order->deliveries as $delivery)
                        <div class="mt-3 rounded-lg border border-cyan-100 bg-cyan-50/40 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-bold text-cyan-900">
                                    Delivery · {{ $delivery->status->label() }}
                                    @if ($delivery->scheduled_at)
                                        · {{ $delivery->scheduled_at->format('M j, Y g:i A') }}
                                    @endif
                                </p>
                                @can('update', $lead)
                                    @if ($delivery->status->value !== 'delivered' && $completingDeliveryId !== $delivery->id)
                                        <button
                                            class="rounded-full border border-cyan-200 bg-white px-3 py-1 text-xs font-semibold text-cyan-800 hover:bg-cyan-50"
                                            type="button"
                                            wire:click="startCompleteDelivery({{ $delivery->id }})"
                                        >
                                            Complete Delivery
                                        </button>
                                    @endif
                                @endcan
                            </div>
                            @if ($delivery->address)
                                <p class="mt-1 text-xs text-slate-600">{{ $delivery->address }}</p>
                            @endif

                            @if ($completingDeliveryId === $delivery->id)
                                <form class="mt-3 space-y-3" wire:submit="completeDelivery">
                                    <div class="space-y-2">
                                        @foreach ($deliveryChecklist as $item => $checked)
                                            <label class="flex items-center gap-2 text-sm text-slate-700" wire:key="delivery-check-{{ $delivery->id }}-{{ md5($item) }}">
                                                <input type="checkbox" wire:model="deliveryChecklist.{{ $item }}" />
                                                {{ $item }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Completion Photos</label>
                                        <input class="w-full text-sm" multiple type="file" wire:model="deliveryPhotos" accept="image/*" />
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600" type="button" wire:click="$set('completingDeliveryId', null)">Cancel</button>
                                        <button class="rounded-full bg-cyan-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-cyan-700" type="submit">Save Delivery</button>
                                    </div>
                                </form>
                            @endif

                            @if ($delivery->photo_paths)
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($delivery->photo_paths as $path)
                                        <a class="text-xs font-semibold text-cyan-700 hover:text-cyan-900" href="{{ Storage::disk('public')->url($path) }}" target="_blank">Photo</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @foreach ($order->installations as $installation)
                        <div class="mt-3 rounded-lg border border-emerald-100 bg-emerald-50/40 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-bold text-emerald-900">
                                    Installation · {{ $installation->status->label() }}
                                    @if ($installation->scheduled_at)
                                        · {{ $installation->scheduled_at->format('M j, Y g:i A') }}
                                    @endif
                                </p>
                                @can('update', $lead)
                                    @if ($installation->status->value !== 'completed' && $completingInstallationId !== $installation->id)
                                        <button
                                            class="rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-50"
                                            type="button"
                                            wire:click="startCompleteInstallation({{ $installation->id }})"
                                        >
                                            Complete Installation
                                        </button>
                                    @endif
                                @endcan
                            </div>

                            @if ($completingInstallationId === $installation->id)
                                <form class="mt-3 space-y-3" wire:submit="completeInstallation">
                                    <div class="space-y-2">
                                        @foreach ($installationChecklist as $item => $checked)
                                            <label class="flex items-center gap-2 text-sm text-slate-700" wire:key="install-check-{{ $installation->id }}-{{ md5($item) }}">
                                                <input type="checkbox" wire:model="installationChecklist.{{ $item }}" />
                                                {{ $item }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Completion Photos</label>
                                        <input class="w-full text-sm" multiple type="file" wire:model="installationPhotos" accept="image/*" />
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600" type="button" wire:click="$set('completingInstallationId', null)">Cancel</button>
                                        <button class="rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700" type="submit">Save Installation</button>
                                    </div>
                                </form>
                            @endif

                            @if ($installation->photo_paths)
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($installation->photo_paths as $path)
                                        <a class="text-xs font-semibold text-emerald-700 hover:text-emerald-900" href="{{ Storage::disk('public')->url($path) }}" target="_blank">Photo</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @can('update', $lead)
                        @if ($order->deliveries->where('status.value', 'delivered')->isNotEmpty() && $order->installations->isEmpty())
                            <form class="mt-3 flex flex-wrap items-end gap-3" wire:submit="scheduleInstallation({{ $order->id }})">
                                <div class="flex-1">
                                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Installation Date</label>
                                    <input class="w-full rounded-lg border-slate-200 text-sm" type="datetime-local" wire:model="installation_scheduled_at" />
                                </div>
                                <button class="rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700" type="submit">
                                    Schedule Installation
                                </button>
                            </form>
                        @endif
                    @endcan
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                    No orders yet. Convert a presented quote to start fulfillment.
                </p>
            @endforelse
        </div>
    </div>
</section>
