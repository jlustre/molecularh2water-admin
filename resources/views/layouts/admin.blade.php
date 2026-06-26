<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: window.innerWidth >= 1024 }" x-cloak>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="//unpkg.com/alpinejs"></script>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden"
            @click="sidebarOpen = false"
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
            class="fixed inset-y-0 left-0 z-50 shrink-0 lg:static lg:z-auto"
        >
            <x-admin.sidebar />
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <x-admin.topbar />

            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                @if (isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
