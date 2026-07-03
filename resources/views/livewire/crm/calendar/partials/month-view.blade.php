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
        @endphp
        <div @class([
            'min-h-28 bg-gradient-to-br from-white to-slate-50/80 p-2',
            'from-slate-50/90 to-slate-100/60 text-slate-400' => ! $isCurrentMonth,
            'from-teal-50/80 via-white to-emerald-50/50 ring-2 ring-inset ring-teal-400' => $isToday,
        ])>
            <div class="mb-1 flex items-center justify-between">
                <span class="text-xs font-semibold {{ $isToday ? 'text-teal-700' : 'text-slate-600' }}">{{ $cursor->day }}</span>
                @if ($canManage)
                    <button class="text-[10px] font-semibold text-teal-600 hover:text-teal-800" type="button" wire:click="openCreate('{{ $dateKey }}')">+</button>
                @endif
            </div>
            <div class="space-y-1">
                @foreach ($dayEntries->take(3) as $entry)
                    <button
                        type="button"
                        class="block w-full truncate rounded-md border px-1.5 py-0.5 text-left text-[10px] font-semibold {{ $typeColors[$entry->color] ?? 'bg-teal-100 text-teal-800 border-teal-200' }}"
                        wire:click="openDetails('{{ $entry->kind }}', {{ $entry->id }})"
                    >
                        @if (! empty($entry->business_line_label))
                            <span class="mr-1 opacity-80">{{ $entry->business_line_label }}</span>
                        @endif
                        {{ $entry->title }}
                    </button>
                @endforeach
                @if ($dayEntries->count() > 3)
                    <p class="text-[10px] text-slate-500">+{{ $dayEntries->count() - 3 }} more</p>
                @endif
            </div>
        </div>
        @php $cursor->addDay(); @endphp
    @endwhile
</div>
