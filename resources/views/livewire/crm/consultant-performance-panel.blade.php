<div>
    <x-crm.calendar-panel title="Performance counters" tone="indigo" panel-key="performance-counters">
        <div class="mt-3 space-y-3">
            <div class="rounded-xl border border-indigo-100/80 bg-white/70 px-2.5 py-2.5">
                <label for="performance-focus-date" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Date</label>
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        id="performance-focus-date"
                        type="date"
                        class="min-w-0 flex-1 rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        wire:model.live="focusDate"
                    />
                    <button
                        type="button"
                        class="rounded-full border border-slate-200 bg-white px-3 py-2 text-[11px] font-semibold text-slate-600 hover:bg-slate-50"
                        wire:click="goToday"
                    >
                        Today
                    </button>
                </div>
                <p class="mt-1.5 text-xs font-semibold text-slate-700">{{ $selectedDateLabel }}</p>
            </div>

            @if ($canPick)
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Consultant</label>
                    <select
                        class="w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        wire:model.live="subjectUserId"
                    >
                        @foreach ($subjects as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <p class="text-xs text-slate-500">Tracking for <span class="font-semibold text-slate-700">{{ $subject->name }}</span></p>
            @endif

            <ul class="space-y-2">
                @foreach ($labels as $key => $label)
                    <li class="flex items-center gap-2 rounded-xl border border-indigo-100/70 bg-gradient-to-r from-white/90 to-indigo-50/40 px-2.5 py-2 shadow-sm">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-slate-800">{{ $label }}</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if ($canEdit)
                                <button
                                    type="button"
                                    class="inline-flex size-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-rose-50 hover:text-rose-700 disabled:opacity-40"
                                    wire:click="decrement('{{ $key }}')"
                                    @disabled(($totals[$key] ?? 0) <= 0)
                                    aria-label="Decrease {{ $label }}"
                                >
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                                </button>
                            @endif
                            <span class="min-w-9 rounded-lg bg-white px-1.5 py-0.5 text-center text-base font-black tabular-nums text-indigo-900 ring-1 ring-indigo-100">{{ $totals[$key] ?? 0 }}</span>
                            @if ($canEdit)
                                <button
                                    type="button"
                                    class="inline-flex size-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-700"
                                    wire:click="increment('{{ $key }}')"
                                    aria-label="Increase {{ $label }}"
                                >
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                </button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            <p class="rounded-lg bg-white/60 px-2.5 py-2 text-[11px] leading-relaxed text-slate-500 ring-1 ring-indigo-100/70">
                Counts are stored for the selected date.
            </p>
        </div>
    </x-crm.calendar-panel>
</div>
