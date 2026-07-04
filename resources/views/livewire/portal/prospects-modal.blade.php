<div>
    @if ($show)
        <div class="shell-modal-overlay fixed inset-0 flex items-center justify-center overflow-y-auto px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="prospects-title">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative mx-auto w-full max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-rose-700">Quick Action</p>
                        <h2 id="prospects-title" class="mt-1 text-xl font-black text-slate-950">Prospects</h2>
                        <p class="mt-1 text-sm text-slate-500">Add new prospects and review your recent pipeline contacts.</p>
                    </div>
                    <button type="button" wire:click="close" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if (session('prospect_status'))
                    <div class="mx-5 mt-4 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 sm:mx-6">
                        {{ session('prospect_status') }}
                    </div>
                @endif

                <div class="max-h-[78vh] overflow-y-auto px-5 py-5 sm:px-6">
                    @if (auth()->user()?->hasPermission('leads.create'))
                        <section>
                            <h3 class="text-sm font-bold text-slate-900">Quick add prospect</h3>
                            <p class="mt-1 text-xs text-slate-500">Capture a new contact — they appear in your CRM prospect list right away.</p>

                            <form class="mt-4 space-y-4" wire:submit="create">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="prospect-first-name" class="mb-1 block text-sm font-semibold text-slate-700">First name</label>
                                        <input id="prospect-first-name" type="text" wire:model="first_name" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500" required />
                                        @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="prospect-last-name" class="mb-1 block text-sm font-semibold text-slate-700">Last name</label>
                                        <input id="prospect-last-name" type="text" wire:model="last_name" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500" />
                                        @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="prospect-email" class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
                                        <input id="prospect-email" type="email" wire:model="email" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500" />
                                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="prospect-phone" class="mb-1 block text-sm font-semibold text-slate-700">Phone</label>
                                        <input id="prospect-phone" type="tel" wire:model="phone" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500" />
                                        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="prospect-company" class="mb-1 block text-sm font-semibold text-slate-700">
                                            Company <span class="font-normal text-slate-400">(optional)</span>
                                        </label>
                                        <input id="prospect-company" type="text" wire:model="company" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500" />
                                        @error('company') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="prospect-notes" class="mb-1 block text-sm font-semibold text-slate-700">
                                            Notes <span class="font-normal text-slate-400">(optional)</span>
                                        </label>
                                        <textarea id="prospect-notes" wire:model="notes" rows="3" placeholder="How you met, interests, follow-up context…" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                                        @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                    <button type="button" wire:click="close" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                                        Cancel
                                    </button>
                                    <x-primary-button type="submit">
                                        Add prospect
                                    </x-primary-button>
                                </div>
                            </form>
                        </section>

                        <div class="my-6 border-t border-slate-200"></div>
                    @endif

                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-900">Recent prospects</h3>
                            <a href="{{ route(\App\Support\Crm\CrmRoutes::name('prospects.index')) }}" class="text-xs font-semibold text-rose-700 hover:text-rose-900">
                                Open Prospects
                            </a>
                        </div>

                        <ul class="space-y-2">
                            @forelse ($recentProspects as $prospect)
                                <li class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                                    <div class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-0.5">
                                        <a href="{{ route(\App\Support\Crm\CrmRoutes::name('prospects.show'), $prospect) }}" class="truncate text-sm font-semibold text-slate-900 hover:text-rose-800" wire:navigate>
                                            {{ $prospect->fullName() }}
                                        </a>
                                        <p class="truncate text-xs text-slate-500">
                                            @if ($prospect->email)
                                                {{ $prospect->email }}
                                            @elseif ($prospect->phone)
                                                {{ $prospect->phone }}
                                            @else
                                                No contact info
                                            @endif
                                            @if ($prospect->company)
                                                · {{ $prospect->company }}
                                            @endif
                                            @if ($prospect->stage)
                                                · {{ $prospect->stage->name }}
                                            @endif
                                            · Added {{ $prospect->created_at?->format('M j') }}
                                        </p>
                                    </div>
                                </li>
                            @empty
                                <li class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                                    No prospects yet. Use the form above to add your first one.
                                </li>
                            @endforelse
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    @endif
</div>
