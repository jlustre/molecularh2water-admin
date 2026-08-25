@php
    $response = $response ?? null;
    $classes = $size ?? 'md';
    $padding = $classes === 'sm'
        ? 'px-2 py-0.5 text-[0.65rem]'
        : 'px-3 py-1 text-xs';
@endphp
@if ($response)
    <span class="inline-flex items-center rounded-full font-bold uppercase tracking-wide {{ $padding }} {{ $response->badgeClasses() }}">
        {{ $response->label() }}
    </span>
@endif
