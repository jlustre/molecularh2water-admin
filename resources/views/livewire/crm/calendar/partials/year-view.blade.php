@php
    $year = $focus->year;
    $months = collect(range(1, 12))->map(fn (int $month) => \Carbon\Carbon::create($year, $month, 1)->startOfDay());
@endphp

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @foreach ($months as $month)
        @php
            $monthStart = $month->copy()->startOfMonth();
            $gridStart = $monthStart->copy()->startOfWeek();
            $gridEnd = $month->copy()->endOfMonth()->endOfWeek();
            $cursor = $gridStart->copy();
            $monthKey = $monthStart->format('Y-m-d');
            $isCurrentMonth = $monthStart->isSameMonth(now());
            $monthCount = collect($countsByDate)
                ->filter(fn ($count, $date) => str_starts_with((string) $date, $monthStart->format('Y-m')))
                ->sum();
        @endphp
        <div @class([
            'rounded-2xl border bg-gradient-to-br from-white to-slate-50/80 p-3 shadow-sm',
            'border-teal-300 ring-1 ring-teal-200' => $isCurrentMonth,
            'border-slate-200' => ! $isCurrentMonth,
        ])>
            <div class="mb-2 flex items-center justify-between gap-2">
                <button
                    type="button"
                    class="text-sm font-bold text-slate-900 transition hover:text-teal-700"
                    wire:click="openMonth('{{ $monthKey }}')"
                    title="Open {{ $monthStart->format('F Y') }}"
                >
                    {{ $monthStart->format('F') }}
                </button>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold tabular-nums text-slate-600 ring-1 ring-inset ring-slate-200">
                    {{ (int) $monthCount }}
                </span>
            </div>

            <div class="grid grid-cols-7 gap-0.5">
                @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dayLabel)
                    <div class="py-0.5 text-center text-[9px] font-bold uppercase tracking-wide text-slate-400">{{ $dayLabel }}</div>
                @endforeach

                @while ($cursor <= $gridEnd)
                    @php
                        $dateKey = $cursor->format('Y-m-d');
                        $inMonth = $cursor->month === $monthStart->month;
                        $count = (int) ($countsByDate[$dateKey] ?? 0);
                        $isToday = $cursor->isToday();
                        $isSelected = $selectedDay === $dateKey;
                    @endphp
                    @if ($inMonth)
                        <button
                            type="button"
                            wire:click="openDay('{{ $dateKey }}')"
                            @class([
                                'relative flex aspect-square items-center justify-center rounded-md text-[10px] font-semibold transition',
                                'text-slate-700 hover:bg-teal-50 hover:ring-1 hover:ring-inset hover:ring-teal-400',
                                'bg-teal-100 text-teal-800 ring-1 ring-inset ring-teal-400' => $isToday && ! $isSelected,
                                'bg-teal-600 text-white' => $isSelected,
                                'font-bold' => $count > 0 && ! $isSelected,
                            ])
                            title="{{ $cursor->format('M j') }}{{ $count ? ' · '.$count.' item'.($count === 1 ? '' : 's') : '' }}"
                            aria-label="{{ $cursor->format('F j, Y') }}{{ $count ? ', '.$count.' items' : '' }}"
                        >
                            {{ $cursor->day }}
                            @if ($count > 0 && ! $isSelected)
                                <span @class([
                                    'absolute bottom-0.5 left-1/2 size-1 -translate-x-1/2 rounded-full',
                                    'bg-teal-600' => ! $isToday,
                                    'bg-teal-800' => $isToday,
                                ])></span>
                            @endif
                        </button>
                    @else
                        <div class="aspect-square"></div>
                    @endif
                    @php $cursor->addDay(); @endphp
                @endwhile
            </div>
        </div>
    @endforeach
</div>
