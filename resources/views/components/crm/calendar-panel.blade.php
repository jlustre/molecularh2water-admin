@props([
    'title' => null,
    'tone' => 'teal',
    'panelKey' => null,
    'count' => null,
])

@php
    $toneClasses = [
        'teal' => 'border-teal-200/80 bg-gradient-to-br from-teal-50 via-white to-emerald-50/70',
        'emerald' => 'border-emerald-200/80 bg-gradient-to-br from-emerald-50 via-white to-teal-50/60',
        'violet' => 'border-violet-200/80 bg-gradient-to-br from-violet-50 via-white to-indigo-50/60',
        'blue' => 'border-blue-200/80 bg-gradient-to-br from-blue-50 via-white to-cyan-50/60',
        'rose' => 'border-rose-200/80 bg-gradient-to-br from-rose-50 via-white to-orange-50/50',
        'amber' => 'border-amber-200/80 bg-gradient-to-br from-amber-50 via-white to-yellow-50/50',
        'slate' => 'border-slate-200/80 bg-gradient-to-br from-slate-50 via-white to-slate-100/70',
        'indigo' => 'border-indigo-200/80 bg-gradient-to-br from-indigo-50 via-white to-blue-50/50',
    ];

    $badgeClasses = [
        'teal' => 'bg-teal-100 text-teal-800 ring-teal-200/80',
        'emerald' => 'bg-emerald-100 text-emerald-800 ring-emerald-200/80',
        'violet' => 'bg-violet-100 text-violet-800 ring-violet-200/80',
        'blue' => 'bg-blue-100 text-blue-800 ring-blue-200/80',
        'rose' => 'bg-rose-100 text-rose-800 ring-rose-200/80',
        'amber' => 'bg-amber-100 text-amber-900 ring-amber-200/80',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200/80',
        'indigo' => 'bg-indigo-100 text-indigo-800 ring-indigo-200/80',
    ];

    $panelClasses = $toneClasses[$tone] ?? $toneClasses['teal'];
    $countBadgeClasses = $badgeClasses[$tone] ?? $badgeClasses['teal'];
    $storageKey = $panelKey ? 'crm-cal-panel-'.$panelKey : null;
    $showCount = $count !== null;
@endphp

<div
    {{ $attributes->merge(['class' => "overflow-hidden rounded-2xl border p-4 shadow-sm {$panelClasses}"]) }}
    @if ($storageKey)
        x-data="{
            open: localStorage.getItem(@js($storageKey)) !== '0',
            toggle() {
                this.open = ! this.open;
                localStorage.setItem(@js($storageKey), this.open ? '1' : '0');
            }
        }"
    @endif
>
    @if ($title)
        <div class="flex items-start justify-between gap-2">
            <div class="flex min-w-0 flex-1 items-center gap-2">
                <h2 class="min-w-0 text-sm font-bold text-slate-900">{{ $title }}</h2>
                @if ($showCount)
                    <span
                        class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[11px] font-bold tabular-nums ring-1 ring-inset {{ $countBadgeClasses }}"
                        aria-label="{{ (int) $count }} {{ \Illuminate\Support\Str::plural('item', (int) $count) }}"
                    >
                        {{ (int) $count }}
                    </span>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-1">
                @isset($actions)
                    {{ $actions }}
                @endisset
                @if ($storageKey)
                    <button
                        type="button"
                        class="inline-flex size-7 items-center justify-center rounded-lg border border-slate-200/80 bg-white/70 text-slate-600 transition hover:bg-white hover:text-slate-900"
                        @click="toggle()"
                        :aria-expanded="open.toString()"
                        :title="open ? 'Collapse' : 'Expand'"
                        :aria-label="open ? 'Collapse panel' : 'Expand panel'"
                    >
                        <svg class="size-3.5 transition-transform" :class="{ 'rotate-180': ! open }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    @endif

    <div @if ($storageKey) x-show="open" x-cloak @endif>
        {{ $slot }}
    </div>
</div>
