{{-- Fixed brand panel: always visible, independent of sidebarOpen. Geometry from [data-shell-brand] in shell layout. --}}
<div
    class="border-b border-teal-600/10 bg-white px-4"
    data-shell-brand
>
    <a
        href="{{ \App\Support\Navigation\AppNavigation::homeUrl() }}"
        @if (! request()->routeIs('admin.*')) wire:navigate @endif
        class="flex min-w-0 items-center rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300"
    >
        <img
            src="{{ asset('images/brand/h2-systems-logo.png') }}"
            alt="H2 Systems — Endless Energy, Cellular Renewal"
            class="block h-14 w-auto max-w-full object-contain object-left"
        >
    </a>
</div>
