@php
    $user = auth()->user();
    $userName = $user?->name ?? 'Admin User';
    $userEmail = $user?->email ?? 'admin@molecularh2water.com';
    $initials = collect(explode(' ', trim($userName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->join('');
    $initials = $initials !== '' ? mb_strtoupper($initials) : 'AU';

    $sidebarLinks = [
        ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => true, 'icon' => 'dashboard'],
        ['label' => 'Resources', 'href' => route('resources'), 'active' => false, 'icon' => 'resources'],
        ['label' => 'Admin Portal', 'href' => route('admin.dashboard'), 'active' => false, 'icon' => 'admin'],
        ['label' => 'Profile', 'href' => route('profile'), 'active' => false, 'icon' => 'profile'],
        ['label' => 'Settings', 'href' => route('admin.settings'), 'active' => false, 'icon' => 'settings'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Dashboard | {{ config('app.name', 'Molecular H2 Water Admin') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#041f1e] font-sans text-white antialiased selection:bg-teal-300 selection:text-[#031a19]">
        <div class="relative min-h-screen overflow-hidden bg-[linear-gradient(135deg,#041f1e_0%,#062926_48%,#031a19_100%)]">
            <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:42px_42px]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-teal-300/70 to-transparent"></div>

            <div class="relative flex min-h-screen">
                <aside class="hidden w-72 shrink-0 border-r border-teal-200/[0.14] bg-[#041f1e]/90 p-5 shadow-[18px_0_50px_rgba(0,0,0,0.18)] backdrop-blur-xl lg:flex lg:flex-col">
                    <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300">
                        <span class="flex size-12 items-center justify-center rounded-full border border-teal-300/[0.45] bg-teal-300/10 shadow-[0_0_24px_rgba(45,212,191,0.18)]">
                            <span class="font-mono text-lg font-bold tracking-tight text-teal-200">H2</span>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold tracking-wide text-white">Molecular H2 Water</span>
                            <span class="block text-xs font-semibold uppercase tracking-[0.22em] text-teal-200/70">Client Portal</span>
                        </span>
                    </a>

                    <nav class="flex-1 space-y-7" aria-label="Dashboard navigation">
                        <div>
                            <p class="px-3 text-xs font-bold uppercase tracking-[0.22em] text-teal-200/45">Workspace</p>
                            <div class="mt-3 space-y-1.5">
                                @foreach ($sidebarLinks as $link)
                                    <a href="{{ $link['href'] }}" class="flex items-center gap-3 rounded-md border px-3 py-2.5 text-sm font-semibold transition {{ $link['active'] ? 'border-teal-300/35 bg-teal-300/15 text-white shadow-[0_0_18px_rgba(45,212,191,0.12)]' : 'border-transparent text-teal-50/70 hover:bg-white/[0.07] hover:text-white' }}">
                                        <span class="flex size-9 items-center justify-center rounded-md bg-white/[0.06] text-teal-200">
                                            @if ($link['icon'] === 'dashboard')
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18"><rect x="2" y="2" width="5.5" height="5.5" rx="1.2" fill="currentColor"/><rect x="10.5" y="2" width="5.5" height="5.5" rx="1.2" fill="currentColor" opacity=".6"/><rect x="2" y="10.5" width="5.5" height="5.5" rx="1.2" fill="currentColor" opacity=".6"/><rect x="10.5" y="10.5" width="5.5" height="5.5" rx="1.2" fill="currentColor" opacity=".35"/></svg>
                                            @elseif ($link['icon'] === 'resources')
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18"><path d="M4 3.5h7.5L14 6v8.5H4v-11Z" stroke="currentColor" stroke-width="1.5"/><path d="M7 8h4M7 11h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            @elseif ($link['icon'] === 'admin')
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18"><path d="M3 5.5 9 2l6 3.5v7L9 16l-6-3.5v-7Z" stroke="currentColor" stroke-width="1.5"/><path d="M6.5 9h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            @elseif ($link['icon'] === 'profile')
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M3.5 15c0-2.5 2.5-4.5 5.5-4.5s5.5 2 5.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            @else
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18"><circle cx="9" cy="9" r="2.3" stroke="currentColor" stroke-width="1.5"/><path d="M9 2v2M9 14v2M2 9h2M14 9h2M4.1 4.1l1.4 1.4M12.5 12.5l1.4 1.4M4.1 13.9l1.4-1.4M12.5 5.5l1.4-1.4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            @endif
                                        </span>
                                        {{ $link['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-200/55">Portal Status</p>
                            <div class="mt-4 flex items-center gap-3">
                                <span class="size-2.5 rounded-full bg-teal-300 shadow-[0_0_14px_rgba(45,212,191,0.9)]"></span>
                                <span class="text-sm font-semibold text-white">Live and secure</span>
                            </div>
                        </div>
                    </nav>

                    <div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">
                        <p class="text-sm font-semibold text-white">{{ $userName }}</p>
                        <p class="mt-1 truncate text-xs text-teal-100/55">{{ $userEmail }}</p>
                    </div>
                </aside>

                <main class="flex min-w-0 flex-1 flex-col">
                    <header class="sticky top-0 z-40 border-b border-teal-100/20 bg-white/[0.88] text-slate-900 shadow-lg shadow-teal-950/5 backdrop-blur-xl">
                        <div class="flex h-20 items-center gap-4 px-4 sm:px-8 lg:px-10">
                            <div class="flex min-w-0 flex-1 items-center gap-4">
                                <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-md lg:hidden">
                                    <span class="flex size-10 items-center justify-center rounded-full border border-teal-600/30 bg-teal-50 text-sm font-bold text-teal-800">H2</span>
                                </a>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Dashboard</p>
                                    <h1 class="truncate text-xl font-black tracking-normal text-slate-950">Welcome back, {{ $userName }}</h1>
                                </div>
                            </div>

                            <div class="hidden flex-1 justify-center px-4 xl:flex">
                                <form class="relative w-full max-w-xl" role="search" aria-label="Portal search">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-teal-500">
                                        <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="2"/><path d="M16 16l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    </span>
                                    <input type="search" class="w-full rounded-full border border-teal-100 bg-white/80 py-3 pl-12 pr-5 text-sm font-medium text-slate-900 shadow-inner placeholder:text-teal-900/40 focus:border-teal-400 focus:ring-2 focus:ring-teal-300/40" placeholder="Search account, orders, support...">
                                </form>
                            </div>

                            <div class="ml-auto flex items-center gap-2 sm:gap-3">
                                <a href="{{ route('admin.dashboard') }}" class="hidden rounded-full bg-teal-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700 sm:inline-flex">
                                    Admin
                                </a>

                                <button type="button" aria-label="Notifications" class="relative flex size-10 items-center justify-center rounded-full bg-white/80 text-teal-700 shadow transition hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-400">
                                    <svg width="22" height="22" fill="none" viewBox="0 0 22 22"><path d="M11 19a2 2 0 0 0 2-2H9a2 2 0 0 0 2 2Zm6-5V9a7 7 0 1 0-14 0v5l-2 2v1h18v-1l-2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span class="absolute -right-1 -top-1 rounded-full border-2 border-white bg-teal-500 px-1.5 py-0.5 text-xs font-bold text-white">3</span>
                                </button>

                                <details class="group relative">
                                    <summary aria-label="Open user menu" class="flex cursor-pointer list-none items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-400 [&::-webkit-details-marker]:hidden">
                                        <span class="relative flex items-center">
                                            <span class="inline-flex size-10 items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-teal-400/80 to-teal-700/80 text-sm font-bold text-white shadow-inner">{{ $initials }}</span>
                                            <span class="absolute bottom-0 right-0 size-3 rounded-full border-2 border-white bg-emerald-400"></span>
                                        </span>
                                        <span class="hidden flex-col items-start sm:flex">
                                            <span class="max-w-32 truncate text-sm font-semibold leading-tight text-slate-900">{{ $userName }}</span>
                                            <span class="text-xs text-teal-700">Member</span>
                                        </span>
                                        <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-700 transition group-open:rotate-180"><path d="M6 7l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </summary>

                                    <div class="absolute right-0 top-full z-50 mt-3 w-64 overflow-hidden rounded-lg border border-teal-100 bg-white shadow-xl shadow-teal-950/10">
                                        <div class="border-b border-teal-50 px-4 py-3">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ $userName }}</p>
                                            <p class="truncate text-xs text-teal-700">{{ $userEmail }}</p>
                                        </div>

                                        <div class="py-2">
                                            <a href="{{ route('resources') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                                                Resources
                                            </a>
                                            <a href="{{ route('profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-600"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M3.5 15c0-2.49 2.46-4.5 5.5-4.5s5.5 2.01 5.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                                Profile
                                            </a>
                                            <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-600"><circle cx="9" cy="9" r="2.4" stroke="currentColor" stroke-width="1.6"/><path d="M9 2v2M9 14v2M2 9h2M14 9h2M4.05 4.05l1.42 1.42M12.53 12.53l1.42 1.42M4.05 13.95l1.42-1.42M12.53 5.47l1.42-1.42" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                                Settings
                                            </a>
                                        </div>

                                        <form method="POST" action="{{ route('logout') }}" class="border-t border-teal-50">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-red-500"><path d="M7 4H4.5A1.5 1.5 0 0 0 3 5.5v7A1.5 1.5 0 0 0 4.5 14H7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M10.5 12.5 14 9l-3.5-3.5M14 9H7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                Log off
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </header>

                    <section class="flex-1 overflow-y-auto px-4 py-6 sm:px-8 lg:px-10">
                        <div class="mx-auto max-w-7xl">
                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-lg border border-white/10 bg-white/[0.94] p-5 text-slate-900 shadow-lg shadow-teal-950/10">
                                    <p class="text-sm font-semibold text-slate-500">Active Leads</p>
                                    <div class="mt-4 flex items-end justify-between">
                                        <p class="text-3xl font-black">28</p>
                                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700">+12%</span>
                                    </div>
                                </div>
                                <div class="rounded-lg border border-white/10 bg-white/[0.94] p-5 text-slate-900 shadow-lg shadow-teal-950/10">
                                    <p class="text-sm font-semibold text-slate-500">Appointments</p>
                                    <div class="mt-4 flex items-end justify-between">
                                        <p class="text-3xl font-black">7</p>
                                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700">Today</span>
                                    </div>
                                </div>
                                <div class="rounded-lg border border-white/10 bg-white/[0.94] p-5 text-slate-900 shadow-lg shadow-teal-950/10">
                                    <p class="text-sm font-semibold text-slate-500">Messages</p>
                                    <div class="mt-4 flex items-end justify-between">
                                        <p class="text-3xl font-black">3</p>
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Open</span>
                                    </div>
                                </div>
                                <div class="rounded-lg border border-white/10 bg-white/[0.94] p-5 text-slate-900 shadow-lg shadow-teal-950/10">
                                    <p class="text-sm font-semibold text-slate-500">Portal</p>
                                    <div class="mt-4 flex items-end justify-between">
                                        <p class="text-3xl font-black">Live</p>
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Secure</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
                                <div class="rounded-lg border border-white/10 bg-white/[0.94] p-6 text-slate-900 shadow-lg shadow-teal-950/10">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Overview</p>
                                            <h2 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Account activity</h2>
                                        </div>
                                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-4 py-2 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.2)] transition hover:bg-teal-300">Open Admin</a>
                                    </div>

                                    <div class="mt-8 space-y-4">
                                        <div class="h-3 rounded-full bg-teal-500/70"></div>
                                        <div class="h-3 w-10/12 rounded-full bg-teal-400/50"></div>
                                        <div class="h-3 w-8/12 rounded-full bg-teal-300/60"></div>
                                        <div class="h-3 w-11/12 rounded-full bg-slate-200"></div>
                                    </div>

                                    <div class="mt-8 grid gap-4 md:grid-cols-3">
                                        <div class="rounded-md border border-teal-100 bg-teal-50 p-4">
                                            <p class="text-sm font-bold text-teal-900">Content</p>
                                            <p class="mt-2 text-sm leading-6 text-teal-800/75">Review education pages, FAQs, and blog drafts.</p>
                                        </div>
                                        <div class="rounded-md border border-teal-100 bg-white p-4">
                                            <p class="text-sm font-bold text-slate-900">Customers</p>
                                            <p class="mt-2 text-sm leading-6 text-slate-500">Track leads and incoming contact requests.</p>
                                        </div>
                                        <div class="rounded-md border border-teal-100 bg-white p-4">
                                            <p class="text-sm font-bold text-slate-900">Settings</p>
                                            <p class="mt-2 text-sm leading-6 text-slate-500">Manage profile and portal preferences.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-white/10 bg-[#041f1e]/72 p-6 text-white shadow-lg shadow-teal-950/20 backdrop-blur-xl">
                                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-200/65">Quick Actions</p>
                                    <div class="mt-5 space-y-3">
                                        <a href="{{ route('profile') }}" class="flex items-center justify-between rounded-md border border-teal-200/[0.16] bg-white/[0.07] px-4 py-3 text-sm font-semibold transition hover:bg-white/[0.12]">
                                            Edit profile
                                            <span class="text-teal-200">-></span>
                                        </a>
                                        <a href="{{ route('resources') }}" class="flex items-center justify-between rounded-md border border-teal-200/[0.16] bg-white/[0.07] px-4 py-3 text-sm font-semibold transition hover:bg-white/[0.12]">
                                            Browse resources
                                            <span class="text-teal-200">-></span>
                                        </a>
                                        <a href="{{ route('admin.settings') }}" class="flex items-center justify-between rounded-md border border-teal-200/[0.16] bg-white/[0.07] px-4 py-3 text-sm font-semibold transition hover:bg-white/[0.12]">
                                            Portal settings
                                            <span class="text-teal-200">-></span>
                                        </a>
                                        <a href="{{ url('/') }}" class="flex items-center justify-between rounded-md border border-teal-200/[0.16] bg-white/[0.07] px-4 py-3 text-sm font-semibold transition hover:bg-white/[0.12]">
                                            View landing page
                                            <span class="text-teal-200">-></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </body>
</html>
