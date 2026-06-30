@php
    $user = auth()->user();
    $userName = $user?->name ?? 'Member';
    $userEmail = $user?->email ?? '';
    $initials = collect(explode(' ', trim($userName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->join('');
    $initials = $initials !== '' ? mb_strtoupper($initials) : 'AU';
    $roles = $user?->roles()->orderBy('name')->get() ?? collect();
    $primaryRole = $roles->first()?->name ?? 'Member';
    $joinedDate = $user?->created_at?->format('M j, Y') ?? 'Today';
    $lastUpdated = $user?->updated_at?->diffForHumans() ?? 'Recently';
    $isVerified = filled($user?->email_verified_at);
    $canDeleteAccount = $user?->hasRole('super-admin') ?? false;
    $avatarUrl = $user?->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) : null;

    $sidebarLinks = [
        ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => false, 'icon' => 'dashboard'],
        ['label' => 'Resources', 'href' => route('resources'), 'active' => false, 'icon' => 'resources'],
        ['label' => 'Admin Portal', 'href' => route('admin.dashboard'), 'active' => false, 'icon' => 'admin'],
        ['label' => 'Profile', 'href' => route('profile'), 'active' => true, 'icon' => 'profile'],
        ['label' => 'Settings', 'href' => route('admin.settings'), 'active' => false, 'icon' => 'settings'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Profile | {{ config('app.name', 'Molecular H2 Water Admin') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-[#041f1e] font-sans text-white antialiased selection:bg-teal-300 selection:text-[#031a19]">
        <div class="relative min-h-screen overflow-hidden bg-[linear-gradient(135deg,#041f1e_0%,#062926_48%,#031a19_100%)]">
            <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:42px_42px]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-teal-300/70 to-transparent"></div>

            <header class="fixed inset-x-0 top-0 z-50 border-b border-teal-100/20 bg-white/[0.88] text-slate-900 shadow-lg shadow-teal-950/5 backdrop-blur-xl">
                <div class="flex h-20 items-center">
                    <a href="{{ url('/') }}" class="hidden h-full w-72 shrink-0 items-center gap-3 border-r border-teal-100/40 px-5 transition hover:bg-white/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-teal-500 lg:flex">
                        <span class="flex size-12 items-center justify-center rounded-full border border-teal-600/25 bg-teal-50 text-sm font-black text-teal-800 shadow-inner">
                            H2
                        </span>
                        <span>
                            <span class="block text-sm font-black tracking-normal text-slate-950">Molecular H2 Water</span>
                            <span class="block text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Client Portal</span>
                        </span>
                    </a>

                    <div class="flex min-w-0 flex-1 items-center gap-4 px-4 sm:px-8 lg:px-10">
                        <div class="flex min-w-0 flex-1 items-center gap-4">
                            <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-md lg:hidden">
                                <span class="flex size-10 items-center justify-center rounded-full border border-teal-600/30 bg-teal-50 text-sm font-bold text-teal-800">H2</span>
                            </a>
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Profile</p>
                                <h1 class="truncate text-xl font-black tracking-normal text-slate-950">Manage your account</h1>
                            </div>
                        </div>

                        <div class="hidden flex-1 justify-center px-4 xl:flex">
                            <form class="relative w-full max-w-xl" role="search" aria-label="Portal search">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-teal-500">
                                    <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="2"/><path d="M16 16l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                </span>
                                <input type="search" class="w-full rounded-full border border-teal-100 bg-white/80 py-3 pl-12 pr-5 text-sm font-medium text-slate-900 shadow-inner placeholder:text-teal-900/40 focus:border-teal-400 focus:ring-2 focus:ring-teal-300/40" placeholder="Search account, resources, settings...">
                            </form>
                        </div>

                        <div class="ml-auto flex items-center gap-2 sm:gap-3">
                            <a href="{{ route('dashboard') }}" class="hidden rounded-full bg-teal-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700 sm:inline-flex">
                                My Dashboard
                            </a>

                            <details class="group relative">
                                <summary aria-label="Open user menu" class="flex cursor-pointer list-none items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-400 [&::-webkit-details-marker]:hidden">
                                    <span class="relative flex items-center">
                                        @if ($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="{{ $userName }} avatar" class="size-10 rounded-full border-2 border-white object-cover shadow-inner">
                                        @else
                                            <span class="inline-flex size-10 items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-teal-400/80 to-teal-700/80 text-sm font-bold text-white shadow-inner">{{ $initials }}</span>
                                        @endif
                                        <span class="absolute bottom-0 right-0 size-3 rounded-full border-2 border-white bg-emerald-400"></span>
                                    </span>
                                    <span class="hidden flex-col items-start sm:flex">
                                        <span class="max-w-32 truncate text-sm font-semibold leading-tight text-slate-900">{{ $userName }}</span>
                                        <span class="text-xs text-teal-700">{{ $primaryRole }}</span>
                                    </span>
                                    <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-700 transition group-open:rotate-180"><path d="M6 7l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </summary>

                                <div class="absolute right-0 top-full z-50 mt-3 w-64 overflow-hidden rounded-lg border border-teal-100 bg-white shadow-xl shadow-teal-950/10">
                                    <div class="border-b border-teal-50 px-4 py-3">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $userName }}</p>
                                        <p class="truncate text-xs text-teal-700">{{ $userEmail }}</p>
                                    </div>

                                    <div class="py-2">
                                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                                            My Dashboard
                                        </a>
                                        <a href="{{ route('resources') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                                            Resources
                                        </a>
                                        <a href="{{ route('profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                                            Profile
                                        </a>
                                        <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                                            Settings
                                        </a>
                                    </div>

                                    <form method="POST" action="{{ route('logout') }}" class="border-t border-teal-50">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                            Log off
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </header>

            <div class="relative flex min-h-screen pt-20">
                <aside class="hidden w-72 shrink-0 border-r border-teal-200/[0.14] bg-[#041f1e]/90 p-5 shadow-[18px_0_50px_rgba(0,0,0,0.18)] backdrop-blur-xl lg:flex lg:flex-col">
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
                    <section class="flex-1 overflow-y-auto px-4 py-6 sm:px-8 lg:px-10">
                        <div class="mx-auto max-w-7xl">
                            <section class="overflow-hidden rounded-lg border border-teal-200/[0.18] bg-white/[0.07] shadow-2xl shadow-teal-950/20 backdrop-blur-xl">
                                <div class="grid gap-0 lg:grid-cols-[0.78fr_1.22fr]">
                                    <aside class="border-b border-teal-200/[0.14] bg-[#041f1e]/85 p-6 sm:p-8 lg:border-b-0 lg:border-r">
                                        <div class="flex flex-col gap-6">
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="relative"
                                                    x-data="{ avatarUrl: @js($avatarUrl) }"
                                                    x-on:profile-updated.window="avatarUrl = $event.detail.avatarUrl || avatarUrl"
                                                >
                                                    <img x-show="avatarUrl" :src="avatarUrl" alt="{{ $userName }} avatar" class="size-20 rounded-full border-2 border-teal-300/40 object-cover shadow-[0_0_32px_rgba(45,212,191,0.22)]">
                                                    <span x-show="! avatarUrl" class="flex size-20 items-center justify-center rounded-full border-2 border-teal-300/40 bg-gradient-to-br from-teal-300/30 to-teal-700/30 text-2xl font-black text-white shadow-[0_0_32px_rgba(45,212,191,0.22)]">
                                                        {{ $initials }}
                                                    </span>
                                                    <span class="absolute bottom-1 right-1 size-4 rounded-full border-2 border-[#041f1e] {{ $isVerified ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-200/65">Account Profile</p>
                                                    <h2 class="mt-2 truncate text-3xl font-black tracking-normal text-white">{{ $userName }}</h2>
                                                    <p class="mt-1 truncate text-sm font-semibold text-teal-100/65">{{ $userEmail }}</p>
                                                </div>
                                            </div>

                                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                                                <div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">
                                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-200/55">Primary Role</p>
                                                    <p class="mt-2 text-lg font-black text-white">{{ $primaryRole }}</p>
                                                </div>
                                                <div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">
                                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-200/55">Joined</p>
                                                    <p class="mt-2 text-lg font-black text-white">{{ $joinedDate }}</p>
                                                </div>
                                            </div>

                                            <div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">
                                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-200/55">Assigned Roles</p>
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    @forelse ($roles as $role)
                                                        <span class="rounded-full bg-teal-300/15 px-3 py-1 text-xs font-bold text-teal-100 ring-1 ring-teal-200/20">{{ $role->name }}</span>
                                                    @empty
                                                        <span class="rounded-full bg-slate-100/10 px-3 py-1 text-xs font-bold text-teal-100 ring-1 ring-teal-200/20">Member</span>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">
                                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-200/55">Account Health</p>
                                                <div class="mt-4 space-y-3">
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-sm font-semibold text-teal-50/75">Email verification</span>
                                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $isVerified ? 'bg-emerald-400/15 text-emerald-200' : 'bg-amber-400/15 text-amber-200' }}">
                                                            {{ $isVerified ? 'Verified' : 'Unverified' }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-sm font-semibold text-teal-50/75">Last profile update</span>
                                                        <span class="text-xs font-bold text-teal-100/65">{{ $lastUpdated }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </aside>

                                    <div class="bg-slate-50 p-4 text-slate-900 sm:p-6 lg:p-8">
                                        <section class="mb-6 rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Profile Center</p>
                                            <h2 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Manage your account details and security.</h2>
                                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">
                                                Keep your contact details current, rotate your password regularly, and review the access roles connected to your account.
                                            </p>
                                        </section>

                                        <div class="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
                                            <div class="space-y-6">
                                                <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                                                    <livewire:profile.update-profile-information-form />
                                                </section>

                                                <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                                                    <livewire:profile.update-password-form />
                                                </section>
                                            </div>

                                            <aside class="space-y-6">
                                                <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                                                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Security Notes</p>
                                                    <div class="mt-5 space-y-4">
                                                        <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3">
                                                            <p class="text-sm font-bold text-slate-950">Use a unique password</p>
                                                            <p class="mt-1 text-sm leading-6 text-slate-600">Choose a password you do not use on any other account.</p>
                                                        </div>
                                                        <div class="rounded-md border border-teal-100 bg-white px-4 py-3">
                                                            <p class="text-sm font-bold text-slate-950">Keep your email current</p>
                                                            <p class="mt-1 text-sm leading-6 text-slate-600">Email changes may require verification before account notifications resume.</p>
                                                        </div>
                                                        <div class="rounded-md border border-teal-100 bg-white px-4 py-3">
                                                            <p class="text-sm font-bold text-slate-950">Review assigned roles</p>
                                                            <p class="mt-1 text-sm leading-6 text-slate-600">Ask a super admin to adjust your access if something looks incorrect.</p>
                                                        </div>
                                                    </div>
                                                </section>

                                                @if ($canDeleteAccount)
                                                    <section class="rounded-lg border border-red-100 bg-white p-6 shadow-sm">
                                                        <livewire:profile.delete-user-form />
                                                    </section>
                                                @endif
                                            </aside>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </section>
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
