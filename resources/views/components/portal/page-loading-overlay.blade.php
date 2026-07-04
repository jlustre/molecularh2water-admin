@props([
    'scope' => 'data-portal-page-scope',
    'message' => 'Loading...',
    'fullscreen' => false,
])

@php
    $wrapperClass = $fullscreen
        ? 'pointer-events-none shell-loading-overlay fixed inset-0 items-center justify-center bg-slate-900/40 backdrop-blur-[1px]'
        : 'pointer-events-none absolute inset-0 z-20 items-center justify-center rounded-2xl bg-white/80 backdrop-blur-[1px]';
@endphp

<div
    x-data="portalPageLoadingOverlay(@js($scope))"
    x-show="visible"
    x-transition.opacity.duration.150ms
    style="display: none;"
    {{ $attributes->merge(['class' => 'flex '.$wrapperClass]) }}
    aria-live="polite"
    aria-busy="true"
    role="status"
    data-portal-page-loading-overlay
>
    <x-ui.loading-card :message="$message" />
</div>
