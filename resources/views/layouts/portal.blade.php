<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal' }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-screen bg-[#041f1e] font-sans text-white antialiased selection:bg-teal-300 selection:text-[#031a19]">
    <div
        class="relative min-h-screen overflow-hidden bg-[linear-gradient(135deg,#041f1e_0%,#062926_48%,#031a19_100%)]"
        x-cloak
        x-data="layoutSidebar()"
    >
        <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div class="relative flex min-h-screen">
            <div
                aria-hidden="true"
                class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden"
                x-show="sidebarOpen"
                x-transition.opacity
                @click="closeSidebar()"
            ></div>

            @persist('portal-sidebar')
            <div
                class="fixed inset-y-0 left-0 z-50 w-72 shrink-0 lg:relative lg:z-auto"
                x-show="sidebarOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="-translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="-translate-x-full opacity-0"
            >
                <x-portal.sidebar />
            </div>
            @endpersist

            <main class="flex min-w-0 flex-1 flex-col">
                <x-portal.page-loading />

                <header class="sticky top-0 z-[60] border-b border-teal-100/20 bg-white/[0.92] text-slate-900 shadow-lg backdrop-blur-xl lg:z-40">
                    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-8 lg:px-10">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-sidebar.toggle />
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Associate Portal</p>
                                <h1 class="truncate text-lg font-bold text-slate-950">{{ $header ?? 'Workspace' }}</h1>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @if (auth()->user()?->canAccessPortal() || auth()->user()?->canAccessAdmin())
                                <livewire:business-line-switcher />
                            @endif
                            @if (auth()->user()?->canAccessAdmin())
                                <a class="hidden rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 sm:inline-flex" href="{{ route('admin.dashboard') }}">
                                    Admin
                                </a>
                            @endif
                            <x-user-menu />
                        </div>
                    </div>
                </header>

                <div class="flex-1 overflow-y-auto bg-slate-50 text-slate-900">
                    @if (request()->routeIs('dashboard', 'profile') && auth()->check() && ! auth()->user()->hasVerifiedEmail())
                        <div class="px-4 pt-4 sm:px-6 sm:pt-6 lg:px-8">
                            <livewire:email-verification-banner />
                        </div>
                    @endif

                    @isset($slot)
                        {{ $slot }}
                    @endisset
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
    @livewireScripts
</body>
</html>
