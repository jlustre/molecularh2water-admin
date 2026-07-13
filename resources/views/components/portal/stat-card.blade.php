@props([
    'card',
])

@php
    $panelClasses = [
        'teal' => 'border-teal-200/80 bg-gradient-to-br from-teal-100 via-teal-50 to-white text-teal-950 hover:border-teal-300',
        'red' => 'border-red-200/80 bg-gradient-to-br from-red-100 via-red-50 to-white text-red-950 hover:border-red-300',
        'orange' => 'border-orange-200/80 bg-gradient-to-br from-orange-100 via-orange-50 to-white text-orange-950 hover:border-orange-300',
        'yellow' => 'border-yellow-200/80 bg-gradient-to-br from-yellow-100 via-yellow-50 to-white text-yellow-950 hover:border-yellow-300',
        'green' => 'border-green-200/80 bg-gradient-to-br from-green-100 via-green-50 to-white text-green-950 hover:border-green-300',
        'blue' => 'border-blue-200/80 bg-gradient-to-br from-blue-100 via-blue-50 to-white text-blue-950 hover:border-blue-300',
        'indigo' => 'border-indigo-200/80 bg-gradient-to-br from-indigo-100 via-indigo-50 to-white text-indigo-950 hover:border-indigo-300',
        'violet' => 'border-violet-200/80 bg-gradient-to-br from-violet-100 via-violet-50 to-white text-violet-950 hover:border-violet-300',
        'emerald' => 'border-emerald-200/80 bg-gradient-to-br from-emerald-100 via-emerald-50 to-white text-emerald-950 hover:border-emerald-300',
        'cyan' => 'border-cyan-200/80 bg-gradient-to-br from-cyan-100 via-cyan-50 to-white text-cyan-950 hover:border-cyan-300',
        'amber' => 'border-amber-200/80 bg-gradient-to-br from-amber-100 via-amber-50 to-white text-amber-950 hover:border-amber-300',
        'rose' => 'border-rose-200/80 bg-gradient-to-br from-rose-100 via-rose-50 to-white text-rose-950 hover:border-rose-300',
        'slate' => 'border-slate-200/80 bg-gradient-to-br from-slate-100 via-slate-50 to-white text-slate-950 hover:border-slate-300',
    ];

    $iconToneClasses = [
        'teal' => 'from-teal-500/20 to-teal-600/10 text-teal-700 ring-teal-200/70',
        'red' => 'from-red-500/20 to-red-600/10 text-red-700 ring-red-200/70',
        'orange' => 'from-orange-500/20 to-orange-600/10 text-orange-700 ring-orange-200/70',
        'yellow' => 'from-yellow-500/20 to-yellow-600/10 text-yellow-700 ring-yellow-200/70',
        'green' => 'from-green-500/20 to-green-600/10 text-green-700 ring-green-200/70',
        'blue' => 'from-blue-500/20 to-blue-600/10 text-blue-700 ring-blue-200/70',
        'indigo' => 'from-indigo-500/20 to-indigo-600/10 text-indigo-700 ring-indigo-200/70',
        'violet' => 'from-violet-500/20 to-violet-600/10 text-violet-700 ring-violet-200/70',
        'emerald' => 'from-emerald-500/20 to-emerald-600/10 text-emerald-700 ring-emerald-200/70',
        'cyan' => 'from-cyan-500/20 to-cyan-600/10 text-cyan-700 ring-cyan-200/70',
        'amber' => 'from-amber-500/20 to-amber-600/10 text-amber-700 ring-amber-200/70',
        'rose' => 'from-rose-500/20 to-rose-600/10 text-rose-700 ring-rose-200/70',
        'slate' => 'from-slate-500/20 to-slate-600/10 text-slate-700 ring-slate-200/70',
    ];

    $labelToneClasses = [
        'teal' => 'text-teal-700/80',
        'red' => 'text-red-700/80',
        'orange' => 'text-orange-700/80',
        'yellow' => 'text-yellow-700/80',
        'green' => 'text-green-700/80',
        'blue' => 'text-blue-700/80',
        'indigo' => 'text-indigo-700/80',
        'violet' => 'text-violet-700/80',
        'emerald' => 'text-emerald-700/80',
        'cyan' => 'text-cyan-700/80',
        'amber' => 'text-amber-700/80',
        'rose' => 'text-rose-700/80',
        'slate' => 'text-slate-600/80',
    ];

    $panel = $panelClasses[$card->tone] ?? $panelClasses['teal'];
    $iconTone = $iconToneClasses[$card->tone] ?? $iconToneClasses['teal'];
    $labelTone = $labelToneClasses[$card->tone] ?? $labelToneClasses['teal'];
    $classes = 'group relative flex w-full flex-col overflow-hidden rounded-xl border p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md '.$panel;
    $isInteractive = filled($card->route) || filled($card->action);
@endphp

@if ($card->action)
    <button type="button" onclick="Livewire.dispatch(@js($card->action))" @class([$classes])>
@elseif ($card->route)
    <a href="{{ $card->route }}" @if (! str_contains($card->route, '/admin')) wire:navigate @endif @class([$classes])>
@else
    <div @class([$classes])>
@endif
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
            <p @class(['text-xs font-semibold', $labelTone])>{{ $card->label }}</p>
            <p class="mt-1 text-2xl font-black tracking-tight leading-none">{{ $card->value }}</p>
            @if ($card->hint)
                <p class="mt-1 text-[11px] leading-4 opacity-70">{{ $card->hint }}</p>
            @endif
        </div>

        @if ($card->icon)
            <span @class([
                'flex size-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br ring-1',
                $iconTone,
            ])>
                @include('components.portal.stat-card-icon', ['icon' => $card->icon])
            </span>
        @endif
    </div>

    @if ($isInteractive)
        <span @class(['mt-2 inline-flex text-[10px] font-bold uppercase tracking-[0.16em] opacity-0 transition group-hover:opacity-100', $labelTone])>
            Open →
        </span>
    @endif
@if ($card->action)
    </button>
@elseif ($card->route)
    </a>
@else
    </div>
@endif
