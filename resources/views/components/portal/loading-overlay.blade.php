@props([
    'target' => null,
    'message' => 'Loading...',
    'fullscreen' => false,
])

@php
    $wrapperClass = $fullscreen
        ? 'shell-loading-overlay fixed inset-0 items-center justify-center bg-slate-900/40 backdrop-blur-[1px]'
        : 'absolute inset-0 z-20 items-center justify-center rounded-2xl bg-white/80 backdrop-blur-[1px]';
@endphp

<div
    wire:loading.delay.flex
    @if (! empty($target)) wire:target="{{ $target }}" @endif
    {{ $attributes->merge(['class' => $wrapperClass]) }}
    aria-live="polite"
    aria-busy="true"
    role="status"
>
    <x-ui.loading-card :message="$message" />
</div>
