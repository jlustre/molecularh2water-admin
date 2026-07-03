<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-gradient-to-r from-teal-50 to-slate-100 px-4 py-2.5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Consultations</h2>
                <p class="text-[11px] text-slate-500">Needs assessment, objections, and product recommendations.</p>
            </div>
            @can('update', $lead)
                <button
                    class="rounded-full bg-teal-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-700"
                    type="button"
                    wire:click="toggleForm"
                >
                    {{ $showForm ? 'Cancel' : 'Record Consultation' }}
                </button>
            @endcan
        </div>
    </div>

    <div class="space-y-4 bg-slate-100 p-4">
        @if ($showForm)
            <form class="grid gap-3 rounded-lg border border-white bg-white p-4 shadow-sm sm:grid-cols-2" wire:submit="saveConsultation">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Customer Needs</label>
                    <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="customer_needs"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Product Recommendation</label>
                    <input class="w-full rounded-lg border-slate-200 text-sm" type="text" wire:model="product_recommendation" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Family Size</label>
                    <input class="w-full rounded-lg border-slate-200 text-sm" min="1" type="number" wire:model="family_size" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Water Consumption</label>
                    <input class="w-full rounded-lg border-slate-200 text-sm" placeholder="Gallons per day..." wire:model="water_consumption" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Budget</label>
                    <input class="w-full rounded-lg border-slate-200 text-sm" min="0" step="0.01" type="number" wire:model="budget" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Financing Option</label>
                    <input class="w-full rounded-lg border-slate-200 text-sm" wire:model="financing_option" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Health Goals</label>
                    <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="health_goals"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Objections</label>
                    <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="objections"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Competitor Comparison</label>
                    <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="competitor_comparison"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Final Recommendation</label>
                    <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="final_recommendation"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Conducted At</label>
                    <input class="w-full rounded-lg border-slate-200 text-sm" type="datetime-local" wire:model="conducted_at" />
                    @error('conducted_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                    <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="notes"></textarea>
                </div>
                <div class="sm:col-span-2 flex justify-end">
                    <button class="rounded-full bg-marine px-5 py-2 text-sm font-bold text-white hover:bg-teal-700" type="submit">
                        Save Consultation
                    </button>
                </div>
            </form>
        @endif

        <div class="space-y-3">
            @forelse ($consultations as $consultation)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-slate-900">
                                {{ $consultation->conducted_at?->format('M j, Y g:i A') ?? 'Consultation' }}
                            </p>
                            @if ($consultation->consultant)
                                <p class="mt-1 text-xs text-slate-500">{{ $consultation->consultant->name }}</p>
                            @endif
                        </div>
                        @if ($consultation->product_recommendation)
                            <span class="rounded-full bg-orange-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-orange-800">
                                {{ $consultation->product_recommendation }}
                            </span>
                        @endif
                    </div>
                    @if ($consultation->final_recommendation)
                        <p class="mt-2 text-sm text-slate-700">{{ $consultation->final_recommendation }}</p>
                    @elseif ($consultation->customer_needs)
                        <p class="mt-2 text-sm text-slate-600">{{ $consultation->customer_needs }}</p>
                    @endif
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                    No consultations recorded yet.
                </p>
            @endforelse
        </div>
    </div>
</section>
