<div>
    @if ($show)
        <div class="shell-modal-overlay fixed inset-0 flex items-center justify-center overflow-y-auto px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="pipeline-stage-leads-title">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative mx-auto w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Pipeline Summary</p>
                        <h2 id="pipeline-stage-leads-title" class="mt-1 text-xl font-black text-slate-950">{{ $stageName }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $leads->count() }} {{ str('lead')->plural($leads->count()) }} in this stage
                        </p>
                    </div>
                    <button type="button" wire:click="close" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto px-5 py-5 sm:px-6">
                    <ul class="space-y-2">
                        @forelse ($leads as $lead)
                            <li class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $lead->fullName() }}</p>
                                        <p class="mt-1 truncate text-xs text-slate-500">
                                            @if ($lead->email)
                                                {{ $lead->email }}
                                            @elseif ($lead->phone)
                                                {{ $lead->phone }}
                                            @else
                                                No contact info
                                            @endif
                                            @if ($lead->assignedUser)
                                                · {{ $lead->assignedUser->name }}
                                            @endif
                                        </p>
                                    </div>
                                    <a
                                        href="{{ \App\Support\Crm\CrmRoutes::url('leads.show', ['lead' => $lead]) }}"
                                        wire:navigate
                                        class="shrink-0 text-xs font-semibold text-teal-700 hover:text-teal-900"
                                    >
                                        View
                                    </a>
                                </div>
                            </li>
                        @empty
                            <li class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                                No leads in this stage.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>
