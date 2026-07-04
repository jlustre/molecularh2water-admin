<div>
    @if ($show)
        <div class="shell-modal-overlay fixed inset-0 flex items-center justify-center overflow-y-auto px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="member-invites-title">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative mx-auto w-full max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Quick Action</p>
                        <h2 id="member-invites-title" class="mt-1 text-xl font-black text-slate-950">Member Invites</h2>
                        <p class="mt-1 text-sm text-slate-500">Generate one-time registration links for people you sponsor.</p>
                    </div>
                    <button type="button" wire:click="close" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="max-h-[75vh] overflow-y-auto px-5 py-5 sm:px-6">
                    @include('livewire.portal.partials.registration-invites-content', ['compact' => true])
                </div>
            </div>
        </div>
    @endif
</div>
