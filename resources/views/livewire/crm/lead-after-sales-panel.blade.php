<div>
    @if ($showPanel)
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-r from-indigo-50 to-slate-100 px-4 py-2.5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">After-Sales Program</h2>
                        <p class="text-[11px] text-slate-500">Warranty, follow-ups, upgrades, and VIP customer care.</p>
                    </div>
                    @can('update', $lead)
                        @if (! $isEnrolled)
                            <button
                                class="rounded-full bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                                type="button"
                                wire:click="enroll"
                                wire:confirm="Enroll this client in the after-sales funnel?"
                            >
                                Enroll in After-Sales
                            </button>
                        @endif
                    @endcan
                </div>
            </div>

            <div class="bg-slate-100 p-4">
                @if ($isEnrolled)
                    <div class="rounded-lg border border-indigo-100 bg-white p-4 shadow-sm">
                        <p class="text-sm font-bold text-slate-900">{{ $lead->funnel?->name ?? 'After-Sales Funnel' }}</p>
                        <p class="mt-1 text-xs text-indigo-700">Current stage: {{ $lead->stage?->name ?? '—' }}</p>

                        @if ($stages->isNotEmpty())
                            <ol class="mt-4 space-y-2">
                                @foreach ($stages as $stage)
                                    <li @class([
                                        'flex items-center gap-2 rounded-lg px-3 py-2 text-xs',
                                        'bg-indigo-50 font-semibold text-indigo-900 ring-1 ring-indigo-200' => $lead->funnel_stage_id === $stage->id,
                                        'text-slate-600' => $lead->funnel_stage_id !== $stage->id,
                                    ])>
                                        <span @class([
                                            'size-2 rounded-full',
                                            'bg-indigo-500' => $lead->funnel_stage_id === $stage->id,
                                            'bg-slate-300' => $lead->funnel_stage_id !== $stage->id,
                                        ])></span>
                                        {{ $stage->name }}
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @else
                    <p class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                        Not enrolled in after-sales yet. Customers are auto-enrolled when moved to Closed Won, or enroll manually.
                    </p>
                @endif
            </div>
        </section>
    @endif
</div>
