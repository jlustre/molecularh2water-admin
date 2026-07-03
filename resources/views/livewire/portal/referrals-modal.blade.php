<div>
    @if ($show)
        <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" role="dialog" aria-modal="true" aria-labelledby="referrals-title">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative mx-auto w-full max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-orange-700">Quick Action</p>
                        <h2 id="referrals-title" class="mt-1 text-xl font-black text-slate-950">Referrals</h2>
                        <p class="mt-1 text-sm text-slate-500">Log referrals from people in your network. Referred contacts are added to your leads and can be converted to prospects when ready.</p>
                    </div>
                    <button type="button" wire:click="close" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if (session('referral_status'))
                    <div class="mx-5 mt-4 rounded-lg border border-orange-100 bg-orange-50 px-4 py-3 text-sm font-medium text-orange-800 sm:mx-6">
                        {{ session('referral_status') }}
                    </div>
                @endif

                <div class="max-h-[78vh] overflow-y-auto px-5 py-5 sm:px-6">
                    @if (auth()->user()?->hasPermission('leads.update'))
                        <section>
                            <h3 class="text-sm font-bold text-slate-900">Log a referral</h3>
                            <p class="mt-1 text-xs text-slate-500">Select the referring person and enter the referred contact. They are saved as a lead on your referral funnel and can be converted to a prospect when you are ready to work them.</p>

                            <form class="mt-4 space-y-4" wire:submit="create">
                                <div class="relative">
                                    <label for="referral-referrer-search" class="mb-1 block text-sm font-semibold text-slate-700">
                                        Referring person
                                    </label>
                                    <input
                                        id="referral-referrer-search"
                                        type="search"
                                        wire:model.live.debounce.300ms="referrer_search"
                                        placeholder="Search prospects and customers (at least 3 characters)…"
                                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        autocomplete="off"
                                    />
                                    @if ($referrer_lead_id)
                                        <div class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2">
                                            <p class="text-sm font-semibold text-orange-900">{{ $referrer_search }}</p>
                                            <button type="button" wire:click="clearReferrer" class="text-xs font-semibold text-orange-700 hover:text-orange-900">
                                                Change
                                            </button>
                                        </div>
                                    @endif
                                    @error('referrer_lead_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                                    @if ($showReferrerResults)
                                        <ul class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                                            @forelse ($referrerResults as $referrer)
                                                <li>
                                                    <button
                                                        type="button"
                                                        wire:click="selectReferrer({{ $referrer->id }})"
                                                        class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-orange-50"
                                                    >
                                                        <span class="font-semibold text-slate-900">{{ $referrer->fullName() }}</span>
                                                        <span @class([
                                                            'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                                            'bg-orange-100 text-orange-800' => $referrer->lifecycle === \App\Enums\Crm\LeadLifecycle::Prospect,
                                                            'bg-emerald-100 text-emerald-800' => $referrer->lifecycle === \App\Enums\Crm\LeadLifecycle::Client,
                                                        ])>
                                                            {{ $referrer->lifecycle?->label() }}
                                                        </span>
                                                    </button>
                                                </li>
                                            @empty
                                                <li class="px-3 py-2 text-sm text-slate-500">No matching prospects or customers found.</li>
                                            @endforelse
                                        </ul>
                                    @endif
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="referral-first-name" class="mb-1 block text-sm font-semibold text-slate-700">Referred first name</label>
                                        <input id="referral-first-name" type="text" wire:model="first_name" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500" required />
                                        @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="referral-last-name" class="mb-1 block text-sm font-semibold text-slate-700">Referred last name</label>
                                        <input id="referral-last-name" type="text" wire:model="last_name" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500" />
                                        @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="referral-email" class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
                                        <input id="referral-email" type="email" wire:model="email" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500" />
                                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="referral-phone" class="mb-1 block text-sm font-semibold text-slate-700">Phone</label>
                                        <input id="referral-phone" type="tel" wire:model="phone" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500" />
                                        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="referral-notes" class="mb-1 block text-sm font-semibold text-slate-700">
                                            Notes <span class="font-normal text-slate-400">(optional)</span>
                                        </label>
                                        <textarea id="referral-notes" wire:model="notes" rows="3" placeholder="How they know each other, interests, follow-up context…" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"></textarea>
                                        @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                    <button type="button" wire:click="close" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                                        Cancel
                                    </button>
                                    <x-primary-button type="submit">
                                        Log referral
                                    </x-primary-button>
                                </div>
                            </form>
                        </section>

                        <div class="my-6 border-t border-slate-200"></div>
                    @endif

                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-900">Recent referrals</h3>
                            <a href="{{ route(\App\Support\Crm\CrmRoutes::name('customers.index')) }}" class="text-xs font-semibold text-orange-700 hover:text-orange-900">
                                Open Customers
                            </a>
                        </div>

                        <ul class="space-y-2">
                            @forelse ($recentReferrals as $referral)
                                <li class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                                    <div class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-700">
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-0.5">
                                        <a href="{{ route(\App\Support\Crm\CrmRoutes::name(match ($referral->referred->lifecycle) {
                                            \App\Enums\Crm\LeadLifecycle::Prospect => 'prospects.show',
                                            \App\Enums\Crm\LeadLifecycle::Client => 'customers.show',
                                            default => 'leads.show',
                                        }), $referral->referred) }}" class="truncate text-sm font-semibold text-slate-900 hover:text-orange-800" wire:navigate>
                                            {{ $referral->referred->fullName() }}
                                        </a>
                                        <p class="truncate text-xs text-slate-500">
                                            Referred by {{ $referral->referrer->fullName() }}
                                            · {{ $referral->status->label() }}
                                            @if ($referral->referred->stage)
                                                · {{ $referral->referred->stage->name }}
                                            @endif
                                            · {{ $referral->created_at?->format('M j') }}
                                        </p>
                                    </div>
                                </li>
                            @empty
                                <li class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                                    No referrals yet. Use the form above to log your first one.
                                </li>
                            @endforelse
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    @endif
</div>
