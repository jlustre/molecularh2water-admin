<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-gradient-to-r from-amber-50 to-slate-100 px-4 py-2.5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Quotations</h2>
                <p class="text-[11px] text-slate-500">Build quotes, present to prospects, and export PDF.</p>
            </div>
            @can('update', $lead)
                <button
                    class="rounded-full bg-amber-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-700"
                    type="button"
                    wire:click="toggleBuilder"
                >
                    {{ $showBuilder ? 'Cancel' : 'New Quote' }}
                </button>
            @endcan
        </div>
    </div>

    <div class="space-y-4 bg-slate-100 p-4">
        @if ($showBuilder)
            <form class="space-y-4 rounded-lg border border-white bg-white p-4 shadow-sm" wire:submit="saveQuotation">
                <div class="space-y-3">
                    @foreach ($lineItems as $index => $line)
                        <div class="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 sm:grid-cols-12" wire:key="line-{{ $index }}">
                            <div class="sm:col-span-4">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Product</label>
                                <select class="w-full rounded-lg border-slate-200 text-sm" wire:model.live="lineItems.{{ $index }}.crm_product_id">
                                    <option value="">Custom line</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Description</label>
                                <input class="w-full rounded-lg border-slate-200 text-sm" type="text" wire:model="lineItems.{{ $index }}.description" />
                                @error('lineItems.'.$index.'.description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Qty</label>
                                <input class="w-full rounded-lg border-slate-200 text-sm" min="1" type="number" wire:model="lineItems.{{ $index }}.quantity" />
                            </div>
                            <div class="sm:col-span-2 flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Price</label>
                                    <input class="w-full rounded-lg border-slate-200 text-sm" min="0" step="0.01" type="number" wire:model="lineItems.{{ $index }}.unit_price" />
                                </div>
                                @if (count($lineItems) > 1)
                                    <button class="mb-1 rounded-full border border-rose-200 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50" type="button" wire:click="removeLineItem({{ $index }})">×</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <button class="text-xs font-semibold text-teal-700 hover:text-teal-900" type="button" wire:click="addLineItem">+ Add line item</button>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Discount</label>
                        <input class="w-full rounded-lg border-slate-200 text-sm" min="0" step="0.01" type="number" wire:model.live="discount_amount" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tax</label>
                        <input class="w-full rounded-lg border-slate-200 text-sm" min="0" step="0.01" type="number" wire:model.live="tax_amount" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Shipping</label>
                        <input class="w-full rounded-lg border-slate-200 text-sm" min="0" step="0.01" type="number" wire:model.live="shipping_amount" />
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Valid Until</label>
                        <input class="w-full rounded-lg border-slate-200 text-sm" type="date" wire:model="valid_until" />
                    </div>
                    <div class="flex items-end justify-end">
                        <p class="text-sm font-bold text-slate-900">Estimated total: ${{ number_format($this->estimatedTotal, 2) }}</p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Warranty Notes</label>
                        <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="warranty_notes"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Financing Notes</label>
                        <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="financing_notes"></textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button class="rounded-full bg-marine px-5 py-2 text-sm font-bold text-white hover:bg-teal-700" type="submit">
                        Save Quote
                    </button>
                </div>
            </form>
        @endif

        <div class="space-y-3">
            @forelse ($quotations as $quotation)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $quotation->quote_number }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $quotation->created_at->format('M j, Y') }}
                                @if ($quotation->author)
                                    · {{ $quotation->author->name }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-800">
                                {{ $quotation->status->label() }}
                            </span>
                            <span class="text-sm font-bold text-slate-900">${{ number_format($quotation->total, 2) }}</span>
                        </div>
                    </div>

                    <ul class="mt-3 space-y-1 text-xs text-slate-600">
                        @foreach ($quotation->items as $item)
                            <li>{{ $item->quantity }}× {{ $item->description }} — ${{ number_format($item->line_total, 2) }}</li>
                        @endforeach
                    </ul>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <a
                            class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            href="{{ $this->pdfUrl($quotation) }}"
                            target="_blank"
                        >
                            Download PDF
                        </a>
                        @can('update', $lead)
                            @if ($quotation->status->value === 'draft')
                                <button
                                    class="rounded-full bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700"
                                    type="button"
                                    wire:click="presentQuote({{ $quotation->id }})"
                                    wire:confirm="Mark this quote as presented and move prospect to Quote Presented?"
                                >
                                    Present Quote
                                </button>
                            @endif
                        @endcan
                    </div>
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                    No quotations yet.
                </p>
            @endforelse
        </div>
    </div>
</section>
