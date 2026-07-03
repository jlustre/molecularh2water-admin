<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900">
    <x-portal.page-loading />

    <div class="flex h-screen" x-data="layoutSidebar()" x-cloak>
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden"
            @click="closeSidebar()"
            aria-hidden="true"
        ></div>

        <aside
            x-show="sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="-translate-x-full opacity-0"
            class="fixed inset-y-0 left-0 z-50 w-[280px] shrink-0 lg:relative lg:z-auto"
        >
            <x-admin.sidebar />
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <x-admin.topbar />

            <main class="flex-1 overflow-y-auto p-6">
                @if (isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>
        </div>
    </div>
    @stack('scripts')
    @livewireScripts
</body>
</html>
