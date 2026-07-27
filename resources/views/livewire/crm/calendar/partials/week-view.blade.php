@php
    $weekStart = $focus->copy()->startOfWeek();
    $days = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));
@endphp

<div class="grid gap-4 md:grid-cols-7">
    @foreach ($days as $day)
        @php
            $dateKey = $day->format('Y-m-d');
            $dayEntries = $entries->filter(fn ($e) =>
                ($e->span_start ?? $e->start_at->copy()->startOfDay())->lte($day->copy()->startOfDay())
                && ($e->span_end ?? ($e->end_at ?? $e->start_at)->copy()->startOfDay())->gte($day->copy()->startOfDay())
            )->sortBy([
                fn ($e) => ! empty($e->is_all_day) || ! empty($e->is_bar) ? 0 : 1,
                fn ($e) => $e->start_at?->timestamp ?? 0,
            ]);
            $isSelected = $selectedDay === $dateKey;
        @endphp
        <div
            role="button"
            tabindex="0"
            wire:click="openDay('{{ $dateKey }}')"
            wire:keydown.enter.prevent="openDay('{{ $dateKey }}')"
            wire:keydown.space.prevent="openDay('{{ $dateKey }}')"
            @class([
                'group cursor-pointer rounded-xl border bg-gradient-to-br from-white to-slate-50/80 p-3 transition',
                'hover:ring-2 hover:ring-teal-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500',
                'border-teal-300 from-teal-50/80 via-white to-emerald-50/50' => $day->isToday() && ! $isSelected,
                'border-slate-200' => ! $day->isToday() && ! $isSelected,
                'border-teal-600 ring-2 ring-teal-600' => $isSelected,
            ])
            aria-label="View schedule for {{ $day->format('F j, Y') }}"
        >
            <p class="text-xs font-bold uppercase text-slate-500">{{ $day->format('D j') }}</p>
            <div class="mt-2 space-y-2">
                @forelse ($dayEntries as $entry)
                    <button
                        type="button"
                        class="block w-full rounded-lg border px-2 py-1.5 text-left text-xs font-semibold {{ $typeColors[$entry->color] ?? 'bg-teal-100 text-teal-800 border-teal-200' }}"
                        wire:click.stop="openDetails('{{ $entry->kind }}', {{ $entry->id }})"
                    >
                        <span class="block">
                            @if (! empty($entry->is_all_day))
                                All day
                            @elseif (! empty($entry->spans_multiple_days))
                                Multi-day
                            @else
                                {{ $entry->start_at->format('g:i A') }}
                            @endif
                        </span>
                        <span class="block truncate">{{ $entry->title }}</span>
                    </button>
                @empty
                    <p class="text-xs text-slate-400">No events</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
