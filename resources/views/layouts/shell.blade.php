<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'App' }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
    {{-- Layout geometry via CSS variables (not Tailwind arbitrary values) so chrome always positions correctly. --}}
    <style>
        :root {
            --shell-brand-w: 280px;
            --shell-header-h: 5rem; /* 80px / h-20 */
            /* Above brand (70) / topbar (60) / sidebar (50) */
            --shell-loading-z: 90;
            --shell-modal-z: 100;
            --shell-modal-nested-z: 110;
        }

        /* Portal/dashboard modals must sit above fixed shell chrome */
        .shell-modal-overlay {
            z-index: var(--shell-modal-z);
        }

        .shell-modal-overlay-nested {
            z-index: var(--shell-modal-nested-z);
        }

        .shell-loading-overlay {
            z-index: var(--shell-loading-z);
        }

        [data-shell-brand] {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 70;
            display: flex;
            align-items: center;
            width: var(--shell-brand-w);
            height: var(--shell-header-h);
        }

        [data-shell-topbar] {
            position: fixed;
            top: 0;
            left: var(--shell-brand-w);
            right: 0;
            z-index: 60;
            height: var(--shell-header-h);
        }

        [data-shell-sidebar] {
            position: fixed;
            top: var(--shell-header-h);
            left: 0;
            bottom: 0;
            z-index: 50;
            width: var(--shell-brand-w);
        }

        [data-shell-main] {
            min-height: 100vh;
            padding-top: var(--shell-header-h);
            padding-left: 0;
        }

        @media (min-width: 1024px) {
            [data-shell-main].is-sidebar-open {
                padding-left: var(--shell-brand-w);
            }
        }

        /*
         * Sidebar text/icon colors via explicit CSS (not Tailwind opacity utilities),
         * so inactive links stay legible on the dark teal panel even when those
         * utilities are missing from the compiled stylesheet.
         */
        [data-shell-sidebar] .shell-nav-section-btn:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        [data-shell-sidebar] .shell-nav-section-label,
        [data-shell-sidebar] .shell-nav-section-chevron {
            color: rgba(153, 246, 228, 0.55);
        }

        [data-shell-sidebar] .shell-nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-color: transparent;
        }

        [data-shell-sidebar] .shell-nav-link:hover {
            color: rgba(255, 255, 255, 0.95);
            background: rgba(94, 234, 212, 0.1);
        }

        [data-shell-sidebar] .shell-nav-link.is-active {
            color: #ffffff;
            font-weight: 500;
            border-color: rgba(94, 234, 212, 0.4);
            background: linear-gradient(135deg, rgba(45, 212, 191, 0.3), rgba(15, 118, 110, 0.15));
            box-shadow: 0 0 14px rgba(20, 184, 166, 0.18);
        }

        [data-shell-sidebar] .shell-nav-link.is-active:hover {
            color: #ffffff;
            background: linear-gradient(135deg, rgba(45, 212, 191, 0.35), rgba(15, 118, 110, 0.2));
        }

        [data-shell-sidebar] .shell-nav-badge {
            color: rgba(255, 255, 255, 0.55);
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.1);
        }

        [data-shell-sidebar] .shell-nav-badge.is-live {
            color: #99f6e4;
            border-color: rgba(94, 234, 212, 0.4);
            background: rgba(94, 234, 212, 0.2);
        }

        [data-shell-sidebar] .shell-nav-badge.is-warn {
            color: #fcd34d;
            border-color: rgba(252, 211, 77, 0.3);
            background: rgba(252, 211, 77, 0.15);
        }

        [data-shell-sidebar] .shell-nav-profile {
            border-color: rgba(153, 246, 228, 0.16);
            background: rgba(255, 255, 255, 0.04);
        }

        [data-shell-sidebar] .shell-nav-profile-name {
            color: #ffffff;
        }

        [data-shell-sidebar] .shell-nav-profile-email {
            color: rgba(153, 246, 228, 0.75);
        }

        [data-shell-sidebar] .shell-nav-sign-out {
            color: rgba(255, 255, 255, 0.8);
            border-color: rgba(94, 234, 212, 0.2);
            background: rgba(255, 255, 255, 0.06);
        }

        [data-shell-sidebar] .shell-nav-sign-out:hover {
            color: rgba(255, 255, 255, 0.95);
            border-color: rgba(94, 234, 212, 0.35);
            background: rgba(94, 234, 212, 0.15);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900" x-data="layoutSidebar()">
    <x-portal.page-loading />

    {{-- Brand: always visible, independent of sidebarOpen --}}
    <x-shell.brand />

    {{-- Topbar: always starts at brand edge, fills to screen right --}}
    <header data-shell-topbar>
        <x-shell.topbar />
    </header>

    {{-- Mobile backdrop --}}
    <div
        x-cloak
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden"
        @click="closeSidebar()"
        aria-hidden="true"
    ></div>

    {{-- Sidebar: below brand, full remaining height; toggles independently of brand --}}
    <aside
        x-cloak
        x-show="sidebarOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="-translate-x-full opacity-0"
        data-shell-sidebar
    >
        <x-shell.sidebar />
    </aside>

    {{-- Main: always pad-top for header; pad-left for sidebar only when open on lg --}}
    <main
        data-shell-main
        :class="{ 'is-sidebar-open': sidebarOpen }"
    >
        @if (! empty($showVerificationBanner) && request()->routeIs('dashboard', 'profile') && auth()->check() && ! auth()->user()->hasVerifiedEmail())
            <div class="px-4 pt-4 sm:px-6 sm:pt-6 lg:px-8">
                <livewire:email-verification-banner />
            </div>
        @endif

        <div @class(['p-6' => empty($flushMainPadding)])>
            @isset($slot)
                {{ $slot }}
            @endisset
            @yield('content')
        </div>
    </main>

    @stack('scripts')
    @livewireScripts
</body>
</html>
