@php
    $monthStart = $focus->copy()->startOfMonth()->startOfWeek();
    $monthEnd = $focus->copy()->endOfMonth()->endOfWeek();
    $cursor = $monthStart->copy();
@endphp

<div class="grid grid-cols-7 gap-px overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-slate-100 to-slate-200">
    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
        <div class="bg-gradient-to-b from-slate-50 to-slate-100/80 px-2 py-2 text-center text-xs font-bold uppercase tracking-wide text-slate-500">{{ $day }}</div>
    @endforeach

    @while ($cursor <= $monthEnd)
        @php
            $dateKey = $cursor->format('Y-m-d');
            $dayEntries = $entriesByDate[$dateKey] ?? collect();
            $isCurrentMonth = $cursor->month === $focus->month;
            $isToday = $cursor->isToday();
            $isSelected = $selectedDay === $dateKey;
        @endphp
        <div
            role="button"
            tabindex="0"
            wire:click="openDay('{{ $dateKey }}')"
            wire:keydown.enter.prevent="openDay('{{ $dateKey }}')"
            wire:keydown.space.prevent="openDay('{{ $dateKey }}')"
            @class([
                'group min-h-28 cursor-pointer bg-gradient-to-br from-white to-slate-50/80 p-2 transition',
                'hover:z-10 hover:ring-2 hover:ring-inset hover:ring-teal-500 focus-visible:z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-teal-500',
                'from-slate-50/90 to-slate-100/60 text-slate-400' => ! $isCurrentMonth,
                'from-teal-50/80 via-white to-emerald-50/50 ring-2 ring-inset ring-teal-400' => $isToday && ! $isSelected,
                'ring-2 ring-inset ring-teal-600' => $isSelected,
            ])
            aria-label="View schedule for {{ $cursor->format('F j, Y') }}"
        >
            <div class="mb-1 flex items-center justify-between">
                <span class="text-xs font-semibold {{ $isToday ? 'text-teal-700' : 'text-slate-600' }}">{{ $cursor->day }}</span>
                @if ($canManage)
                    <button
                        type="button"
                        class="rounded px-1 text-[10px] font-semibold text-teal-600 opacity-0 transition group-hover:opacity-100 hover:bg-teal-50 hover:text-teal-800"
                        wire:click.stop="openCreate('{{ $dateKey }}')"
                        title="Add event"
                        aria-label="Add event on {{ $cursor->format('F j, Y') }}"
                    >+</button>
                @endif
            </div>
            <div class="space-y-1">
                @foreach ($dayEntries->take(3) as $entry)
                    <button
                        type="button"
                        class="block w-full truncate rounded-md border px-1.5 py-0.5 text-left text-[10px] font-semibold {{ $typeColors[$entry->color] ?? 'bg-teal-100 text-teal-800 border-teal-200' }}"
                        wire:click.stop="openDetails('{{ $entry->kind }}', {{ $entry->id }})"
                    >
                        @if (! empty($entry->business_line_label))
                            <span class="mr-1 opacity-80">{{ $entry->business_line_label }}</span>
                        @endif
                        {{ $entry->title }}
                    </button>
                @endforeach
                @if ($dayEntries->count() > 3)
                    <p class="text-[10px] font-semibold text-teal-700">+{{ $dayEntries->count() - 3 }} more</p>
                @endif
            </div>
        </div>
        @php $cursor->addDay(); @endphp
    @endwhile
</div>
