@props([
    'title' => null,
    'tone' => 'teal',
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

    $panelClasses = $toneClasses[$tone] ?? $toneClasses['teal'];
@endphp

<div {{ $attributes->merge(['class' => "overflow-hidden rounded-2xl border p-4 shadow-sm {$panelClasses}"]) }}>
    @if ($title)
        <h2 class="text-sm font-bold text-slate-900">{{ $title }}</h2>
    @endif

    {{ $slot }}
</div>
