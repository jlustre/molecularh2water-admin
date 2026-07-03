<div
    x-data="{ navigating: false }"
    x-on:livewire:navigate.window="navigating = true"
    x-on:livewire:navigated.window="navigating = false"
    x-show="navigating"
    x-transition.opacity.duration.200ms
    class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/30 backdrop-blur-sm"
    style="display: none;"
    aria-live="polite"
    aria-busy="true"
    role="status"
>
    <x-ui.loading-card message="Loading page..." />
</div>
