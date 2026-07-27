<div>
<x-crm.calendar-panel tone="emerald">
    @if ($view === 'year')
        @include('livewire.crm.calendar.partials.year-view', [
            'focus' => $focus,
            'countsByDate' => $countsByDate,
            'selectedDay' => $selectedDay,
        ])
    @elseif ($view === 'month')
        @include('livewire.crm.calendar.partials.month-view', [
            'focus' => $focus,
            'entriesByDate' => $entriesByDate,
            'typeColors' => $typeColors,
            'canManage' => $canManage,
            'selectedDay' => $selectedDay,
        ])
    @elseif ($view === 'week')
        @include('livewire.crm.calendar.partials.week-view', [
            'focus' => $focus,
            'entries' => $entries,
            'typeColors' => $typeColors,
            'selectedDay' => $selectedDay,
        ])
    @elseif ($view === 'day')
        @include('livewire.crm.calendar.partials.day-view', [
            'focus' => $focus,
            'entries' => $entries,
            'typeColors' => $typeColors,
        ])
    @else
        @include('livewire.crm.calendar.partials.agenda-view', [
            'entries' => $entries,
            'typeColors' => $typeColors,
        ])
    @endif
</x-crm.calendar-panel>

@if ($selectedDay)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        wire:click="closeDay"
        wire:keydown.escape.window="closeDay"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
            wire:click.stop
            role="dialog"
            aria-modal="true"
            aria-labelledby="calendar-day-title"
        >
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-600">Day schedule</p>
                    <h3 id="calendar-day-title" class="mt-1 text-lg font-bold text-slate-900">{{ $selectedDayLabel }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $selectedDayEntries->count() }}
                        {{ \Illuminate\Support\Str::plural('item', $selectedDayEntries->count()) }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    wire:click="closeDay"
                    aria-label="Close"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="max-h-[60vh] space-y-2 overflow-y-auto px-5 py-4">
                @forelse ($selectedDayEntries as $entry)
                    <button
                        type="button"
                        class="flex w-full items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-3 text-left transition hover:border-teal-300 hover:bg-teal-50/50"
                        wire:click="openDetails('{{ $entry->kind }}', {{ $entry->id }})"
                    >
                        <span class="mt-0.5 inline-flex shrink-0 rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $typeColors[$entry->color] ?? 'bg-teal-100 text-teal-800 border-teal-200' }}">
                            {{ $entry->type_name ?? \Illuminate\Support\Str::headline($entry->kind) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-slate-900">{{ $entry->title }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500">
                                @if (! empty($entry->is_all_day))
                                    All day
                                    @if (! empty($entry->spans_multiple_days))
                                        · {{ $entry->span_start?->format('M j') }} – {{ $entry->span_end?->format('M j') }}
                                    @endif
                                @else
                                    {{ $entry->start_at?->format('g:i A') }}
                                    @if ($entry->end_at && ! $entry->start_at?->eq($entry->end_at))
                                        – {{ $entry->end_at->format('g:i A') }}
                                    @endif
                                @endif
                                @if (! empty($entry->is_recurring))
                                    · Repeats {{ strtolower($entry->recurrence_label ?? '') }}
                                @endif
                                @if (! empty($entry->lead_name))
                                    · {{ $entry->lead_name }}
                                @endif
                            </span>
                        </span>
                    </button>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                        No items scheduled for this day.
                    </p>
                @endforelse
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-4">
                <button
                    type="button"
                    class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    wire:click="closeDay"
                >
                    Close
                </button>
                @if ($canManage)
                    <button
                        type="button"
                        class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700"
                        wire:click="openCreate('{{ $selectedDay }}')"
                    >
                        Add event
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif
</div>
