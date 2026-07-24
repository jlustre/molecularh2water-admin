<div>
    <div class="overflow-hidden rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50 via-white to-violet-50/70 p-5 shadow-sm sm:p-6">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-indigo-600">Coaching lens</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Performance summary</h2>
            <p class="mt-1 text-sm text-slate-500">
                Weekly and monthly totals for
                <span class="font-semibold text-slate-700">{{ $subject->name }}</span>
            </p>
        </div>

        @if ($insight)
            <div class="mt-4 rounded-xl border border-indigo-100 bg-white/80 px-4 py-3 text-sm text-indigo-900 shadow-sm">
                {{ $insight }}
            </div>
        @endif

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @foreach ([
                [
                    'key' => 'week',
                    'title' => $weekTitle,
                    'label' => $weekLabel,
                    'totals' => $weekTotals,
                    'accent' => 'teal',
                    'prev' => 'previousWeek',
                    'next' => 'nextWeek',
                    'canNext' => $canGoNextWeek,
                    'prevLabel' => 'Previous week',
                    'nextLabel' => 'Next week',
                ],
                [
                    'key' => 'month',
                    'title' => $monthTitle,
                    'label' => $monthLabel,
                    'totals' => $monthTotals,
                    'accent' => 'violet',
                    'prev' => 'previousMonth',
                    'next' => 'nextMonth',
                    'canNext' => $canGoNextMonth,
                    'prevLabel' => 'Previous month',
                    'nextLabel' => 'Next month',
                ],
            ] as $block)
                @php
                    $accent = $block['accent'];
                    $cardRing = $accent === 'teal'
                        ? 'border-teal-200/80 from-teal-50/90 via-white to-emerald-50/50'
                        : 'border-violet-200/80 from-violet-50/90 via-white to-indigo-50/50';
                    $badge = $accent === 'teal'
                        ? 'bg-teal-100 text-teal-800 ring-teal-200'
                        : 'bg-violet-100 text-violet-800 ring-violet-200';
                    $metricTint = $accent === 'teal'
                        ? 'border-teal-100/80 bg-white/80 hover:border-teal-200'
                        : 'border-violet-100/80 bg-white/80 hover:border-violet-200';
                    $numberColor = $accent === 'teal' ? 'text-teal-800' : 'text-violet-800';
                    $navBtn = $accent === 'teal'
                        ? 'border-teal-200 bg-white text-teal-800 hover:bg-teal-50 disabled:border-slate-200 disabled:bg-slate-50 disabled:text-slate-300'
                        : 'border-violet-200 bg-white text-violet-800 hover:bg-violet-50 disabled:border-slate-200 disabled:bg-slate-50 disabled:text-slate-300';
                @endphp
                <section class="rounded-2xl border bg-gradient-to-br p-4 shadow-sm {{ $cardRing }}">
                    <div class="mb-4 flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-slate-900">{{ $block['title'] }}</h3>
                            <p class="text-xs text-slate-500">{{ $block['label'] }}</p>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 ring-inset {{ $badge }}">
                            {{ array_sum($block['totals']) }} total
                        </span>
                    </div>

                    <div class="mb-4 flex items-center justify-between gap-2">
                        <button
                            type="button"
                            wire:click="{{ $block['prev'] }}"
                            class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition {{ $navBtn }}"
                            aria-label="{{ $block['prevLabel'] }}"
                        >
                            <span aria-hidden="true">←</span>
                            Prev
                        </button>
                        <button
                            type="button"
                            wire:click="{{ $block['next'] }}"
                            @disabled(! $block['canNext'])
                            class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition {{ $navBtn }}"
                            aria-label="{{ $block['nextLabel'] }}"
                        >
                            Next
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($labels as $key => $label)
                            <div class="rounded-xl border px-3 py-3 transition {{ $metricTint }}">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                                <p class="mt-1 text-2xl font-black tabular-nums {{ $numberColor }}">{{ $block['totals'][$key] ?? 0 }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
