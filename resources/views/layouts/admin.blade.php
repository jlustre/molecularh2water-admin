<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: false }" x-cloak>
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
        <x-admin.sidebar />

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
