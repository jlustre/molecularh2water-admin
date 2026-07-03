@props([
    'href' => null,
    'portalLabel' => null,
    'size' => 'md',
    'navigate' => true,
    'align' => 'left',
])

@php
    $sizes = [
        'sm' => ['h-10', 'max-w-[220px]'],
        'md' => ['h-14', 'max-w-[260px]'],
        'lg' => ['h-16', 'max-w-[300px]'],
        'xl' => ['h-20', 'max-w-[340px]'],
        'sidebar' => ['h-14', 'max-w-[240px]'],
    ];

    [$heightClass, $maxWidthClass] = $sizes[$size] ?? $sizes['md'];
    $tag = $href ? 'a' : 'div';
    $isCentered = $align === 'center';
    $containerClass = $isCentered
        ? 'flex w-full max-w-full flex-col items-center justify-center gap-2 text-center'
        : 'inline-flex max-w-full flex-col gap-2';
    $imageAlignClass = $isCentered ? 'mx-auto object-center' : 'object-left';
@endphp

<{{ $tag }}
    @if ($href)
        href="{{ $href }}"
        @if ($navigate)
            wire:navigate
        @endif
    @endif
    {{ $attributes->merge(['class' => "{$containerClass} rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300"]) }}
>
    <img
        src="{{ asset('images/brand/h2-systems-logo.png') }}"
        alt="H2 Systems — Endless Energy, Cellular Renewal"
        class="{{ $heightClass }} {{ $maxWidthClass }} w-auto object-contain {{ $imageAlignClass }}"
    >

    @if ($portalLabel)
        <span @class([
            'block text-xs font-semibold uppercase tracking-[0.22em] text-teal-700',
            'text-center' => $isCentered,
        ])>
            {{ $portalLabel }}
        </span>
    @endif
</{{ $tag }}>
