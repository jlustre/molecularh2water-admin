@props([
    'card',
])

@php
    $toneClasses = [
        'teal' => 'from-teal-500/15 to-teal-600/5 text-teal-700 ring-teal-200/60',
        'emerald' => 'from-emerald-500/15 to-emerald-600/5 text-emerald-700 ring-emerald-200/60',
        'cyan' => 'from-cyan-500/15 to-cyan-600/5 text-cyan-700 ring-cyan-200/60',
        'amber' => 'from-amber-500/15 to-amber-600/5 text-amber-700 ring-amber-200/60',
        'indigo' => 'from-indigo-500/15 to-indigo-600/5 text-indigo-700 ring-indigo-200/60',
        'slate' => 'from-slate-500/15 to-slate-600/5 text-slate-700 ring-slate-200/60',
    ];

    $iconTone = $toneClasses[$card->tone] ?? $toneClasses['teal'];
    $classes = 'group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition';
    $interactiveClasses = 'hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md';
@endphp

@if ($card->route)
    <a href="{{ $card->route }}" @if (! str_contains($card->route, '/admin')) wire:navigate @endif @class([$classes, $interactiveClasses])>
@else
    <div @class([$classes])>
@endif
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-slate-500">{{ $card->label }}</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $card->value }}</p>
            @if ($card->hint)
                <p class="mt-2 text-xs leading-5 text-slate-500">{{ $card->hint }}</p>
            @endif
        </div>

        @if ($card->icon)
            <span @class([
                'flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br ring-1',
                $iconTone,
            ])>
                @include('components.portal.stat-card-icon', ['icon' => $card->icon])
            </span>
        @endif
    </div>

    @if ($card->route)
        <span class="mt-4 inline-flex text-xs font-bold uppercase tracking-[0.18em] text-teal-700 opacity-0 transition group-hover:opacity-100">
            Open →
        </span>
    @endif
@if ($card->route)
    </a>
@else
    </div>
@endif
