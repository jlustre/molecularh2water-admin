@props([
    'message' => 'Loading calendar...',
])

<div
    x-data="crmCalendarLoadingOverlay()"
    x-show="visible"
    x-transition.opacity.duration.150ms
    style="display: none;"
    class="pointer-events-none fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/25 backdrop-blur-[1px]"
    aria-live="polite"
    aria-busy="true"
    role="status"
    data-crm-calendar-loading-overlay
>
    <x-ui.loading-card :message="$message" />
</div>
