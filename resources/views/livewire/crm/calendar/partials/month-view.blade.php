<div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-slate-100 to-slate-200">
    <div class="grid grid-cols-7 gap-px border-b border-slate-200">
        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
            <div class="bg-gradient-to-b from-slate-50 to-slate-100/80 px-2 py-2 text-center text-xs font-bold uppercase tracking-wide text-slate-500">{{ $day }}</div>
        @endforeach
    </div>

    <div class="space-y-px">
        @foreach ($monthWeeks as $week)
            <div class="bg-white">
                <div class="grid grid-cols-7 gap-px bg-slate-100">
                    @foreach ($week['days'] as $day)
                        @php
                            $dateKey = $day->format('Y-m-d');
                            $isCurrentMonth = $day->month === $focus->month;
                            $isToday = $day->isToday();
                            $isSelected = $selectedDay === $dateKey;
                        @endphp
                        <div
                            role="button"
                            tabindex="0"
                            wire:click="openDay('{{ $dateKey }}')"
                            wire:keydown.enter.prevent="openDay('{{ $dateKey }}')"
                            wire:keydown.space.prevent="openDay('{{ $dateKey }}')"
                            @class([
                                'group min-h-10 cursor-pointer bg-gradient-to-br from-white to-slate-50/80 px-2 pt-2 transition',
                                'hover:z-10 hover:ring-2 hover:ring-inset hover:ring-teal-500 focus-visible:z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-teal-500',
                                'from-slate-50/90 to-slate-100/60 text-slate-400' => ! $isCurrentMonth,
                                'from-teal-50/80 via-white to-emerald-50/50 ring-2 ring-inset ring-teal-400' => $isToday && ! $isSelected,
                                'ring-2 ring-inset ring-teal-600' => $isSelected,
                            ])
                            aria-label="View schedule for {{ $day->format('F j, Y') }}"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold {{ $isToday ? 'text-teal-700' : 'text-slate-600' }}">{{ $day->day }}</span>
                                @if ($canManage)
                                    <button
                                        type="button"
                                        class="rounded px-1 text-[10px] font-semibold text-teal-600 opacity-0 transition group-hover:opacity-100 hover:bg-teal-50 hover:text-teal-800"
                                        wire:click.stop="openCreate('{{ $dateKey }}')"
                                        title="Add event"
                                        aria-label="Add event on {{ $day->format('F j, Y') }}"
                                    >+</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @foreach ($week['lanes'] as $lane)
                    <div class="grid grid-cols-7 gap-px px-0.5 py-0.5">
                        @foreach ($lane as $segment)
                            @php $entry = $segment['entry']; @endphp
                            <button
                                type="button"
                                style="grid-column: {{ $segment['start_col'] + 1 }} / span {{ $segment['span'] }};"
                                class="z-[1] truncate border px-1.5 py-0.5 text-left text-[10px] font-semibold shadow-sm {{ $typeColors[$entry->color] ?? 'bg-teal-100 text-teal-800 border-teal-200' }} {{ $segment['continues_before'] ? 'rounded-l-none border-l-0' : 'rounded-l-md' }} {{ $segment['continues_after'] ? 'rounded-r-none border-r-0' : 'rounded-r-md' }}"
                                wire:click.stop="openDetails('{{ $entry->kind }}', {{ $entry->id }})"
                                title="{{ $entry->title }}"
                            >
                                @if ($segment['continues_before'])
                                    <span class="mr-0.5 opacity-70">‹</span>
                                @endif
                                {{ $entry->title }}
                                @if ($segment['continues_after'])
                                    <span class="ml-0.5 opacity-70">›</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endforeach

                <div class="grid grid-cols-7 gap-px bg-slate-100 pb-px">
                    @foreach ($week['days'] as $day)
                        @php
                            $dateKey = $day->format('Y-m-d');
                            $dayEntries = $week['timed_by_date'][$dateKey] ?? collect();
                            $isCurrentMonth = $day->month === $focus->month;
                        @endphp
                        <div
                            role="button"
                            tabindex="0"
                            wire:click="openDay('{{ $dateKey }}')"
                            @class([
                                'min-h-16 cursor-pointer space-y-1 bg-gradient-to-br from-white to-slate-50/80 p-1.5 transition hover:bg-teal-50/40',
                                'from-slate-50/90 to-slate-100/60' => ! $isCurrentMonth,
                            ])
                        >
                            @foreach ($dayEntries->take(3) as $entry)
                                <button
                                    type="button"
                                    class="block w-full truncate rounded-md border px-1.5 py-0.5 text-left text-[10px] font-semibold {{ $typeColors[$entry->color] ?? 'bg-teal-100 text-teal-800 border-teal-200' }}"
                                    wire:click.stop="openDetails('{{ $entry->kind }}', {{ $entry->id }})"
                                >
                                    <span class="opacity-70">{{ $entry->start_at?->format('g:ia') }}</span>
                                    {{ $entry->title }}
                                </button>
                            @endforeach
                            @if ($dayEntries->count() > 3)
                                <p class="text-[10px] font-semibold text-teal-700">+{{ $dayEntries->count() - 3 }} more</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
